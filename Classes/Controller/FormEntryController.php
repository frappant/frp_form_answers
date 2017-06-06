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
     * formEntryRepository
     *
     * @var \Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository
     * @inject
     */
    protected $formEntryRepository = null;

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
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
     * @param array $config
     * @return file The Excel file with data
     */
    public function exportAction($config)
    {
        $formEntries = $this->formEntryRepository->findByConfig($config);

        if (count($formEntries) == 0) {
            $this->addFlashMessage('No entries found with your criteria',
               'No Entries found',
               \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING,
               true
            );
            $this->redirect("prepareExport");
        }

        $exporter = $this->objectManager->get("Frappant\\FrpFormAnswers\\DataExporter\\DataExporter");
        $exporter->getExport($config['exportType'], $formEntries, $config);

        foreach ($formEntries as $entry) {
            $entry->setExported(true);
            $this->formEntryRepository->update($entry);
        }

        $persistenceManager = $this->objectManager->get("TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager");
        $persistenceManager->persistAll();

        die();
    }
}
