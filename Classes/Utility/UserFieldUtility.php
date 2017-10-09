<?php
namespace Frappant\FrpFormAnswers\Utility;

class UserFieldUtility
{
    public function getStaticContent($PA, $fObj)
    {
        $out = '<ul>';

        $fieldValues = json_decode($PA['itemFormElValue'], true);

        if (is_array($fieldValues)) {
            foreach ($fieldValues as $fieldKey => $fieldValue) {
                if ($fieldValue['conf']['label']) {
                    $out .= '<li>'.$fieldValue['conf']['label'].' - '.(is_array($fieldValue['value']) ? implode(",", $fieldValue['value']) : $fieldValue['value']).'</li>';
                } else {
                    $out .= '<li>'.$fieldKey.' - '.(is_array($fieldValue['value']) ? implode(",", $fieldValue['value']) : $fieldValue['value']).'</li>';
                }
            }
        }
        $out .= '</ul>';
        return $out;
    }
}
