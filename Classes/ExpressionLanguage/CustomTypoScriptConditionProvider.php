<?php


namespace Frappant\FrpFormAnswers\ExpressionLanguage;
use Frappant\FrpFormAnswers\TypoScript\CustomConditionFunctionsProvider;
use TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider;


class CustomTypoScriptConditionProvider extends AbstractProvider
{
    public function __construct()
    {
        $this->expressionLanguageProviders = [
            CustomConditionFunctionsProvider::class,
        ];
    }

}
