<?php

namespace Frappant\FrpFormAnswers\Domain\Finishers;

use Frappant\FrpFormAnswers\Domain\Model\FormEntry;
use Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository;
use Frappant\FrpFormAnswers\Event\ManipulateFormValuesEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;

class SaveFormToDatabaseFinisher extends AbstractFinisher
{
    public function __construct(protected EventDispatcherInterface $eventDispatcher, protected FormEntryRepository $formEntryRepository, protected FormEntry $formEntry, protected PersistenceManager $persistenceManager)
    {
        $this->formEntry = $formEntry;
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Executes this finisher
     * @throws AspectNotFoundException
     * @see AbstractFinisher::execute()
     */
    protected function executeInternal(): void
    {
        // Values of all fields, getFormValues() also gives pages, so it will be filled in foreach
        $values = $this->getFormValues();
        // Identifier for the yaml file of the form
        $formRuntime = $this->finisherContext->getFormRuntime();

        $identifier = $formRuntime->getIdentifier();
        // Default Value is new Form
        $lastFormUid = 1;

        /**
         * Dispatch an Event to manipulate $values (Use this instead of preInsertSignal above)
         */
        $event = $this->eventDispatcher->dispatch(new ManipulateFormValuesEvent($values, $formRuntime));
        $values = $event->getValues();
        $this->formEntry->setExported(false);
        // The int-typed crdate property is persisted as-is; Extbase no longer auto-fills it
        $this->formEntry->setCrdate(time());
        $this->formEntry->setAnswers($values);

        $this->formEntry->setForm($identifier);

        $attrs = $this->finisherContext->getFormRuntime()->getRequest()->getAttributes();
        $pageId = (int)($attrs['routing']['pageId'] ?? 0);
        $this->formEntry->setPid($pageId);

        $lastForm = $this->formEntryRepository->getLastFormAnswerByIdentifyer($identifier);

        // If there already exists a formAnswers, override lastFormUid
        if ($lastForm instanceof FormEntry) {
            $lastFormUid += $lastForm->getSubmitUid();
        }

        $this->formEntry->setSubmitUid($lastFormUid);

        $this->formEntryRepository->add($this->formEntry);
        $this->persistenceManager->persistAll();
    }

    /**
     * Returns the values of the submitted form
     *
     * @return array<string, array{value:mixed, conf: array{label:mixed, inputType:string}}>
     */
    protected function getFormValues(): array
    {
        // All values, with pages
        $valuesWithPages = $this->finisherContext->getFormValues();
        $values = [];

        // Goes trough all form-pages - and there trough all PageElements (Questions)
        foreach ($this->finisherContext->getFormRuntime()->getPages() as $page) {
            foreach ($page->getElementsRecursively() as $pageElem) {
                if ($pageElem->getType() === 'Honeypot') {
                    continue;
                }

                $identifier = $pageElem->getIdentifier();
                $submittedValue = $this->getSubmittedValue($valuesWithPages, $identifier);

                if ($pageElem->getType() === 'FileUpload' || $pageElem->getType() === 'ImageUpload') {
                    $values[$identifier]['value'] = $this->getUploadedFileNames($submittedValue);
                } else {
                    $values[$identifier]['value'] = $submittedValue;
                }

                $values[$identifier]['conf']['label'] = $pageElem->getLabel();
                $values[$identifier]['conf']['inputType'] = $pageElem->getType();
            }
        }
        return $values;
    }

    /**
     * Reads one submitted value.
     *
     * Element identifiers can contain dots, and the form framework stores the
     * values by that path (a "foo.0.bar" element lives in
     * $values['foo'][0]['bar']), so a flat lookup would miss them.
     */
    private function getSubmittedValue(mixed $submittedValues, string $identifier): mixed
    {
        if (!is_array($submittedValues)) {
            return null;
        }

        // An identifier that is stored as it is wins, so the usual case keeps
        // working exactly as before
        if (array_key_exists($identifier, $submittedValues)) {
            return $submittedValues[$identifier];
        }

        if ($identifier === '' || !str_contains($identifier, '.')) {
            return null;
        }

        try {
            return ArrayUtility::getValueByPath($submittedValues, $identifier, '.');
        } catch (MissingArrayPathException) {
            return null;
        }
    }

    /**
     * The name of the uploaded file, or a list of names when the element
     * accepts multiple files - those arrive as an ObjectStorage of file
     * references instead of a single one.
     *
     * @return string|list<string>|null
     */
    private function getUploadedFileNames(mixed $upload): string|array|null
    {
        if ($upload instanceof FileReference || (is_object($upload) && method_exists($upload, 'getOriginalResource'))) {
            $resource = $upload->getOriginalResource();

            return is_object($resource) && method_exists($resource, 'getName') ? $resource->getName() : null;
        }

        if (is_iterable($upload)) {
            $names = [];
            foreach ($upload as $file) {
                $name = $this->getUploadedFileNames($file);
                if (is_string($name) && $name !== '') {
                    $names[] = $name;
                }
            }

            return $names;
        }

        return null;
    }

    /**
     * Returns a form element object for a given identifier.
     *
     * @param string $elementIdentifier
     * @return FormElementInterface|null
     */
    protected function getElementByIdentifier(string $elementIdentifier): ?FormElementInterface
    {
        return $this
            ->finisherContext
            ->getFormRuntime()
            ->getFormDefinition()
            ->getElementByIdentifier($elementIdentifier);
    }
}
