<?php

namespace Frappant\FrpFormAnswers\Domain\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Frappant\FrpFormAnswers\Database\QueryGenerator;
use Frappant\FrpFormAnswers\Domain\Model\FormEntry;
use Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand;
use Frappant\FrpFormAnswers\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

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
 * The repository for FormEntries
 */
/**
 * @extends Repository<FormEntry>
 */
class FormEntryRepository extends Repository
{
    private const TABLE_NAME = 'tx_frpformanswers_domain_model_formentry';

    public function __construct(
        private readonly QueryGenerator $queryGenerator,
        private readonly ConnectionPool $connectionPool,
    ) {
        parent::__construct();
    }

    /**
     * Finds all FormEntries given by conf Array
     * @param FormEntryDemand $formEntryDemand
     * @return QueryResultInterface<int, FormEntry>
     */
    public function findByDemand(FormEntryDemand $formEntryDemand, int $pid = 0): QueryResultInterface
    {
        return $this->buildDemandQuery($formEntryDemand, $pid)->execute();
    }

    /**
     * @return QueryInterface<FormEntry>
     */
    private function buildDemandQuery(FormEntryDemand $formEntryDemand, int $pid): QueryInterface
    {
        $query = $this->createQuery();

        if ($formEntryDemand->getAllPids()) {
            $settings = $query->getQuerySettings();
            $settings->setRespectStoragePage(false);
            $query->setQuerySettings($settings);
        } else {
            $query->getQuerySettings()->setRespectStoragePage(true);
            $query->getQuerySettings()->setStoragePageIds([$pid]);
        }

        $constraints = [];

        if (!$formEntryDemand->getSelectAll()) {
            $constraints[] = $query->equals('exported', false);
        }

        if ($formEntryDemand->getForm()) {
            $constraints[] = $query->equals('fieldHash', $formEntryDemand->getForm());
        }

        if ($formEntryDemand->getFormName()) {
            $constraints[] = $query->equals('form', $formEntryDemand->getFormName());
        }

        if (count($constraints)) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        // A stable order is required: the export reads the result in chunks,
        // and without it the database may return a row twice or not at all
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        return $query;
    }

    /**
     * Yields the entries of a demand in chunks, so that an export does not
     * hold tens of thousands of hydrated objects at once. The persistence
     * session is cleared per chunk, otherwise the objects would pile up in
     * the identity map anyway.
     *
     * @return \Generator<int, FormEntry>
     */
    public function iterateByDemand(FormEntryDemand $formEntryDemand, int $pid = 0, int $chunkSize = 500): \Generator
    {
        $offset = 0;

        do {
            $query = $this->buildDemandQuery($formEntryDemand, $pid);
            $query->setOffset($offset);
            $query->setLimit($chunkSize);

            $entries = $query->execute()->toArray();

            foreach ($entries as $entry) {
                yield $entry;
            }

            $offset += $chunkSize;
            $fetched = count($entries);

            $this->persistenceManager->clearState();
        } while ($fetched === $chunkSize);
    }

    /**
     * Counts the entries of a demand without loading them.
     */
    public function countByDemand(FormEntryDemand $formEntryDemand, int $pid = 0): int
    {
        return $this->buildDemandQuery($formEntryDemand, $pid)->execute()->count();
    }

    /**
     * The pages below (and including) the given one that the current backend
     * user is allowed to see.
     *
     * @return list<int>
     */
    public function findAccessiblePidsInRootline(int $pid): array
    {
        $pids = GeneralUtility::trimExplode(',', $this->queryGenerator->getTreeList($pid, 20, 0, '1'), true);

        if (!BackendUtility::isBackendAdmin()) {
            $pids = BackendUtility::filterPagesForAccess($pids);
        }

        return array_values(array_map('intval', $pids));
    }

    /**
     * Counts the entries per page and form name, without loading them.
     *
     * @param list<int> $pids
     * @return array<int, array<string, array{tot: int, new: int}>>
     */
    public function countByPidAndForm(array $pids): array
    {
        if ($pids === []) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $rows = $queryBuilder
            ->select('pid', 'form')
            ->addSelectLiteral(
                'COUNT(*) AS ' . $queryBuilder->quoteIdentifier('tot'),
                'SUM(' . $queryBuilder->quoteIdentifier('exported') . ') AS ' . $queryBuilder->quoteIdentifier('exported_count'),
            )
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($pids, ArrayParameterType::INTEGER),
                ),
            )
            ->groupBy('pid', 'form')
            ->executeQuery()
            ->fetchAllAssociative();

        $counts = [];
        foreach ($rows as $row) {
            $formName = (string)$row['form'];
            if ($formName === '') {
                continue;
            }

            $total = (int)$row['tot'];
            $counts[(int)$row['pid']][$formName] = [
                'tot' => $total,
                'new' => $total - (int)$row['exported_count'],
            ];
        }

        return $counts;
    }

    /**
     * Returns the distinct values of a column for one page, without loading
     * the entries themselves.
     *
     * @return list<string>
     */
    public function findDistinctValues(string $column, int $pid): array
    {
        if (!in_array($column, ['form', 'field_hash'], true)) {
            throw new \InvalidArgumentException('Unsupported column: ' . $column, 1755690000);
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $values = $queryBuilder
            ->select($column)
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
            )
            ->groupBy($column)
            ->orderBy($column)
            ->executeQuery()
            ->fetchFirstColumn();

        return array_values(array_filter(array_map('strval', $values), static fn(string $value): bool => $value !== ''));
    }

    /**
     * Finds the last Form Entry of a given yaml File (form) - used to set the submitUid in SaveFormToDatabaseFinisher
     *
     * @param string $form
     * @return FormEntry|null
     */
    public function getLastFormAnswerByIdentifyer(string $form): ?FormEntry
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->setOrderings(
            [
                'submitUid' => QueryInterface::ORDER_DESCENDING,
            ]
        );

        $query->matching($query->equals('form', $form));
        $query->setLimit(1);

        $first = $query->execute()->getFirst();
        return $first instanceof FormEntry ? $first : null;
    }

    /**
     * @param array<int, int|null> $uids
     */
    public function setExportedByUids(array $uids): void
    {
        $uids = array_values(array_unique(array_filter($uids, static fn(?int $uid): bool => $uid !== null)));
        if ($uids === []) {
            return;
        }

        // One statement instead of an update per entry - exports can cover
        // tens of thousands of rows
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        foreach (array_chunk($uids, 500) as $chunk) {
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder
                ->update(self::TABLE_NAME)
                ->set('exported', 1, true, Connection::PARAM_INT)
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($chunk, ArrayParameterType::INTEGER),
                    ),
                )
                ->executeStatement();
        }
    }
}
