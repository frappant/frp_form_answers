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
    'interface' => [
        'showRecordFieldList' => 'answers, field_hash, form, exported',
    ],
    'types' => [
        '1' => ['showitem' => 'answers, field_hash, form, exported'],
    ],
    'columns' => [
        'answers' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_db.xlf:tx_frpformanswers_domain_model_formentry.answers',
            'config' => [
                'type' => 'user',
                'userFunc' => 'Frappant\\FrpFormAnswers\\Utility\\UserFieldUtility->getStaticContent',
                /*'cols' => 40,
                'rows' => 15,
                'eval' => 'trim'*/
            ]
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
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
                    ]
                ],
                'readOnly' =>1,
                'default' => 0
            ]
        ],
    ],
];
