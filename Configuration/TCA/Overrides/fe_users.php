<?php
defined('TYPO3') or die();

$tempColumns = array(
    'tx_simulatebe_beuser' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:simulatebe/locallang_db.xml:fe_users.tx_simulatebe_beuser',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array(
                array('', 0),
            ),
            'foreign_table' => 'be_users',
            'foreign_table_where' => 'ORDER BY be_users.username',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
        )
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users', '--div--;LLL:EXT:simulatebe/locallang_db.xml:fe_users.tx_simulatebe,tx_simulatebe_beuser');
