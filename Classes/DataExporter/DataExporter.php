<?php

namespace Frappant\FrpFormAnswers\DataExporter;

use Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class DataExporter
{
    /**
     * getExport
     * @param  Array  $rowAnswers   Assossiative rray with all rowAnswers
     * @param  \Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand $formEntryDemand
     * @param  boolean  $useSubmitUid    bollean check if export UID values should use uid or submitUid
     * @return array   Rows with formatted formAnswers
     */
    public function getExport($rowAnswers, FormEntryDemand $formEntryDemand, $useSubmitUid)
    {
        $rows = array();
        $header = array();
        $headerKeys = (array)array_values($rowAnswers[0]->getAnswers());

        // add header for crdate
        $headerKeys[] = [
            'value' => '',
            'conf' => [
                'label' => LocalizationUtility::translate('LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_db.xlf:tx_frpformanswers_domain_model_formentry.crdate'),
                'inputType' => 'DateTime',
            ],
        ];

        $this->setHeaders($rowAnswers, $formEntryDemand, $headerKeys, $header);

        foreach ($rowAnswers as $key => $entry) {
            $uid = ($useSubmitUid) ? $entry->getSubmitUid() : $entry->getUid();

            if ($formEntryDemand->getUidLabel()) {
                $rows[$uid][$formEntryDemand->getUidLabel()] = $uid;
            }
            foreach ($entry->getAnswers() as $fieldName => $field) {
                if ($this->isExportableType($field['conf']['inputType'])) {
                    $rows[$uid][$fieldName] = (is_array($field['value'] ?? '') ? implode(",", $field['value']) : $field['value']);
                }
            }
            $rows[$uid]['crdate'] = $entry->_getProperty('crdate');
        }

        array_unshift($rows, $header);

        return $rows;
    }

    /**
     * Set header labels in an array
     * @param array   $rowAnswers
     * @param FormEntryDemand $formEntryDemand
     * @param array $headerKeys
     * @param array   &$header
     */
    protected function setHeaders($rowAnswers, FormEntryDemand $formEntryDemand, $headerKeys, &$header)
    {
        if ($formEntryDemand->getUidLabel()) {
            $header[] = $formEntryDemand->getUidLabel();
        }

        foreach ($headerKeys as $field => $val) {
            if ($this->isExportableType($val['conf']['inputType'])) {
                $header[] = ($val['conf']['label'] ? $val['conf']['label'] : $field);
            }
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
