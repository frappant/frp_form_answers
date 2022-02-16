<?php
namespace Frappant\FrpFormAnswers\Controller;

use Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\Utility\BackendUtility as BackendUtilityCore;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = \TYPO3\CMS\Backend\View\BackendTemplateView::class;

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
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        
	    if ($this->actionMethodName != 'exportAction') {
	        $view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);

	        $pageRenderer = $this->view->getModuleTemplate()->getPageRenderer();
	        $pageRenderer->addInlineLanguageLabelFile('EXT:lang/Resources/Private/Language/locallang_core.xlf');

	        $this->createMenu();
	        $this->createButtons();

	        $view->assign('showSupportArea', $this->showSupportArea());
	    }
    }

    /**
     * Create menu
     *
     */
    protected function createMenu()
    {
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('news');

        $actions = [
            ['action' => 'list', 'label' => 'Overview'],
            ['action' => 'prepareExport', 'label' => 'Export'],
            ['action' => 'prepareRemove', 'label' => 'Remove'],
        ];

        foreach ($actions as $action) {
            $item = $menu->makeMenuItem()
                ->setTitle($action['label'])
                ->setHref($uriBuilder->reset()->uriFor($action['action'], [], 'FormEntry'))
                ->setActive($this->request->getControllerActionName() === $action['action']);
            $menu->addMenuItem($item);
        }

        if ($menu instanceof Menu) {
            $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
        }
    }

    /**
     * Create the panel of buttons
     *
     */
    protected function createButtons()
    {
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        // Refresh
        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref(GeneralUtility::getIndpEnv('REQUEST_URI'))
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);

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
     * action prepareRemove
     *
     * @return void
     */
    public function prepareRemoveAction()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_frpformanswers_domain_model_formentry');
        $queryBuilder->getRestrictions()->removeAll();

        $count = $queryBuilder->count('*')
            ->from('tx_frpformanswers_domain_model_formentry')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(\Frappant\FrpFormAnswers\Utility\BackendUtility::getCurrentPid(), \PDO::PARAM_INT)))
            ->andWhere($queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))
            ->execute()->fetchFirstColumn();
        //DebuggerUtility::var_dump($count);
        $this->view->assign('count', $count[0]);
    }

    /**
     * action prepareRemove
     *
     * @return void
     */
    public function removeAction()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_frpformanswers_domain_model_formentry');

        $queryBuilder->delete('tx_frpformanswers_domain_model_formentry')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(\Frappant\FrpFormAnswers\Utility\BackendUtility::getCurrentPid(), \PDO::PARAM_INT)))
            ->andWhere($queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))
            ->execute();

        $this->addFlashMessage(
            LocalizationUtility::translate('LLL:EXT:frp_form_answers/Resources/Private/Language/de.locallang_be.xlf:flashmessage.removeEntries.body', null, [\Frappant\FrpFormAnswers\Utility\BackendUtility::getCurrentPid()]),
            LocalizationUtility::translate('LLL:EXT:frp_form_answers/Resources/Private/Language/de.locallang_be.xlf:flashmessage.removeEntries.title'),
            \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
            true);
        $this->redirect('list');
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
        $demandObject = GeneralUtility::makeInstance(FormEntryDemand::class);

        $this->view->assign('formEntryDemand', $demandObject);
        $this->view->assign('formHashes', $this->formAnswersUtility->getAllFormHashes());
    }

    public function initializeExportAction(){
        $format = $this->request->getArguments()['format'];

        switch ($format){
            case 'Csv':
                $this->defaultViewObjectName = \Frappant\FrpFormAnswers\View\FormEntry\ExportCsv::class;
            break;
            case 'Xls':
                $this->defaultViewObjectName = \Frappant\FrpFormAnswers\View\FormEntry\ExportXls::class;
            break;
            case 'Xml':
                $this->defaultViewObjectName = \Frappant\FrpFormAnswers\View\FormEntry\ExportXml::class;
            break;
        }

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

        $extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['frp_formanswers'];

        $exportData = $this->dataExporter->getExport($formEntries, $formEntryDemand, $extensionConfiguration['useSubmitUid']['value']);

        $this->formEntryRepository->setFormsToExported($formEntries);

        $this->view->assign('rows', $exportData);
        $this->view->assign('formEntryDemand', $formEntryDemand);
    }

    /**
     * @param string $formName
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function deleteFormnameAction($formName = ''){

        if(strlen($formName) > 0){

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_frpformanswers_domain_model_formentry');

            $queryBuilder->update(
                'tx_frpformanswers_domain_model_formentry',
                [ 'deleted' => 1 ], // set
                [ 'form' => $formName, 'pid' => \Frappant\FrpFormAnswers\Utility\BackendUtility::getCurrentPid()]
            );

            $this->addFlashMessage(
                LocalizationUtility::translate('LLL:EXT:frp_form_answers/Resources/Private/Language/de.locallang_be.xlf:flashmessage.deleteFormName.body', 'frp_form_answers', [$formName, \Frappant\FrpFormAnswers\Utility\BackendUtility::getCurrentPid()]),
                LocalizationUtility::translate('LLL:EXT:frp_form_answers/Resources/Private/Language/de.locallang_be.xlf:flashmessage.deleteFormName.title'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                true);
        }
        $this->redirect('list');
    }

    /**
     * Show support area only for admins in given percent of time
     *
     * @param int $probabilityInPercent
     * @return bool
     */
    private function showSupportArea(int $probabilityInPercent = 10): bool
    {
        if (!$this->getBackendUser()->isAdmin()) {
            return false;
        }

        if (mt_rand() % 100 <= $probabilityInPercent) {
            return true;
        }

        return false;
    }

    /**
     * Returns the LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Get backend user
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
