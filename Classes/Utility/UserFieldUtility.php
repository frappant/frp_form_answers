<?php
namespace Frappant\FrpFormAnswers\Utility;

class UserFieldUtility
{
    public function getStaticContent($PA, $fObj)
    {
        $out = '<ul>';
        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($PA);
        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($fObj);

        $fieldValues = json_decode($PA['itemFormElValue']);
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($fieldValues);

        foreach ($fieldValues as $fieldKey => $fieldValue) {
            $out .= '<li>'.$fieldKey.' - '.$fieldValue.'</li>';
        }
        $out .= '</ul>';
        return $out;
    }
}
