<?php


namespace Frappant\FrpFormAnswers\TypoScript;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class CustomConditionFunctionsProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions():array
    {
        return [
            $this->getWebserviceFunction(),
        ];
    }

    protected function getWebserviceFunction(): ExpressionFunction
    {
        return new ExpressionFunction('BeUserHasAccessRights', function () {
            // Not implemented, we only use the evaluator
        }, function () {
            return (is_object($GLOBALS['BE_USER']) ? ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->check('modules', 'web_FrpFormAnswersFormanswers')) : false);
        });
    }

}
