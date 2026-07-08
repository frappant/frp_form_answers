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
class ExportXml
{
    /**
     * View variables and their values
     *
     * @var array<string, mixed>
     * @see assign()
     */
    protected $variables = [];

    /**
     * Add a variable to $this->viewData.
     * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
     *
     * @param string $key Key of variable
     * @param mixed $value Value of object
     * @return ExportXml an instance of $this, to enable chaining
     */
    public function assign(string $key, mixed $value): self
    {
        $this->variables[$key] = $value;
        return $this;
    }

    /**
     * Add multiple variables to $this->viewData.
     *
     * @param array<string, mixed> $values array in the format array(key1 => value1, key2 => value2).
     * @return ExportXml an instance of $this, to enable chaining
     */
    public function assignMultiple(array $values)
    {
        foreach ($values as $key => $value) {
            $this->assign($key, $value);
        }
        return $this;
    }

    public function initializeView(mixed $view): void {
    }

    /**
     * Renders the view
     *
     * @return string The rendered view
     * @api
     */
    public function render(): string
    {
        ob_start();

        $rows = $this->variables['rows'];
        $this->array_shift($rows);

        echo "<?xml version='1.0' standalone='yes'?>\n";
        echo "<tx_frpformanswers_domain_model_formentry>\n";

        for ($i = 0; $i < count($rows); $i++) {
            echo $this->arr2xml($rows[$i], $i);
        }

        echo "</tx_frpformanswers_domain_model_formentry>\n";

        return ob_get_clean();
    }

    /**
     * function array_shift
     * Function array_shift with resetting the key values (Indexed!)
     * @param array<int, mixed> $arr
     */
    protected function array_shift(array &$arr): void
    {
        array_shift($arr);
        $arr = array_values($arr);
    }

    /**
     * @param array<string, mixed> $arr
     */
    protected function arr2xml(array $arr, int $index): string
    {
        $str = "\t<row index=\"".$index."\" type=\"array\">\n";

        foreach ($arr as $field => $value) {
            if ($value instanceof \DateTime) {
                $value = $value->format('c');
            }
            $value = (string)$value;
            $str .= "\t\t<".$field.">".htmlspecialchars(stripslashes($value))."</".$field.">\n";
        }

        $str .= "\t</row>\n";
        return $str;
    }
}
