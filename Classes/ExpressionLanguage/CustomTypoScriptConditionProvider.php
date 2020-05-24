<?php


namespace Frappant\FrpFormAnswers\ExpressionLanguage;
use TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider;


class CustomTypoScriptConditionProvider extends AbstractProvider
{
    public function __construct()
    {
        $this->expressionLanguageProviders = [
            \Frappant\FrpFormAnswers\TypoScript\CustomConditionFunctionsProvider::class,
        ];
    }

}
