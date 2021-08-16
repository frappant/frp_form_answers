<?php
namespace Frappant\FrpFormAnswers\Utility;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility as BackendUtilityCore;
use TYPO3\CMS\Frontend\Page\PageRepository;

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

            if (version_compare(TYPO3_branch, '10', '<')) {
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
    protected static function getBackendUserAuthentication()
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
            //$pageRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Domain\Repository\PageRepository::class);
            //list($page) = $pageRepository->getSubpagesForPages([0]);
            //$pageUid = intval($page['uid']);
            $pageUid = 0;
        }
        return $pageUid;
    }
}
