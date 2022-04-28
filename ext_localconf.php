<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][] = \Cabag\Simulatebe\Handler\LogoutHandler::class.'->logout';
