<?php

declare(strict_types=1);

namespace Frappant\FrpFormAnswers\ViewHelpers\Be;

use Doctrine\DBAL\ParameterType;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class TableViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    private const MODULE_ROUTE = 'web_FrpFormAnswersFormanswers';

    private const DEFAULT_ITEMS_PER_PAGE = 50;

    public function __construct(
        private readonly UriBuilder $uriBuilder,
        private readonly IconFactory $iconFactory,
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('table', 'string', 'Database table name', true);
        $this->registerArgument('filter', 'array', 'Filter conditions', false, []);
        $this->registerArgument('columns', 'array', 'Columns to display', true);
        $this->registerArgument('pid', 'int', 'PID', true);
        $this->registerArgument('itemsPerPage', 'int', 'Number of entries per page', false, self::DEFAULT_ITEMS_PER_PAGE);
        $this->registerArgument('page', 'int', 'Page to display, 1 based', false, 1);
    }

    /**
     * @throws RouteNotFoundException
     */
    public function render(): string
    {
        $table = (string)$this->arguments['table'];
        $filter = (array)$this->arguments['filter'];
        $columns = (array)$this->arguments['columns'];
        $pid = (int)$this->arguments['pid'];

        $this->assertValidIdentifier($table);

        $columns = array_values(array_unique(array_map('strval', $columns)));
        $columns = array_values(array_filter($columns, static fn(string $column): bool => $column !== 'uid'));

        foreach ($columns as $column) {
            $this->assertValidIdentifier($column);
        }

        $connection = $this->connectionPool->getConnectionForTable($table);
        $queryBuilder = $connection->createQueryBuilder();

        $queryBuilder
            ->select('uid')
            ->from($table)
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, ParameterType::INTEGER),
                ),
            );

        foreach ($columns as $column) {
            $queryBuilder->addSelect($column);
        }

        if ($filter !== []) {
            $queryBuilder->andWhere(...$filter);
        }

        $itemsPerPage = max(1, (int)$this->arguments['itemsPerPage']);
        $numberOfEntries = $this->countEntries($queryBuilder);
        $numberOfPages = max(1, (int)ceil($numberOfEntries / $itemsPerPage));
        $currentPage = min(max(1, $this->currentPage($table)), $numberOfPages);

        // Never render the whole table: a page can hold tens of thousands of
        // entries, and every row costs three backend uris
        $entries = $queryBuilder
            ->setFirstResult(($currentPage - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->executeQuery()
            ->fetchAllAssociative();

        $pencilIcon = $this->iconFactory
            ->getIcon('actions-view', IconSize::SMALL)
            ->render();

        $trashIcon = $this->iconFactory
            ->getIcon('actions-delete', IconSize::SMALL)
            ->render();

        if ($entries === []) {
            return $this->renderEmptyTable();
        }

        return $this->renderTable(
            $entries,
            $table,
            $pid,
            $pencilIcon,
            $trashIcon,
            $numberOfEntries,
            $currentPage,
            $numberOfPages,
        );
    }

    /**
     * The page to show: taken from the request, so that the pagination links
     * work without the template having to pass anything through.
     */
    private function currentPage(string $table): int
    {
        $page = (int)$this->arguments['page'];
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        if ($request instanceof ServerRequestInterface) {
            $page = (int)($request->getQueryParams()[$this->pageParameterName($table)] ?? $page);
        }

        return $page;
    }

    /**
     * Counts the rows the given query would return, without fetching them.
     */
    private function countEntries(QueryBuilder $queryBuilder): int
    {
        $countQuery = clone $queryBuilder;
        $countQuery->resetOrderBy();

        return (int)$countQuery
            ->count('uid')
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     *
     * @throws RouteNotFoundException
     */
    private function renderTable(
        array $entries,
        string $table,
        int $pid,
        string $pencilIcon,
        string $trashIcon,
        int $numberOfEntries,
        int $currentPage,
        int $numberOfPages,
    ): string {
        $output = '<div class="recordlist mb-5 mt-4 border">';
        $output .= '<div class="recordlist-heading row m-0 p-2 g-0 gap-1 align-items-center multi-record-selection-panel">
            <div class="col ms-2">
                <div class="recordlist-heading-title">' . $this->escape($this->translate('table.entries', [$numberOfEntries])) . '</div>
            </div>
        </div>';

        $output .= '<div class="recordlist-body">';
        $output .= '<table class="table table-striped table-borderless">';

        $output .= '<thead><tr>';
        foreach (array_keys($entries[0]) as $key) {
            $output .= '<th>' . $this->escape($key) . '</th>';
        }
        $output .= '<th>' . $this->escape($this->translate('table.actions')) . '</th>';
        $output .= '</tr></thead>';

        $output .= '<tbody>';

        // Loop invariant: building it per row means one more route lookup and
        // hmac per entry
        $backUrl = (string)$this->uriBuilder->buildUriFromRoute(
            self::MODULE_ROUTE,
            [
                'id' => $pid,
            ],
        );

        foreach ($entries as $entry) {
            $uid = (int)$entry['uid'];

            $editUri = (string)$this->uriBuilder->buildUriFromRoutePath(
                '/record/edit',
                [
                    'edit' => [
                        $table => [
                            $uid => 'edit',
                        ],
                    ],
                    'returnUrl' => $backUrl,
                ],
            );

            $deleteUri = (string)$this->uriBuilder->buildUriFromRoute(
                self::MODULE_ROUTE . '.FormEntry_removeEntry',
                [
                    'uid' => $uid,
                    'pid' => $pid,
                ],
            );

            $output .= '<tr>';

            $iterator = 0;
            foreach ($entry as $column => $value) {
                $value = $this->formatValue($table, (string)$column, $value);

                if ($iterator++ === 0) {
                    $output .= '<td><a href="' . $this->escape($editUri) . '">' . $this->escape($value) . '</a></td>';
                    continue;
                }

                $output .= '<td>' . $this->escape($value) . '</td>';
            }

            $output .= '<td>';
            $output .= '<a href="' . $this->escape($editUri) . '" class="btn btn-default btn-sm">' . $pencilIcon . '</a>';
            $output .= '<a href="' . $this->escape($deleteUri) . '" class="btn btn-default btn-sm">' . $trashIcon . '</a>';
            $output .= '</td>';

            $output .= '</tr>';
        }

        $output .= '</tbody>';
        $output .= '</table>';
        $output .= $this->renderPagination($table, $pid, $currentPage, $numberOfPages);
        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * @throws RouteNotFoundException
     */
    private function renderPagination(string $table, int $pid, int $currentPage, int $numberOfPages): string
    {
        if ($numberOfPages < 2) {
            return '';
        }

        $pageParameter = $this->pageParameterName($table);
        $item = function (int $page, string $label, bool $disabled = false, bool $active = false) use ($pid, $pageParameter): string {
            $uri = (string)$this->uriBuilder->buildUriFromRoute(
                self::MODULE_ROUTE,
                [
                    'id' => $pid,
                    $pageParameter => $page,
                ],
            );

            $classes = 'page-item' . ($disabled ? ' disabled' : '') . ($active ? ' active' : '');

            return '<li class="' . $classes . '"><a class="page-link" href="' . $this->escape($uri) . '">' . $this->escape($label) . '</a></li>';
        };

        $output = '<nav class="p-2"><ul class="pagination pagination-sm mb-0">';
        $output .= $item(max(1, $currentPage - 1), '‹', $currentPage <= 1);

        foreach ($this->pageNumbers($currentPage, $numberOfPages) as $page) {
            $output .= $item($page, (string)$page, false, $page === $currentPage);
        }

        $output .= $item(min($numberOfPages, $currentPage + 1), '›', $currentPage >= $numberOfPages);
        $output .= '</ul></nav>';

        return $output;
    }

    /**
     * A window around the current page, so that thousands of pages do not
     * produce thousands of links.
     *
     * @return list<int>
     */
    private function pageNumbers(int $currentPage, int $numberOfPages): array
    {
        $first = max(1, $currentPage - 2);
        $last = min($numberOfPages, $first + 4);
        $first = max(1, $last - 4);

        return range($first, $last);
    }

    /**
     * Each table on the page needs its own page parameter, otherwise paging
     * one form's entries would page all of them.
     */
    private function pageParameterName(string $table): string
    {
        return 'entryPage_' . substr(md5($table . '-' . ($this->arguments['filter'][0] ?? '')), 0, 8);
    }

    private function renderEmptyTable(): string
    {
        $output = '<div class="recordlist mb-5 mt-4 border">';
        $output .= '<div class="recordlist-heading row m-0 p-2 g-0 gap-1 align-items-center multi-record-selection-panel">
            <div class="col ms-2">
                <div class="recordlist-heading-title">' . $this->escape($this->translate('table.entries', [0])) . '</div>
            </div>
        </div>';
        $output .= '<div class="recordlist-body">';
        $output .= '<div class="alert alert-info">' . $this->escape($this->translate('table.noentries')) . '</div>';
        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Renders a raw database value the way the backend would, so that for
     * example date columns show a date instead of a unix timestamp.
     */
    private function formatValue(string $table, string $column, mixed $value): string
    {
        $rawValue = (string)($value ?? '');

        return BackendUtility::getProcessedValue($table, $column, $rawValue) ?? $rawValue;
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private function translate(string $key, array $arguments = []): string
    {
        return LocalizationUtility::translate(
            'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_be.xlf:' . $key,
            null,
            $arguments,
        ) ?? $key;
    }

    private function escape(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function assertValidIdentifier(string $identifier): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid database identifier "%s".', $identifier),
                1762350501,
            );
        }
    }
}
