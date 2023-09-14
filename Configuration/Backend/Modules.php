<?php

return [
    'web_examples' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/page/formanswers',
        'labels' => 'LLL:EXT:frp_form_answers/Resources/Private/Language/locallang_formanswers.xlf',
        'extensionName' => 'frp-form-answers',
        'controllerActions' => [
            \Frappant\FrpFormAnswers\Controller\FormEntryController::class => [
                'show',
                'list',
                'prepareRemove',
                'remove',
                'prepareExport',
                'initializeExport',
                'export',
                'deleteFormname'
            ],
        ],
    ],
];
