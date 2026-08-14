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
class ExportCsv
{
    /**
     * Delimiter Array
     * @var array<string, string>
     */
    protected $delimiter = [
        'komma' => ',',
        'semikolon' => ';',
        'tab' => '\t',
    ];

    /**
     * Enclosure Array
     * @var array<string, string>
     */
    protected $enclosure = [
        'single' => '\'',
        'double' => '"',
    ];

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
     * @return ExportCsv an instance of $this, to enable chaining
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
     * @return ExportCsv an instance of $this, to enable chaining
     */
    public function assignMultiple(array $values)
    {
        foreach ($values as $key => $value) {
            $this->assign($key, $value);
        }
        return $this;
    }

    public function initializeView(): void {}

    /**
     * Renders the view
     *
     * @return string The rendered view
     * @api
     */
    public function render(): string
    {
        ob_start();
        foreach ($this->variables['rows'] as $fields) {
            echo $this->fputcsv2(
                $fields,
                $this->delimiter[$this->variables['formEntryDemand']->getDelimiter()],
                $this->enclosure[$this->variables['formEntryDemand']->getEnclosure()]
            );
        }
        return ob_get_clean();
    }

    /**
     * Renders a partial.
     *
     * @param string $partialName
     * @param string $sectionName
     * @param array<string, mixed> $variables
     * @param bool $ignoreUnknown Ignore an unknown section and just return an empty string
     * @return string
     */
    public function renderPartial(string $partialName, string $sectionName, array $variables, bool $ignoreUnknown = false): string
    {
        return $this->render();
    }

    /**
     * Renders a given section.
     *
     * @param string $sectionName Name of section to render
     * @param array<string, mixed> $variables The variables to use
     * @param bool $ignoreUnknown Ignore an unknown section and just return an empty string
     * @return string rendered template for the section
     */
    public function renderSection(string $sectionName, array $variables = [], bool $ignoreUnknown = false): string
    {
        return $this->render();
    }

    /**
     * function fputscv2
     * Funktion gem. php.net
     * Behebt mögliche Fehlerfälle der ursprünglichen Funktion fputcsv
     * @param array<int, mixed> $fields
     * @param string $delimiter
     * @param string $enclosure
     * @param bool $mysql_null
     * @return string
     */
    private function fputcsv2(array $fields, string $delimiter = ';', string $enclosure = '"', bool $mysql_null = false): string
    {
        $delimiter_esc = preg_quote($delimiter, '/');
        $enclosure_esc = preg_quote($enclosure, '/');

        $output = [];
        foreach ($fields as $field) {
            if ($field === null && $mysql_null) {
                $output[] = 'NULL';
                continue;
            }
            if ($field instanceof \DateTime) {
                $field = $field->format('r');
            }

            $field = (string)$field;
            $output[] = preg_match("/(?:{$delimiter_esc}|{$enclosure_esc}|\s)/", $field) ? (
                $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure
            ) : $field;
        }
        return implode($delimiter, $output) . "\n";
    }
}
