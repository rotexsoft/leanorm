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
            $model->getSelect()->where(' author_id = ? ', [$authorId])
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
        $initialData = []; // set it to [], so it can be set again by loadData below
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
        self::assertEquals([], $record->getInitialData());
        
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
        self::assertEquals([], $record1->getInitialData());
    }
    
    public function testThatSaveWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        ////////////////////////////////////////////////////////////////////////
        // New Record without data
        ////////////////////////////////////////////////////////////////////////
        $recordWithNoData = new LeanOrm\Model\Record([], $model);
        self::assertNull($recordWithNoData->save());
        
        ////////////////////////////////////////////////////////////////////////
        // New Record with data
        ////////////////////////////////////////////////////////////////////////
        $newRecordWithData = new LeanOrm\Model\Record(
            [ 
                'name' => 'Author 888', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        self::assertTrue($newRecordWithData->isNew());
        self::assertTrue($newRecordWithData->save());
        self::assertFalse($newRecordWithData->isNew());
        
        // verify that the data was really saved to the DB
        $newlySavedRecordFromDb = $model->fetchOneRecord(
            $model->getSelect()->where(' name = ? ', ['Author 888'])
        );
        self::assertInstanceOf(\LeanOrm\Model\Record::class, $newlySavedRecordFromDb);
        
        ////////////////////////////////////////////////////////////////////////
        // Update Existing Record with new data
        ////////////////////////////////////////////////////////////////////////
        // Fetching an existing record should return a non-new record
        $existingRecord = $model->fetchOneRecord(
            $model->getSelect()->orderBy(['author_id asc'])
        );
        
        self::assertFalse($existingRecord->isNew());
        self::assertTrue(
            $existingRecord->save([ 
                'name' => 'Author 9999', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ])
        );
        self::assertFalse($existingRecord->isNew());
        
        // verify that the data was really saved to the DB
        $updatedExistingRecordFromDb = $model->fetchOneRecord(
            $model->getSelect()->where(' name = ? ', ['Author 9999'])
        );
        self::assertInstanceOf(\LeanOrm\Model\Record::class, $updatedExistingRecordFromDb);
        
        ////////////////////////////////////////////////////////////////////////
        // Save new Record with new data via Model that always returns false
        // on save
        ////////////////////////////////////////////////////////////////////////
        $alwaysFalseOnSaveModel = 
            new class(
                    static::$dsn, 
                    static::$username ?? "", 
                    static::$password ?? "", 
                    [], 'author_id', 'authors'
                ) extends \LeanOrm\Model {
            
                public function insert(array $data_2_insert = []): array|bool {
                    return false;
                }
                public function insertMany(array $rows_of_data_2_insert = []): bool {
                    return false;
                }
            };
        
        $newRecordWithData2 = new LeanOrm\Model\Record(
            [ 
                'name' => 'Author 1999', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $alwaysFalseOnSaveModel
        );
        self::assertTrue($newRecordWithData2->isNew());
        self::assertFalse($newRecordWithData2->save());
        self::assertTrue($newRecordWithData2->isNew()); // still new because not saved
        
        // The record cannot be fetched because the save failed
        self::assertNull(
            $model->fetchOneRecord(
                $model->getSelect()->where(' name = ? ', ['Author 1999'])
            )
        );
            
        ////////////////////////////////////////////////////////////////////////
        // Save existing Record with new data via Model that always returns false
        // on save. 
        // 
        // I can't test this scenario because Model->updateSpecifiedRecord
        // returns $this, so no way for Record->save() to know if the underlying
        // call to Model->updateSpecifiedRecord() did not save.
        ////////////////////////////////////////////////////////////////////////
    }
    
    public function testThatSaveInTransactionWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        $timestamp = date('Y-m-d H:i:s');
        
        ////////////////////////////////////////////////////////////////////////
        // New Record without data
        ////////////////////////////////////////////////////////////////////////
        $recordWithNoData = new LeanOrm\Model\Record([], $model);
        self::assertNull($recordWithNoData->saveInTransaction());
        
        ////////////////////////////////////////////////////////////////////////
        // New Record with data
        ////////////////////////////////////////////////////////////////////////
        $newRecordWithData = new LeanOrm\Model\Record(
            [ 
                'name' => 'Author 888', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $model
        );
        self::assertTrue($newRecordWithData->isNew());
        self::assertTrue($newRecordWithData->saveInTransaction());
        self::assertFalse($newRecordWithData->isNew());
        
        // verify that the data was really saved to the DB
        $newlySavedRecordFromDb = $model->fetchOneRecord(
            $model->getSelect()->where(' name = ? ', ['Author 888'])
        );
        self::assertInstanceOf(\LeanOrm\Model\Record::class, $newlySavedRecordFromDb);
        
        ////////////////////////////////////////////////////////////////////////
        // Update Existing Record with new data
        ////////////////////////////////////////////////////////////////////////
        // Fetching an existing record should return a non-new record
        $existingRecord = $model->fetchOneRecord(
            $model->getSelect()->orderBy(['author_id asc'])
        );
        
        self::assertFalse($existingRecord->isNew());
        self::assertTrue(
            $existingRecord->saveInTransaction([ 
                'name' => 'Author 9999', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ])
        );
        self::assertFalse($existingRecord->isNew());
        
        // verify that the data was really saved to the DB
        $updatedExistingRecordFromDb = $model->fetchOneRecord(
            $model->getSelect()->where(' name = ? ', ['Author 9999'])
        );
        self::assertInstanceOf(\LeanOrm\Model\Record::class, $updatedExistingRecordFromDb);
        
        ////////////////////////////////////////////////////////////////////////
        // Save new Record with new data via Model that always returns false
        // on save
        ////////////////////////////////////////////////////////////////////////
        $alwaysFalseOnSaveModel = 
            new class(
                    static::$dsn, 
                    static::$username ?? "", 
                    static::$password ?? "", 
                    [], 'author_id', 'authors'
                ) extends \LeanOrm\Model {
            
                public function insert(array $data_2_insert = []): array|bool {
                    return false;
                }
                public function insertMany(array $rows_of_data_2_insert = []): bool {
                    return false;
                }
            };
        
        $newRecordWithData2 = new LeanOrm\Model\Record(
            [ 
                'name' => 'Author 1999', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $alwaysFalseOnSaveModel
        );
        self::assertTrue($newRecordWithData2->isNew());
        self::assertFalse($newRecordWithData2->saveInTransaction());
        self::assertTrue($newRecordWithData2->isNew()); // still new because not saved
        
        // The record cannot be fetched because the save failed
        self::assertNull(
            $model->fetchOneRecord(
                $model->getSelect()->where(' name = ? ', ['Author 1999'])
            )
        );
    }
    
    public function testThatSaveInTransactionThrowsException() {

        $this->expectException(\Exception::class);

        $exceptionThrowingOnInsertModel = 
            new class(
                    static::$dsn, 
                    static::$username ?? "", 
                    static::$password ?? "", 
                    [], 'author_id', 'authors'
                ) extends \LeanOrm\Model {

                public function insert(array $data_2_insert = []): array|bool {

                    throw new \Exception('Yabadabadoo');

                    return false;
                }
                public function insertMany(array $rows_of_data_2_insert = []): bool {
                    return false;
                }
            };

        $timestamp = date('Y-m-d H:i:s');
        $newRecordWithData = new \LeanOrm\Model\Record(
            [ 
                'name' => 'Author 1999', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ],
            $exceptionThrowingOnInsertModel
        );

        $newRecordWithData->saveInTransaction(); // will throw exception
    }
    
    public function testThatSetModelWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        
        $postsModel = new LeanOrm\TestObjects\PostsModel(
            static::$dsn, static::$username ?? "", static::$password ?? ""
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
        self::assertSame($record, $record->setModel($postsModel));
        self::assertSame($postsModel, $record->getModel());
    }
    
    public function testThatToArrayWorksAsExpected() {
        
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
        
        $recordAsArray = $record->toArray();
        
        self::assertArrayHasAllKeys(
            $recordAsArray, 
            [
                'data', 
                'non_table_col_and_non_related_data', 
                'initial_data', 
                'related_data', 
                'is_new',
            ]
        );
        self::assertEquals(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ], 
            $recordAsArray['data']
        );
        self::assertEquals(
            [
                'non_existent_col_1' => 'Some Data 1',
                'non_existent_col_2' => 'Some Data 2',
            ], 
            $recordAsArray['non_table_col_and_non_related_data']
        );
        self::assertEquals(
            [
                'author_id' => 888, 
                'name' => 'Author 1', 
                'm_timestamp' => $timestamp, 
                'date_created' => $timestamp,
            ], 
            $recordAsArray['initial_data']
        );
        self::assertEquals(
            [], 
            $recordAsArray['related_data']
        );
        
        self::assertIsBool($recordAsArray['is_new']);
        self::assertTrue($recordAsArray['is_new']);
    }
    
    public function testThatOffsetExistsWorksAsExpected() {
        
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
        
        self::assertTrue($record->offsetExists('name'));
        self::assertTrue($record->offsetExists('non_existent_col_1'));
        self::assertFalse($record->offsetExists('non_existent'));
    }
    
    public function testThatOffsetGetWorksAsExpected() {
        
        $authorsModel = new \LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");

        $emptyRecord = $authorsModel->createNewRecord();
        self::assertNull($emptyRecord->offsetGet('posts'));
        self::assertNull($emptyRecord->offsetGet('name'));
        
        $firstAuthorInTheTable = $authorsModel->fetchOneRecord();
        
        // this is a non-eager loaded relationship
        self::assertInstanceOf(GDAO\Model\CollectionInterface::class, $firstAuthorInTheTable->offsetGet('posts'));
        
        ////////////////////////////////////////////////////////////////////////
        // fetch Author with author_id = 2 & include posts during the fetch
        // fetch first record in the table
        $secondAuthorInTheTable = $authorsModel->fetchOneRecord(
            $authorsModel->getSelect()->where(' author_id =  2 '), ['posts']
        );
        self::assertInstanceOf(GDAO\Model\RecordInterface::class, $secondAuthorInTheTable);
        
        self::assertEquals('user_2', $secondAuthorInTheTable->offsetGet('name'));
        self::assertEquals('2', ''.$secondAuthorInTheTable->offsetGet('author_id'));
        
        // get eager loaded relationship data
        self::assertInstanceOf(GDAO\Model\CollectionInterface::class, $secondAuthorInTheTable->offsetGet('posts'));
        
        $secondAuthorInTheTable->non_table_col = 'a val';
        self::assertEquals('a val', $secondAuthorInTheTable->offsetGet('non_table_col'));
    }
    
    public function testThatOffsetGetThrowsException() {
        
        $this->expectException(\LeanOrm\Model\NoSuchPropertyForRecordException::class);
        $authorsModel = new \LeanOrm\TestObjects\AuthorsModel(
            static::$dsn, static::$username ?? "", static::$password ?? ""
        );
        $emptyRecord = $authorsModel->createNewRecord();
        
        // Not a table col, or relationship name or non-table col that was explicitly set
        $emptyRecord->offsetGet('non_existent_property');
    }
    
    public function testThatOffsetSetWorksAsExpected() {
        
        $authorsModel = new \LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");

        $emptyRecord = $authorsModel->createNewRecord();
        $emptyRecord->offsetSet('posts', $authorsModel->createNewCollection());
        $emptyRecord->offsetSet('name', 'Author Name');
        $emptyRecord->offsetSet('non_table_and_non_relation_name', 'Other data');
        
        self::assertInstanceOf(\GDAO\Model\CollectionInterface::class, $emptyRecord->offsetGet('posts'));
        self::assertCount(0, $emptyRecord->offsetGet('posts'));
        
        self::assertInstanceOf(\GDAO\Model\CollectionInterface::class, $emptyRecord->getRelatedData()['posts']);
        self::assertCount(0, $emptyRecord->getRelatedData()['posts']);
        
        self::assertEquals('Author Name', $emptyRecord->offsetGet('name'));
        self::assertEquals('Other data', $emptyRecord->offsetGet('non_table_and_non_relation_name'));
        
        self::assertEquals('Author Name', $emptyRecord->getData()['name']);
        self::assertEquals('Other data', $emptyRecord->getNonTableColAndNonRelatedData()['non_table_and_non_relation_name']);
    }
    
    public function testThatOffsetUnsetWorksAsExpected() {
        
        $authorsModel = new \LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");

        $emptyRecordOnCreation = $authorsModel->createNewRecord();
        
        $emptyRecordOnCreation->offsetSet('name', 'Author Name');
        $emptyRecordOnCreation->offsetSet('posts', $authorsModel->createNewCollection());
        $emptyRecordOnCreation->offsetSet('non_table_and_non_relation_name', 'Other data');
        
        self::assertTrue($emptyRecordOnCreation->offsetExists('posts'));
        self::assertTrue($emptyRecordOnCreation->offsetExists('name'));
        self::assertTrue($emptyRecordOnCreation->offsetExists('non_table_and_non_relation_name'));
        
        $emptyRecordOnCreation->offsetUnset('posts');
        $emptyRecordOnCreation->offsetUnset('name');
        $emptyRecordOnCreation->offsetUnset('non_table_and_non_relation_name');
        
        self::assertNull($emptyRecordOnCreation->offsetGet('posts'));
        self::assertNull($emptyRecordOnCreation->offsetGet('name'));
        self::assertNull($emptyRecordOnCreation->offsetGet('non_table_and_non_relation_name'));
        
        self::assertEquals([], $emptyRecordOnCreation->getInitialData());
        self::assertEquals(['name'=>null], $emptyRecordOnCreation->getData());
        self::assertEquals(['posts'=>null], $emptyRecordOnCreation->getRelatedData());
        self::assertEquals(['non_table_and_non_relation_name'=>null], $emptyRecordOnCreation->getNonTableColAndNonRelatedData());
        
        ////////////////////////////////////////////////////////////////////////
        $nonEmptyRecordOnCreation = $authorsModel->createNewRecord(['name' => 'Author Name 2']);
        
        self::assertTrue($nonEmptyRecordOnCreation->offsetExists('name'));
        
        $nonEmptyRecordOnCreation->offsetUnset('name');
        self::assertNull($nonEmptyRecordOnCreation->offsetGet('name'));
        
        self::assertArrayHasKey('name', $nonEmptyRecordOnCreation->getInitialData());
        self::assertNull($nonEmptyRecordOnCreation->getInitialData()['name']);
        
        self::assertEquals(['name'=>null], $nonEmptyRecordOnCreation->getData());
        self::assertEquals([], $nonEmptyRecordOnCreation->getRelatedData());
        self::assertEquals([], $nonEmptyRecordOnCreation->getNonTableColAndNonRelatedData());
    }
    
    public function testThatCountWorksAsExpected() {
        
        $authorsModel = new \LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");

        $emptyRecord = $authorsModel->createNewRecord();
        
        // count returns the number of table col fields that have been set
        
        self::assertEquals(0, $emptyRecord->count());
        
        // set a table col
        $emptyRecord->offsetSet('name', 'Author Name');
        self::assertEquals(1, $emptyRecord->count());
        
        // set a relation
        $emptyRecord->offsetSet('posts', $authorsModel->createNewCollection());
        self::assertEquals(1, $emptyRecord->count());
        
        // set a non-table col & non-relation
        $emptyRecord->offsetSet('non_table_and_non_relation_name', 'Other data');
        self::assertEquals(1, $emptyRecord->count());
        
        // set another table col
        $emptyRecord->offsetSet('author_id', 777);
        self::assertEquals(2, $emptyRecord->count());
    }
    
    public function testThatGetIteratorWorksAsExpected() {
        
        $model = new \LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
        );
        $record = $model->createNewRecord();
        
        self::assertIsNotArray($record);
        self::assertIsIterable($record);
        self::assertInstanceOf(\Traversable::class, $record);
        self::assertInstanceOf(\Iterator::class, $record->getIterator());
    }
    
    
    public function testThat__GetWorksAsExpected() {
        
        $authorsModel = new \LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");

        $emptyRecord = $authorsModel->createNewRecord();
        self::assertNull($emptyRecord->__get('posts'));
        self::assertNull($emptyRecord->__get('name'));
        
        $firstAuthorInTheTable = $authorsModel->fetchOneRecord();
        
        // this is a non-eager loaded relationship
        self::assertInstanceOf(GDAO\Model\CollectionInterface::class, $firstAuthorInTheTable->__get('posts'));
        
        ////////////////////////////////////////////////////////////////////////
        // fetch Author with author_id = 2 & include posts during the fetch
        // fetch first record in the table
        $secondAuthorInTheTable = $authorsModel->fetchOneRecord(
            $authorsModel->getSelect()->where(' author_id =  2 '), ['posts']
        );
        self::assertInstanceOf(GDAO\Model\RecordInterface::class, $secondAuthorInTheTable);
        
        self::assertEquals('user_2', $secondAuthorInTheTable->__get('name'));
        self::assertEquals('2', ''.$secondAuthorInTheTable->__get('author_id'));
        
        // get eager loaded relationship data
        self::assertInstanceOf(GDAO\Model\CollectionInterface::class, $secondAuthorInTheTable->__get('posts'));
        
        $secondAuthorInTheTable->non_table_col = 'a val';
        self::assertEquals('a val', $secondAuthorInTheTable->__get('non_table_col'));
    }
    
    public function testThat__GetThrowsException() {
        
        $this->expectException(\LeanOrm\Model\NoSuchPropertyForRecordException::class);
        $authorsModel = new \LeanOrm\TestObjects\AuthorsModel(
            static::$dsn, static::$username ?? "", static::$password ?? ""
        );
        $emptyRecord = $authorsModel->createNewRecord();
        
        // Not a table col, or relationship name or non-table col that was explicitly set
        $emptyRecord->__get('non_existent_property');
    }
    
    public function testThat__issetWorksAsExpected() {
        
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
        
        self::assertTrue($record->__isset('name'));
        self::assertTrue($record->__isset('non_existent_col_1'));
        self::assertFalse($record->__isset('non_existent'));
    }
    
    public function testThat__setWorksAsExpected() {
        
        $authorsModel = new \LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");

        $emptyRecord = $authorsModel->createNewRecord();
        $emptyRecord->__set('posts', $authorsModel->createNewCollection());
        $emptyRecord->__set('name', 'Author Name');
        $emptyRecord->__set('non_table_and_non_relation_name', 'Other data');
        
        self::assertInstanceOf(\GDAO\Model\CollectionInterface::class, $emptyRecord->offsetGet('posts'));
        self::assertCount(0, $emptyRecord->offsetGet('posts'));
        
        self::assertInstanceOf(\GDAO\Model\CollectionInterface::class, $emptyRecord->getRelatedData()['posts']);
        self::assertCount(0, $emptyRecord->getRelatedData()['posts']);
        
        self::assertEquals('Author Name', $emptyRecord->offsetGet('name'));
        self::assertEquals('Other data', $emptyRecord->offsetGet('non_table_and_non_relation_name'));
        
        self::assertEquals('Author Name', $emptyRecord->getData()['name']);
        self::assertEquals('Other data', $emptyRecord->getNonTableColAndNonRelatedData()['non_table_and_non_relation_name']);
    }
    
    public function testThat__unsetWorksAsExpected() {
        
        $authorsModel = new \LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");

        $emptyRecordOnCreation = $authorsModel->createNewRecord();
        
        $emptyRecordOnCreation->offsetSet('name', 'Author Name');
        $emptyRecordOnCreation->offsetSet('posts', $authorsModel->createNewCollection());
        $emptyRecordOnCreation->offsetSet('non_table_and_non_relation_name', 'Other data');
        
        self::assertTrue($emptyRecordOnCreation->offsetExists('posts'));
        self::assertTrue($emptyRecordOnCreation->offsetExists('name'));
        self::assertTrue($emptyRecordOnCreation->offsetExists('non_table_and_non_relation_name'));
        
        $emptyRecordOnCreation->__unset('posts');
        $emptyRecordOnCreation->__unset('name');
        $emptyRecordOnCreation->__unset('non_table_and_non_relation_name');
        
        self::assertNull($emptyRecordOnCreation->offsetGet('posts'));
        self::assertNull($emptyRecordOnCreation->offsetGet('name'));
        self::assertNull($emptyRecordOnCreation->offsetGet('non_table_and_non_relation_name'));
        
        self::assertEquals([], $emptyRecordOnCreation->getInitialData());
        self::assertEquals(['name'=>null], $emptyRecordOnCreation->getData());
        self::assertEquals(['posts'=>null], $emptyRecordOnCreation->getRelatedData());
        self::assertEquals(['non_table_and_non_relation_name'=>null], $emptyRecordOnCreation->getNonTableColAndNonRelatedData());
        
        ////////////////////////////////////////////////////////////////////////
        $nonEmptyRecordOnCreation = $authorsModel->createNewRecord(['name' => 'Author Name 2']);
        
        self::assertTrue($nonEmptyRecordOnCreation->offsetExists('name'));
        
        $nonEmptyRecordOnCreation->__unset('name');
        self::assertNull($nonEmptyRecordOnCreation->offsetGet('name'));
        
        self::assertArrayHasKey('name', $nonEmptyRecordOnCreation->getInitialData());
        self::assertNull($nonEmptyRecordOnCreation->getInitialData()['name']);
        
        self::assertEquals(['name'=>null], $nonEmptyRecordOnCreation->getData());
        self::assertEquals([], $nonEmptyRecordOnCreation->getRelatedData());
        self::assertEquals([], $nonEmptyRecordOnCreation->getNonTableColAndNonRelatedData());
    }
    
    public function testThat__toStringWorksAsExpected() {
        
        $model = new \LeanOrm\TestObjects\AuthorsModel(
            static::$dsn, static::$username ?? "", static::$password ?? ""
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
        
        $recordAsArray = $record->toArray();

        $recordToStringAsArray = eval(' return ' . $record->__toString() . ';');
                
        self::assertEquals($recordAsArray, $recordToStringAsArray);
    }
}
