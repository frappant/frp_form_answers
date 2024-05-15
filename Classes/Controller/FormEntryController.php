<?php
namespace Frappant\FrpFormAnswers\Controller;

use Frappant\FrpFormAnswers\DataExporter\DataExporter;
use Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand;
use Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository;
use Frappant\FrpFormAnswers\Utility\FormAnswersUtility;
use Frappant\FrpFormAnswers\View\FormEntry\ExportCsv;
use Frappant\FrpFormAnswers\View\FormEntry\ExportXls;
use Frappant\FrpFormAnswers\View\FormEntry\ExportXml;
use JetBrains\PhpStorm\NoReturn;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/***
 *
 * This file is part of the "Form Answer Saver" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2022 !Frappant <support@frappant.ch>
 *
 ***/

/**
 * FormEntryController
 */
class FormEntryController extends ActionController
{
    /**
     * @var ModuleTemplateFactory $moduleTemplateFactory
     */
    protected ModuleTemplateFactory $moduleTemplateFactory;

    /**
     * @var IconFactory $iconFactory
     */
    protected IconFactory $iconFactory;

    /**
     * @var FormAnswersUtility $formAnswersUtility
     */
    protected FormAnswersUtility $formAnswersUtility;

    /**
     * @var FormEntryRepository $formEntryRepository
     */
    protected FormEntryRepository $formEntryRepository;

    /**
     * @var \Frappant\FrpFormAnswers\DataExporter\DataExporter
     */
    protected DataExporter $dataExporter;

    /**
    * @var PageRepository $pageRepository
    */
    protected PageRepository $pageRepository;

    /**
     * @var PersistenceManager  $persistenceManager
     */
    protected PersistenceManager $persistenceManager;

    /**
     * @var string $filename
     */
    protected string $filename = '';

    /**
     * @var integer
     */
   protected $pid;

