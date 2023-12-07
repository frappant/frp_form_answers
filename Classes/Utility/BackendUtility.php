<?php
namespace Frappant\FrpFormAnswers\Utility;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility as BackendUtilityCore;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Class BackendUtility
 */
class BackendUtility extends BackendUtilityCore
{

    /**
     * Check if backend user is admin
     *
     * @return bool
     */
    public static function isBackendAdmin()
    {
        if (isset(self::getBackendUserAuthentication()->user)) {
            return self::getBackendUserAuthentication()->user['admin'] === 1;
        }
        return false;
    }

    /**
     * Filter a pid array with only the pages that are allowed to be viewed from the backend user.
     * If the backend user is an admin, show all of course - so ignore this filter.
     *
     * @param array $pids
     * @return array
     */
    public static function filterPagesForAccess(array $pids)
    {
        if (!self::isBackendAdmin()) {
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);

            /**
             * @todo check if this if block can be deleted
             */
            $t3Version = GeneralUtility::makeInstance(Typo3Version::class);

            if (version_compare($t3Version->getBranch(), '10', '<')) {
                $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('pages')
                    ->expr()
                ;
                $oldExpression = $expressionBuilder->lt('pages.doktype', 200);
                $newExpression = $expressionBuilder->neq('pages.doktype', PageRepository::DOKTYPE_RECYCLER);
                $pageRepository->where_hid_del = str_replace(
                    $oldExpression,
                    $newExpression,
                    $pageRepository->where_hid_del
                );
            }

            $newPids = [];
            foreach ($pids as $pid) {
                $page = $pageRepository->getPage($pid);
                if (self::getBackendUserAuthentication()->doesUserHaveAccess($page, 1)) {
                    $newPids[] = $pid;
                }
            }
            $pids = $newPids;
        }
        return $pids;
    }

    /**
     * @return BackendUserAuthentication
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected static function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     *   Get current PID in backend.
     *   Uses various fallbacks depending on current view and backend module.
     *   ToDo: Ask somebody, how this can be done simple :)
     */
    public static function getCurrentPid($pageUid = null)
    {
        $context = GeneralUtility::makeInstance(Context::class);

        if (!$pageUid) {
            $pageUid = (int) $GLOBALS['_REQUEST']['popViewId'];
        }
        if (!$pageUid) {
            $pageUid = (int) preg_replace('/(.*)(id=)([0-9]*)(.*)/i', '\\3', $GLOBALS['_REQUEST']['returnUrl']);
        }
        if (!$pageUid) {
            $pageUid = (int) preg_replace('/(.*)(id=)([0-9]*)(.*)/i', '\\3', $GLOBALS['_POST']['returnUrl']);
        }
        if (!$pageUid) {
            $pageUid = (int) preg_replace('/(.*)(id=)([0-9]*)(.*)/i', '\\3', $GLOBALS['_GET']['returnUrl']);
        }
        if (!$pageUid) {
            $pageUid = (int) $GLOBALS['TSFE']->id;
        }
        if (!$pageUid) {
            $pageUid = (int) $_GET['id'];
        }
        if (!$pageUid) {
            $pageUid = 0;
        }
        return $pageUid;
    }
}
