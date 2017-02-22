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

use Helhum\Typo3Console\Service\Database\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;


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
     * Typo3QuerySettings $defaultQuerySettings
     */
    private $defaultQuerySettings = null;

    /**
     * FieldGenerator constructor.
     * @param $table
     */
    public function __construct($table)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        if (isset($GLOBALS['TCA'][$table])) {
            $this->tca = $GLOBALS['TCA'][$table]['ctrl'];
        }
        $this->table = $table;
        $this->defaultQuerySettings = $objectManager->get(Typo3QuerySettings::class);
    }

    /**
     *
     */
    public function generateFieldsForTable()
    {
        if ($this->hasTableTcaTheGeneratorSettings($this->table)) {
            $repository = $this->getRepository();
            $this->defaultQuerySettings->setIgnoreEnableFields(true);
            $this->defaultQuerySettings->setIncludeDeleted(true);
            $repository->setDefaultQuerySettings($this->defaultQuerySettings);
            $records = $repository->findAll();

            foreach ($this->getLanguages() as $language) {
                foreach ($records as $record) {
                    $this->generateFields($record->getUid(), $language);
                }
            }
        }
    }

    /**
     * @param $recordId
     * @param $languageUid
     */
    public function generateFields($recordId, $languageUid)
    {
        if ($this->hasTableTcaTheGeneratorSettings($this->table)) {
            $repository = $this->getRepository();

            foreach ($this->tca['fieldsGenerator']['generate'] as $field) {
                $keywords = [];
                $record = $repository->findByUid($recordId);
                $localizedRecord = $this->findRecordByUid($record, $languageUid);

                if ($localizedRecord !== null) {
                    $nestedFieldDepth = 0;

                    foreach (explode(',', $field['fields']) as $fieldToAdd) {
                        $nestedFieldArray = explode('.', $fieldToAdd);
                        $this->traverseNestedObject($localizedRecord, $nestedFieldArray, $nestedFieldDepth, $keywords);
                    }

                    if (count($keywords)) {
                        /** @var PersistenceManager $ersistenceManager */
                        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
                        $stringKeywords = implode(' ', $keywords);

                        if (
                            isset($field['preg_replace']) && is_array($field['preg_replace'])
                            && isset($field['preg_replace']['pattern']) && isset($field['preg_replace']['replacement'])
                        ) {
                            $stringKeywords = preg_replace($field['preg_replace']['pattern'],
                                $field['preg_replace']['replacement'], $stringKeywords);
                        }
                        $localizedRecord->setKeywords($stringKeywords);
                        $repository->update($localizedRecord);
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

    /**
     * @param $record
     * @param $languageUid
     * @return mixed
     */
    public function findRecordByUid($record, $languageUid)
    {
        $persistenceSession = GeneralUtility::makeInstance(Session::class);
        $repository = $this->getRepository();

        if ($record != null && $repository) {
            $query = $repository->createQuery();
            $query->getQuerySettings()->setLanguageUid($languageUid);
            $query->getQuerySettings()->setLanguageMode('strict');
            $query->matching(
                $query->equals('uid', $record->getUid())
            );
            $persistenceSession->unregisterObject($record);

            return $query->execute()->getFirst();
        }
    }

    /**
     * @return array
     */
    private function getLanguages()
    {
        $repository = $this->getRepository();
        $languages = [];

        if ($repository) {
            $query = $repository->createQuery();
            $query->statement('SELECT DISTINCT(sys_language_uid) FROM ' . $this->table);
            $result = $query->execute(true);

            if ($result) {
                foreach ($result as $record) {
                    $languages[] = $record['sys_language_uid'];
                }
            }
        }

        return $languages;
    }

    /**
     * @return object
     * @throws Exception
     */
    private function getRepository()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $repository = $objectManager->get($this->tca['fieldsGenerator']['repositoryClass']);

        if ($repository) {
            $this->defaultQuerySettings->setRespectSysLanguage(true);
            $this->defaultQuerySettings->setRespectStoragePage(false);
            $repository->setDefaultQuerySettings($this->defaultQuerySettings);

            return $repository;
        } else {
            throw new Exception('Can\'t load repository ' . $this->tca['fieldsGenerator']['repositoryClass']);
        }
    }
}