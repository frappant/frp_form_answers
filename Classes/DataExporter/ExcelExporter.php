<?php

namespace Frappant\FrpFormAnswers\DataExporter;

class ExcelExporter
{

    /**
     * tableName
     *
     * @var \PHPExcel
     */
    private $phpExcel = null;

    public function __construct()
    {
        $this -> phpExcel = new \PHPExcel();
    }


    /**
     * function export
     * @var resource $fp
     * @var array $rows
     * @var array $config
     */
    public function export($rows, $config)
    {

        // PHPExcel does not work with associative arrays - then to indexed array
        foreach ($rows as $key => $value) {
            $this->setIndexedArray($rows[$key]);
        }

        $this->phpExcel->setActiveSheetIndex(0);
        $this->phpExcel->getActiveSheet()->fromArray($rows, null, 'A1');

        $objWriter = \PHPExcel_IOFactory::createWriter($this->phpExcel, 'Excel2007');
        $objWriter->save('php://output');
        //exit;
    }

    /**
     * function setIndexedArray
     * Sets an associative array to an indexed array
     */
    private function setIndexedArray(&$arr)
    {
        $arr = array_values($arr);
    }
}
