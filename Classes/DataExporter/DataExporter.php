<?php

namespace Frappant\FrpFormAnswers\DataExporter;

class DataExporter
{
    /**
     * getExport
     * @param  Array  $rowAnswers   Assossiative rray with all rowAnswers
     * @param  \Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand $formEntryDemand
     * @param  boolean  $useSubmitUid    bollean check if export UID values should use uid or submitUid
     * @return array   Rows with formatted formAnswers
     */
    public function getExport($rowAnswers, \Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand $formEntryDemand, $useSubmitUid)
    {
        $rows = array();
        $header = array();
        $headerKeys = (array)array_values($rowAnswers[0]->getAnswers());

        $this->setHeaders($rowAnswers, $formEntryDemand, $headerKeys, $header);

        foreach ($rowAnswers as $key => $entry) {
            $uid = ($useSubmitUid) ? $entry->getSubmitUid() : $entry->getUid();

            if ($formEntryDemand->getUidLabel()) {
                $rows[$uid][$formEntryDemand->getUidLabel()] = $uid;
            }
            foreach ($entry->getAnswers() as $fieldName => $field) {
                if (!preg_match('/^fieldset/', $fieldName) && !preg_match('/^statictext/', $fieldName)) {
                    $rows[$uid][$fieldName] = (is_array($field['value']) ? implode(",", $field['value']) : $field['value']);
                }
            }
        }

        array_unshift($rows, $header);

        return $rows;
    }

    /**
     * Set header labels in an array
     * @param array   $rowAnswers
     * @param \Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand $formEntryDemand
     * @param array $headerKeys
     * @param array   &$header
     */
    protected function setHeaders($rowAnswers, \Frappant\FrpFormAnswers\Domain\Model\FormEntryDemand $formEntryDemand, $headerKeys, &$header)
    {
        if ($formEntryDemand->getUidLabel()) {
            $header[] = $formEntryDemand->getUidLabel();
        }

        foreach ($headerKeys as $field => $val) {
            if ($val['conf']['inputType'] != 'Fieldset' && $val['conf']['inputType'] != 'StaticText') {
                $header[] = ($val['conf']['label'] ? $val['conf']['label'] : $field);
            }
        }
    }
}
