<?php
namespace Frappant\FrpFormAnswers\View\FormEntry;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use TYPO3\CMS\Extbase\Mvc\View\AbstractView;

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
class ExportXls extends AbstractView
{

	/**
	 * Spreadsheet
	 *
	 * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
	 * @inject
	 */
	private $spreadsheet;

    public function initializeView()
    {
        $this->controllerContext->getResponse()->setHeader('Content-Type', 'application/force-download');
        $this->controllerContext->getResponse()->setHeader('Content-Disposition', 'attachment;filename=export.xls');
        $this->controllerContext->getResponse()->setHeader('Content-Type', 'application/download; charset=$this->variables[\'formEntryDemand\']->getCharset()');
    }

	/**
	 * @return string|void
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
	 */
	public function render()
    {
        $rows = $this->variables['rows'];
        // PHPExcel does not work with associative arrays - then to indexed array
        foreach ($rows as $key => $value) {
            $this->setIndexedArray($rows[$key]);
        }

        $this->spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $objWriter = new Xlsx($this->spreadsheet);
        $objWriter->save('php://output');
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