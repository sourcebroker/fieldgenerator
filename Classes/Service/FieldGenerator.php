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


namespace SourceBroker\Fieldgenerator\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A class that generates content of fields described in TCA section 'fieldsGenerator'.
 *
 * @package    TYPO3
 */
class FieldGenerator
{

    /**
     * @var array
     */
    private $tca = [];
    /**
     * @var
     */
    private $table;

    /**
     * FieldGenerator constructor.
     * @param $table
     */
    public function __construct($table)
    {
        if (isset($GLOBALS['TCA'][$table])) {
            $this->tca = $GLOBALS['TCA'][$table]['ctrl'];
        }
        $this->table = $table;
    }

    /**
     *
     */
    public function generateFieldsForTable()
    {
        if ($this->hasTableTcaTheGeneratorSettings($this->table)) {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            /** @var Repository $repository */
            $repository = $objectManager->get($this->tca['fieldsGenerator']['repositoryClass']);

            /** @var Typo3QuerySettings $defaultQuerySettings */
            $defaultQuerySettings = $objectManager->get(Typo3QuerySettings::class);
            $defaultQuerySettings->setRespectStoragePage(FALSE);
            $defaultQuerySettings->setIgnoreEnableFields(TRUE);
            $defaultQuerySettings->setIncludeDeleted(TRUE);
            $defaultQuerySettings->setRespectSysLanguage(FALSE);
            $repository->setDefaultQuerySettings($defaultQuerySettings);
            $records = $repository->findAll();
            foreach ($records as $record) {
                $this->generateFields($record->getUid());
            }
        }
    }

    /**
     * @param $recordId
     */
    public function generateFields($recordId)
    {
        if ($this->hasTableTcaTheGeneratorSettings($this->table)) {

            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            /** @var Repository $repository */
            $repository = $objectManager->get($this->tca['fieldsGenerator']['repositoryClass']);

            foreach ($this->tca['fieldsGenerator']['generate'] as $field) {
                $keywords = [];
                $record = $repository->findByUid($recordId);
                if ($record !== null) {
                    $nestedFieldDepth = 0;
                    foreach (explode(',', $field['fields']) as $fieldToAdd) {
                        $nestedFieldArray = explode('.', $fieldToAdd);
                        $this->traverseNestedObject($record, $nestedFieldArray, $nestedFieldDepth, $keywords);
                    }
                    if (count($keywords)) {
                        /** @var PersistenceManager $ersistenceManager */
                        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
                        $stringKeywords = implode(' ', $keywords);
                        if (
                            isset($field['preg_replace']) && is_array($field['preg_replace'])
                            && isset($field['preg_replace']['pattern']) && isset($field['preg_replace']['replacement'])
                        ) {
                            $stringKeywords = preg_replace($field['preg_replace']['pattern'], $field['preg_replace']['replacement'], $stringKeywords);
                        }
                        $record->setKeywords($stringKeywords);
                        $repository->update($record);
                        $persistenceManager->persistAll();
                    }
                }
            }
        }
    }

    /**
     * @param $currentObject
     * @param $nestedFieldArray
     * @param $nestedFieldDepth
     * @param $keywords
     */
    protected function traverseNestedObject($currentObject, $nestedFieldArray, $nestedFieldDepth, &$keywords)
    {
        if ($nestedFieldDepth < 100) {
            $nestedField = $nestedFieldArray[$nestedFieldDepth];
            $objectStorageClass = ObjectStorage::class;
            $fieldGetter = 'get' . ucfirst($nestedField);
            $propertyValue = $currentObject->{$fieldGetter}();
            if (!$propertyValue instanceof $objectStorageClass) {
                $keywords[] = $propertyValue;
            } else {
                $nestedFieldDepth++;
                foreach ($propertyValue as $object) {
                    if (isset($nestedFieldArray[$nestedFieldDepth])) {
                        $this->traverseNestedObject($object, $nestedFieldArray, $nestedFieldDepth, $keywords);
                    }
                }
            }
        }
    }

    /**
     * @param $table
     * @return bool
     */
    protected function hasTableTcaTheGeneratorSettings($table)
    {
        $result = false;
        if (isset($GLOBALS['TCA'][$table])) {
            if (isset($this->tca['fieldsGenerator'])) {
                if (isset($this->tca['fieldsGenerator']['generate'])) {
                    $result = true;
                }
            }
        }
        return $result;
    }

}

