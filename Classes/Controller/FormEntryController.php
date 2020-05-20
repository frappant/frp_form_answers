<?php
namespace Frappant\FrpFormAnswers\Controller;

use Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @var \TYPO3\CMS\Core\Domain\Repository\PageRepository
     */
    protected $pageRepository = null;

    /**
     * Inject a page repository to enable DI
     *
     * @param \TYPO3\CMS\Core\Domain\Repository\PageRepository $pageRepository
     */
    public function injectPageRepository(\TYPO3\CMS\Core\Domain\Repository\PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }

    /**
     * formEntryRepository
     *
     * @var \Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository
     */
    protected $formEntryRepository;

    /**
     * Inject a page repository to enable DI
     *
     * @param \Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository $formEntryRepository
     */
    public function injectFormEntryRepository(\Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository $formEntryRepository)
    {
        $this->formEntryRepository = $formEntryRepository;
    }

    /**
     * dataExporter
     *
     * @var \Frappant\FrpFormAnswers\DataExporter\DataExporter
     */
    protected $dataExporter = null;

    /**
     * Inject a page repository to enable DI
     *
     * @param \Frappant\FrpFormAnswers\DataExporter\DataExporter $dataExporter
     */
    public function injectDataExporter(\Frappant\FrpFormAnswers\DataExporter\DataExporter $dataExporter)
    {
        $this->dataExporter = $dataExporter;
    }

    /**
     * formAnswersUtility
     *
     * @var \Frappant\FrpFormAnswers\Utility\FormAnswersUtility
     */
    protected $formAnswersUtility = null;

    /**
     * Inject a page repository to enable DI
     *
     * @param \Frappant\FrpFormAnswers\Utility\FormAnswersUtility $formAnswersUtility
     */
    public function injectFormAnswersUtility(\Frappant\FrpFormAnswers\Utility\FormAnswersUtility $formAnswersUtility)
    {
        $this->formAnswersUtility = $formAnswersUtility;
    }

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $pageIds = $this->formAnswersUtility->prepareFormAnswersArray();

        if (count($pageIds) > 0) {
            $this->view->assign('subPagesWithFormEntries', $this->pageRepository->getMenuForPages(array_keys($pageIds)));
            $this->view->assign('formEntriesStatus', $pageIds);
        }
        $this->view->assign('pid', (int)GeneralUtility::_GP('id'));
        $this->view->assign('formNames', $this->formAnswersUtility->getAllFormNames());
        $this->view->assign('settings', $this->settings);
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
        $demandObject = $this->objectManager->get(FormEntryDemand::class);

        $this->view->assign('formEntryDemand', $demandObject);
        $this->view->assign('formHashes', $this->formAnswersUtility->getAllFormHashes());
    }

	/**
	 * action export
	 * @param FormEntryDemand $formEntryDemand
	 * @return void The Excel file with data
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
	 */
    public function exportAction(FormEntryDemand $formEntryDemand)
    {
        $formEntries = $this->formEntryRepository->findbyDemand($formEntryDemand);

        if (count($formEntries) === 0) {
            $this->addFlashMessage('No entries found with your criteria',
               'No Entries found',
               FlashMessage::WARNING,
               true
            );
            $this->redirect('list');
        }

        if(class_exists('\TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility')) {
            /** @var \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility $configurationUtility */
            $configurationUtility = GeneralUtility::makeInstance(\TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility::class);
            $extensionConfiguration = $configurationUtility->getCurrentConfiguration('frp_form_answers');
        } else {
            $extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['frp_formanswers'];
        }

        $exportData = $this->dataExporter->getExport($formEntries, $formEntryDemand, $extensionConfiguration['useSubmitUid']['value']);

        $this->formEntryRepository->setFormsToExported($formEntries);

        $this->view->assign('rows', $exportData);
        $this->view->assign('formEntryDemand', $formEntryDemand);
    }
}
