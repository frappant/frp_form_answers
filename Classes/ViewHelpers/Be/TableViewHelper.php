<?php

declare(strict_types=1);

namespace Frappant\FrpFormAnswers\ViewHelpers\Be;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class TableViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    private const MODULE_ROUTE = 'web_FrpFormAnswersFormanswers';

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

        $entries = $queryBuilder
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
        );
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
    ): string {
        $numberOfEntries = count($entries);

        $output = '<div class="recordlist mb-5 mt-4 border">';
        $output .= '<div class="recordlist-heading row m-0 p-2 g-0 gap-1 align-items-center multi-record-selection-panel">
            <div class="col ms-2">
                <div class="recordlist-heading-title">Entry (' . $numberOfEntries . ')</div>
            </div>
        </div>';

        $output .= '<div class="recordlist-body">';
        $output .= '<table class="table table-striped table-borderless">';

        $output .= '<thead><tr>';
        foreach (array_keys($entries[0]) as $key) {
            $output .= '<th>' . $this->escape($key) . '</th>';
        }
        $output .= '<th>Actions</th>';
        $output .= '</tr></thead>';

        $output .= '<tbody>';

        foreach ($entries as $entry) {
            $uid = (int)$entry['uid'];

            $backUrl = (string)$this->uriBuilder->buildUriFromRoute(
                self::MODULE_ROUTE,
                [
                    'id' => $pid,
                ],
            );

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
        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    private function renderEmptyTable(): string
    {
        $output = '<div class="recordlist mb-5 mt-4 border">';
        $output .= '<div class="recordlist-heading row m-0 p-2 g-0 gap-1 align-items-center multi-record-selection-panel">
            <div class="col ms-2">
                <div class="recordlist-heading-title">Entry (0)</div>
            </div>
        </div>';
        $output .= '<div class="recordlist-body">';
        $output .= '<div class="alert alert-info">Keine Einträge vorhanden</div>';
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
