<?php

return [
    'web_FrpFormAnswersFormanswers' => [
        'parent' => 'web',
        'iconIdentifier' => 'modulIcon',
        'position' => ['after' => 'web_FormFormbuilder'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/page/formanswers',
        'labels' => 'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_formanswers.xlf',
        'extensionName' => 'frp_form_answers',
        'controllerActions' => [
            \Frappant\FrpFormAnswers\Controller\FormEntryController::class => [
                'list',
                'show',
                'prepareRemove',
                'remove',
                'prepareExport',
                'initializeExport',
                'export',
                'deleteFormname',
                'removeEntry'
            ],
        ],
    ],
];
