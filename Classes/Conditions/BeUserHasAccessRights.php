<?php

namespace Frappant\FrpFormAnswers\TypoScript;

class BeUserHasAccessRights extends \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractCondition
{

    /**
     * @param  beUser   $beUser
     * @param  string  $moduleName
     * @return boolean
     */
    public function matchCondition(beUser $beUser, string $moduleName)
    {
    }
}
