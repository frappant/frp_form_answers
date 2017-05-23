<?php

namespace Frappant\FrpFormAnswers\Utility;

class FormExportUtility
{

    /**
     * tableName
     *
     * @var \PHPExcel
     */
    private static $phpExcel = null;

    public function __construct()
    {
        $this -> phpExcel = new \PHPExcel();
    }


    /**
     * function export
     * @var array $formEntries
     */
    public function export($formEntries)
    {
        $rows = array();
        $header = array();
        foreach ($formEntries as $entry) {
            $rows[$entry->getUid()] = (array)json_decode($entry->getAnswers());
        }

        $header = array_keys(array_values($rows)[0]);

        // PHPExcel does not work with associative arrays - then to indexed array
        foreach ($rows as $key => $value) {
            self::setIndexedArray($rows[$key]);
        }
        array_unshift($rows, $header);



        $this->phpExcel->setActiveSheetIndex(0);
        $this->phpExcel->getActiveSheet()->fromArray($rows, null, 'A1');

        $objWriter = \PHPExcel_IOFactory::createWriter($this->phpExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    /**
     * function setIndexedArray
     * Sets an associative array to an indexed array
     * @var array $arr
     */
    private function setIndexedArray(&$arr)
    {
        $arr = array_values($arr);
    }
}
