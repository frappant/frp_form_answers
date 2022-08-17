<?php
namespace Frappant\FrpFormAnswers\Domain\Finishers;

use Frappant\FrpFormAnswers\Event\ManipulateFormValuesEvent;
use Frappant\FrpFormAnswers\Domain\Model\FormEntry;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Form\Domain\Finishers;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

class SaveFormToDatabaseFinisher extends AbstractFinisher
{
    /**
     * formEntryRepository
     *
     * @var \Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository
     */
    protected $formEntryRepository = null;

    /**
     * signalSlotDispatcher
     *
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher = null;

    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $dispatcher
     */
    public function injectDispatcher(Dispatcher $dispatcher) {
        $this->signalSlotDispatcher = $dispatcher;
    }

    protected EventDispatcherInterface $eventDispatcher;

    public function injectEventDispatcherInterface(EventDispatcherInterface $eventDispatcher) {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param \Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository $formEntryRepository
     */
    public function injectFormEntryRepository(FormEntryRepository $formEntryRepository) {
        $this->formEntryRepository = $formEntryRepository;
    }


    protected FormEntry $formEntry;

    public function injectFormEntry(FormEntry $formEntry) {
        $this->formEntry = $formEntry;
    }

    protected PersistenceManager $persistenceManager;

    public function injectPersistenceManager(PersistenceManager $persistenceManager) {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     */
    protected function executeInternal()
    {
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);

        // Values of all fields, getFormValues() also gives pages,
        // so it will be filled in foreach
        $values = $this->getFormValues();
        // Identifier for the yaml file of the form
        $identifier = $this->finisherContext->getFormRuntime()->getIdentifier();
        // Default Value is new Form
        $lastFormUid = 1;

        /**
         * Provide a Signal to manipulate $values before saving
         * @deprecated since v4 will be removed in v5
         */
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'preInsertSignal', array(&$values));

        /**
         * Dispatch an Event to manipulate $values (Use this instead of preInsertSignal above)
         */
        $event = $this->eventDispatcher->dispatch(new ManipulateFormValuesEvent($values));
        $values = $event->getValues();

        $this->formEntry->setExported(false);
        $this->formEntry->setAnswers($values);

        $this->formEntry->setForm($identifier);
        $this->formEntry->setPid($GLOBALS['TSFE']->id);

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
     * @return []
     */
    protected function getFormValues(): array
    {
        // All values, with pages
        $valuesWithPages = $this->finisherContext->getFormValues();
        $values = [];

        // Goes trough all form-pages - and there trough all PageElements (Questions)
        foreach ($this->finisherContext->getFormRuntime()->getPages() as $page) {
            foreach ($page->getElementsRecursively() as $pageElem) {
                if ($pageElem->getType() !== 'Honeypot') {
                	if($pageElem->getType() !== 'FileUpload' && $pageElem->getType() !== 'ImageUpload'){
		                $values[$pageElem->getIdentifier()]['value'] = $valuesWithPages[$pageElem->getIdentifier()];
	                }else{
                		if($valuesWithPages[$pageElem->getIdentifier()]){
			                $values[$pageElem->getIdentifier()]['value'] = $valuesWithPages[$pageElem->getIdentifier()]->getOriginalResource()->getName();
		                }
	                }
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
