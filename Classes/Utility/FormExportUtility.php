<?php

namespace Frappant\FrpFormAnswers\Utility;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FormExportUtility
{

    /**
     * tableName
     *
     * @var Spreadsheet
     */
    private Spreadsheet $spreadsheet;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
    }


    /**
     * function export
     * @param iterable<object> $formEntries
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(iterable $formEntries): string
    {
        $rows = [];
        foreach ($formEntries as $entry) {
            if (!is_object($entry) || !method_exists($entry, 'getUid') || !method_exists($entry, 'getAnswers')) {
                continue;
            }
            $rows[(int)$entry->getUid()] = (array)$entry->getAnswers();
        }

        if ($rows === []) {
            return '';
        }

        $header = array_keys(array_values($rows)[0]);

        // PHPExcel does not work with associative arrays - then to indexed array
        foreach ($rows as $key => $value) {
            self::setIndexedArray($rows[$key]);
        }
        array_unshift($rows, $header);

        $this->spreadsheet->setActiveSheetIndex(0);
        $this->spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $writer = new Xlsx($this->spreadsheet);
        ob_start();
        $writer->save('php://output');
        return (string)ob_get_clean();
    }

    /**
     * function setIndexedArray
     * Sets an associative array to an indexed array
     * @param array<mixed> $arr
     */
    private static function setIndexedArray(array &$arr): void
    {
        $arr = array_values($arr);
    }
}
