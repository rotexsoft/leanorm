<?php

/**
 * Description of RecordTest
 *
 * @author rotimi
 */
class RecordTest extends \PHPUnit\Framework\TestCase {
    
    use CommonPropertiesAndMethodsTrait;
    
    public function testThatConstructorWorksAsExpected() {
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        $timestamp = date('Y-m-d H:i:s');
        $record = new LeanOrm\Model\Record(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
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
            $record->getData()
        );
        self::assertSame($model, $record->getModel());
        
        ////////////////////////////////////////////////////////////////////////////////////////
        // Create Record with an array with some keys not mapping to an actual table column name
        ////////////////////////////////////////////////////////////////////////////////////////
        $record2 = new LeanOrm\Model\Record(
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
            $record2->getData()
        );

        self::assertEquals(
            [
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $record2->getNonTableColAndNonRelatedData()
        );
    }
    
    public function testThatDeleteWorksAsExpected() {
        
        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors');
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
        
        self::assertTrue($record->delete());
        
        self::assertEquals(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ],
            $record->getInitialData()
        );
        
        self::assertEquals(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ],
            $record->getData()
        );

        self::assertEquals(
            [
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $record->getNonTableColAndNonRelatedData()
        );

        self::assertEquals([], $record->getRelatedData());
        
        ////////////////////////////////////////////////////////////////////
        // Delete with true as first argument
        ////////////////////////////////////////////////////////////////////
        $record1 = $model->fetchOneRecord(
            $model->getSelect()->orderBy(['author_id asc'])
        );
        $authorId = $record1->getPrimaryVal();
        self::assertInstanceOf(\LeanOrm\Model\Record::class, $record1);
        self::assertTrue($record1->delete(true));
        self::assertEquals([], $record1->getData());
        self::assertEquals([], $record1->getInitialData());
        self::assertEquals([], $record1->getNonTableColAndNonRelatedData());
        self::assertEquals([], $record1->getRelatedData());
        
        $refetchRecord = $model->fetchOneRecord(
            $model->getSelect()->where(' author_id = ? ', $authorId)
        );
        
        self::assertNull($refetchRecord);
    }
    
