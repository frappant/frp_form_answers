<?php

namespace Frappant\FrpFormAnswers\DataExporter;

class DataExporter
{
    private $csvExporter = null;

    private $excelExporter = null;

    private $xmlExporter = null;

    private $outputPath = 'php://output';

    private $fileTypes = array(
        'csv' => 'csv',
        'xml' => 'xml',
        'excel' => 'xlsx'
    );

    public function __construct()
    {
        $this->csvExporter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Frappant\\FrpFormAnswers\\DataExporter\\CsvExporter');
        $this->excelExporter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Frappant\\FrpFormAnswers\\DataExporter\\ExcelExporter');
        $this->xmlExporter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Frappant\\FrpFormAnswers\\DataExporter\\XmlExporter');
    }

    public function getExport($exportType, $rowAnswers, $config)
    {
        $exportClass = strtolower($exportType)."Exporter";
        $rows = array();
        $header = array();
        $headerKeys = (array)array_values(json_decode($rowAnswers[0]->getAnswers(), 1));

        foreach ($headerKeys as $field => $val) {
            $header[] = ($val['conf']['label'] ? $val['conf']['label'] : $field);
        }

        foreach ($rowAnswers as $key => $entry) {
            foreach ((array)json_decode($entry->getAnswers(), true) as $fieldName => $field) {
                $rows[$entry->getUid()][$fieldName] = (is_array($field['value']) ? implode(",", $field['value']) : $field['value']);
            }
        }

        array_unshift($rows, $header);

        $this->download_send_headers($config['fileName'].".".$this->fileTypes[strtolower($exportType)], ($config['charset'] ? $config['charset'] : null));

        // Get File Content
        $this->$exportClass->export($rows, $config);
    }


    private function download_send_headers($filename, $charset= "UTF-8")
    {
        // disable caching
        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");

        header("Content-Type: application/force-download");
        header("Content-Type: application/download; charset=$charset");
        if (preg_match('/.csv/', $filename)) {
            header('Content-Encoding: '.$charset);
            header('Content-type: text/csv; charset='.$charset);
        }

        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
    }
}
