<?php
declare(strict_types=1);

namespace Frappant\FrpFormAnswers\EventListener;

use Frappant\FrpFormAnswers\Event\ManipulateFormValuesEvent;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Folder;

final class UploadPathEnrichmentEventListener
{
    public function __invoke(ManipulateFormValuesEvent $event): void
    {
        // Get extension settings
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('frp_form_answers');

        $enableUploadPathEnrichment = (bool)($extConf['enableUploadPathEnrichment'] ?? false);
        $savePublicUploadPath       = (bool)($extConf['savePublicUploadPath'] ?? false);

        if (!$enableUploadPathEnrichment) {
            return; // Feature disabled
        }

        $values = $event->getValues();

        // Get FormDefinition to read element properties (saveToFileMount)
        $formDef = $event->getFormRuntime()->getFormDefinition();

        // Build map: field identifier => combined target (keep combined for reliable folder lookup)
        $uploadTargets = [];
        foreach ($formDef->getPages() as $page) {
            foreach ($page->getElementsRecursively() as $element) {
                if (!in_array($element->getType(), ['FileUpload', 'ImageUpload'], true)) {
                    continue;
                }
                $props = $element->getProperties();
                $combinedTarget = rtrim($props['saveToFileMount'] ?? '1:/user_upload/', '/') . '/';
                $uploadTargets[$element->getIdentifier()] = $combinedTarget;
            }
        }

        // Enrich values with final prefix (base + detected submission subfolder)
        $added = [];
        foreach ($uploadTargets as $fieldIdentifier => $combinedTarget) {
            if (!isset($values[$fieldIdentifier])) {
                continue;
            }
            $entry = $values[$fieldIdentifier];

            // Expect documented structure: ['value' => string|array, 'conf' => ...]
            if (is_array($entry) && array_key_exists('value', $entry)) {

                // Determine final prefix per file (handles form_<hash> folder)
                $entry['value'] = $this->prependWithSubmissionPrefix(
                    $entry['value'],
                    $combinedTarget,
                    $savePublicUploadPath
                );

                $added[$fieldIdentifier] = $entry;
            }
        }

        if (!empty($added)) {
            $event->addValue($added);
        }
    }

    /**
     * Resolve base prefix from combined target:
     * - If $usePublic: try folder public URL (relative, no domain)
     * - Else: return combined identifier
     */
    private function resolveBasePrefix(string $combinedFolderIdentifier, bool $usePublic): string
    {
        $combined = rtrim($combinedFolderIdentifier, '/') . '/';
        if (!$usePublic) {
            return $combined;
        }

        try {
            $folder = GeneralUtility::makeInstance(ResourceFactory::class)
                ->getFolderObjectFromCombinedIdentifier($combinedFolderIdentifier);

            $public = $folder->getPublicUrl(); // e.g. "/fileadmin/user_upload/"
            if (!empty($public)) {
                return rtrim($public, '/') . '/';
            }
        } catch (\Throwable) {
            // Fallback below
        }

        return $combined;
    }

    /**
     * Find the submission subfolder (form_*) that actually contains $filename.
     * Returns a prefix that includes this subfolder. If not found, returns base prefix.
     */
    private function resolveSubmissionPrefix(string $combinedFolderIdentifier, bool $usePublic, string $filename): string
    {
        // Always search via FAL (combined) to be storage-agnostic
        try {
            /** @var Folder $baseFolder */
            $baseFolder = GeneralUtility::makeInstance(ResourceFactory::class)
                ->getFolderObjectFromCombinedIdentifier($combinedFolderIdentifier);

            // Look only at first-level subfolders named form_*
            foreach ($baseFolder->getSubfolders() as $sub) {
                $name = $sub->getName();
                if (str_starts_with($name, 'form_') && $sub->hasFile($filename)) {
                    // Build prefix in desired notation (public or combined)
                    if ($usePublic) {
                        $public = $sub->getPublicUrl();
                        if (!empty($public)) {
                            return rtrim($public, '/') . '/';
                        }
                    }
                    return rtrim($sub->getCombinedIdentifier(), '/') . '/';
                }
            }
        } catch (\Throwable) {
            // Fall back to base prefix below
        }

        // Fallback: no form_* match → use base folder prefix
        return $this->resolveBasePrefix($combinedFolderIdentifier, $usePublic);
    }

    /**
     * Prepend final prefix per value (handles string or string[]).
     * For each file, detect correct form_* folder and prefix accordingly.
     */
    private function prependWithSubmissionPrefix(string|array $value, string $combinedTarget, bool $usePublic): string|array
    {
        if (is_string($value)) {
            $prefix = $this->resolveSubmissionPrefix($combinedTarget, $usePublic, ltrim($value, '/'));
            return rtrim($prefix, '/') . '/' . ltrim($value, '/');
        }

        $out = [];
        foreach ($value as $k => $v) {
            if (!is_string($v)) {
                $out[$k] = $v;
                continue;
            }
            $prefix = $this->resolveSubmissionPrefix($combinedTarget, $usePublic, ltrim($v, '/'));
            $out[$k] = rtrim($prefix, '/') . '/' . ltrim($v, '/');
        }
        return $out;
    }
}
