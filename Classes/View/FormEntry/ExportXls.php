<?php
namespace Frappant\FrpFormAnswers\View\FormEntry;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 !frappant <support@frappant.ch>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * ExportXls
 */
class ExportXls
{

    /**
     * @var Spreadsheet|null
     */
    protected static ?Spreadsheet $spreadsheet = null;

    /**
     * View variables and their values
     *
     * @var array
     * @see assign()
     */
    protected $variables = [];

    /**
     * Add a variable to $this->viewData.
     * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
     *
     * @param string $key Key of variable
     * @param mixed $value Value of object
     * @return ExportXls an instance of $this, to enable chaining
     */
    public function assign($key, $value)
    {
        $this->variables[$key] = $value;
        return $this;
    }

    /**
     * Add multiple variables to $this->viewData.
     *
     * @param array $values array in the format array(key1 => value1, key2 => value2).
     * @return ExportXls an instance of $this, to enable chaining
     */
    public function assignMultiple(array $values)
    {
        foreach ($values as $key => $value) {
            $this->assign($key, $value);
        }
        return $this;
    }

    /**
     * @return string|void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function render($data = null)
    {
        if (null === self::$spreadsheet) {
            self::$spreadsheet = new Spreadsheet();
            self::$spreadsheet->getProperties()->setCreator("Frappant Forms Export")
                ->setLastModifiedBy("Frappant Forms Export")
                ->setCreated(time());
        }

        $rows = $this->variables['rows'];
        // PHPExcel does not work with associative arrays - then to indexed array
        foreach ($rows as $key => $value) {
            $this->setIndexedArray($rows[$key]);
        }

        self::$spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $objWriter = new Xlsx(self::$spreadsheet);

        ob_start();
        $objWriter->save('php://output');
        return ob_get_clean();
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
