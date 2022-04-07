<?php
namespace Frappant\FrpFormAnswers\View\FormEntry;

use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;

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
class ExportXml implements \TYPO3\CMS\Extbase\Mvc\View\ViewInterface
{
    /**
     * Controller Context
     * @var TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     */
    protected ControllerContext $controllerContext;

    /**
     * View variables and their values
     *
     * @var array
     * @see assign()
     */
    protected $variables = [];

    /**
     * @param ControllerContext $controllerContext
     */
    public function injectControllerContext(ControllerContext $controllerContext) {
        $this->controllerContext = $controllerContext;
    }

    /**
     * Sets the current controller context
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }

    /**
     * Add a variable to $this->viewData.
     * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
     *
     * @param string $key Key of variable
     * @param mixed $value Value of object
     * @return Frappant\FrpFormAnswers\View\FormEntry an instance of $this, to enable chaining
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
     * @return Frappant\FrpFormAnswers\View\FormEntry an instance of $this, to enable chaining
     */
    public function assignMultiple(array $values)
    {
        foreach ($values as $key => $value) {
            $this->assign($key, $value);
        }
        return $this;
    }

    public function initializeView() {
        return null;
    }

    /**
     * Renders the view
     *
     * @return string The rendered view
     * @api
     */
    public function render()
    {
        ob_start();
        
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

        return ob_get_clean();
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
            // consider crdate
            if ($value instanceof \DateTime) {
                $value = $value->format('c');
            }
            $str .= "\t\t<".$field.">".htmlspecialchars(stripslashes($value))."</".$field.">\n";
        }


        // close row
        $str .= "\t</row>\n";

        return $str;
    }
}
