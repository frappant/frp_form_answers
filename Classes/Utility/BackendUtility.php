<?php

declare(strict_types=1);

namespace Frappant\FrpFormAnswers\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility as BackendUtilityCore;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Class BackendUtility
 */
class BackendUtility extends BackendUtilityCore
{
    /**
     * Check if backend user is admin.
     */
    public static function isBackendAdmin(): bool
    {
        return self::getBackendUserAuthentication()?->isAdmin() ?? false;
    }

    /**
     * Filter a pid array with only pages that are allowed to be viewed
     * by the current backend user.
     *
     * Admin users can access all passed page ids.
     *
     * @param array<int|string> $pids
     * @return array<int>
     */
    public static function filterPagesForAccess(array $pids): array
    {
        $backendUser = self::getBackendUserAuthentication();

        if (!$backendUser instanceof BackendUserAuthentication) {
            return [];
        }

        $pids = array_map('intval', $pids);

        if ($backendUser->isAdmin()) {
            return $pids;
        }

        $allowedPids = [];

        foreach ($pids as $pid) {
            if ($pid <= 0) {
                continue;
            }

            $page = self::getRecord('pages', $pid);

            if (
                is_array($page)
                && $backendUser->isInWebMount($pid)
                && $backendUser->doesUserHaveAccess($page, 1)
            ) {
                $allowedPids[] = $pid;
            }
        }

        return $allowedPids;
    }

    /**
     * Get current PID in backend / frontend contexts.
     *
     * Uses PSR-7 request data first. The superglobal fallback is only kept
     * for old call sites where no request object is passed or available.
     */
    public static function getCurrentPid(
        ?int $pageUid = null,
        ?ServerRequestInterface $request = null
    ): int {
        if ($pageUid !== null && $pageUid > 0) {
            return $pageUid;
        }

        $request ??= $GLOBALS['TYPO3_REQUEST'] ?? null;

        if ($request instanceof ServerRequestInterface) {
            $pid = self::getPidFromRequest($request);

            if ($pid > 0) {
                return $pid;
            }
        }

        return self::normalizePageUid($_GET['id'] ?? 0);
    }

    private static function getPidFromRequest(ServerRequestInterface $request): int
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $bodyParams = is_array($parsedBody) ? $parsedBody : [];

        foreach ([$queryParams, $bodyParams] as $params) {
            $pid = self::normalizePageUid($params['popViewId'] ?? null);

            if ($pid > 0) {
                return $pid;
            }
        }

        foreach ([$bodyParams, $queryParams] as $params) {
            $pid = self::extractPageUidFromUrl((string)($params['returnUrl'] ?? ''));

            if ($pid > 0) {
                return $pid;
            }
        }

        $pageInformation = $request->getAttribute('frontend.page.information');
        if (is_object($pageInformation) && method_exists($pageInformation, 'getId')) {
            $pid = self::normalizePageUid($pageInformation->getId());

            if ($pid > 0) {
                return $pid;
            }
        }

        $routing = $request->getAttribute('routing');
        if (is_object($routing) && method_exists($routing, 'getPageId')) {
            $pid = self::normalizePageUid($routing->getPageId());

            if ($pid > 0) {
                return $pid;
            }
        }

        if (is_object($routing) && method_exists($routing, 'getArguments')) {
            $arguments = $routing->getArguments();
            $pid = self::normalizePageUid($arguments['id'] ?? null);

            if ($pid > 0) {
                return $pid;
            }
        }

        foreach ([$queryParams, $bodyParams] as $params) {
            $pid = self::normalizePageUid($params['id'] ?? null);

            if ($pid > 0) {
                return $pid;
            }
        }

        return 0;
    }

    private static function normalizePageUid(mixed $value): int
    {
        if (!is_scalar($value)) {
            return 0;
        }

        return max(0, (int)$value);
    }

    private static function extractPageUidFromUrl(string $url): int
    {
        if ($url === '') {
            return 0;
        }

        $url = rawurldecode($url);
        $query = parse_url($url, PHP_URL_QUERY);

        if (is_string($query)) {
            parse_str($query, $params);

            $pid = self::normalizePageUid($params['id'] ?? null);

            if ($pid > 0) {
                return $pid;
            }
        }

        if (preg_match('/(?:^|[?&])id=(\d+)/', $url, $matches) === 1) {
            return (int)$matches[1];
        }

        return 0;
    }
}
