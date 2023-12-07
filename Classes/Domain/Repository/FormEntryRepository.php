<?php
namespace Frappant\FrpFormAnswers\Domain\Repository;

use Frappant\FrpFormAnswers\Database\QueryGenerator;
use Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use Frappant\FrpFormAnswers\Utility\BackendUtility;

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
class FormEntryRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Finds all FormEntries given by conf Array
     * @param  \Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand $formEntryDemand
     * @return QueryResult
     */
    public function findByDemand(FormEntryDemand $formEntryDemand, int $pid = 0)
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
     * @return QueryResult
     */
    public function findAllInPidAndRootline($pid)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $queryGenerator = GeneralUtility::makeInstance(QueryGenerator::class);
        $pids = GeneralUtility::trimExplode(',', $queryGenerator->getTreeList($pid, 20, 0, 1), true);

        if (!BackendUtility::isBackendAdmin()) {
            $pids = BackendUtility::filterPagesForAccess($pids);
        }

        if (is_array($pids) && count($pids)) {
            $query->matching($query->in('pid', $pids));
        }

        $query->setOrderings(['pid' => QueryInterface::ORDER_ASCENDING]);

        return $query->execute();
    }

    /**
     * Finds the last Form Entry of a given yaml File (form) - used to set the submitUid in SaveFormToDatabaseFinisher
     *
     * @param String $form
     * @return QueryResult
     */
    public function getLastFormAnswerByIdentifyer($form)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->setOrderings(
            array(
                'submitUid' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
            )
        );

        $query->matching($query->equals('form', $form));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    public function setFormsToExported($forms)
    {
        foreach ($forms as $entry) {
            $entry->setExported(true);
            $this->update($entry);
        }

        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        $persistenceManager->persistAll();
    }
}
