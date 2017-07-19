<?php

namespace Frappant\FrpFormAnswers\DataExporter;

class DataExporter
{
    public function getExport($rowAnswers, \Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand $formEntryDemand)
    {
        $rows = array();
        $header = array();
        $headerKeys = (array)array_values(json_decode($rowAnswers[0]->getAnswers(), 1));

        if ($formEntryDemand->getUidLabel()) {
            $header[] = $formEntryDemand->getUidLabel();
        }

        foreach ($headerKeys as $field => $val) {
            if ($val['conf']['inputType'] != 'Fieldset' && $val['conf']['inputType'] != 'StaticText') {
                $header[] = ($val['conf']['label'] ? $val['conf']['label'] : $field);
            }
        }

        foreach ($rowAnswers as $key => $entry) {
            if ($formEntryDemand->getUidLabel()) {
                $rows[$entry->getUid()][$formEntryDemand->getUidLabel()] = $entry->getUid();
            }
            foreach ((array)json_decode($entry->getAnswers(), true) as $fieldName => $field) {
                if (!preg_match('/^fieldset/', $fieldName) && !preg_match('/^statictext/', $fieldName)) {
                    $rows[$entry->getUid()][$fieldName] = (is_array($field['value']) ? implode(",", $field['value']) : $field['value']);
                }
            }
        }

        array_unshift($rows, $header);

        return $rows;
    }
}
