<?php

namespace Frappant\FrpFormAnswers\DataExporter;

class CsvExporter
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

    /**
     * function export
     * @var resource $fp
     * @var array $rows
     * @var array $config
     */
    public function export($rows, $config)
    {
        foreach ($rows as $fields) {
            $this->fputcsv2($fields, $this->delimiter[$config['delimiter']], $this->enclosure[$config['enclosure']]);
        }

        echo "\xEF\xBB\xBF"; // UTF-8 BOM
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
