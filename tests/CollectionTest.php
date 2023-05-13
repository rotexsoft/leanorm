<?php

/**
 * Description of CollectionTest
 *
 * @author rotimi
 */
class CollectionTest extends \PHPUnit\Framework\TestCase {
    
    use CommonPropertiesAndMethodsTrait;
    
    protected const POST_POST_IDS                   = [ '1', '2', '3', '4', ];
    protected const POST_POST_IDS_KEYED_ON_POST_ID  = [ 1=>'1', 2=>'2', 3=>'3', 4=>'4', ];
    
    public function testThatConstructorWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        
        $collection = new \LeanOrm\Model\Collection($model);
        self::assertSame($model, $collection->getModel()); // model was propely set
        self::assertCount(0, $collection); // no records in this collection
        
        $records = [$model->createNewRecord(), $model->createNewRecord(),];
        $collection2 = new \LeanOrm\Model\Collection($model, ...$records);
        self::assertSame($model, $collection2->getModel()); // model was propely set
        self::assertCount(2, $collection2); // 2 records in this collection
        
        foreach ($records as $record) {
            
            // records injected are still in the collection
            self::assertContains($record, $collection2);
        }
    } // public function testThatConstructorWorksAsExpected()
    
    public function testThatDeleteAllWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        
        ////////////////////
        // Empty Collection
        $collection = new \LeanOrm\Model\Collection($model);
        self::assertCount(0, $collection); // no records in this collection
        self::assertTrue($collection->deleteAll());
        
        //////////////////////////////////
        // Collection with 2 empty records
        $records = [$model->createNewRecord(), $model->createNewRecord(),];
        $collection2 = new \LeanOrm\Model\Collection($model, ...$records);
        self::assertCount(2, $collection2); // 2 records in this collection
        
        // The records are not in the database to start with which means 
        // deleteAll should still return true as the records are not in the db
        self::assertTrue($collection2->deleteAll());
        self::assertCount(2, $collection2); // 2 records still in this collection
        
        foreach ($records as $record) {
            
            // records injected are still in the collection
            self::assertContains($record, $collection2);
        }
        
        ////////////////////////////////////////////////////////////////////////
        // Test that deleting records using a model that always returns false on
        // delete will lead to deleteAll returning an array of the primary key
        // vals of the records in the collection
        $modelThatAlwaysReturnsFalseOnDeletes = 
            new class(
                    static::$dsn, 
                    static::$username ?? "", 
                    static::$password ?? "", 
                    [], 'author_id', 'authors'
                ) extends \LeanOrm\Model {
            
                public function deleteSpecifiedRecord(\GDAO\Model\RecordInterface $record): ?bool {

                    return false;
                }
            };
        $records = [
            $modelThatAlwaysReturnsFalseOnDeletes->createNewRecord(['author_id'=>1]), 
            $modelThatAlwaysReturnsFalseOnDeletes->createNewRecord(['author_id'=>2]),
        ];
        $collection3 = new \LeanOrm\Model\Collection($model, ...$records);
        
        self::assertCount(2, $collection3); // 2 records in this collection
        $deleteAllResults = $collection3->deleteAll();
        self::assertCount(2, $deleteAllResults); // 2 items in the result
        self::assertEquals([0,1], $deleteAllResults); // Collection keys of undeleted records returned
        
        foreach ($records as $record) {
            
            // records injected are still in the collection
            self::assertContains($record, $collection3);
        } 
        
        /////////////////////////////////////////////////////////////////
        // Test that deleting records with data that were never in the db
        // also returns true
        $records = [
            $model->createNewRecord([
                'author_id'     => 777,
                'name'          => 'Author 1',
                'm_timestamp'   => date('Y-m-d H:i:s'),
                'date_created'  => date('Y-m-d H:i:s'),
            ]), 
            $model->createNewRecord([
                'author_id'     => 888,
                'name'          => 'Author 2',
                'm_timestamp'   => date('Y-m-d H:i:s'),
                'date_created'  => date('Y-m-d H:i:s'),
            ]),
        ];
        $collection4 = new \LeanOrm\Model\Collection($model, ...$records);
        self::assertCount(2, $collection4); // 2 records in this collection
        self::assertTrue($collection4->deleteAll());
        
        foreach ($records as $record) {
            
            // records injected are still in the collection
            self::assertContains($record, $collection4);
        } 
        
        ///////////////////////////////////////////////////////////////
        // Test that deleting records with data that were in the db
        // also returns true & try re-fetching the records from the db
        // to make sure they are no longer there.
        $collection5 = $model->fetchRecordsIntoCollection();
        $collection5Copy = $model->fetchRecordsIntoCollection();
        
        // There are 10 records in the authors table based on seeded data by
        // TestSchemaCreatorAndSeeder->populateTables()
        // See CommonPropertiesAndMethodsTrait->setUp()
        // Which always gets called before each test method 
        // is invoked by phpunit.
         self::assertCount(10, $collection5); // 10 records in this collection
         
        // 10 records in the db were deleted but still remain in the collection
        self::assertTrue($collection5->deleteAll());
        self::assertCount(10, $collection5); // 10 records still in this collection
        $refetchedRecordsFromTheDb = $model->fetchRecordsIntoCollection();
        
        // 10 records still in $collection5 but all deleted from the DB 
        self::assertCount(0, $refetchedRecordsFromTheDb); 
        
        // verify that the records remaining in $collection5 were the
        // initial records fetched from the db
        foreach($collection5Copy as $key => $record) {
            
            // loop through the fields in each record in the copy collection
            // & make sure that each field in the current record has the same 
            // value as the same field in the corresponding record in the 
            // collection in which records were deleted from the DB.
            // Skip the primary key field because it might have been set to
            // null if it's an auto-incrementing primary key field in the db
            foreach ($record as $col_name => $col_val) {
                
                if($record->getPrimaryCol() !== $col_name) {
                    
                    self::assertEquals($col_val, $collection5[$key]->$col_name);
                    
                } elseif(
                    $record->getPrimaryCol() === $col_name
                    && array_key_exists($record->getPrimaryCol(), $record->getModel()->getTableCols())
                    && $record->getModel()->getTableCols()[$record->getPrimaryCol()]['autoinc']
                ) {
                    // this is an auto-incrementing PK col, it should 
                    // now have a null value after the deleteAll
                    self::assertNull($collection5[$key]->$col_name);
                }
            } // foreach ($record as $col_name => $col_val)
        } // foreach($collection5Copy as $key => $record)
    } // public function testThatDeleteAllWorksAsExpected()
    
    public function testThatDeleteAllOnCollectionWithAtLeastOneReadOnlyRecordThrowsException() {
        
        $this->expectException(\LeanOrm\CantDeleteReadOnlyRecordFromDBException::class);
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], '', 'authors'
        );
        
        $records = [
            $model->createNewRecord(), 
            (new LeanOrm\Model\ReadOnlyRecord([], $model)),
        ];
        $collection = new \LeanOrm\Model\Collection($model, ...$records);
        self::assertCount(2, $collection); // 2 records in this collection
        
        // Throws \LeanOrm\CantDeleteReadOnlyRecordFromDBException
        // because a ReadOnlyRecord exists in the collection
        $collection->deleteAll();
    } // public function testThatDeleteAllOnCollectionWithAtLeastOneReadOnlyRecordThrowsException()
    
    public function testThatGetColValsWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'post_id', 'posts'
        );
        $emptyCollection = new \LeanOrm\Model\Collection($model);
        
        self::assertEquals([], $emptyCollection->getColVals('post_id'));
        self::assertEquals(
            self::POST_POST_IDS, 
            $model->fetchRecordsIntoCollection()->getColVals('post_id')
        );
        self::assertEquals(
            self::POST_POST_IDS_KEYED_ON_POST_ID, 
            $model->fetchRecordsIntoCollectionKeyedOnPkVal()->getColVals('post_id')
        );
    } // public function testThatGetColValsWorksAsExpected()
    
    public function testThatGetKeysWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'post_id', 'posts'
        );
        $emptyCollection = new \LeanOrm\Model\Collection($model);
        
        self::assertEquals([], $emptyCollection->getKeys());
        self::assertEquals(
            array_keys(self::POST_POST_IDS), 
            $model->fetchRecordsIntoCollection()->getKeys()
        );
        self::assertEquals(
            array_keys(self::POST_POST_IDS_KEYED_ON_POST_ID), 
            $model->fetchRecordsIntoCollectionKeyedOnPkVal()->getKeys()
        );
    } // public function testThatGetKeysWorksAsExpected()
    
    public function testThatGetModelWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'post_id', 'posts'
        );
        $emptyCollection = new \LeanOrm\Model\Collection($model);
        
        self::assertSame($model, $emptyCollection->getModel());
        self::assertSame(
            $model, $model->fetchRecordsIntoCollection()->getModel()
        );
    } // public function testThatGetModelWorksAsExpected()
    
    public function testThatIsEmptyWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'post_id', 'posts'
        );
        $emptyCollection = new \LeanOrm\Model\Collection($model);
        $records = [$model->createNewRecord(), $model->createNewRecord(),];
        $collection = new \LeanOrm\Model\Collection($model, ...$records);
        
        self::assertTrue($emptyCollection->isEmpty());
        self::assertFalse(
            $model->fetchRecordsIntoCollection()->isEmpty()
        );
        self::assertFalse(
            $collection->isEmpty()
        );
    } // public function testThatIsEmptyWorksAsExpected()
    
    public function testThatLoadDataWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'post_id', 'posts'
        );
        $emptyCollection = new \LeanOrm\Model\Collection($model);
        $records = [$model->createNewRecord(), $model->createNewRecord(),];
        $collection = new \LeanOrm\Model\Collection($model);
        
        self::assertTrue($emptyCollection->loadData()->isEmpty());
        self::assertSame($emptyCollection, $emptyCollection->loadData());
        
        self::assertFalse(
            $collection->loadData(...$records)->isEmpty()
        );
        self::assertSame($collection, $collection->loadData(...$records));
    } // public function testThatLoadDataWorksAsExpected()
    
    public function testThatRemoveAllWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'post_id', 'posts'
        );
        $emptyCollection = new \LeanOrm\Model\Collection($model);
        $records = [$model->createNewRecord(), $model->createNewRecord(),];
        $collection = new \LeanOrm\Model\Collection($model, ...$records);
        
        self::assertTrue($emptyCollection->isEmpty());
        self::assertSame($emptyCollection, $emptyCollection->removeAll());
        self::assertTrue($emptyCollection->isEmpty());
        
        self::assertFalse($collection->isEmpty());
        self::assertSame($collection, $collection->removeAll());
        self::assertTrue($collection->isEmpty());
    } // public function testThatRemoveAllWorksAsExpected()
    
    public function testThatSaveAllWorksAsExpected() {
        
        $this->runCommonSaveAllTests(false);
        $this->setUp(); // restore db to baseline
        $this->runCommonSaveAllTests(true);
        $this->setUp(); // restore db to baseline
        
        // We want to test that failed saves causes Collection->saveAll to return 
        // an array of keys in the collection of the unsaved records. To do this, 
        // we are going to create records from a Model whose insert* methods always
        // returns false & its record class always returns false on save. We will
        // also add some savable records from a good Model class to this same 
        // collection.
        $alwaysFalseOnSaveModel = 
            new class(
                    static::$dsn, 
                    static::$username ?? "", 
                    static::$password ?? "", 
                    [], 'author_id', 'authors'
                ) extends \LeanOrm\Model {
            
                public function insert(array $data_2_insert = []) {
                    return false;
                }
                public function insertMany(array $rows_of_data_2_insert = []): bool {
                    return false;
                }
            };
        $alwaysFalseOnSaveModel->setRecordClassName(\AlwaysFalseOnSaveRecord::class);
        
        ///////////////////////////////////////
        $modelThatCanSave = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        
        ////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////
        // In each of the 4 calls below to $this->performOtherSaveAllTests(...),   
        // a new collection with 6 records is created
        //  - 3 records (2 new & 1 existing) created & fetched 
        //    by a model that can save to the DB. 
        //    Their keys in the collection are always 2, 4 & 6 respectively
        //  - Another 3 records (2 new & 1 existing) created & fetched 
        //    by a model that can't save to the DB.
        //    Their keys in the collection are always 1, 3 & 5 respectively
        ////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////
        
        ////////////////////////////////////////////////////////////////////////
        // The call below to $this->performOtherSaveAllTests(...) 
        // will lead to $modelThatCanSave to be used to create the collection
        // that saveAll(false) will be called on, which should lead to all the 
        // records created or fetched by a model that can save to the DB with 
        // keys 2, 4 & 6 in the collection saveAll is being called on getting 
        // saved to the DB.
        // 
        // Also all the records created or fetched by a model that CANNOT save 
        // to the DB with keys 1, 3 & 5 in the collection saveAll is being called 
        // on will not get saved to the DB, meaning that saveAll(false) will return
        // [1, 3, 5].
        $this->performOtherSaveAllTests(
            $alwaysFalseOnSaveModel, 
            $modelThatCanSave, 
            true,  // use $modelThatCanSave->saveAll(...)
            false, // NO bulk insert
            [1, 3, 5]
        );
        
        $this->setUp(); // restore db to baseline
        
        ////////////////////////////////////////////////////////////////////////
        // The call below to $this->performOtherSaveAllTests(...) 
        // will lead to $modelThatCanSave to be used to create the collection
        // that saveAll(true) will be called on, which should lead to all the 
        // records created or fetched by a model that can save to the DB with 
        // keys 2, 4 & 6 in the collection saveAll is being called on and also
        // the two new records created by a model that CANNOT save to the DB
        // with keys 1 & 3 getting saved to the DB. The two new records created 
        // by a model that CANNOT save to the DB will get saved because we are
        // doing a bulk insert in this call & the model $modelThatCanSave that 
        // was used to create the collection we are calling saveAll(true) on is
        // also the model that will be used under the hood to perform the
        // bulk insert.
        // 
        // The one existing record fetched by a model that CANNOT save to the DB
        // with key 5 in the collection saveAll(true) is being called on will not 
        // get saved to the DB because that record would be atempted to be saved 
        // to the DB using the $alwaysFalseOnSaveModel under the hood, meaning that 
        // saveAll(true) will return [5], the only key of the record that was not saved.
        $this->performOtherSaveAllTests(
            $alwaysFalseOnSaveModel, 
            $modelThatCanSave, 
            true, // use $modelThatCanSave->saveAll(...)
            true, // bulk insert 
            [5]
        );
        
        $this->setUp(); // restore db to baseline
        
        ////////////////////////////////////////////////////////////////////////
        // The call below to $this->performOtherSaveAllTests(...) 
        // will lead to $alwaysFalseOnSaveModel to be used to create the collection
        // that saveAll(false) will be called on, which should lead to all the 
        // records created or fetched by a model that can save to the DB with 
        // keys 2, 4 & 6 in the collection saveAll is being called on getting 
        // saved to the DB.
        // 
        // These records will get saved even though we are in a collection created 
        // by $alwaysFalseOnSaveModel because these records were created by 
        // $modelThatCanSave & during the saveAll(false) call on the collection 
        // created by $alwaysFalseOnSaveModel, these records will actually get 
        // saved using the $modelThatCanSave model object they were created with.
        // 
        // 
        // Also all the records created or fetched by a model that CANNOT save 
        // to the DB with keys 1, 3 & 5 in the collection saveAll is being called 
        // on will not get saved to the DB, meaning that saveAll(false) will return
        // [1, 3, 5].
        $this->performOtherSaveAllTests(
            $alwaysFalseOnSaveModel, 
            $modelThatCanSave, 
            false, // use $alwaysFalseOnSaveModel->saveAll(...)
            false, // NO bulk insert 
            [1, 3, 5]
        );
        
        $this->setUp(); // restore db to baseline
        
        // The call below to $this->performOtherSaveAllTests(...) 
        // will lead to $alwaysFalseOnSaveModel to be used to create the collection
        // that saveAll(true) will be called on, which should lead to all the 
        // records created or fetched by a model that can save to the DB with 
        // keys 2, 4 & 6 in the collection saveAll is being called on and also
        // the two new records created by a model that CANNOT save to the DB
        // with keys 1 & 3 NOT getting saved to the DB. This is because, the
        // collection saveAll(true) is being called on uses $alwaysFalseOnSaveModel
        // under the hood to try to perform the bulk insert of the 4 new records
        // and that bulk insert will fail because the model being used CANNOT save
        // to the DB. Also, the 1 existing record fetched by the model that CANNOT
        // save that is also in the collection will not get saved to the DB. 
        // This leads to saveAll(true) returning [1, 2, 3, 4, 5] (the keys in the
        // collection for the unsaved records) in this scenario.
        // 
        // Only the one existing record fetched by a model that can save to the DB
        // with key 6 in the collection saveAll(true) is being called on will get 
        // saved to the DB because that record would be atempted to be saved 
        // to the DB using the $modelThatCanSave under the hood, and that save
        // operation will succeed.
        $this->performOtherSaveAllTests(
            $alwaysFalseOnSaveModel, 
            $modelThatCanSave, 
            false, // use $alwaysFalseOnSaveModel->saveAll(...)
            true,  // bulk insert 
            [1, 2, 3, 4, 5]
        );        
    }

    protected function performOtherSaveAllTests(
        \LeanOrm\Model $alwaysFalseOnSaveModel,
        \LeanOrm\Model $modelThatCanSave,
        bool $useSavableModelToCreateCollection,
        bool $groupInserts,
        array $keysInCollectionOfUnsavedRecords
    ): void {
        [
            $unsavableNewRecord1, $savableNewRecord1,
            $unsavableNewRecord2, $savableNewRecord2,
            $unsavableExistingRecord, $savableExistingRecord
        ] = $this->createSavableAndUnsavableRecords($alwaysFalseOnSaveModel, $modelThatCanSave);

        $collectionToPerfomSaveAll = 
            ($useSavableModelToCreateCollection)
                ? $modelThatCanSave->createNewCollection()
                : $alwaysFalseOnSaveModel->createNewCollection();
        
        $collectionRecordsDataB4SaveAllAsArrays = [];
        
        $collectionToPerfomSaveAll[1] = $unsavableNewRecord1;
        $collectionRecordsDataB4SaveAllAsArrays[1] = $unsavableNewRecord1->getData();
        
        $collectionToPerfomSaveAll[2] = $savableNewRecord1;
        $collectionRecordsDataB4SaveAllAsArrays[2] = $savableNewRecord1->getData();
        
        $collectionToPerfomSaveAll[3] = $unsavableNewRecord2;
        $collectionRecordsDataB4SaveAllAsArrays[3] = $unsavableNewRecord2->getData();
        
        $collectionToPerfomSaveAll[4] = $savableNewRecord2;
        $collectionRecordsDataB4SaveAllAsArrays[4] = $savableNewRecord2->getData();
        
        $collectionToPerfomSaveAll[5] = $unsavableExistingRecord;
        $collectionRecordsDataB4SaveAllAsArrays[5] = $unsavableExistingRecord->getData();
        
        $collectionToPerfomSaveAll[6] = $savableExistingRecord;
        $collectionRecordsDataB4SaveAllAsArrays[6] = $savableExistingRecord->getData();

        // make sure the number of items in the new collection is as expected
        self::assertCount(6, $collectionToPerfomSaveAll);

        $saveAllResult = $collectionToPerfomSaveAll->saveAll($groupInserts);

        // Because there are unsavable records in the collection,
        // saveAll must return an array of the collection keys of
        // the records that were not saved to the DB
        self::assertIsArray($saveAllResult);

        // Assert that there are as many expected items in the array returned by saveAll
        self::assertEquals(
            count($keysInCollectionOfUnsavedRecords), 
            count($saveAllResult)
        );
        
        // Assert that the expected keys were returned by saveAll
        foreach($keysInCollectionOfUnsavedRecords as $key) {
            
            self::assertContains($key, $saveAllResult);
        } // foreach($keysInCollectionOfUnsavedRecords as $key)

        foreach($collectionToPerfomSaveAll as $key => $record) {
            
            if(!in_array($key, $keysInCollectionOfUnsavedRecords)) {
                
                ////////////////////////////////////////////////////
                // This is a record that we believe has been saved
                ////////////////////////////////////////////////////
                
                // Try to fetch the record by name, if it is returned from the
                // fetch, that means the record was successfully saved.
                $refetchedPotentiallySavedRecord = $record->getModel()->fetchOneRecord(
                    $record->getModel()
                           ->getSelect()
                           ->where(' name = ? ', $collectionRecordsDataB4SaveAllAsArrays[$key]['name'])
                );
                
                // if the result is a record, then the saveAll successfully saved this record
                self::assertInstanceOf(
                    GDAO\Model\RecordInterface::class, 
                    $refetchedPotentiallySavedRecord
                );
            } else {
                
                ///////////////////////////////////////////////////////////////////////
                // This is a record that is not expected to have been saved to the DB
                ///////////////////////////////////////////////////////////////////////
                
                if(
                    array_key_exists('author_id', $collectionRecordsDataB4SaveAllAsArrays[$key])
                ) {
                    // if author_id is present in the data for the record before
                    // saveAll was called, then it's an existing record, not a new one
                    
                    // we use the same query that was used to fetch each
                    // existing record in $this->createSavableAndUnsavableRecords(...)
                    $existingRecord = $record->getModel()->fetchOneRecord(
                        $record->getModel()->getSelect()->orderBy(['author_id asc'])
                    );
                    self::assertInstanceOf(GDAO\Model\RecordInterface::class, $existingRecord);
                    
                    // Because this is an existing record that is not expected to
                    // have been saved, modification to the value of the name
                    // field in the record should NOT have been saved to the DB
                    self::assertNotEquals($existingRecord->name, $collectionRecordsDataB4SaveAllAsArrays[$key]['name']);
                    
                } else {
                    
                    // if author_id is NOT present in the data for the record before
                    // saveAll was called, then it's a new one. The record should not
                    // be fetchable by name.
                    self::assertNull(
                        $record->getModel()->fetchOneRecord(
                            $record->getModel()
                                   ->getSelect()
                                   ->where(' name = ? ', $collectionRecordsDataB4SaveAllAsArrays[$key]['name'])
                        )
                    );
                    
                } // if(array_key_exists('author_id', $collectionRecordsDataAsArrays[$key]))
            } // if(!in_array($key, $keysInCollectionOfUnsavedRecords))
        } // foreach($collectionToPerfomSaveAll as $key => $record)
    } // protected function performOtherSaveAllTests(...
    
    protected function createSavableAndUnsavableRecords(
        \LeanOrm\Model $alwaysFalseOnSaveModel, 
        \LeanOrm\Model $modelThatCanSave   
    ): array {
        
        $unsavableNewRecord1 = $alwaysFalseOnSaveModel->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author unsavable 1',
            'm_timestamp'   => date('Y-m-d H:i:s'),
            'date_created'  => date('Y-m-d H:i:s'),
        ]);
        
        $savableNewRecord1 = $modelThatCanSave->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author savable 333',
            'm_timestamp'   => date('Y-m-d H:i:s'),
            'date_created'  => date('Y-m-d H:i:s'),
        ]);
        
        $unsavableNewRecord2 = $alwaysFalseOnSaveModel->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author unsavable 2',
            'm_timestamp'   => date('Y-m-d H:i:s'),
            'date_created'  => date('Y-m-d H:i:s'),
        ]);
        
        $savableNewRecord2 = $modelThatCanSave->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author savable 444',
            'm_timestamp'   => date('Y-m-d H:i:s'),
            'date_created'  => date('Y-m-d H:i:s'),
        ]);
        
        $unsavableExistingRecord = $alwaysFalseOnSaveModel->fetchOneRecord(
            $alwaysFalseOnSaveModel->getSelect()->orderBy(['author_id asc'])
        );
        // Change a field, but it won't be saved because it is an unsavable record
        $unsavableExistingRecord->name .= " unsavableExistingRecord";
        
        $savableExistingRecord = $modelThatCanSave->fetchOneRecord(
            $modelThatCanSave->getSelect()->orderBy(['author_id asc'])
        );
        // Change a field so it can be saved
        $savableExistingRecord->name .= " savableExistingRecord";
        
        return [
            $unsavableNewRecord1, $savableNewRecord1,
            $unsavableNewRecord2, $savableNewRecord2,
            $unsavableExistingRecord, $savableExistingRecord
        ];
    } // protected function createSavableAndUnsavableRecords(...
    
    public function testThatSaveAllThrowsExceptionOnTryingToSaveOneOrMoreReadOnlyRecords1() {
        
        $this->expectException(\LeanOrm\CantSaveReadOnlyRecordException::class);
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], '', 'authors'
        );
        
        $records = [
            $model->createNewRecord(), 
            (new LeanOrm\Model\ReadOnlyRecord([], $model)),
        ];
        $collection = new \LeanOrm\Model\Collection($model, ...$records);
        self::assertCount(2, $collection); // 2 records in this collection
        
        // Throws \LeanOrm\CantSaveReadOnlyRecordException
        // because the collection collects a ReadOnlyRecord
        $collection->saveAll(false);
    } // public function testThatSaveAllThrowsExceptionOnTryingToSaveOneOrMoreReadOnlyRecords1()
    
    public function testThatSaveAllWithBulkInsertThrowsExceptionOnTryingToSaveOneOrMoreReadOnlyRecords() {
        
        $this->expectException(\LeanOrm\CantSaveReadOnlyRecordException::class);
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], '', 'authors'
        );
        
        $records = [
            $model->createNewRecord(), 
            (new LeanOrm\Model\ReadOnlyRecord([], $model)),
        ];
        $collection = new \LeanOrm\Model\Collection($model, ...$records);
        self::assertCount(2, $collection); // 2 records in this collection
        
        // Throws \LeanOrm\CantSaveReadOnlyRecordException
        // because the collection collects a ReadOnlyRecord
        $collection->saveAll(true);
    } // public function testThatSaveAllWithBulkInsertThrowsExceptionOnTryingToSaveOneOrMoreReadOnlyRecords()
    
    public function testThatSaveAllThrowsExceptionOnTryingToSaveRecordBelongingToDifferentTableNameFromCollectionsModel() {
        
        $this->expectException(\LeanOrm\Model\TableNameMismatchInCollectionSaveAllException::class);
        
        $authorsModel = new \LeanOrm\TestObjects\AuthorsModel(
            static::$dsn, static::$username ?? "", static::$password ?? ""
        );
        
        $postsModel = new \LeanOrm\TestObjects\PostsModel(
            static::$dsn, static::$username ?? "", static::$password ?? ""
        );
        
        $records = [
            $authorsModel->createNewRecord(), 
            $postsModel->createNewRecord(),
        ];
        $collection = new \LeanOrm\Model\Collection($authorsModel, ...$records);
        self::assertCount(2, $collection); // 2 records in this collection
        
        // Throws \LeanOrm\Model\TableNameMismatchInCollectionSaveAllException
        // because there's a record belonging to the posts table in this
        // collection whose model is associated with the authors table
        $collection->saveAll(true);
    } // public function testThatSaveAllThrowsExceptionOnTryingToSaveRecordBelongingToDifferentTableNameFromCollectionsModel()
    
    protected function runCommonSaveAllTests(bool $groupInsertsTogether) {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        
        /////////////////////////////////////////////////////////
        // Empty Collection returns true as no error & no effect
        /////////////////////////////////////////////////////////
        $emptyCollection = new \LeanOrm\Model\Collection($model);
        
        $recordsFromDbPreSaveAll = $model->fetchRecordsIntoCollection();
        
        self::assertTrue($emptyCollection->saveAll($groupInsertsTogether));
        
        $recordsFromDbPostSaveAll = $model->fetchRecordsIntoCollection();
        
        self::assertEquals(
            $recordsFromDbPreSaveAll->count(), 
            $recordsFromDbPostSaveAll->count()
        );
        
        ////////////////////////////////////////////////////////
        // Collection with only new records, should return true
        ////////////////////////////////////////////////////////
        $records = [
            $model->createNewRecord([
                //'author_id'     => 777, // new record, no need for primary key
                'name'          => 'Author 1',
                'm_timestamp'   => date('Y-m-d H:i:s'),
                'date_created'  => date('Y-m-d H:i:s'),
            ]), 
            $model->createNewRecord([
                //'author_id'     => 888, // new record, no need for primary key
                'name'          => 'Author 2',
                'm_timestamp'   => date('Y-m-d H:i:s'),
                'date_created'  => date('Y-m-d H:i:s'),
            ]),
        ];
        $collection = new \LeanOrm\Model\Collection($model, ...$records);
        self::assertCount(2, $collection);
        self::assertCount(
            0,
            $model->fetchRecordsIntoCollection(
                $model->getSelect()
                      ->where(' name IN (?, ?) ', 'Author 1', 'Author 2')
            )
        );
        self::assertTrue($collection->saveAll($groupInsertsTogether));
        self::assertCount(
            2,
            $model->fetchRecordsIntoCollection(
                $model->getSelect()
                      ->where(' name IN (?, ?) ', 'Author 1', 'Author 2')
            )
        );
        
        $this->setUp(); // restore db to baseline
        
        /////////////////////////////////////////////////////////////
        // Collection with only existing records, should return true
        /////////////////////////////////////////////////////////////
        $collection = $model->fetchRecordsIntoCollection();
        
        self::assertGreaterThan(0, $collection->count());
        
        // update the name for each record in the collection
        $i = 1;
        foreach ($collection as $record) {
            
            $record->name .= '__'.($i++);
        }
        
        // save modified records to DB
        self::assertTrue($collection->saveAll($groupInsertsTogether));
        
        // Re-fetch records to verify that modified names were saved
        $i = 1;
        foreach ($model->fetchRecordsIntoCollection() as $record) {
            
            self::assertStringEndsWith('__'.($i++), $record->name);
        }
        
        ////////////////////////////////////////////////////////////////////
        // Collection with both new & existing records, should return true
        ///////////////////////////////////////////////////////////////////
        $collection = $model->fetchRecordsIntoCollection(); // fetch existing records
        $numberOfExistingRecordsPreSaveAll = $collection->count();
        
        self::assertGreaterThan(0, $numberOfExistingRecordsPreSaveAll);
        
        $numOfNewRecords = 2;

        $collection[] = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 1',
            'm_timestamp'   => date('Y-m-d H:i:s'),
            'date_created'  => date('Y-m-d H:i:s'),
        ]); //add new record

        $collection[] = $model->createNewRecord([
            //'author_id'     => 888, // new record, no need for primary key
            'name'          => 'Author 2',
            'm_timestamp'   => date('Y-m-d H:i:s'),
            'date_created'  => date('Y-m-d H:i:s'),
        ]); //add new record

        // update the name for each record in the collection
        $i = 1;
        foreach ($collection as $record) {
            
            $record->name .= '###'.($i++);
        }
        
        // save modified new and existing records to DB
        self::assertTrue($collection->saveAll($groupInsertsTogether));
        
        // Re-fetch records to verify that modified names were saved        
        $refetchedRecords = $model->fetchRecordsIntoCollection();
        
        self::assertGreaterThan($numberOfExistingRecordsPreSaveAll, $refetchedRecords->count());
        self::assertEquals(
            $numberOfExistingRecordsPreSaveAll + $numOfNewRecords, 
            $refetchedRecords->count()
        ); // Make sure the total number of records fetched from the DB is the number
           // of initially existing records plus the new records we just saved
        
        $i = 1;
        foreach ($refetchedRecords as $record) {
            
            self::assertStringEndsWith('###'.($i++), $record->name);
        }
    } // protected function runCommonSaveAllTests(bool $groupInsertsTogether)
    
    public function testThatSetModelWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'post_id', 'posts'
        );
        $emptyCollection = new \LeanOrm\Model\Collection($model);
        
        self::assertSame($model, $emptyCollection->getModel());
        
        $authorsModel = new \LeanOrm\TestObjects\AuthorsModel(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        
        //test return $this
        self::assertSame(
            $emptyCollection, 
            $emptyCollection->setModel($authorsModel)
        );
        
        //test that the model we set is now associated with the collection
        self::assertSame(
            $authorsModel, 
            $emptyCollection->getModel()
        );
    } // public function testThatSetModelWorksAsExpected()
    
    public function testThatGetDataWorksAsExpected() {

        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        // empty collection
        self::assertEquals([], $collection->getData());
        
        $timestamp = date('Y-m-d H:i:s');
        $newRecord1 = $model->createNewRecord([
            'name'          => 'Author 1 for getData Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        $newRecord2 = $model->createNewRecord([
            'name'          => 'Author 2 for getData Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        
        $collection[] = $newRecord1;
        $collection[] = $newRecord2;
        $getDataOnCollection = $collection->getData();
        
        foreach($collection as $record) {
            
            self::assertContains($record->getData(), $getDataOnCollection);
        }
    }
    
    public function testThatToArrayWorksAsExpected() {

        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        // empty collection
        self::assertEquals([], $collection->toArray());
        
        $timestamp = date('Y-m-d H:i:s');
        $newRecord1 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 1 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        $newRecord2 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 2 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        
        $collection[777] = $newRecord1;
        $collection[888] = $newRecord2;
        $expectedToArrayResult = [
            777 => $newRecord1->toArray(), 888 => $newRecord2->toArray(),
        ];
        
        // non-empty collection should return expected array data
        self::assertEquals($expectedToArrayResult, $collection->toArray());
    }
    
    public function testThatOffsetExistsWorksAsExpected() {

        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        // empty collection
        self::assertFalse($collection->offsetExists(777));
        self::assertFalse($collection->offsetExists('Yabadabadoo'));
        
        $timestamp = date('Y-m-d H:i:s');
        $newRecord1 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 1 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        $newRecord2 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 2 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        
        $collection[777] = $newRecord1;
        $collection['Yabadabadoo'] = $newRecord2;
        
        // non-empty collection
        self::assertTrue($collection->offsetExists(777));
        self::assertTrue($collection->offsetExists('Yabadabadoo'));
    }
    
    public function testThatOffsetGetWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        $timestamp = date('Y-m-d H:i:s');
        $newRecord1 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 1 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        $newRecord2 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 2 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        
        $collection[777] = $newRecord1;
        $collection['Yabadabadoo'] = $newRecord2;
        
        // non-empty collection
        self::assertSame($newRecord1, $collection->offsetGet(777));
        self::assertSame($newRecord2, $collection->offsetGet('Yabadabadoo'));
    }
    
    public function testThatOffsetGetThrowsException() {
        
        $this->expectException(\GDAO\Model\ItemNotFoundInCollectionException::class);
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        // empty collection will throw exception
        $collection->offsetGet(777);
    }
    
    public function testThatOffsetSetWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        $timestamp = date('Y-m-d H:i:s');
        $newRecord1 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 1 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        $newRecord2 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 2 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        
        $collection->offsetSet(777, $newRecord1);
        $collection->offsetSet('Yabadabadoo', $newRecord2);
        
        // non-empty collection
        self::assertSame($newRecord1, $collection->offsetGet(777));
        self::assertSame($newRecord2, $collection->offsetGet('Yabadabadoo'));
    }
    
    public function testThatOffsetSetThrowsException() {
        
        $this->expectException(\GDAO\Model\CollectionCanOnlyContainGDAORecordsException::class);
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        // Adding an item that is not an instance of \GDAO\Model\RecordInterface
        // will throw exception
        $collection->offsetSet(777, new \ArrayObject());
    }
    
    public function testThatOffsetUnsetWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        $timestamp = date('Y-m-d H:i:s');
        $newRecord1 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 1 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        $newRecord2 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 2 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        
        $collection->offsetSet(777, $newRecord1);
        $collection->offsetSet('Yabadabadoo', $newRecord2);
        
        // non-empty collection
        self::assertSame($newRecord1, $collection->offsetGet(777));
        self::assertSame($newRecord2, $collection->offsetGet('Yabadabadoo'));
        
        self:: assertCount(2, $collection);
        
        $collection->offsetUnset(777);
        $collection->offsetUnset('Yabadabadoo');
        
        // collection is now empty because of the unset
        self:: assertCount(0, $collection);
    }
    
    public function testThatCountWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        self::assertEquals(0, $collection->count());
        
        $timestamp = date('Y-m-d H:i:s');
        $newRecord1 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 1 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        $newRecord2 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 2 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        
        $collection[777] =  $newRecord1;
        $collection['Yabadabadoo'] =  $newRecord2;
        
        // non-empty collection
        self::assertSame($newRecord1, $collection->offsetGet(777));
        self::assertSame($newRecord2, $collection->offsetGet('Yabadabadoo'));
        
        self::assertEquals(2, $collection->count());
        
        // update an item 
        $collection[777] =  $newRecord2;
        
        self::assertEquals(2, $collection->count());
        
        // add another item
        $collection[888] =  $model->createNewRecord();
        
        self::assertEquals(3, $collection->count());
    }
    
    public function testThatGetIteratorWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        self::assertIsNotArray($collection);
        self::assertIsIterable($collection);
        self::assertInstanceOf(\Traversable::class, $collection);
        self::assertInstanceOf(\Iterator::class, $collection->getIterator());
    }
    
    public function testThat__GetWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        $timestamp = date('Y-m-d H:i:s');
        $newRecord1 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 1 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        $newRecord2 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 2 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        
        $collection->_777 = $newRecord1;
        $collection->Yabadabadoo = $newRecord2;
        
        // non-empty collection
        self::assertSame($newRecord1, $collection->__get('_777'));
        self::assertSame($newRecord2, $collection->__get('Yabadabadoo'));
    }
    
    public function testThat__GetThrowsException() {
        
        $this->expectException(\GDAO\Model\ItemNotFoundInCollectionException::class);
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        // empty collection will throw exception
        $collection->__get(777);
    }
    
    public function testThat__issetWorksAsExpected() {

        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        // empty collection
        self::assertFalse($collection->__isset(777));
        self::assertFalse($collection->__isset('Yabadabadoo'));
        
        $timestamp = date('Y-m-d H:i:s');
        $newRecord1 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 1 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        $newRecord2 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 2 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        
        $collection->_777 = $newRecord1;
        $collection['Yabadabadoo'] = $newRecord2;
        
        // non-empty collection
        self::assertTrue($collection->__isset('_777'));
        self::assertTrue($collection->__isset('Yabadabadoo'));
    }
    
    public function testThat__setWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        $timestamp = date('Y-m-d H:i:s');
        $newRecord1 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 1 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        $newRecord2 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 2 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        
        $collection->__set(777, $newRecord1);
        $collection->__set('Yabadabadoo', $newRecord2);
        
        // non-empty collection
        self::assertSame($newRecord1, $collection->offsetGet(777));
        self::assertSame($newRecord2, $collection->offsetGet('Yabadabadoo'));
    }
    
    public function testThat__toStringWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        $timestamp = date('Y-m-d H:i:s');
        $newRecord1 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 1 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        $newRecord2 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 2 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        
        ////////////////////////////////////////////////////
        // __toString() returns a var_export representation 
        // of the array returned by the toArray() method
        ////////////////////////////////////////////////////
        
        $emptyCollectionAsArray = $collection->toArray();
        $emptyCollectionTostringAsArray = eval(' return ' . $collection->__toString() . ';');
        
        self::assertEquals($emptyCollectionAsArray, $emptyCollectionTostringAsArray);
        
        // add 2 records to the collection
        $collection[] = $newRecord1;
        $collection[] = $newRecord2;
        
        $nonEmptyCollectionAsArray = $collection->toArray();
        $nonEmptyCollectionTostringAsArray = eval(' return ' . $collection->__toString() . ';');

        self::assertEquals($nonEmptyCollectionAsArray, $nonEmptyCollectionTostringAsArray);
    }
    
    public function testThat__unsetWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $collection = new \LeanOrm\Model\Collection($model);
        
        $timestamp = date('Y-m-d H:i:s');
        $newRecord1 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 1 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        $newRecord2 = $model->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author 2 for toArray Testing',
            'm_timestamp'   => $timestamp,
            'date_created'  => $timestamp,
        ]);
        
        $collection[777]            = $newRecord1;
        $collection['Yabadabadoo']  = $newRecord2;
        
        // non-empty collection
        self:: assertCount(2, $collection);
        self::assertSame($newRecord1, $collection[777]);
        self::assertSame($newRecord2, $collection['Yabadabadoo']);
        
        $collection->__unset(777);
        $collection->__unset('Yabadabadoo');
        
        // collection is now empty because of the unset
        self:: assertCount(0, $collection);
    }
}
