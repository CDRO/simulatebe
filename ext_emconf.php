<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "simulatebe".
 *
 * Auto generated 15-03-2015 16:37
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'BE Login Simulation for FE Users',
	'description' => 'Simulates BE Login for FE Users. With this extension you can grant Frontend Users Backend User Rights. This means that, if a Frontend User logs into the Frontend and he has the proper rights, the Edit Icons will be displayed allowing him to edit the Content.',
	'category' => 'fe',
	'shy' => 0,
	'version' => '3.0.5',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'fe_users,be_users',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Sonja Scholz, Jonas Felix, Tizian Schmidlin',
	'author_email' => 'ss@cabag.ch, jf@cabag.ch, st@cabag.ch',
	'author_company' => 'cab services ag',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.6.0-8.7.99',
			'php' => '5.6.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);