    /**
     * http headers to send with filedownload request @see exportAction
     *
     * @var array
     */
    protected $requestHeaders = [];


    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        IconFactory $iconFactory,
        FormAnswersUtility $formAnswersUtility,
        FormEntryRepository $formEntryRepository,
        DataExporter $dataExporter,
        PageRepository $pageRepository,
        PersistenceManager $persistenceManager
    ) {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->iconFactory = $iconFactory;
        $this->formAnswersUtility = $formAnswersUtility;
        $this->formEntryRepository = $formEntryRepository;
        $this->dataExporter = $dataExporter;
        $this->pageRepository = $pageRepository;
        $this->pid = $_GET['id'] ?? 0;
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * action list, Show saved form entries from database
     *
     * @return ResponseInterface
     */
    public function listAction(): ResponseInterface
    {
        $pageIds = $this->formAnswersUtility->prepareFormAnswersArray();

        if (count($pageIds) > 0) {
            $this->view->assign('subPagesWithFormEntries', $this->pageRepository->getMenuForPages(array_keys($pageIds)));
            $this->view->assign('formEntriesStatus', $pageIds);
        }
        $this->view->assign('pid', $this->pid);
        $this->view->assign('formNames', $this->formAnswersUtility->getAllFormNames([$this->pid]));
        $this->view->assign('settings', $this->settings);

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $this->createMenu($moduleTemplate);
	    $this->createButtons($moduleTemplate);

        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * action show
     *
     * @param \Frappant\FrpFormAnswers\Domain\Model\FormEntry $formEntry
     * @return ResponseInterface
     */
    public function showAction(\Frappant\FrpFormAnswers\Domain\Model\FormEntry $formEntry): ResponseInterface
    {
        $this->view->assign('formEntry', $formEntry);

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * action prepareRemove, Show form entries which are marked as deleted
     *
     * @return ResponseInterface
     */
    public function prepareRemoveAction(): ResponseInterface
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_frpformanswers_domain_model_formentry');
        $queryBuilder->getRestrictions()->removeAll();

        $count = $queryBuilder->count('*')
            ->from('tx_frpformanswers_domain_model_formentry')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($this->pid, \PDO::PARAM_INT)))
            ->andWhere($queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))
            ->executeQuery()->fetchFirstColumn();
        //DebuggerUtility::var_dump($count);
        $this->view->assign('count', $count[0]);

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $this->createMenu($moduleTemplate);
	    $this->createButtons($moduleTemplate);

        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * action mark single entry as deleted
     *
     * @return void
     * @throws IllegalObjectTypeException
     */
    public function removeEntryAction(): \Psr\Http\Message\ResponseInterface
    {
        $arguments = $this->request->getArguments();
        $uid = $arguments['uid'];
        $pid = $arguments['pid'];
        $entry = $this->formEntryRepository->findByUid($uid);

        $this->formEntryRepository->remove($entry);
        $this->persistenceManager->persistAll();

        $this->addFlashMessage(
            'Deleted entry with uid: ' . $uid,
            'Entry deleted',
            \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::OK,
            true
        );

        return $this->redirect('list', null, null, ['id' => $pid]);
    }

    /**
     * action remove, Remove form entries which are marked as deleted
     *
     * @return ResponseInterface
     */
    public function removeAction()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_frpformanswers_domain_model_formentry');

        $queryBuilder->delete('tx_frpformanswers_domain_model_formentry')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($this->pid, \PDO::PARAM_INT)))
            ->andWhere($queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))
            ->execute();

        $this->addFlashMessage(
            LocalizationUtility::translate('LLL:EXT:frp_form_answers/Resources/Private/Language/de.locallang_be.xlf:flashmessage.removeEntries.body', null, [$this->pid]),
            LocalizationUtility::translate('LLL:EXT:frp_form_answers/Resources/Private/Language/de.locallang_be.xlf:flashmessage.removeEntries.title'),
            \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::OK,
            true);

        return $this->redirect('list', null, null, ['id' => $this->pid]);
    }

    /**
     * action prepareExport
     *
     * @return ResponseInterface
     */
    public function prepareExportAction(): ResponseInterface
    {
        $demandObject = GeneralUtility::makeInstance(FormEntryDemand::class);

        $this->formEntryDemand = $demandObject;
        $this->view->assign('formEntryDemand', $demandObject);
        $this->view->assign('formHashes', $this->formAnswersUtility->getAllFormHashes($this->pid));

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $this->createMenu($moduleTemplate);
	    $this->createButtons($moduleTemplate);

        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    public function initializeExportAction(){

        $args = $this->request->getArguments();
        $format = $args['format'];
        // $this->filename = $args['formEntryDemand']['formName'];

        $charset = (strlen($args['formEntryDemand']['charset'] ?? '') > 0 ? $args['formEntryDemand']['charset'] : 'iso-8859-1');

        switch ($format){
            case 'Csv':
                $this->filename = (strlen($this->filename) > 0 ? $this->filename.'.csv' : 'export.csv');
                $this->setRequestHeader('Content-Type', 'application/force-download');
                $this->setRequestHeader('Content-Type', 'text/csv');
                $this->setRequestHeader('Content-Disposition', "attachment;filename=$this->filename");
                $this->setRequestHeader('Content-Transfer-Encoding', 'binary');
                $this->setRequestHeader('Content-Type', "application/download; charset=$charset");
            break;
            case 'Xls':
                $this->filename = (strlen($this->filename) > 0 ? $this->filename.'.xlsx' : 'export.xlsx');
                $this->setRequestHeader('Content-Type', 'application/force-download');
                $this->setRequestHeader('Content-Disposition', "attachment;filename=$this->filename");
                $this->setRequestHeader('Content-Type', "application/download; charset=$charset");
            break;
            case 'Xml':
                $this->filename = (strlen($this->filename) > 0 ? $this->filename.'.xml' : 'export.xml');
                $this->setRequestHeader('Content-Type', 'application/force-download');
                $this->setRequestHeader('Content-Type', 'application/xml');
                $this->setRequestHeader('Content-Disposition', "attachment;filename=$this->filename");
                $this->setRequestHeader('Content-Transfer-Encoding', 'binary');
                $this->setRequestHeader('Content-Type', "application/download; charset=$charset");
            break;
        }
    }

	/**
	 * export Action
     *
	 * @param FormEntryDemand $formEntryDemand
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
	 */
    public function exportAction(\Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand $formEntryDemand = null)
    {

        $format = $this->request->getArguments()['format'];
        $formEntryDemand->setAllPids($this->request->getArguments()['allPids'] ?? false);
        $pid = $_GET['id'];

        if($formEntryDemand) {
            $formEntries = $this->formEntryRepository->findbyDemand($formEntryDemand, $pid);
            if (count($formEntries) === 0) {
                $this->addFlashMessage('No entries found with your criteria',
                    'No Entries found',
                    \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::WARNING,
                    true
                );
                return $this->redirect('list', null, null, ['id' => $this->pid]);
            }
        } else {
            $this->addFlashMessage('No Demand set',
                'No Demand found',
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR,
                true
            );
            return $this->redirect('list', null, null, ['id' => $this->pid]);
        }

        $extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['frp_formanswers'] ?? null;
        $exportData = $this->dataExporter->getExport($formEntries, $formEntryDemand, $extensionConfiguration['useSubmitUid']['value'] ?? false);

        $this->formEntryRepository->setFormsToExported($formEntries);


        $exporter = Null;
        switch ($format) {
            case 'Csv':
                $exporter = new ExportCsv();
                break;
            case 'Xls':
                $exporter = new ExportXls();
                break;
            case 'Xml':
                $exporter = new ExportXml();
                break;
        }



        $exporter->assign('rows', $exportData);
        $exporter->assign('formEntryDemand', $formEntryDemand);

        // Get the content as a string
        $content = $exporter->render();

        // Prepare a PSR-7 Response
        $stream = new Stream('php://memory', 'rw');
        $stream->write($content);

        switch ($format) {
            case 'Csv':
                $response = new Response($stream, 200, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="export.csv"',
                ]);
                break;
            case 'Xls':
                $response = new Response($stream, 200, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="export.xlsx"',
                ]);
                break;
            case 'Xml':
                $response = new Response($stream, 200, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="export.xml"',
                ]);
                break;
        }

        return $response;
    }

    /**
     * Prepare the download request
     *
     * @param string File Contents wich would be downloaded
     * @return ResponseInterface http response with http headers and file contents
     */
    protected function generateDownloadResponse($renderedContent): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();

        foreach($this->getRequestHeaders() as $header) {
            $response = $response->withHeader("$header[0]", "$header[1]");
        }

        $response = $response->withBody($this->streamFactory->createStream($renderedContent));

        return $response;
    }

    /**
     * @todo check where this method is used
     * @param string $formName
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function deleteFormnameAction($formName = ''){

        if(strlen($formName) > 0){

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_frpformanswers_domain_model_formentry');

            $queryBuilder->update(
                'tx_frpformanswers_domain_model_formentry',
                [ 'deleted' => 1 ], // set
                [ 'form' => $formName, 'pid' => $this->pid]
            );

            $this->addFlashMessage(
                LocalizationUtility::translate('LLL:EXT:frp_form_answers/Resources/Private/Language/de.locallang_be.xlf:flashmessage.deleteFormName.body', 'frp_form_answers', [$formName, $this->pid]),
                LocalizationUtility::translate('LLL:EXT:frp_form_answers/Resources/Private/Language/de.locallang_be.xlf:flashmessage.deleteFormName.title'),
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::OK,
                true);
        }
        return $this->redirect('list', null, null, ['id' => $this->pid]);
    }

    /**
     * Create menu
     *
     */
    protected function createMenu($moduleTemplate)
    {
        $this->uriBuilder->setRequest($this->request);

        $menu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        // $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('frpformanswers_main');

        $actions = [
            ['action' => 'list', 'label' => 'Overview'],
            ['action' => 'prepareExport', 'label' => 'Export'],
            ['action' => 'prepareRemove', 'label' => 'Remove'],
        ];

        foreach ($actions as $action) {
            $item = $menu->makeMenuItem()
                ->setTitle($action['label'])
                ->setHref($this->uriBuilder->reset()->uriFor($action['action'], [], 'FormEntry'))
                ->setActive($this->request->getControllerActionName() === $action['action']);
            $menu->addMenuItem($item);
        }

        if ($menu instanceof Menu) {
            $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
        }
    }

    /**
     * Create the panel of buttons
     *
     */
    protected function createButtons($moduleTemplate)
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $this->uriBuilder->setRequest($this->request);

        // Refresh
        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref(GeneralUtility::getIndpEnv('REQUEST_URI'))
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);

    }

    /**
     * @param string $name — Case-insensitive header field name.
     * @param string|string[] $value — Header value(s).
     */
    protected function setRequestHeader($name, $value) {
        $this->requestHeaders[] = [$name, $value];
    }

    /**
     * @return array headers wich should set on response
     */
    protected function getRequestHeaders()
    {
        return $this->requestHeaders;
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

}
