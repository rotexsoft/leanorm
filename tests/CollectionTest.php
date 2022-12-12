<?php

/**
 * Description of CollectionTest
 *
 * @author rotimi
 */
class CollectionTest extends \PHPUnit\Framework\TestCase {
    
    use CommonPropertiesAndMethodsTrait;
    
    protected const POST_POST_IDS =                  [ '1', '2', '3', '4', ];
    protected const POST_POST_IDS_KEYED_ON_POST_ID = [ 1=>'1', 2=>'2', 3=>'3', 4=>'4', ];
    
    public function testThatConstructorWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [PDO::ATTR_PERSISTENT => true], 'author_id', 'authors'
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
            [PDO::ATTR_PERSISTENT => true], 'author_id', 'authors'
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
            [PDO::ATTR_PERSISTENT => true], '', 'authors'
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
            [PDO::ATTR_PERSISTENT => true], 'post_id', 'posts'
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
            [PDO::ATTR_PERSISTENT => true], 'post_id', 'posts'
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
            [PDO::ATTR_PERSISTENT => true], 'post_id', 'posts'
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
            [PDO::ATTR_PERSISTENT => true], 'post_id', 'posts'
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
            [PDO::ATTR_PERSISTENT => true], 'post_id', 'posts'
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
            [PDO::ATTR_PERSISTENT => true], 'post_id', 'posts'
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
        // returns false & it's record class always returns false on save. We will
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
        $alwaysFalseOnSaveModel->setRecordClassName(AlwaysFalseOnSaveRecord::class);
        
