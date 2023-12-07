<?php
use TYPO3\CMS\Core\Http\ApplicationType;

defined('TYPO3') or die();
call_user_func(
    function () {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_frpformanswers_domain_model_formentry', 'EXT:frp_form_answers/Resources/Private/Language/locallang_csh_tx_frpformanswers_domain_model_formentry.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_frpformanswers_domain_model_formentry');
    }
);
