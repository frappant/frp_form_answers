<?php
namespace Frappant\FrpFormAnswers\Domain\Repository;

use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
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
    public function initializeObject()
    {
        $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');

        $querySettings->setStoragePageIds(array((int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id')));

        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * setRespectStoragePage description
     * @param boolean $bool Check if respectStoragePage should be set or nor
     */
    public function setRespectStoragePage($bool)
    {
        $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');

        $querySettings->setRespectStoragePage($bool);

        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Finds all FormEntries given by conf Array
     * @param  \Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand $formEntryDemand
     * @return QueryResult
     */
    public function findByDemand(\Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand $formEntryDemand)
    {
        $query = $this->createQuery();

        if ($formEntryDemand->getAllPids()) {
            $query->getQuerySettings()->setRespectStoragePage(false);
        }

        $constraints = array();

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
            $query->matching($query->logicalAnd($constraints));
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

        $persistenceManager = $this->objectManager->get("TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager");
        $persistenceManager->persistAll();
    }
}
