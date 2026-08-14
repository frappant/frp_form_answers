<?php

namespace Frappant\FrpFormAnswers\DataExporter;

use Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand;
use Frappant\FrpFormAnswers\Domain\Model\FormEntry;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class DataExporter
{
    /**
     * getExport
     * @param array<int, FormEntry> $rowAnswers
     * @return array<int, array<string, mixed>>
     */
    public function getExport(array $rowAnswers, FormEntryDemand $formEntryDemand, bool $useSubmitUid): array
    {
        if ($rowAnswers === []) {
            return [];
        }

        $rows = [];
        $header = [];
        $headerKeys = array_values($rowAnswers[0]->getAnswers());

        // add header for crdate
        $headerKeys[] = [
            'value' => '',
            'conf' => [
                'label' => LocalizationUtility::translate('LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_db.xlf:tx_frpformanswers_domain_model_formentry.crdate'),
                'inputType' => 'DateTime',
            ],
        ];

        $this->setHeaders($formEntryDemand, $headerKeys, $header);

        foreach ($rowAnswers as $key => $entry) {
            $uid = ($useSubmitUid) ? $entry->getSubmitUid() : $entry->getUid();

            if ($formEntryDemand->getUidLabel()) {
                $rows[$uid][$formEntryDemand->getUidLabel()] = $uid;
            }
            foreach ($entry->getAnswers() as $fieldName => $field) {
                if ($this->isExportableType($field['conf']['inputType'])) {
                    $rows[$uid][$fieldName] = (is_array($field['value'] ?? '') ? implode(",", $field['value']) : ($field['value'] ?? ''));
                }
            }
            // The model stores crdate as unix timestamp; exporters format \DateTime values
            $rows[$uid]['crdate'] = (new \DateTime())->setTimestamp($entry->getCrdate());
        }

        array_unshift($rows, $header);

        return $rows;
    }

    /**
     * Set header labels in an array
     * @param array<int, array{value:mixed, conf: array{label?: mixed, inputType: string}}> $headerKeys
     * @param array<int, string> &$header
     */
    protected function setHeaders(FormEntryDemand $formEntryDemand, array $headerKeys, array &$header): void
    {
        if ($formEntryDemand->getUidLabel()) {
            $header[] = $formEntryDemand->getUidLabel();
        }

        foreach ($headerKeys as $field => $val) {
            if (!isset($val['conf']['inputType']) || !$this->isExportableType($val['conf']['inputType'])) {
                continue;
            }

            $label = (string)($val['conf']['label'] ?? '');
            $header[] = $label !== '' ? $label : (string)$field;
        }
    }

    private function isExportableType(string $inputType): bool
    {
        $typesToSkip = [
            'Fieldset',
            'StaticText',
            'GridRow'
        ];
        return !\in_array($inputType, $typesToSkip);
    }
}
