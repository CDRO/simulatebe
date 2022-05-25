<?php
defined('TYPO3') or die();

$tempColumns = array(
    'tx_simulatebe_feuserusername' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:simulatebe/locallang_db.xml:be_users.tx_simulatebe_feuserusername',
        'config' => array(
            'type' => 'input',
            'size' => 30,
        )
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', '--div--;LLL:EXT:simulatebe/locallang_db.xml:be_users.tx_simulatebe,tx_simulatebe_feuserusername');
