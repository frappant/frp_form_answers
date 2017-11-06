<?php

namespace Frappant\FrpFormAnswers\Condition;

use TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractCondition;

class BeUserHasAccessRightsCondition extends AbstractCondition
{

    /**
     * @param   $conditionParameters
     * @return  boolean
     */
    public function matchCondition(array $conditionParameters)
    {
        return true;
        return $conditionParameters[0]->check('modules', 'web_formAnswers');
    }
}
