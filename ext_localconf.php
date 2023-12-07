<?php
defined('TYPO3') or die();
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
        mod.web_list.hideTables = tx_frpformanswers_domain_model_formentry
');

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1590321374] = [
    'nodeName' => 'formAnswersJsonElement',
    'priority' => 40,
    'class' => \Frappant\FrpFormAnswers\Form\FormAnswersJsonElement::class
];