        ///////////////////////////////////////
        $modelThatCanSave = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [PDO::ATTR_PERSISTENT => true], 'author_id', 'authors'
        );
        
        $unsavableNewRecord1 = $alwaysFalseOnSaveModel->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author unsavable 1',
            'm_timestamp'   => date('Y-m-d H:i:s'),
            'date_created'  => date('Y-m-d H:i:s'),
        ]);
        
        $unsavableNewRecord2 = $alwaysFalseOnSaveModel->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author unsavable 2',
            'm_timestamp'   => date('Y-m-d H:i:s'),
            'date_created'  => date('Y-m-d H:i:s'),
        ]);
        
        $savableExistingRecord = $modelThatCanSave->fetchOneRecord(
            $modelThatCanSave->getSelect()->orderBy(['author_id asc'])
        );
        // Change a field so it can be saved
        $savableExistingRecord->name .= " savableExistingRecord";
        
        $unsavableExistingRecord = $alwaysFalseOnSaveModel->fetchOneRecord(
            $alwaysFalseOnSaveModel->getSelect()->orderBy(['author_id asc'])
        );
        // Change a field, but it won't be saved because it is an unsavable record
        $unsavableExistingRecord->name .= " unsavableExistingRecord";
        
        $savableNewRecord1 = $modelThatCanSave->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author savable 333',
            'm_timestamp'   => date('Y-m-d H:i:s'),
            'date_created'  => date('Y-m-d H:i:s'),
        ]);
        
        $savableNewRecord2 = $modelThatCanSave->createNewRecord([
            //'author_id'     => 777, // new record, no need for primary key
            'name'          => 'Author savable 444',
            'm_timestamp'   => date('Y-m-d H:i:s'),
            'date_created'  => date('Y-m-d H:i:s'),
        ]);
        
        $collectionCreatedBySavableModel = $modelThatCanSave->createNewCollection();
        $collectionCreatedBySavableModel[1] = $unsavableNewRecord1;
        $collectionCreatedBySavableModel[2] = $savableNewRecord1;
        $collectionCreatedBySavableModel[3] = $unsavableNewRecord2;
        $collectionCreatedBySavableModel[4] = $savableNewRecord2;
        $collectionCreatedBySavableModel[5] = $unsavableExistingRecord;
        $collectionCreatedBySavableModel[6] = $savableExistingRecord;
        
        $keysForUnsavableRecords = [1, 3, 5];
        
        self::assertCount(6, $collectionCreatedBySavableModel);
        
        // 3 savable (1 existing & 2 new) records should be saved
        // 3 unsavable (1 existing & 2 new) records should NOT be saved,
        // the keys to the unsaved unsavable records in the collection 
        // should be returned in an array.
        $savableModelsResult = $collectionCreatedBySavableModel->saveAll();
        
        //////////////////////////////////////////////////////////////
        // Make sure unsavable records were not saved
        self::assertCount(3, $savableModelsResult);
        
        // The unsavable records have the ff keys in the collection: 1, 3 & 5
        // These keys should be returned by saveAll
        self::assertContains(1, $savableModelsResult);
        self::assertContains(3, $savableModelsResult);
        self::assertContains(5, $savableModelsResult);
        
        // Verify that the savable records were actually saved
        self::assertStringContainsString(
            " savableExistingRecord", 
            $modelThatCanSave->fetchOneRecord(
                $modelThatCanSave->getSelect()->orderBy(['author_id asc'])
            )->name 
        );  // Check that substring above is contained in the name from the 
            // first existing record re-fetched from the db indicating a 
            // successful update of that record by saveAll
        
        $refetchedSavableNewRecord1 = $modelThatCanSave->fetchOneRecord(
            $modelThatCanSave->getSelect()
                             ->where(' name = ? ', 'Author savable 333')  
        ); // re-fetch the first new savable record & make sure it is an instance of record
        self::assertInstanceOf(GDAO\Model\RecordInterface::class, $refetchedSavableNewRecord1);
        
        $refetchedSavableNewRecord2 = $modelThatCanSave->fetchOneRecord(
            $modelThatCanSave->getSelect()
                             ->where(' name = ? ', 'Author savable 444')  
        ); // re-fetch the second new savable record & make sure it is an instance of record
        self::assertInstanceOf(GDAO\Model\RecordInterface::class, $refetchedSavableNewRecord2);
        
        // make sure that the two new unsavable records were not saved
        self::assertNull(
            $modelThatCanSave->fetchOneRecord(
                $modelThatCanSave->getSelect()
                                 ->where(' name = ? ', 'Author unsavable 1')  
            )
        );
        self::assertNull(
            $modelThatCanSave->fetchOneRecord(
                $modelThatCanSave->getSelect()
                                 ->where(' name = ? ', 'Author unsavable 2')  
            )
        );
        
        // the one existing unsavable record also should not have been updated
        self::assertNull(
            $modelThatCanSave->fetchOneRecord(
                $modelThatCanSave->getSelect()
                                 ->where(' name = ? ', 'user_1 unsavableExistingRecord')  
            )
        );
        
        ///////////////////////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////////////////////
        // We are now going to create a 2nd collection, update the name field of
        // each record and then call saveAll on this 2nd collection to save the 
        // records again.
        // 
        // This 2nd collection will be created via the Model whose insert methods 
        // always return false & whose Record class always returns false on save.
        //  
        // The result of calling saveAll on this new collection would be that:
        // 
        //      - The 3 savable records (1 existing & 2 new) will still be saved 
        //      because the model associated with those records allows saving.
        //      
        //      - The 3 unsavable records will still not be saved because the 
        //      model they were created with always returns false on insert* &
        //      it's record class always returns false on save.
        
        $collectionCreatedByUnsavableModel = $alwaysFalseOnSaveModel->createNewCollection();
        $collectionCreatedByUnsavableModel[1] = $unsavableNewRecord1;
        $collectionCreatedByUnsavableModel[2] = $savableNewRecord1;
        $collectionCreatedByUnsavableModel[3] = $unsavableNewRecord2;
        $collectionCreatedByUnsavableModel[4] = $savableNewRecord2;
        $collectionCreatedByUnsavableModel[5] = $unsavableExistingRecord;
        $collectionCreatedByUnsavableModel[6] = $savableExistingRecord;
        
        self::assertCount(6, $collectionCreatedByUnsavableModel);
        
        ///////////////////////////////////////////////////////////////////////
        // Let's tweak all the records in the second collection before calling
        // saveAll
        foreach ($collectionCreatedByUnsavableModel as $record) {
            
            $record->name .= '__SecondCollectionCreatedByTheUnsavableModel';
        }

        // 3 savable (1 existing & 2 new) records should still be saved
        // 3 unsavable (1 existing & 2 new) records should NOT be saved,
        // the keys to the unsaved unsavable records in the collection 
        // should still be returned in an array.
        $unsavableModelsResult = $collectionCreatedByUnsavableModel->saveAll(false);
        
        // Make sure unsavable records were not saved
        self::assertEquals($keysForUnsavableRecords, $unsavableModelsResult);
        
        // Verify that the savable records were actually saved
        self::assertStringContainsString(
            "__SecondCollectionCreatedByTheUnsavableModel", 
            $alwaysFalseOnSaveModel->fetchOneRecord(
                $alwaysFalseOnSaveModel->getSelect()->orderBy(['author_id asc'])
            )->name
        );
        
        $refetchedSavableNewRecord1 = $alwaysFalseOnSaveModel->fetchOneRecord(
            $alwaysFalseOnSaveModel->getSelect()
                                   ->where(' name = ? ', 'Author savable 333__SecondCollectionCreatedByTheUnsavableModel')  
        );
        self::assertInstanceOf(GDAO\Model\RecordInterface::class, $refetchedSavableNewRecord1);
        
        
        $refetchedSavableNewRecord2 = $alwaysFalseOnSaveModel->fetchOneRecord(
            $alwaysFalseOnSaveModel->getSelect()
                                   ->where(' name = ? ', 'Author savable 444__SecondCollectionCreatedByTheUnsavableModel')  
        );
        self::assertInstanceOf(GDAO\Model\RecordInterface::class, $refetchedSavableNewRecord2);
        
        // make sure that the two new unsavable records were not saved
        self::assertNull(
            $alwaysFalseOnSaveModel->fetchOneRecord(
                $alwaysFalseOnSaveModel->getSelect()
                                       ->where(' name = ? ', 'Author unsavable 1__SecondCollectionCreatedByTheUnsavableModel')  
            )
        );
        self::assertNull(
            $alwaysFalseOnSaveModel->fetchOneRecord(
                $alwaysFalseOnSaveModel->getSelect()
                                       ->where(' name = ? ', 'Author unsavable 2__SecondCollectionCreatedByTheUnsavableModel')  
            )
        );
        
        // the one existing unsavable record also should not have been updated
        self::assertNull(
            $alwaysFalseOnSaveModel->fetchOneRecord(
                $alwaysFalseOnSaveModel->getSelect()
                                       ->where(' name = ? ', 'user_1 unsavableExistingRecord__SecondCollectionCreatedByTheUnsavableModel')  
            )
        );
        
        // Let's call saveAll with bulk insert set to true, we should still get
        // a different result
        $unsavableModelsResult = $collectionCreatedByUnsavableModel->saveAll(true);
