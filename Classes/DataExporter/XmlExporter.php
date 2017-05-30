<?php

namespace Frappant\FrpFormAnswers\DataExporter;

class XmlExporter
{


    /**
     * function export
     * @var resource $fp
     * @var array $rows
     * @var array $config
     */
    public function export($rows, $config)
    {

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
