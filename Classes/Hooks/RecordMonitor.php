<?php

/***************************************************************
 *  Copyright notice
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


namespace SourceBroker\Fieldgenerator\Hooks;

use SourceBroker\Fieldgenerator\Service\FieldGenerator;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A class that monitors changes to records.
 * When record is create or updated the fields described in TCA section 'fieldsGenerator' are generated.
 *
 * @package    TYPO3
 */
class RecordMonitor
{
    /**
     * Generate keywords for record from fields declared in TCA
     *
     * @param string $status TCEmain operation status, either 'new' or 'update'
     * @param string $table The DB table the operation was carried out on
     * @param mixed $recordId The record's uid for update records, a string to look the record's uid up after it has been created
     * @param array $updatedFields Array of changed fiels and their new values
     * @param DataHandler $tceMain TCEmain parent object
     * @return void
     */
    public function processDatamap_afterDatabaseOperations($status, $table, $recordId, array $updatedFields, DataHandler $tceMain)
    {
        if ($status == 'new') {
            $recordId = $tceMain->substNEWwithIDs[$recordId];
        }
        /** @var FieldGenerator $fieldGenerator */
        $fieldGenerator = GeneralUtility::makeInstance(FieldGenerator::class, $table);
        $fieldGenerator->generateFields($recordId);
    }
}