    public function testThatGetDataWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
            $record->getData()
        );
    }
    
    public function testThatGetInitialDataWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
            $record->getInitialData()
        );
    }
    
    public function testThatGetRelatedDataWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
        
        $record->setRelatedData(
            'related_data', 
            [
                'author_id' => 777, 
                'name' => 'Author 777', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ]
        );
        
        self::assertEquals(
            [
                'related_data' => [
                    'author_id' => 777, 
                    'name' => 'Author 777', 
                    'm_timestamp' => $timestamp, 
                    'date_created' => $timestamp,
                    'non_existent_col_1' => 'Some Data 1',
                    'non_existent_col_2' => 'Some Data 2',
                ]
            ],
            $record->getRelatedData()
        );
    }
    
    public function testThatGetNonTableColAndNonRelatedDataWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $record->getNonTableColAndNonRelatedData()
        );
    }
    
    public function testThatGetDataByRefWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
            $record->getDataByRef()
        );
    }
    
    public function testThatGetInitialDataByRefWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
            $record->getInitialDataByRef()
        );
    }
    
    public function testThatGetRelatedDataByRefWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
        
        $record->setRelatedData(
            'related_data', 
            [
                'author_id' => 777, 
                'name' => 'Author 777', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ]
        );
        
        self::assertEquals(
            [
                'related_data' => [
                    'author_id' => 777, 
                    'name' => 'Author 777', 
                    'm_timestamp' => $timestamp, 
                    'date_created' => $timestamp,
                    'non_existent_col_1' => 'Some Data 1',
                    'non_existent_col_2' => 'Some Data 2',
                ]
            ],
            $record->getRelatedDataByRef()
        );
    }
    
    public function testThatGetNonTableColAndNonRelatedDataByRefWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $record->getNonTableColAndNonRelatedDataByRef()
        );
    }
    
    public function testThatSetRelatedDataWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
        
        $record->setRelatedData(
            'related_data', 
            [
                'author_id' => 777, 
                'name' => 'Author 777', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ]
        );
        
        self::assertEquals(
            [
                'related_data' => [
                    'author_id' => 777, 
                    'name' => 'Author 777', 
                    'm_timestamp' => $timestamp, 
                    'date_created' => $timestamp,
                    'non_existent_col_1' => 'Some Data 1',
                    'non_existent_col_2' => 'Some Data 2',
                ]
            ],
            $record->getRelatedData()
        );
    }
    
    public function testThatSetRelatedDataThrowsException() {
        
        $this->expectException(\GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException::class);
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
        
        $record->setRelatedData(
            'author_id', // naming a relationship with the same name as a column should lead to exception
            [
                'author_id' => 777, 
                'name' => 'Author 777', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ]
        );
    }
    
    public function testThatGetModelWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
        
        self::assertSame($model, $record->getModel());
    }
    
    public function testThatGetPrimaryColWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
        
        self::assertEquals('author_id', $record->getPrimaryCol());
    }
    
    public function testThatGetPrimaryValWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
        
        self::assertEquals(888, $record->getPrimaryVal());
    }
    
    public function testThatIsChangedWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new \LeanOrm\Model\Record(
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
        
        self::assertFalse($record->isChanged());
        
        $record->author_id = 999;
        
        self::assertTrue($record->isChanged());
        
        self::assertNull($record->isChanged('non_existent'));
        
        $record2 = new LeanOrm\Model\Record(
            [
                'author_id' => 888, 
                'name' => null, 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        
        // initially not null & now null
        $record->name = null;
        self::assertTrue($record->isChanged('name'));
        
        // initially null & now not null
        $record2->name = 'Not Null Value';
        self::assertTrue($record2->isChanged('name'));
        
        // initially numeric & now not numeric
        $record2->author_id = 'Non Numeric Value';
        self::assertTrue($record2->isChanged('author_id'));
        
        $record3 = new LeanOrm\Model\Record(
            [
                'author_id' => 777, 
                'name' => 'Non Null', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        
        // time to do some hacky stuff to modify 
        // \LeanOrm\Model\Record->data and 
        // \LeanOrm\Model\Record->initial_data
        $data = &$record3->getDataByRef();
        $initialData = &$record3->getInitialDataByRef();
        
        unset($data['author_id']);
        unset($initialData['name']);
        
        self::assertTrue($record3->isChanged('name'));
        self::assertTrue($record3->isChanged('author_id'));
    }
    
    public function testThatIsNewWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
        
        self::assertTrue($record->isNew());
        
        // Fetching an existing record should return a non-new record
        $record1 = $model->fetchOneRecord(
            $model->getSelect()->orderBy(['author_id asc'])
        );
        
        self::assertFalse($record1->isNew());
    }
    
    public function testThatLoadDataThrowsException() {
        
        $this->expectException(\GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException::class);
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        
        $record = new LeanOrm\Model\Record([], $model);
        
        $record->loadData(
            null // This arg should be an array of instance of \GDAO\Model\RecordInterface
        );
    }
    
    public function testThatLoadDataThrowsException2() {
        
        $this->expectException(\GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException::class);
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        
        $postsModel = new \LeanOrm\TestObjects\PostsModel(
            static::$dsn, static::$username ?? "", static::$password ?? ""
        );
        
        $record = new LeanOrm\Model\Record([], $model);
        
        // loading data from a record belonging to a different db table should
        // throw exception
        $record->loadData(
            $postsModel->createNewRecord() 
        );
    }
    
    public function testThatLoadDataWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        $record = new LeanOrm\Model\Record([], $model);
        
        ///////////////////////////////////////////////////////////////////////
        // $cols_2_load === [] scenarios
        ///////////////////////////////////////////////////////////////////////
        
        // is_array($data_2_load) && $data_2_load !== []
        $data2Load = [
            'author_id' => 999, 
            'name' => 'Author 1 B', 
            'm_timestamp' => $timestamp, 
            'date_created' => $timestamp,
            'non_existent_col_1' => 'Some Data 1 B',
            'non_existent_col_2' => 'Some Data 2 B',
        ];
        $cols2Load = [];
        
        $initialData = &$record->getInitialDataByRef();
        $initialData = null; // set it to null, so it can be set again by loadData below
        $result = $record->loadData($data2Load, $cols2Load);
        
        self::assertSame($record, $result); // test fluent return
        
        self::assertEquals(
            [
                'non_existent_col_1' => 'Some Data 1 B',
                'non_existent_col_2' => 'Some Data 2 B',
            ],
            $record->getNonTableColAndNonRelatedData()
        );
        
        self::assertEquals(
            [
                'author_id' => 999, 
                'name' => 'Author 1 B', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ],
            $record->getData()
        );
        
        self::assertEquals(
            [
                'author_id' => 999, 
                'name' => 'Author 1 B', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ],
            $record->getInitialData()
        );
        
        // elseif ($data_2_load instanceof \GDAO\Model\RecordInterface)
        
        // Fetching an existing record & load that record's data
        $existingRecord = $model->fetchOneRecord(
            $model->getSelect()->orderBy(['author_id asc'])
        );
        
        $cols2Load2 = [];
        $record->loadData($existingRecord, $cols2Load2);
        
        self::assertEquals(
            $existingRecord->getNonTableColAndNonRelatedData(),
            $record->getNonTableColAndNonRelatedData()
        );
        
        self::assertEquals(
            $existingRecord->getData(),
            $record->getData()
        );
        
        $record->loadData($data2Load, $cols2Load); // Set the record back to initial state
        
        ///////////////////////////////////////////////////////////////////////
        // $cols_2_load !== [] scenarios
        ///////////////////////////////////////////////////////////////////////
        $data2Load = [
            'author_id' => 1999, 
            'name' => 'Author 1 ABC', 
            'm_timestamp' => $timestamp, 
            'date_created' => $timestamp,
            'non_existent_col_1' => 'Some Data 1 ABC',
            'non_existent_col_2' => 'Some Data 2 B',
        ];
        $cols2Load = ['name', 'non_existent_col_1']; // only update the name & non_existent_col_1
        
        $record->loadData($data2Load, $cols2Load);
        
        self::assertEquals(
            [
                'non_existent_col_1' => 'Some Data 1 ABC',
                'non_existent_col_2' => 'Some Data 2 B',
            ],
            $record->getNonTableColAndNonRelatedData()
        );
        
        self::assertEquals(
            [
                'author_id' => 999, 
                'name' => 'Author 1 ABC', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ],
            $record->getData()
        );
        
        
        ////////////////////////////////////////////////
        $cols2Load = ['name']; // only update the name
        $record->loadData($existingRecord, $cols2Load);
        
        self::assertEquals(
            [
                'non_existent_col_1' => 'Some Data 1 ABC',
                'non_existent_col_2' => 'Some Data 2 B',
            ],
            $record->getNonTableColAndNonRelatedData()
        );
        
        self::assertEquals(
            [
                'author_id' => 999, 
                'name' => $existingRecord->name, 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ],
            $record->getData()
        );
    }
    
    public function testThatMarkAsNewWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
        
        self::assertTrue($record->isNew());
        self::assertSame($record, $record->markAsNew());
        self::assertTrue($record->isNew());
        
        // Fetching an existing record should return a non-new record
        $record1 = $model->fetchOneRecord(
            $model->getSelect()->orderBy(['author_id asc'])
        );
        
        self::assertFalse($record1->isNew());
        self::assertSame($record1, $record1->markAsNew());
        self::assertTrue($record1->isNew());
    }
    
    public function testThatMarkAsNotNewWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
        
        self::assertTrue($record->isNew());
        self::assertSame($record, $record->markAsNotNew());
        self::assertFalse($record->isNew());
        
        // Fetching an existing record should return a non-new record
        $record1 = $model->fetchOneRecord(
            $model->getSelect()->orderBy(['author_id asc'])
        );
        
        self::assertFalse($record1->isNew());
        self::assertSame($record1, $record1->markAsNotNew());
        self::assertFalse($record1->isNew());
    }
    
    public function testThatSetStateToNewWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
        
        self::assertTrue($record->isNew());
        self::assertSame($record, $record->setStateToNew());
        self::assertTrue($record->isNew());
        self::assertEquals([], $record->getData());
        self::assertEquals([], $record->getRelatedData());
        self::assertEquals([], $record->getNonTableColAndNonRelatedData());
        self::assertNull($record->getInitialData());
        
        // Fetching an existing record should return a non-new record
        $record1 = $model->fetchOneRecord(
            $model->getSelect()->orderBy(['author_id asc'])
        );
        
        self::assertFalse($record1->isNew());
        self::assertSame($record1, $record1->setStateToNew());
        self::assertTrue($record1->isNew());
        self::assertEquals([], $record1->getData());
        self::assertEquals([], $record1->getRelatedData());
        self::assertEquals([], $record1->getNonTableColAndNonRelatedData());
        self::assertNull($record1->getInitialData());
    }
    
    public function testThatSaveWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        $record = new LeanOrm\Model\Record(
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
        
        // Fetching an existing record should return a non-new record
        $record1 = $model->fetchOneRecord(
            $model->getSelect()->orderBy(['author_id asc'])
        );
        
    }
}