//var_dump($unsavableModelsResult);exit;
        // Make sure unsavable records were not saved
        self::assertContains(1, $unsavableModelsResult);
        self::assertContains(3, $unsavableModelsResult);
        self::assertContains(5, $unsavableModelsResult);
        
        // make sure that the two new unsavable records were not saved
        self::assertNull(
            $alwaysFalseOnSaveModel->fetchOneRecord(
                $alwaysFalseOnSaveModel->getSelect()
                                       ->where(' name = ? ', 'Author unsavable 1__SecondCollectionCreatedByTheUnsavableModel')  
            )
        );
        self::assertNull(
            $alwaysFalseOnSaveModel->fetchOneRecord(
                $alwaysFalseOnSaveModel->getSelect()
                                       ->where(' name = ? ', 'Author unsavable 2__SecondCollectionCreatedByTheUnsavableModel')  
            )
        );
        
        // the one existing unsavable record also should not have been updated
        self::assertNull(
            $alwaysFalseOnSaveModel->fetchOneRecord(
                $alwaysFalseOnSaveModel->getSelect()
                                       ->where(' name = ? ', 'user_1 unsavableExistingRecord__SecondCollectionCreatedByTheUnsavableModel')  
            )
        );
    }
    
    public function testThatSaveAllThrowsExceptionOnTryingToSaveOneOrMoreReadOnlyRecords1() {
        
        $this->expectException(\LeanOrm\CantSaveReadOnlyRecordException::class);
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [PDO::ATTR_PERSISTENT => true], '', 'authors'
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
    
    public function testThatSaveAllThrowsExceptionOnTryingToSaveOneOrMoreReadOnlyRecords2() {
        
        $this->expectException(\LeanOrm\CantSaveReadOnlyRecordException::class);
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [PDO::ATTR_PERSISTENT => true], '', 'authors'
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
    } // public function testThatSaveAllThrowsExceptionOnTryingToSaveOneOrMoreReadOnlyRecords2()
    
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
            [PDO::ATTR_PERSISTENT => true], 'author_id', 'authors'
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
}
