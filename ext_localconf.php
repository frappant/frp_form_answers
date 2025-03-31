<?php
defined('TYPO3') or die();


$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1590321374] = [
    'nodeName' => 'formAnswersJsonElement',
    'priority' => 40,
    'class' => \Frappant\FrpFormAnswers\Form\FormAnswersJsonElement::class
];

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][1743423601] = 'EXT:frp_form_answers/Resources/Private/CommandTask/Templates/FormEntries/';