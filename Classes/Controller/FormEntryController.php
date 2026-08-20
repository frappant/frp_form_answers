<?php

declare(strict_types=1);

namespace Frappant\FrpFormAnswers\Controller;

use Frappant\FrpFormAnswers\DataExporter\DataExporter;
use Frappant\FrpFormAnswers\Domain\Model\FormEntry;
use Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand;
use Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository;
use Frappant\FrpFormAnswers\Utility\FormAnswersUtility;
use Frappant\FrpFormAnswers\View\FormEntry\ExportCsv;
use Frappant\FrpFormAnswers\View\FormEntry\ExportXls;
use Frappant\FrpFormAnswers\View\FormEntry\ExportXml;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
#[AsController]
class FormEntryController extends ActionController
{
    protected int $pid = 0;

    protected ?FormEntryDemand $formEntryDemand = null;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly IconFactory $iconFactory,
        protected readonly FormAnswersUtility $formAnswersUtility,
        protected readonly FormEntryRepository $formEntryRepository,
        protected readonly DataExporter $dataExporter,
        protected readonly PageRepository $pageRepository,
        protected readonly PersistenceManager $persistenceManager,
        protected readonly ConnectionPool $connectionPool,
    ) {}

    protected function initializeAction(): void
    {
        $queryParams = $this->request->getQueryParams();
        $parsedBody = $this->request->getParsedBody();

        $this->pid = (int)($queryParams['id'] ?? (is_array($parsedBody) ? ($parsedBody['id'] ?? 0) : 0));
    }

    /**
     * action list, Show saved form entries from database
     */
    public function listAction(): ResponseInterface
    {
        $pageIds = $this->formAnswersUtility->prepareFormAnswersArray($this->request);
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        if ($pageIds !== []) {
            $moduleTemplate->assign('subPagesWithFormEntries', $this->pageRepository->getMenuForPages(array_keys($pageIds)));
            $moduleTemplate->assign('formEntriesStatus', $pageIds);
        }

        $moduleTemplate->assign('pid', $this->pid);
        $moduleTemplate->assign('formNames', $this->formAnswersUtility->getAllFormNames($this->pid));
        $moduleTemplate->assign('settings', $this->settings);

        $this->createMenu($moduleTemplate);
        $this->createButtons($moduleTemplate);

        return $moduleTemplate->renderResponse($this->templateFilenameFromRequest());
    }

    /**
     * action show
     */
    public function showAction(FormEntry $formEntry): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assign('formEntry', $formEntry);

        $this->createMenu($moduleTemplate);
        $this->createButtons($moduleTemplate);

        return $moduleTemplate->renderResponse($this->templateFilenameFromRequest());
    }

    /**
     * action prepareRemove, Show form entries which are marked as deleted
     */
    public function prepareRemoveAction(): ResponseInterface
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_frpformanswers_domain_model_formentry');
        $queryBuilder->getRestrictions()->removeAll();

        $count = (int)$queryBuilder
            ->count('*')
            ->from('tx_frpformanswers_domain_model_formentry')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($this->pid, Connection::PARAM_INT)))
            ->andWhere($queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assign('count', $count);

        $this->createMenu($moduleTemplate);
        $this->createButtons($moduleTemplate);

        return $moduleTemplate->renderResponse($this->templateFilenameFromRequest());
    }

    /**
     * action mark single entry as deleted
     *
     * @throws IllegalObjectTypeException
     */
    public function removeEntryAction(): ResponseInterface
    {
        $arguments = $this->request->getArguments();
        $uid = (int)($arguments['uid'] ?? 0);
        $pid = (int)($arguments['pid'] ?? $this->pid);
        $entry = $this->formEntryRepository->findByUid($uid);

        if ($entry === null) {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_be.xlf:flashmessage.entryNotFound.body',
                ) ?? '',
                LocalizationUtility::translate(
                    'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_be.xlf:flashmessage.entryNotFound.header',
                ) ?? '',
                ContextualFeedbackSeverity::WARNING,
                true,
            );

            return $this->redirect('list', null, null, ['id' => $pid]);
        }

        $this->formEntryRepository->remove($entry);
        $this->persistenceManager->persistAll();

        $this->addFlashMessage(
            LocalizationUtility::translate(
                'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_be.xlf:flashmessage.removeEntry.body',
                null,
                [$uid],
            ) ?? '',
            LocalizationUtility::translate(
                'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_be.xlf:flashmessage.removeEntry.header',
            ) ?? '',
            ContextualFeedbackSeverity::OK,
            true,
        );

        return $this->redirect('list', null, null, ['id' => $pid]);
    }

    /**
     * action remove, Remove form entries which are marked as deleted
     */
    public function removeAction(): ResponseInterface
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_frpformanswers_domain_model_formentry');

        $queryBuilder->delete('tx_frpformanswers_domain_model_formentry')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($this->pid, Connection::PARAM_INT)))
            ->andWhere($queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))
            ->executeStatement();

        $this->addFlashMessage(
            LocalizationUtility::translate(
                'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_be.xlf:flashmessage.removeEntries.body',
                null,
                [$this->pid],
            ) ?? '',
            LocalizationUtility::translate(
                'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_be.xlf:flashmessage.removeEntries.header',
            ) ?? '',
            ContextualFeedbackSeverity::OK,
            true,
        );

        return $this->redirect('list', null, null, ['id' => $this->pid]);
    }

    /**
     * action prepareExport
     */
    public function prepareExportAction(): ResponseInterface
    {
        $demandObject = GeneralUtility::makeInstance(FormEntryDemand::class);
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $this->formEntryDemand = $demandObject;
        $moduleTemplate->assign('formEntryDemand', $demandObject);
        $moduleTemplate->assign('formHashes', $this->formAnswersUtility->getAllFormHashes($this->pid));

        $this->createMenu($moduleTemplate);
        $this->createButtons($moduleTemplate);

        return $moduleTemplate->renderResponse($this->templateFilenameFromRequest());
    }

    /**
     * export Action
     */
    public function exportAction(?FormEntryDemand $formEntryDemand = null): ResponseInterface
    {
        if ($formEntryDemand === null) {
            $this->addFlashMessage(
                'No Demand set',
                'No Demand found',
                ContextualFeedbackSeverity::ERROR,
                true,
            );

            return $this->redirect('list', null, null, ['id' => $this->pid]);
        }

        $arguments = $this->request->getArguments();
        $format = (string)($arguments['format'] ?? '');
        $formEntryDemand->setAllPids((bool)($arguments['allPids'] ?? false));

        $exporter = $this->createExporter($format);
        if ($exporter === null) {
            $this->addFlashMessage(
                'The requested export format is not supported.',
                'Unsupported export format',
                ContextualFeedbackSeverity::ERROR,
                true,
            );

            return $this->redirect('list', null, null, ['id' => $this->pid]);
        }

        if ($this->formEntryRepository->countByDemand($formEntryDemand, $this->pid) === 0) {
            $this->addFlashMessage(
                'No entries found with your criteria',
                'No Entries found',
                ContextualFeedbackSeverity::WARNING,
                true,
            );

            return $this->redirect('list', null, null, ['id' => $this->pid]);
        }

        $extensionConfiguration = $this->getExtensionConfiguration();
        $useSubmitUid = (bool)($extensionConfiguration['useSubmitUid']['value'] ?? $extensionConfiguration['useSubmitUid'] ?? false);

        // Stream the entries through the exporter and remember which ones were
        // written, instead of holding every hydrated entry in memory
        $exportedUids = [];
        $entries = (function () use ($formEntryDemand, &$exportedUids): \Generator {
            foreach ($this->formEntryRepository->iterateByDemand($formEntryDemand, $this->pid) as $entry) {
                $uid = $entry->getUid();
                if ($uid !== null) {
                    $exportedUids[] = $uid;
                }

                yield $entry;
            }
        })();

        $exportData = $this->dataExporter->getExport($entries, $formEntryDemand, $useSubmitUid);

        $exporter->assign('rows', $exportData);
        $exporter->assign('formEntryDemand', $formEntryDemand);

        // Build the whole response before flagging the entries: if rendering
        // or encoding fails - which is what happens on large data sets when
        // the memory limit is hit - the entries must stay unexported,
        // otherwise the next export with "only new entries" silently returns
        // nothing and the submissions look lost
        $response = $this->createDownloadResponse(
            $exporter->render(),
            $format,
            $arguments['formEntryDemand']['charset'] ?? null,
            $formEntryDemand->getFileName() ?: (string)$formEntryDemand->getFormName(),
        );

        $this->formEntryRepository->setExportedByUids($exportedUids);

        return $response;
    }

    /**
     * @todo check where this method is used
     */
    public function deleteFormnameAction(string $formName = ''): ResponseInterface
    {
        if ($formName !== '') {
            $connection = $this->connectionPool->getConnectionForTable('tx_frpformanswers_domain_model_formentry');

            $connection->update(
                'tx_frpformanswers_domain_model_formentry',
                ['deleted' => 1],
                ['form' => $formName, 'pid' => $this->pid],
            );

            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_be.xlf:flashmessage.deleteFormName.body',
                    'FrpFormAnswers',
                    [$formName, $this->pid],
                ) ?? '',
                LocalizationUtility::translate(
                    'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_be.xlf:flashmessage.deleteFormName.header',
                ) ?? '',
                ContextualFeedbackSeverity::OK,
                true,
            );
        }

        return $this->redirect('list', null, null, ['id' => $this->pid]);
    }

    protected function createMenu(ModuleTemplate $moduleTemplate): void
    {
        $this->uriBuilder->setRequest($this->request);

        $menu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
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

        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    protected function createButtons(ModuleTemplate $moduleTemplate): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        /** @var NormalizedParams|null $normalizedParams */
        $normalizedParams = $this->request->getAttribute('normalizedParams');
        $requestUri = $normalizedParams instanceof NormalizedParams
            ? $normalizedParams->getRequestUri()
            : (string)$this->request->getUri();

        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref($requestUri)
            ->setTitle($this->getLanguageService()->sL('core.core:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', IconSize::SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Returns the LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function createExporter(string $format): ExportCsv|ExportXls|ExportXml|null
    {
        return match ($format) {
            'Csv' => new ExportCsv(),
            'Xls' => new ExportXls(),
            'Xml' => new ExportXml(),
            default => null,
        };
    }

    private function createDownloadResponse(
        string $content,
        string $format,
        ?string $charset,
        string $fileName = '',
    ): ResponseInterface {
        $charset = $this->normalizeCharset($charset);
        $baseName = $this->sanitizeFileName($fileName);

        // Only the csv export is converted. The xlsx is a binary zip, and the
        // xml declares no encoding in its prolog, so both have to stay as
        // they were rendered - UTF-8.
        if ($format === 'Csv') {
            $content = $this->convertCharset($content, $charset);
        } else {
            $charset = 'utf-8';
        }

        [$filename, $contentType] = match ($format) {
            'Csv' => [$baseName . '.csv', 'text/csv; charset=' . $charset],
            'Xls' => [$baseName . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'Xml' => [$baseName . '.xml', 'application/xml; charset=' . $charset],
            default => throw new \InvalidArgumentException('Unsupported export format: ' . $format, 1710001001),
        };

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Content-Disposition', sprintf('attachment; filename="%s"', $filename))
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withBody($this->streamFactory->createStream($content));
    }

    /**
     * The charset arrives from the request, so it has to be checked against
     * the encodings the export actually offers. Anything else would reach
     * mb_convert_encoding(), which throws on an unknown encoding.
     */
    private function normalizeCharset(?string $charset): string
    {
        return match (strtolower(trim((string)$charset))) {
            'iso-8859-1' => 'iso-8859-1',
            'utf-16le' => 'utf-16le',
            default => 'utf-8',
        };
    }

    /**
     * The exported content is built as UTF-8. Convert it when the editor asked
     * for a legacy charset, so that the Content-Type header does not lie about
     * the bytes we send.
     */
    private function convertCharset(string $content, string $charset): string
    {
        if ($charset === 'utf-8' || $content === '') {
            return $content;
        }

        return mb_convert_encoding($content, $charset, 'UTF-8');
    }

    private function sanitizeFileName(string $fileName): string
    {
        $fileName = (string)preg_replace('/[^A-Za-z0-9._-]/', '-', $fileName);
        $fileName = (string)preg_replace('/\.(csv|xml|xlsx?)$/i', '', $fileName);
        $fileName = trim($fileName, '-.');
        // Keep the Content-Disposition header within what proxies accept
        $fileName = substr($fileName, 0, 100);

        return $fileName !== '' ? $fileName : 'export';
    }

    /**
     * @return array<string, mixed>
     */
    private function getExtensionConfiguration(): array
    {
        $extensionConfigurations = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] ?? [];
        $configuration = $extensionConfigurations['frp_form_answers']
            ?? $extensionConfigurations['frp_formanswers']
            ?? [];

        return is_array($configuration) ? $configuration : [];
    }

    private function templateFilenameFromRequest(): string
    {
        return $this->request->getControllerName() . '/' . ucfirst($this->request->getControllerActionName());
    }
}
