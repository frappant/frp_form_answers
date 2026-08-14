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
        return $this->getUniqueFormValues(
            $pid,
            static fn(object $answer): ?string => $answer->getForm(),
        );
    }

    /**
     * Get all hashes of the saved forms.
     *
     * @return list<string>
     */
    public function getAllFormHashes(int $pid): array
    {
        return $this->getUniqueFormValues(
            $pid,
            static fn(object $answer): ?string => $answer->getFieldHash(),
        );
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
        foreach ($this->formEntryRepository->findAllInPidAndRootline($pageId) as $formEntry) {
            $pid = $formEntry->getPid();
            $formName = $formEntry->getForm();

            if (!is_int($pid) || $formName === null || $formName === '') {
                continue;
            }

            $pageIds[$pid][$formName]['tot'] = ($pageIds[$pid][$formName]['tot'] ?? 0) + 1;

            if (!$formEntry->isExported()) {
                $pageIds[$pid][$formName]['new'] = ($pageIds[$pid][$formName]['new'] ?? 0) + 1;
            }
        }
    }

    /**
     * @param callable(object): ?string $valueGetter
     * @return list<string>
     */
    private function getUniqueFormValues(int $pid, callable $valueGetter): array
    {
        $values = [];

        foreach ($this->findAllByStoragePid($pid) as $answer) {
            $value = $valueGetter($answer);

            if ($value === null || $value === '') {
                continue;
            }

            $values[$value] = true;
        }

        return array_keys($values);
    }

    /**
     * @return iterable<\Frappant\FrpFormAnswers\Domain\Model\FormEntry>
     */
    private function findAllByStoragePid(int $pid): iterable
    {
        $query = $this->formEntryRepository->createQuery();

        $query->getQuerySettings()
            ->setRespectStoragePage(true)
            ->setStoragePageIds([$pid]);

        return $query->execute();
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
