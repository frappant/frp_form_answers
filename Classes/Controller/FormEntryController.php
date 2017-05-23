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
    }

    /**
     * action export
     * @param boolean $selectAll
     * @param boolean $allPids
     * @return file The Excel file with data
     */
    public function exportAction($selectAll, $allPids)
    {
        $formEntries = array();
        $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
        if ($allPids) {
            $querySettings->setRespectStoragePage(false);
        } else {
            $querySettings->setStoragePageIds(array((int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id')));
        }

        $this->formEntryRepository->setDefaultQuerySettings($querySettings);

        if ($selectAll) {
            $formEntries = $this->formEntryRepository->findAll()->toArray();
        } else {
            $formEntries = $this->formEntryRepository->findByExported(false)->toArray();
        }

        $exporter = $this->objectManager->get("Frappant\\FrpFormAnswers\\Utility\\FormExportUtility");

        // Get Headers for File download
        $exporter->export($formEntries);
        $this->download_send_headers("formExport.xlsx");

        die();
    }

    private function download_send_headers($filename = "formExport.xls", $charset= "UTF-8")
    {
        // disable caching
        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2099 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");

        // force download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download; charset=".$charset);

        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
    }
}
