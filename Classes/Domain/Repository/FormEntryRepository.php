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
     * Finds all FormEntries given by conf Array
     * @param  [type] $config [description]
     * @return [type]         [description]
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

        if ($pid > 0) {
            $queryGenerator = GeneralUtility::makeInstance(QueryGenerator::class);
            $pids = GeneralUtility::trimExplode(',', $queryGenerator->getTreeList($pid, 20, 0, 1), true);
            $pids = BackendUtility::filterPagesForAccess($pids);
            $query->matching($query->in('pid', $pids));
        } else {
            if (!BackendUtility::isBackendAdmin()) {
                $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
                $pids = $pageRepository->getAllPages();
                $pids = BackendUtility::filterPagesForAccess($pids);
                $query->matching($query->in('pid', $pids));
            }
        }
        $query->setOrderings(['pid' => QueryInterface::ORDER_ASCENDING]);

        return $query->execute();
    }
}
