<?php
namespace Frappant\FrpFormAnswers\Domain\Finishers;

use TYPO3\CMS\Form\Domain\Finishers;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;

class SaveFormToDatabaseFinisher extends \TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher
{
    /**
     * formEntryRepository
     *
     * @var \Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository
     * @inject
     */
    protected $formEntryRepository = null;

    /**
     * signalSlotDispatcher
     *
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     * @inject
     */
    protected $signalSlotDispatcher = null;

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     */
    protected function executeInternal()
    {
        // All fields, in array
        $fields = [];
        // Values of all fields, getFormValues() also gives pages,
        // so it will be filled in foreach
        $values = $this->getFormValues();

        $this->signalSlotDispatcher->dispatch(__CLASS__, 'preInsertSignal', array(&$values));

        $formEntry = $this->objectManager->get(\Frappant\FrpFormAnswers\Domain\Model\FormEntry::class);
        $formEntry->setExported(false);
        $formEntry->setAnswers($values);

        $formEntry->setForm($this->finisherContext->getFormRuntime()->getIdentifier());
        $formEntry->setPid($GLOBALS['TSFE']->id);



        $this->formEntryRepository->add($formEntry);
    }

    /**
     * Returns the values of the submitted form
     *
     * @return []
     */
    protected function getFormValues(): array
    {
        // All values, with pages
        $valuesWithPages = $this->finisherContext->getFormValues();
        $values = [];

        // Goes trough all form-pages - and there trough all PageElements (Questions)
        foreach ($this->finisherContext->getFormRuntime()->getPages() as $key => $page) {
            foreach ($page->getElementsRecursively() as $pageElem) {
                if ($pageElem->getType() != 'Honeypot') {
                    $fields[] = $pageElem->getIdentifier();
                    $values[$pageElem->getIdentifier()]['value'] = $valuesWithPages[$pageElem->getIdentifier()];
                    $values[$pageElem->getIdentifier()]['conf']['label'] = $pageElem->getLabel();
                    $values[$pageElem->getIdentifier()]['conf']['inputType'] = $pageElem->getType();
                }
            }
        }
        return $values;
    }

    /**
     * Returns a form element object for a given identifier.
     *
     * @param string $elementIdentifier
     * @return NULL|FormElementInterface
     */
    protected function getElementByIdentifier(string $elementIdentifier)
    {
        return $this
            ->finisherContext
            ->getFormRuntime()
            ->getFormDefinition()
            ->getElementByIdentifier($elementIdentifier);
    }
}
