<?php

namespace SourceBroker\Fieldgenerator\Command;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use SourceBroker\Fieldgenerator\Service\FieldGenerator;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @package SourceBroker\Fieldgenerator\Command
 */
class FieldGeneratorCommandController extends CommandController
{
    /**
     * Generate field contents for specific table
     *
     * @param string $table Table to rebuild.
     */
    public function generateForTableCommand($table)
    {
        /** @var \SourceBroker\Fieldgenerator\Service\FieldGenerator $fieldGeneratorService */
        $fieldGeneratorService = GeneralUtility::makeInstance(FieldGenerator::class, $table);
        $fieldGeneratorService->generateFieldsForTable();
    }

    /**
     * Generate field contents for all tables
     *
     */
    public function generateForAllTablesCommand()
    {
        foreach ($GLOBALS['TCA'] as $table => $tca) {
            /** @var \SourceBroker\Fieldgenerator\Service\FieldGenerator $fieldGeneratorService */
            $fieldGeneratorService = GeneralUtility::makeInstance(FieldGenerator::class, $table);
            $fieldGeneratorService->generateFieldsForTable();
        }
    }
}
