<?php

return [
    'frontend' => [
        'simulatebe/backend-user-simulator' => [
            'target' => \Cabag\Simulatebe\Middleware\BackendUserSimulator::class,
            'after' => [
                'typo3/cms-frontend/authentication',
                'typo3/cms-frontend/backend-user-authentication',
                'typo3/cms-adminpanel/initiator',
            ],
        ],
    ],
];
