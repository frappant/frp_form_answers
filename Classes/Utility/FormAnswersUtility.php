<?php

declare(strict_types=1);

namespace Frappant\FrpFormAnswers\Utility;

use Frappant\FrpFormAnswers\Domain\Repository\FormEntryRepository;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

final class FormAnswersUtility
{
    public function __construct(
        private readonly FormEntryRepository $formEntryRepository,
    ) {}

    /**
     * @return array<int, array<string, array{tot: int, new?: int}>>
     */
    public function prepareFormAnswersArray(?ServerRequestInterface $request = null): array
    {
        $currentPageId = $this->getCurrentPageId($request);
        $startPageIds = $this->getStartPageIds($currentPageId);

        $pageIds = [];

        foreach ($startPageIds as $pageId) {
            $this->addFormEntryCountsForPage($pageIds, $pageId);
        }

        unset($pageIds[$currentPageId]);

        return $pageIds;
    }

    /**
     * Get all names of the saved forms.
     *
     * @return list<string>
     */
    public function getAllFormNames(int $pid): array
    {
        return $this->formEntryRepository->findDistinctValues('form', $pid);
    }

    /**
     * Get all hashes of the saved forms.
     *
     * @return list<string>
     */
    public function getAllFormHashes(int $pid): array
    {
        return $this->formEntryRepository->findDistinctValues('field_hash', $pid);
    }

    private function getCurrentPageId(?ServerRequestInterface $request): int
    {
        $request ??= $this->getRequest();

        if (!$request instanceof ServerRequestInterface) {
            return 0;
        }

        return max(0, (int)($request->getQueryParams()['id'] ?? 0));
    }

    /**
     * @return list<int>
     */
    private function getStartPageIds(int $currentPageId): array
    {
        if ($currentPageId > 0) {
            return [$currentPageId];
        }

        return array_map(
            'intval',
            $this->getBackendUser()->getWebmounts(),
        );
    }

    /**
     * @param array<int, array<string, array{tot: int, new?: int}>> $pageIds
     */
    private function addFormEntryCountsForPage(array &$pageIds, int $pageId): void
    {
        // Counting happens in the database. Loading the entries as objects
        // exhausts the memory limit on pages with tens of thousands of them.
        $accessiblePids = $this->formEntryRepository->findAccessiblePidsInRootline($pageId);

        foreach ($this->formEntryRepository->countByPidAndForm($accessiblePids) as $pid => $formCounts) {
            foreach ($formCounts as $formName => $counts) {
                $pageIds[$pid][$formName]['tot'] = ($pageIds[$pid][$formName]['tot'] ?? 0) + $counts['tot'];

                if ($counts['new'] > 0) {
                    $pageIds[$pid][$formName]['new'] = ($pageIds[$pid][$formName]['new'] ?? 0) + $counts['new'];
                }
            }
        }
    }

    private function getRequest(): ?ServerRequestInterface
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        return $request instanceof ServerRequestInterface ? $request : null;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
