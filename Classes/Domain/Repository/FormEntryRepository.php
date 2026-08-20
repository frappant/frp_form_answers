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
        return $query->execute();
    }

    /**
     * Find all within a Page and all subpages
     *
     * @param int $pid start page identifier
     * @return QueryResultInterface<int, FormEntry>
     */
    public function findAllInPidAndRootline(int $pid): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $pids = GeneralUtility::trimExplode(',', $this->queryGenerator->getTreeList($pid, 20, 0, '1'), true);

        if (!BackendUtility::isBackendAdmin()) {
            $pids = BackendUtility::filterPagesForAccess($pids);
        }

        if (count($pids)) {
            $query->matching($query->in('pid', $pids));
        }

        $query->setOrderings(['pid' => QueryInterface::ORDER_ASCENDING]);

        return $query->execute();
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
     * @param QueryResultInterface<int, FormEntry> $forms
     */
    public function setFormsToExported(QueryResultInterface $forms): void
    {
        $uids = [];
        foreach ($forms as $entry) {
            $uids[] = $entry->getUid();
        }

        $uids = array_values(array_filter($uids, static fn(?int $uid): bool => $uid !== null));
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
