<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'BE Login Simulation for FE Users',
    'description' => 'Simulates BE Login for FE Users. With this extension you can grant Frontend Users Backend User Rights. This means that, if a Frontend User logs into the Frontend and he has the proper rights, the Edit Icons will be displayed allowing him to edit the Content.',
    'category' => 'fe',
    'version' => '11.5.0-dev',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'author' => 'Sonja Scholz, Jonas Felix, Tizian Schmidlin',
    'author_email' => 'ss@cabag.ch, jf@cabag.ch, st@cabag.ch',
    'author_company' => 'cab services ag',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.10-11.5.99',
            'php' => '7.4.0-0.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
);
