<?php
return [
    'ctrl' => [
        'title'    => 'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_db.xlf:tx_frpformanswers_domain_model_formentry',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'enablecolumns' => [
        ],
        'searchFields' => 'answers,field_hash,form,exported',
        'iconfile' => 'EXT:frp_form_answers/Resources/Public/Icons/tx_frpformanswers_domain_model_formentry.svg'
    ],
    'types' => [
        '1' => ['showitem' => 'submit_uid, answers, field_hash, form, exported'],
    ],
    'columns' => [
        'answers' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_db.xlf:tx_frpformanswers_domain_model_formentry.answers',
            'config' => [
                'type' => 'user',
                'renderType' => 'formAnswersJsonElement',
            ]
        ],
        'submit_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_db.xlf:tx_frpformanswers_domain_model_formentry.submit_uid',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'readOnly' =>1,
                'eval' => 'trim, int'
            ],
        ],
        'field_hash' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_db.xlf:tx_frpformanswers_domain_model_formentry.field_hash',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'readOnly' =>1,
                'eval' => 'trim'
            ],
        ],
        'form' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_db.xlf:tx_frpformanswers_domain_model_formentry.form',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'readOnly' =>1,
                'eval' => 'trim'
            ],
        ],
        'exported' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_db.xlf:tx_frpformanswers_domain_model_formentry.exported',
            'config' => [
                'type' => 'check',
                'items' => [
                    0 => [
                        '0' => 'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_db.xlf:tx_frpformanswers_domain_model_formentry.exported'
                    ]
                ],
                'readOnly' =>1,
                'default' => 0
            ]
        ],
        'crdate' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_db.xlf:tx_frpformanswers_domain_model_formentry.crdate',
            'config' => [
                'type' => 'input',
                'eval' => 'datetime',
                'renderType' => 'InputDateTime',
                'size' => 20,
                'readOnly' => true,
            ],
        ],
    ],
];
