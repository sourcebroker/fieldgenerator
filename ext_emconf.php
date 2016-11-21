<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "fieldgenerator".
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Allows to create content of one field based on other fields',
	'description' => 'Controlled fully by TCA settings. Support for nested property read. Useful to create record keywords for nested structures.',
	'category' => 'be',
	'version' => '1.0.0',
	'state' => 'stable',
	'uploadfolder' => false,
	'createDirs' => '',
	'clearcacheonload' => true,
    'author' => 'SourceBroker Team',
    'author_email' => 'office@sourcebroker.net',
    'author_company' => 'SourceBroker',
	'constraints' => 
	array (
		'depends' => 
		array (
			'typo3' => '6.2.0-7.6.99',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
		),
	),
);

?>