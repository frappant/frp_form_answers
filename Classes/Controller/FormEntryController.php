<?php
namespace Frappant\FrpFormAnswers\Controller;

/***
 *
 * This file is part of the "Form Answer Saver" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2017 !frappant <support@frappant.ch>
 *
 ***/

/**
 * FormEntryController
 */
class FormEntryController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * pageRepository
     *
     * @var \TYPO3\CMS\Frontend\Page\PageRepository
     * @inject
     */
    protected $pageRepository = null;

    /**
     * formEntryRepository
     *
     * @var \Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository
     * @inject
     */
    protected $formEntryRepository = null;

    /**
     * dataExporter
     *
     * @var \Frappant\FrpFormAnswers\DataExporter\DataExporter
     * @inject
     */
    protected $dataExporter = null;

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        // get All FormAnswers from this Page
        $allFormAnswers = $this->formEntryRepository->findAll();
        $formNames = array();

        // Get FormNames from this page. We will separate them in the list View
        foreach ($allFormAnswers as $answer) {
            $formNames[$answer->getForm()] = $answer->getForm();
        }

        // Get a List from FormEntries in subpages
        $formEntriesInSubPages = $this->formEntryRepository->findAllInPidAndRootline((int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'));
        // Get all Pids with a formEntry list
        $pageIds = array();
        foreach ($formEntriesInSubPages as $formEntry) {
            $pageIds[$formEntry->getPid()] = $formEntry->getPid();
        }
        unset($pageIds[(int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id')]);

        $this->view->assign("formNames", $formNames);
        $this->view->assign('subPagesWithFormEntries', $this->pageRepository->getMenuForPages($pageIds));
        $this->view->assign("pid", (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'));
    }

    /**
     * action show
     *
     * @param \Frappant\FrpFormAnswers\Domain\Model\FormEntry $formEntry
     * @return void
     */
    public function showAction(\Frappant\FrpFormAnswers\Domain\Model\FormEntry $formEntry)
    {
        $this->view->assign('formEntry', $formEntry);
    }

    /**
     * action prepareExport
     *
     * @return void
     */
    public function prepareExportAction()
    {
        $formHashes = array();
        $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
        $querySettings->setRespectStoragePage(false);
        $this->formEntryRepository->setDefaultQuerySettings($querySettings);

        $demandObject = $this->objectManager->get(\Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand::class);
        $this->view->assign('formEntryDemand', $demandObject);
        $forms = $this->formEntryRepository->findAll();

        foreach ($forms as $form) {
            $formHashes[$form->getFieldHash()] = $form->getFieldHash();
        }
        $this->view->assign('formHashes', $formHashes);
    }

    public function initializeExportAction()
    {
    }

    /**
     * action export
     * @param \Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand $formEntryDemand
     * @return file The Excel file with data
     */
    public function exportAction(\Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand $formEntryDemand)
    {
        $formEntries = $this->formEntryRepository->findbyDemand($formEntryDemand);

        if (count($formEntries) === 0) {
            $this->addFlashMessage('No entries found with your criteria',
               'No Entries found',
               \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING,
               true
            );
            $this->redirect("list");
        }

        $exportData = $this->dataExporter->getExport($formEntries, $formEntryDemand);

        $this->view->assign("rows", $exportData);
        $this->view->assign('formEntryDemand', $formEntryDemand);

        foreach ($formEntries as $entry) {
            $entry->setExported(true);
            $this->formEntryRepository->update($entry);
        }

        $persistenceManager = $this->objectManager->get("TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager");
        $persistenceManager->persistAll();
    }
}
