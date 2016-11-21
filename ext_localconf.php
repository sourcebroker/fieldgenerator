<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['recordMonitor'] = \SourceBroker\Fieldgenerator\Hooks\RecordMonitor::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \SourceBroker\Fieldgenerator\Command\FieldGeneratorCommandController::class;
