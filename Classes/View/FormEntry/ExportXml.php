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
 * ExportXls
 */
class ExportXml extends \TYPO3\CMS\Extbase\Mvc\View\AbstractView
{
    public function initializeView()
    {
        $this->controllerContext->getResponse()->setHeader('Content-Type', 'application/force-download');
        $this->controllerContext->getResponse()->setHeader('Content-Type', 'application/xml');
        $this->controllerContext->getResponse()->setHeader('Content-Disposition', 'attachment;filename=export.xml');
        $this->controllerContext->getResponse()->setHeader('Content-Transfer-Encoding', 'binary');
        $this->controllerContext->getResponse()->setHeader("Content-Type", "application/download; charset=$this->variables['formEntryDemand']->getCharset()");
    }

    public function render()
    {
        $rows = $this->variables['rows'];

         // remove header line
        $this->array_shift($rows);


        // write xml header
        echo "<?xml version='1.0' standalone='yes'?>\n";

        // write tableName
        echo "<tx_frpformanswers_domain_model_formentry>\n";

        // write all array rows - inner Array in separated function
        for ($i = 0; $i < count($rows); $i++) {
            echo $this->arr2xml($rows[$i], $i);
        }

        // close tableName
        echo "</tx_frpformanswers_domain_model_formentry>\n";
    }

    /**
     * function array_shift
     * Function array_shift with resetting the key values (Indexed!)
     * @param array $arr
     */
    protected function array_shift(&$arr)
    {
        array_shift($arr);
        $rows = array_values($arr);
    }

    /**
     * function arr2xml
     * Sets an associative Array into an XML Element
     * @var array $arr
     * @param int $index
     * @return string The xml Tag
     */
    protected function arr2xml($arr, $index)
    {
        // open row
        $str = "\t<row index=\"".$index."\" type=\"array\">\n";

        // put value in row
        foreach ($arr as $field => $value) {
            $str .= "\t\t<".$field.">".htmlspecialchars(stripslashes($value))."</".$field.">\n";
        }


        // close row
        $str .= "\t</row>\n";

        return $str;
    }
}
