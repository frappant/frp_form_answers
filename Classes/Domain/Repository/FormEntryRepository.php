<?php
namespace Frappant\FrpFormAnswers\Domain\Repository;

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
     * @param  [type] $config [description]
     * @return [type]         [description]
     */
    public function findByConfig($config)
    {
        $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');

        if ($config['allPids']) {
            $querySettings->setRespectStoragePage(false);
        } else {
            $querySettings->setRespectStoragePage(true);
            $querySettings->setStoragePageIds(array((int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id')));
        }

        $this->setDefaultQuerySettings($querySettings);

        $query = $this->createQuery();

        $constraints = array();

        if (!$config['selectAll']) {
            $constraints[] = $query->equals('exported', false);
        }

        if ($config['form']) {
            $constraints[] = $query->equals('formHash', $config['form']);
        }

        if (count($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }
        return $query->execute();
    }
}
