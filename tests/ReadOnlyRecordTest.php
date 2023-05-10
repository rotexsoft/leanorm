<?php

/**
 * Only testing methods physically present in \LeanOrm\Model\ReadOnlyRecord, not
 * testing methods in \LeanOrm\Model\CommonRecordCodeTrait that get injected into
 * \LeanOrm\Model\ReadOnlyRecord, because all those methods have been tested in
 * RecordTest which tests all \LeanOrm\Model\Record methods.
 *
 * @author rotimi
 */
class ReadOnlyRecordTest extends \PHPUnit\Framework\TestCase {
    
    use CommonPropertiesAndMethodsTrait;
    
    public function testThatDeleteWorksAsExpected() {
        
        $this->expectException(\GDAO\Model\RecordOperationNotSupportedException::class);
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        
        $timestamp = date('Y-m-d H:i:s');
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        
        $readOnlyRecord->delete();
    }
    
    public function testThatGetInitialDataWorksAsExpected() {
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        
        $timestamp = date('Y-m-d H:i:s');
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        self::assertEquals([], $readOnlyRecord->getInitialData());
        
        $readOnlyRecord2 = new \LeanOrm\Model\ReadOnlyRecord([], $model);
        $readOnlyRecord2->loadData(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ]
        );
        self::assertEquals([], $readOnlyRecord2->getInitialData());
    }
    
    public function testThatGetInitialDataByRefWorksAsExpected() {
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        
        $timestamp = date('Y-m-d H:i:s');
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        self::assertEquals([], $readOnlyRecord->getInitialDataByRef());
        
        $readOnlyRecord2 = new \LeanOrm\Model\ReadOnlyRecord([], $model);
        $readOnlyRecord2->loadData(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ]
        );
        self::assertEquals([], $readOnlyRecord2->getInitialDataByRef());
    }
    
    public function testThatIsChangedWorksAsExpected() {
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        $model->setRecordClassName(\LeanOrm\Model\ReadOnlyRecord::class);
        
        $timestamp = date('Y-m-d H:i:s');
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        self::assertFalse($readOnlyRecord->isChanged());
        
        $readOnlyRecord->loadData([
            'author_id' => 999,
            'name' => 'Author 1BB',
        ]);
        self::assertFalse($readOnlyRecord->isChanged());
        
        ////////////////////////////////////////////////////////////////////////
        $readOnlyRecord2 = $model->fetchOneRecord();
        self::assertInstanceOf(\LeanOrm\Model\ReadOnlyRecord::class, $readOnlyRecord2);
        self::assertFalse($readOnlyRecord2->isChanged());
        
        $readOnlyRecord2->loadData([
            'author_id' => 999,
            'name' => 'Author 1BB',
        ]);
        self::assertFalse($readOnlyRecord2->isChanged());
    }
    
    public function testThatIsNewWorksAsExpected() {
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        $model->setRecordClassName(\LeanOrm\Model\ReadOnlyRecord::class);
        
        $timestamp = date('Y-m-d H:i:s');
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        self::assertFalse($readOnlyRecord->isNew());
        
        $readOnlyRecord->loadData([
            'author_id' => 999,
            'name' => 'Author 1BB',
        ]);
        self::assertFalse($readOnlyRecord->isNew());
        
        ////////////////////////////////////////////////////////////////////////
        $readOnlyRecord2 = $model->fetchOneRecord();
        self::assertInstanceOf(\LeanOrm\Model\ReadOnlyRecord::class, $readOnlyRecord2);
        self::assertFalse($readOnlyRecord2->isNew());
        
        $readOnlyRecord2->loadData([
            'author_id' => 999,
            'name' => 'Author 1BB',
        ]);
        self::assertFalse($readOnlyRecord2->isNew());
    }
    
    public function testThatLoadDataThrowsException() {
        
        $this->expectException(\GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException::class);
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        $model->setRecordClassName(\LeanOrm\Model\ReadOnlyRecord::class);
        
        $timestamp = date('Y-m-d H:i:s');
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        
        $postRecord = (new \LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? ""))
                        ->createNewRecord();
        self::assertInstanceOf(\LeanOrm\TestObjects\PostRecord::class, $postRecord);
        
        $readOnlyRecord->loadData($postRecord); //Record belonging to different table
    }
    
    public function testThatLoadDataWorksAsExpected() {
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        $model->setRecordClassName(\LeanOrm\Model\ReadOnlyRecord::class);
        
        $timestamp = date('Y-m-d H:i:s');
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        
        //////////////////////////////////////////////////////////////////////
        // $cols_2_load === [] scenarios
        self::assertEquals(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ], 
            $readOnlyRecord->getData()
        );
        self::assertEquals(
            [
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ], 
            $readOnlyRecord->getNonTableColAndNonRelatedData()
        );
        
        self::assertSame(
            $readOnlyRecord,
            $readOnlyRecord->loadData(
                [
                    'author_id' => 999, 
                    'name' => 'Author 1BBB',
                    'non_existent_col_1' => 'Some Data 1B',
                    'non_existent_col_3' => 'Some Data 3',
                ]
            )
        );
        
        self::assertEquals(
            [
                'author_id' => 999, 
                'name' => 'Author 1BBB', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ], 
            $readOnlyRecord->getData()
        );
        self::assertEquals(
            [
                'non_existent_col_1' => 'Some Data 1B',
                'non_existent_col_2' => 'Some Data 2',
                'non_existent_col_3' => 'Some Data 3',
            ], 
            $readOnlyRecord->getNonTableColAndNonRelatedData()
        );
        
        // LoadData from another record scenario
        $readOnlyRecord2 = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        
        self::assertEquals(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ], 
            $readOnlyRecord2->getData()
        );
        self::assertEquals(
            [
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ], 
            $readOnlyRecord2->getNonTableColAndNonRelatedData()
        );
        
        self::assertSame($readOnlyRecord2, $readOnlyRecord2->loadData($readOnlyRecord));
        
        self::assertEquals(
            [
                'author_id' => 999, 
                'name' => 'Author 1BBB', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ], 
            $readOnlyRecord2->getData()
        );
        self::assertEquals(
            [
                'non_existent_col_1' => 'Some Data 1B',
                'non_existent_col_2' => 'Some Data 2',
                'non_existent_col_3' => 'Some Data 3',
            ], 
            $readOnlyRecord2->getNonTableColAndNonRelatedData()
        );
        
        //////////////////////////////////////////////////////////////////////
        // $cols_2_load !== [] scenarios
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        
        self::assertEquals(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ], 
            $readOnlyRecord->getData()
        );
        self::assertEquals(
            [
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ], 
            $readOnlyRecord->getNonTableColAndNonRelatedData()
        );
        
        self::assertSame(
            $readOnlyRecord,
            $readOnlyRecord->loadData(
                [
                    'author_id' => 999, 
                    'name' => 'Author 1BBB',
                    'non_existent_col_1' => 'Some Data 1B',
                    'non_existent_col_3' => 'Some Data 3',
                ],
                ['name', 'non_existent_col_3']
            )
        );
        
        self::assertEquals(
            [
                'author_id' => 888, 
                'name' => 'Author 1BBB', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ], 
            $readOnlyRecord->getData()
        );
        self::assertEquals(
            [
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
                'non_existent_col_3' => 'Some Data 3',
            ], 
            $readOnlyRecord->getNonTableColAndNonRelatedData()
        );
        
        
        // LoadData from another record scenario
        $readOnlyRecord2 = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        
        self::assertEquals(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ], 
            $readOnlyRecord2->getData()
        );
        self::assertEquals(
            [
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ], 
            $readOnlyRecord2->getNonTableColAndNonRelatedData()
        );
        
        self::assertSame(
            $readOnlyRecord2,
            $readOnlyRecord2->loadData($readOnlyRecord, ['name', 'non_existent_col_3'])
        );
        
        self::assertEquals(
            [
                'author_id' => 888, 
                'name' => 'Author 1BBB', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ], 
            $readOnlyRecord2->getData()
        );
        self::assertEquals(
            [
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
                'non_existent_col_3' => 'Some Data 3',
            ], 
            $readOnlyRecord2->getNonTableColAndNonRelatedData()
        );
    }
    
    public function testThatMarkAsNewWorksAsExpected() {
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        $model->setRecordClassName(\LeanOrm\Model\ReadOnlyRecord::class);
        
        $timestamp = date('Y-m-d H:i:s');
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        
        $modifyRecord = function(\LeanOrm\Model\ReadOnlyRecord $record) {
            
            return $record->markAsNew();
        };
        
        $this->ensureReadOnlyRecordNewnessStateDoesntChange($readOnlyRecord, $modifyRecord);
    }
    
    public function testThatMarkAsNotNewWorksAsExpected() {
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        $model->setRecordClassName(\LeanOrm\Model\ReadOnlyRecord::class);
        
        $timestamp = date('Y-m-d H:i:s');
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        
        $modifyRecord = function(\LeanOrm\Model\ReadOnlyRecord $record) {
            
            return $record->markAsNotNew();
        };
        
        $this->ensureReadOnlyRecordNewnessStateDoesntChange($readOnlyRecord, $modifyRecord);
    }
    
    public function testThatSetStateToNewWorksAsExpected() {
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        $model->setRecordClassName(\LeanOrm\Model\ReadOnlyRecord::class);
        
        $timestamp = date('Y-m-d H:i:s');
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        
        $modifyRecord = function(\LeanOrm\Model\ReadOnlyRecord $record) {
            
            return $record->setStateToNew();
        };
        
        $this->ensureReadOnlyRecordNewnessStateDoesntChange($readOnlyRecord, $modifyRecord);
    }
    
    protected function ensureReadOnlyRecordNewnessStateDoesntChange(
        \LeanOrm\Model\ReadOnlyRecord $record,
        callable $modifyRecord
    ) {
        $originalData = $record->getData();
        $originalInitialData = $record->getInitialData();
        $originalRelatedData = $record->getRelatedData();
        $originalNonTableColAndNonRelatedData = $record->getNonTableColAndNonRelatedData();
        
        self::assertFalse($record->isChanged());
        self::assertFalse($record->isNew());
        
        self::assertSame($record, $modifyRecord($record));
        
        self::assertEquals($originalData, $record->getData());
        self::assertEquals($originalInitialData, $record->getInitialData());
        self::assertEquals($originalRelatedData, $record->getRelatedData());
        self::assertEquals($originalNonTableColAndNonRelatedData, $record->getNonTableColAndNonRelatedData());
        self::assertFalse($record->isChanged());
        self::assertFalse($record->isNew());
    }
    
    public function testThatSaveWorksAsExpected() {
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        $model->setRecordClassName(\LeanOrm\Model\ReadOnlyRecord::class);
        
        $timestamp = date('Y-m-d H:i:s');
        $recordData = [
            'author_id' => 888, 
            'name' => 'Author 1', 
            'm_timestamp' => $timestamp, 
            'date_created' => $timestamp,
            'non_existent_col_1' => 'Some Data 1',
            'non_existent_col_2' => 'Some Data 2',
        ];
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord([], $model);
        $readOnlyRecord2 = new \LeanOrm\Model\ReadOnlyRecord($recordData, $model);
        
        self::assertNull($readOnlyRecord->save(null)); // null arg on empty record
        
        $readOnlyRecord->loadData($recordData);
        
        self::assertNull($readOnlyRecord->save(null)); // null arg on record with data
        self::assertNull($readOnlyRecord->save($recordData)); // array arg
        self::assertNull($readOnlyRecord->save($readOnlyRecord2)); // record arg
    }
    
    public function testThatSaveInTransactionWorksAsExpected() {
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        $model->setRecordClassName(\LeanOrm\Model\ReadOnlyRecord::class);
        
        $timestamp = date('Y-m-d H:i:s');
        $recordData = [
            'author_id' => 888, 
            'name' => 'Author 1', 
            'm_timestamp' => $timestamp, 
            'date_created' => $timestamp,
            'non_existent_col_1' => 'Some Data 1',
            'non_existent_col_2' => 'Some Data 2',
        ];
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord([], $model);
        $readOnlyRecord2 = new \LeanOrm\Model\ReadOnlyRecord($recordData, $model);
        
        self::assertNull($readOnlyRecord->saveInTransaction(null)); // null arg on empty record
        
        $readOnlyRecord->loadData($recordData);
        
        self::assertNull($readOnlyRecord->saveInTransaction(null)); // null arg on record with data
        self::assertNull($readOnlyRecord->saveInTransaction($recordData)); // array arg
        self::assertNull($readOnlyRecord->saveInTransaction($readOnlyRecord2)); // record arg
    }
    
    public function testThat__SetThrowsException() {
        
        $this->expectException(\GDAO\Model\RecordOperationNotSupportedException::class);
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        $model->setRecordClassName(\LeanOrm\Model\ReadOnlyRecord::class);
        
        $timestamp = date('Y-m-d H:i:s');
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        
        $readOnlyRecord->__set('field', 'val');
    }
    
    public function testThat__UnsetThrowsException() {
        
        $this->expectException(\GDAO\Model\RecordOperationNotSupportedException::class);
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        $model->setRecordClassName(\LeanOrm\Model\ReadOnlyRecord::class);
        
        $timestamp = date('Y-m-d H:i:s');
        $readOnlyRecord = new \LeanOrm\Model\ReadOnlyRecord(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        
        $readOnlyRecord->__unset('name');
    }
}
