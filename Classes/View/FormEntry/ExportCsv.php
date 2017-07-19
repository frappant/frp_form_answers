<?php
namespace Frappant\FrpFormAnswers\View\FormEntry;

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
 * ExportCSV
 */
class ExportCsv extends \TYPO3\CMS\Extbase\Mvc\View\AbstractView
{
    /**
     * Delimiter Array
     * @var array
     */
    protected $delimiter = array(
        'komma' => ',',
        'semikolon' => ';',
        'tab' => '\t'
    );

    /**
     * Enclosure Array
     * @var array
     */
    protected $enclosure = array(
        'single' => '\'',
        'double' => '"'
    );


    public function initializeView()
    {
        $this->controllerContext->getResponse()->setHeader('Content-Type', 'application/force-download');
        $this->controllerContext->getResponse()->setHeader('Content-Type', 'text/csv');
        $this->controllerContext->getResponse()->setHeader('Content-Disposition', 'attachment;filename=export.csv');
        $this->controllerContext->getResponse()->setHeader('Content-Transfer-Encoding', 'binary');
        $this->controllerContext->getResponse()->setHeader("Content-Type", "application/download; charset=$this->variables['formEntryDemand']->getCharset()");
    }

    public function render()
    {
        foreach ($this->variables['rows'] as $fields) {
            $this->fputcsv2($fields, $this->delimiter[$this->variables['formEntryDemand']->getDelimiter()], $this->enclosure[$this->variables['formEntryDemand']->getEnclosure()]);
        }
    }

    /**
     * function fputscv2
     * Funktion gem. php.net
     * Behebt mögliche Fehlerfälle der ursprünglichen Funktion fputcsv
     * @param array $fields
     * @param string $delimiter
     * @param string $enclosure
     * @param boolean $mysql_null
     * @return void
     */
    private function fputcsv2(array $fields, $delimiter = ';', $enclosure = '"', $mysql_null = false)
    {
        $delimiter_esc = preg_quote($delimiter, '/');
        $enclosure_esc = preg_quote($enclosure, '/');

        $output = array();
        foreach ($fields as $field) {
            if ($field === null && $mysql_null) {
                $output[] = 'NULL';
                continue;
            }

            $output[] = preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field) ? (
                $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure
            ) : $field;
        }
        echo join($delimiter, $output) . "\n";
    }
}
