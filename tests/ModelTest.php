<?php
use Atlas\Pdo\Connection;
use Aura\SqlQuery\QueryFactory;
use VersatileCollections\GenericCollection;

/**
 * Description of ModelTest
 *
 * @author Rotimi Adegbamigbe
 */
class ModelTest extends \PHPUnit\Framework\TestCase {
    
    /**
     * @var \LeanOrm\Model[]
     */
    protected array $testModelObjects = [];
    
    protected static string $dsn;
    protected static ?string $username = null;
    protected static ?string $password = null;
    protected static Connection $atlasPdo;
    protected static QueryFactory $auraQueryFactory;
    
    protected const POST_POST_IDS = [
        '1',
        '2',
        '3',
        '4',
    ];
    protected const POST_AUTHOR_IDS = [
        '1',
        '2',
        '1',
        '2',
    ];
    protected const POST_TITLES = [
        'Post 1',
        'Post 2',
        'Post 3',
        'Post 4',
    ];
    protected const POST_BODIES = [
        'Post Body 1',
        'Post Body 2',
        'Post Body 3',
        'Post Body 4',
    ];
    
    public static function setUpBeforeClass(): void {
        
        parent::setUpBeforeClass();
        $dsn = getenv('LEANORM_PDO_DSN');
        $username = getenv('LEANORM_PDO_USERNAME');
        $password = getenv('LEANORM_PDO_PASSWORD');
        $connection = null;
        
        if(is_string($dsn) && strlen($dsn) > 0) {
            
            static::$dsn = $dsn;
            
            if(
                is_string($username) && strlen($username) > 0
                && is_string($password) && strlen($password) > 0
            ) {
                static::$password = $password;
                static::$username = $username;
                $connection = Connection::new($dsn, $username, $password);
                
            } else { $connection = Connection::new($dsn); }
            
        } else {
            
            $sqliteFile = __DIR__.DIRECTORY_SEPARATOR .'DbFiles'.DIRECTORY_SEPARATOR .'blog.sqlite';

            // delete previously existing sqlite db file
            if(file_exists($sqliteFile)) { unlink($sqliteFile); }
            
            //create sqlite file & create $dsn connection for it
            $dsn = "sqlite:{$sqliteFile}";
            
            try {
                $connection = Connection::new($dsn);
                static::$dsn = $dsn;
                
            } catch (\Exception $exc) {
                
                $connection = Connection::new('sqlite::memory:'); // default to sqlite memory db
                static::$dsn = 'sqlite::memory:';
            }
        }
        
        $schemaCreatorAndSeeder = new TestSchemaCreatorAndSeeder($connection);
        $schemaCreatorAndSeeder->createTables();
        $schemaCreatorAndSeeder->populateTables();
        
        $driverName = $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        static::$atlasPdo = $connection;
        static::$auraQueryFactory = (new QueryFactory($driverName));
    }
    
    public static function tearDownAfterClass(): void {
        
        parent::tearDownAfterClass();
    }
    
    protected function setUp(): void {
        
        parent::setUp();

        $this->testModelObjects['authors_with_specialized_collection_and_record'] = 
            (
                new \ModelForTestingPublicAndProtectedMethods(
                    static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
                )
            )
            ->hasMany(
                'posts', 
                'author_id', 
                'posts', 
                'author_id', 
                'post_id', 
                \ModelForTestingPublicAndProtectedMethods::class, 
                \RecordForTestingPublicAndProtectedMethods::class, 
                \CollectionForTestingPublicAndProtectedMethods::class, 
                null
            )
            ->setCollectionClassName(\CollectionForTestingPublicAndProtectedMethods::class)
            ->setRecordClassName(\RecordForTestingPublicAndProtectedMethods::class)
            ;
    }
    
    public function testThatConstructorWithNoArgsWorksAsExpected() {
        
        $this->expectException(\PDOException::class);

        $model = new LeanOrm\Model();
    }
    
    public function testThatConstructorWithNoPrimaryColWorksAsExpected() {
        
        $this->expectException(\GDAO\ModelPrimaryColNameNotSetDuringConstructionException::class);

        $model = new LeanOrm\Model('sqlite::memory:');
    }
    
    public function testThatConstructorWithNoTableNameWorksAsExpected() {
        
        $this->expectException(\GDAO\ModelTableNameNotSetDuringConstructionException::class);

        $model = new LeanOrm\Model('sqlite::memory:','','',[],'id','');
    }
    
    public function testThatConstructorWorksAsExpected() {
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        
        $this->assertTrue($model->getDsn() === static::$dsn);
        $this->assertTrue($model->getUsername() === '');
        $this->assertTrue($model->getPasswd() === '');
        $this->assertTrue($model->getPdoDriverOpts() === []);
        $this->assertTrue($model->getPrimaryCol() === 'author_id');
        $this->assertTrue($model->getTableName() === 'authors');
        
        $this->assertContains('author_id', $model->getTableColNames());
        $this->assertContains('name', $model->getTableColNames());
        $this->assertContains('m_timestamp', $model->getTableColNames());
        $this->assertContains('date_created', $model->getTableColNames());
        
        // Test that the schema query gets & sets the primary key col
        // when an empty primary key value is passed to the constructor
        $model = new LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [PDO::ATTR_PERSISTENT => true], '', 'authors'
        );
        $this->assertTrue($model->getPrimaryCol() === 'author_id');
        $this->assertTrue($model->getPdoDriverOpts() === [PDO::ATTR_PERSISTENT => true]);
    }

    public function testCreateNewCollection() {
        
        $modelWithMockCollAndRec = $this->testModelObjects['authors_with_specialized_collection_and_record'];
        $nonGenericCollection = $modelWithMockCollAndRec->createNewCollection();
        
        //exact class
        $this->assertEquals(\CollectionForTestingPublicAndProtectedMethods::class, get_class($nonGenericCollection));
        
        //has the right parent class
        $this->assertInstanceOf(\LeanOrm\Model\Collection::class, $nonGenericCollection);
        $this->assertInstanceOf(\GDAO\Model\CollectionInterface::class, $nonGenericCollection);
        
        //exact class test
        $modelWithAuthorCollAndRec = new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $coll_generic = $modelWithAuthorCollAndRec->createNewCollection();
        $this->assertEquals(LeanOrm\TestObjects\AuthorsCollection::class, get_class($coll_generic));
        
        // Test creating collection with some records
        $collection1WithRecords = 
            $modelWithAuthorCollAndRec
                ->createNewCollection(...[$modelWithAuthorCollAndRec->createNewRecord(), $modelWithAuthorCollAndRec->createNewRecord(),]);
        
        $collection2WithRecords = 
            $modelWithMockCollAndRec
                ->createNewCollection(...[
                    $modelWithMockCollAndRec->createNewRecord(),
                    $modelWithMockCollAndRec->createNewRecord(),
                    $modelWithMockCollAndRec->createNewRecord(),
                ]);
        
        $this->assertTrue($collection1WithRecords->count() === 2);
        $this->assertTrue($collection2WithRecords->count() === 3);
        
        //generic model
        $genericModel = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'author_id','authors');
        $genericModel->setCollectionClassName('');
        $this->assertEquals(\LeanOrm\Model\Collection::class, get_class($genericModel->createNewCollection()));
    }

    public function testCreateNewRecord() {
        
        $modelWithMockCollAndRec =
            $this->testModelObjects['authors_with_specialized_collection_and_record'];
        $specializedRecord = $modelWithMockCollAndRec->createNewRecord();
        
        //exact class
        $this->assertEquals(
            \RecordForTestingPublicAndProtectedMethods::class, 
            get_class($specializedRecord)
        );
        
        //has the right parent class
        $this->assertInstanceOf(\LeanOrm\Model\Record::class, $specializedRecord);
        $this->assertInstanceOf(\GDAO\Model\RecordInterface::class, $specializedRecord);
        
        ////////////////////////////////////////////////////////////////////////
        // exact class test
        $modelWithLeanormCollAndRec = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        $modelWithLeanormCollAndRec->setRecordClassName('');
        $leanOrmRecord = $modelWithLeanormCollAndRec->createNewRecord();
        $this->assertEquals(\LeanOrm\Model\Record::class, get_class($leanOrmRecord));
        
        // exact class test
        $modelWithAuthorCollAndRec =  new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $this->assertEquals(LeanOrm\TestObjects\AuthorRecord::class, get_class($modelWithAuthorCollAndRec->createNewRecord()));
    }
    
    public function testThatBelongsToThrowsExceptionWhenRelationNameCollidesWithColumnName() {
        
        $this->expectException(\GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        // relation name with the same name as p key column
        $model->belongsTo('author_id', '', '', '', '');
    }
    
    public function testThatBelongsToWorksAsExpected() {

        $postsModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        $postsModel->belongsTo(
            'author', 'author_id', 'authors', 'author_id', 'author_id', \LeanOrm\Model::class,
            \LeanOrm\Model\Record::class, \LeanOrm\Model\Collection::class, null
        );
        $this->assertTrue($postsModel->getRelationNames() === ['author']);
        $relations = $postsModel->getRelations();
        $this->assertArrayHasKey('author', $relations);
        $this->assertArrayHasKey('relation_type', $relations['author']);
        $this->assertArrayHasKey('foreign_key_col_in_my_table', $relations['author']);
        $this->assertArrayHasKey('foreign_table', $relations['author']);
        $this->assertArrayHasKey('foreign_key_col_in_foreign_table', $relations['author']);
        $this->assertArrayHasKey('primary_key_col_in_foreign_table', $relations['author']);
        $this->assertArrayHasKey('foreign_models_class_name', $relations['author']);
        $this->assertArrayHasKey('foreign_models_record_class_name', $relations['author']);
        $this->assertArrayHasKey('foreign_models_collection_class_name', $relations['author']);
        $this->assertArrayHasKey('sql_query_modifier', $relations['author']);
        
        $this->assertTrue($relations['author']['relation_type'] === \GDAO\Model::RELATION_TYPE_BELONGS_TO);
        $this->assertTrue($relations['author']['foreign_key_col_in_my_table'] === 'author_id');
        $this->assertTrue($relations['author']['foreign_table'] === 'authors');
        $this->assertTrue($relations['author']['foreign_key_col_in_foreign_table'] === 'author_id');
        $this->assertTrue($relations['author']['primary_key_col_in_foreign_table'] === 'author_id');
        $this->assertTrue($relations['author']['foreign_models_class_name'] === \LeanOrm\Model::class);
        $this->assertTrue($relations['author']['foreign_models_record_class_name'] === \LeanOrm\Model\Record::class);
        $this->assertTrue($relations['author']['foreign_models_collection_class_name'] === \LeanOrm\Model\Collection::class);
        $this->assertTrue($relations['author']['sql_query_modifier'] === null);
        
        $callback = fn(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select => $selectObj;
        $postsModel->belongsTo(
            'author2', 'author_id', 'authors', 'author_id', 'author_id', \LeanOrm\Model::class,
            \LeanOrm\Model\Record::class, \LeanOrm\Model\Collection::class, 
            $callback
        );
        $this->assertTrue($postsModel->getRelationNames() === ['author', 'author2']);
        $this->assertTrue($postsModel->getRelations()['author2']['sql_query_modifier'] === $callback);
    }
    
    public function testThatCanLogQueriesWorksAsExpected() {
        
        $postsModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        
        $this->assertFalse($postsModel->canLogQueries());
        $this->assertTrue($postsModel->enableQueryLogging()->canLogQueries());
    }
    
    public function testThatDisableQueryLoggingWorksAsExpected() {
        
        $postsModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        
        $this->assertFalse($postsModel->canLogQueries());
        $this->assertSame($postsModel->disableQueryLogging(), $postsModel);
        $this->assertFalse($postsModel->canLogQueries());
    }
    
    public function testThatEnableQueryLoggingWorksAsExpected() {
        
        $postsModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        
        $this->assertFalse($postsModel->canLogQueries());
        $this->assertSame($postsModel->enableQueryLogging(), $postsModel);
        $this->assertTrue($postsModel->canLogQueries());
    }
    
    public function testThatDeleteMatchingDbTableRowsWorksAsExpected() {
        
        $keyValueModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id','key_value');
        $recordsToInsert = [
            ['key'=> 'key 1', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+2 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+1 minutes"))],
            ['key'=> 'key 2', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+3 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+2 minutes"))],
            ['key'=> 'key 3', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+4 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+3 minutes"))],
        ];
        
        $this->insertDataIntoTable('key_value', $recordsToInsert[0]);
        $this->insertDataIntoTable('key_value', $recordsToInsert[1]);
        $this->insertDataIntoTable('key_value', $recordsToInsert[2]);
        
        // only one matching record
        $this->assertTrue( $keyValueModel->deleteMatchingDbTableRows(['key'=> 'key 1']) === 1 );
        
        // two matching records
        $this->assertTrue( $keyValueModel->deleteMatchingDbTableRows(['key'=> ['key 2', 'key 3']]) === 2 );
        
        // no matching record
        $this->assertTrue( $keyValueModel->deleteMatchingDbTableRows(['key'=> 'key 55']) === 0 );
    }
    
    public function testThatDeleteSpecifiedRecordWorksAsExpected() {
        
        $keyValueModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id','key_value');
        $recordsToInsert = [
            ['key'=> 'key 1', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+2 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+1 minutes"))],
            ['key'=> 'key 2', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+3 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+2 minutes"))],
            ['key'=> 'key 3', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+4 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+3 minutes"))],
        ];
        
        // Deleting a record that has never been saved to the DB
        $this->assertNull( $keyValueModel->deleteSpecifiedRecord($keyValueModel->createNewRecord()));
        $this->assertNull( $keyValueModel->deleteSpecifiedRecord($keyValueModel->createNewRecord($recordsToInsert[0])));
        
        $this->insertDataIntoTable('key_value', $recordsToInsert[0]);
        $this->insertDataIntoTable('key_value', $recordsToInsert[1]);
        $this->insertDataIntoTable('key_value', $recordsToInsert[2]);

        // see \TestSchemaCreatorAndSeeder::populateTables();
        // [ 'author_id'=> 1, 'name'=> 'user_1']
        $aRecord = $keyValueModel->fetchOneRecord($keyValueModel->getSelect()->where('key = ? ', 'key 1'));
        $this->assertFalse($aRecord->isNew());
        $this->assertTrue($keyValueModel->deleteSpecifiedRecord($aRecord));
        // record is new after being deleted
        $this->assertTrue($aRecord->isNew());
    }
    
    public function testThatDeleteSpecifiedRecordThrowsExceptionForReadOnlyRecords()  {
        
        $this->expectException(\LeanOrm\CantDeleteReadOnlyRecordFromDBException::class);
        
        $authorsModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        
        $authorsModel->setRecordClassName(LeanOrm\Model\ReadOnlyRecord::class);
        
        // should throw exception
        $authorsModel->deleteSpecifiedRecord($authorsModel->createNewRecord());
    }
    
    public function testThatFetchWorksAsExpected() {
        
        $authorsModel = new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        //empty array with no ids for fetch returns empty array
        $this->assertTrue($authorsModel->fetch([]) === []);
         
        //empty array with no ids for fetch returns empty collection
        $potentiallyEmptyCollection = $authorsModel->fetch([], null, [], false, true);
        $this->assertTrue($potentiallyEmptyCollection instanceof \LeanOrm\TestObjects\AuthorsCollection);
        $this->assertTrue($potentiallyEmptyCollection->count() === 0);
        
        // fetch rows as arrays into an array not keyed on primary key
        $fiveRecords = $authorsModel->fetch([1,2,3,4,5]);
        
        // fetch rows as arrays into an array keyed on primary key
        $fiveRecords2 = $authorsModel->fetch([1,2,3,4,5], null, [], false, false, true);
        
        // test keyed on PK
        $this->assertTrue(\array_keys($fiveRecords2) === [1, 2, 3, 4, 5]);

        $this->assertTrue(is_array($fiveRecords));
        $this->assertTrue(count($fiveRecords) === 5);
        
        $nameColumnValues = ['user_1', 'user_2', 'user_3', 'user_4', 'user_5'];
        
        foreach ($fiveRecords as $record) { $this->assertContains($record["name"], $nameColumnValues); }
        foreach ($fiveRecords2 as $record) { $this->assertContains($record["name"], $nameColumnValues); }
        
        // fetch rows as records into a collection not keyed on primary key
        $fiveRecordsCollection = $authorsModel->fetch([1,2,3,4,5], null, [], false, true);
        
        // fetch rows as records into a collection keyed on primary key
        $fiveRecords2Collection = $authorsModel->fetch([1,2,3,4,5], null, [], false, true, true);

        $this->assertTrue($fiveRecordsCollection instanceof \LeanOrm\TestObjects\AuthorsCollection);
        $this->assertTrue($fiveRecordsCollection->count() === 5);
        
        $this->assertTrue($fiveRecords2Collection instanceof \LeanOrm\TestObjects\AuthorsCollection);
        $this->assertTrue($fiveRecords2Collection->count() === 5);
        // test keyed on PK
        $this->assertTrue($fiveRecords2Collection->getKeys() === [1, 2, 3, 4, 5]);

        foreach ($fiveRecordsCollection as $record) { $this->assertContains($record["name"], $nameColumnValues); }
        foreach ($fiveRecords2Collection as $record) { $this->assertContains($record["name"], $nameColumnValues); }
        
        // fetch rows as records into an array not keyed on primary key
        $fiveRecordsArrayOfRecords = $authorsModel->fetch([1,2,3,4,5], null, [], true, false);
        
        // fetch rows as records into an array keyed on primary key
        $fiveRecords2ArrayOfRecords = $authorsModel->fetch([1,2,3,4,5], null, [], true, false, true);
        
        $this->assertTrue(\is_array($fiveRecordsArrayOfRecords ));
        $this->assertTrue(\count($fiveRecordsArrayOfRecords) === 5);
        
        $this->assertTrue(\is_array($fiveRecords2ArrayOfRecords));
        $this->assertTrue(\count($fiveRecords2ArrayOfRecords) === 5);
        // test keyed on PK
        $this->assertTrue(\array_keys($fiveRecords2ArrayOfRecords) === [1, 2, 3, 4, 5]);
        
        foreach ($fiveRecordsArrayOfRecords as $record) {

            $this->assertContains($record["name"], $nameColumnValues); 
            $this->assertTrue($record instanceof \LeanOrm\TestObjects\AuthorRecord);
        }
        
        foreach ($fiveRecords2ArrayOfRecords as $record) {
            
            $this->assertContains($record["name"], $nameColumnValues); 
            $this->assertTrue($record instanceof \LeanOrm\TestObjects\AuthorRecord);
        }
        
        ////////////////////////////////////////////////////////////////////////
        //test relationship inclusion & the use of a query object
        // fetch rows as arrays into an array
        $threeRecordsArrayOfRecords = $authorsModel->fetch(
            [1,2,3,4,5], $authorsModel->getSelect()->where(' author_id <= 3 '), 
            ['posts'], false, false
        );
        
        $this->assertTrue(is_array($threeRecordsArrayOfRecords));
        $this->assertTrue(count($threeRecordsArrayOfRecords) === 3);
        $this->assertTrue(array_column($threeRecordsArrayOfRecords, 'name') === ['user_1', 'user_2', 'user_3']);
        
        foreach($threeRecordsArrayOfRecords as $authorRecord) {
            
            $this->assertTrue(is_array($authorRecord['posts']));
            
            foreach($authorRecord['posts'] as $post) {
                
                $this->assertTrue($authorRecord['author_id'] === $post['author_id']);
                $this->assertArrayHasKey('post_id', $post);
                $this->assertArrayHasKey('title', $post);
                $this->assertArrayHasKey('body', $post);
                $this->assertArrayHasKey('m_timestamp', $post);
                $this->assertArrayHasKey('date_created', $post);
            }
        }
        
        ////////////////////////////////////////////////////////////////////////
        // test relationship inclusion & the use of a query object
        // fetch rows as records into a collection
        $threeRecordsCollectionOfRecords = $authorsModel->fetch(
            [1,2,3,4,5], $authorsModel->getSelect()->where(' author_id <= 3 '), 
            ['posts'], false, true
        );

        $this->assertTrue($threeRecordsCollectionOfRecords instanceof \LeanOrm\TestObjects\AuthorsCollection);
        $this->assertTrue($threeRecordsCollectionOfRecords->count() === 3);
        $this->assertTrue($threeRecordsCollectionOfRecords->getColVals('name') === ['user_1', 'user_2', 'user_3']);
        
        foreach($threeRecordsCollectionOfRecords as $authorRecord) {
            
            $this->assertTrue($authorRecord instanceof \LeanOrm\TestObjects\AuthorRecord);
            $this->assertTrue($authorRecord->posts instanceof LeanOrm\TestObjects\PostsCollection);
            $this->assertTrue($authorRecord['posts'] instanceof LeanOrm\TestObjects\PostsCollection);
            
            foreach($authorRecord['posts'] as $post) {
                
                $this->assertTrue($post instanceof \LeanOrm\TestObjects\PostRecord);
                $this->assertTrue($authorRecord['author_id'] === $post['author_id']);
                $this->assertArrayHasKey('post_id', $post->getData());
                $this->assertArrayHasKey('title', $post->getData());
                $this->assertArrayHasKey('body', $post->getData());
                $this->assertArrayHasKey('m_timestamp', $post->getData());
                $this->assertArrayHasKey('date_created', $post->getData());
            }
        }
        
        // Test with empty table
        $emptyModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        
        $expectedEmptyCollection = $emptyModel->fetch([1,2,3], null, [], true, true);
        $this->assertInstanceOf(GDAO\Model\CollectionInterface::class, $expectedEmptyCollection);
        $this->assertCount(0, $expectedEmptyCollection);
        
        $expectedEmptyArray = $emptyModel->fetch([1,2,3], null, [], false, false);
        $this->assertIsArray($expectedEmptyArray);
        $this->assertCount(0, $expectedEmptyArray);
    }
    
    public function testThatFetchColWorksAsExpected() {
        
        $tagsModel = new LeanOrm\TestObjects\TagsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        // return all the values under the first column in the table which is 
        // the tag_id column 
        $cols = $tagsModel->fetchCol();
        $this->assertEquals(['1', '2', '3', '4'], $cols);
        
        $cols2 = $tagsModel->fetchCol(
                $tagsModel->getSelect()->cols(['name'])
            );
        $this->assertEquals(['tag_1', 'tag_2', 'tag_3', 'tag_4'], $cols2);
        
        $cols3 = $tagsModel->fetchCol(
                $tagsModel->getSelect()->cols(['name'])->where(' tag_id < 3 ')
            );
        $this->assertEquals(['tag_1', 'tag_2'], $cols3);
        
        // Test with empty table
        $emptyModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        $this->assertTrue($emptyModel->fetchCol() === []);
    }
    
    public function testThatFetchOneRecordWorksAsExpected() {
        
        $authorsModel = new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        ////////////////////////////////////////////////////////////////////////
        // fetch first record in the table
        $firstAuthorInTheTable = $authorsModel->fetchOneRecord();
        $this->assertInstanceOf(GDAO\Model\RecordInterface::class, $firstAuthorInTheTable);
        $this->assertArrayHasKey('author_id', $firstAuthorInTheTable->getData());
        $this->assertArrayHasKey('name', $firstAuthorInTheTable->getData());
        $this->assertArrayHasKey('m_timestamp', $firstAuthorInTheTable->getData());
        $this->assertArrayHasKey('date_created', $firstAuthorInTheTable->getData());
        
        // test that lazy loaded relationship data works from a fetch without
        // relations to include supplied
        $this->assertInstanceOf(GDAO\Model\CollectionInterface::class, $firstAuthorInTheTable->posts);
        $this->assertCount(2, $firstAuthorInTheTable->posts);
        $this->assertEquals(['1', '3'], $firstAuthorInTheTable->posts->getColVals('post_id'));
        $this->assertEquals(['1', '1'], $firstAuthorInTheTable->posts->getColVals('author_id'));
        $this->assertEquals(['Post 1', 'Post 3'], $firstAuthorInTheTable->posts->getColVals('title'));
        $this->assertEquals(['Post Body 1', 'Post Body 3'], $firstAuthorInTheTable->posts->getColVals('body'));
        
        ////////////////////////////////////////////////////////////////////////
        // fetch Author with author_id = 2 & include posts during the fetch
        // fetch first record in the table
        $secondAuthorInTheTable = $authorsModel->fetchOneRecord(
            $authorsModel->getSelect()->where(' author_id =  2 '), ['posts']
        );
        $this->assertInstanceOf(GDAO\Model\RecordInterface::class, $secondAuthorInTheTable);
        $this->assertArrayHasKey('author_id', $secondAuthorInTheTable->getData());
        $this->assertArrayHasKey('name', $secondAuthorInTheTable->getData());
        $this->assertArrayHasKey('m_timestamp', $secondAuthorInTheTable->getData());
        $this->assertArrayHasKey('date_created', $secondAuthorInTheTable->getData());
        
        // test that eager loaded relationship data works
        $this->assertInstanceOf(GDAO\Model\CollectionInterface::class, $secondAuthorInTheTable->posts);
        $this->assertCount(2, $secondAuthorInTheTable->posts);
        $this->assertEquals(['2', '4'], $secondAuthorInTheTable->posts->getColVals('post_id'));
        $this->assertEquals(['2', '2'], $secondAuthorInTheTable->posts->getColVals('author_id'));
        $this->assertEquals(['Post 2', 'Post 4'], $secondAuthorInTheTable->posts->getColVals('title'));
        $this->assertEquals(['Post Body 2', 'Post Body 4'], $secondAuthorInTheTable->posts->getColVals('body'));
        
        ///////////////////////////////////////////////////////////////////////
        // Test that record not in db returns false
        $authorNotInTheTable = $authorsModel->fetchOneRecord(
            $authorsModel->getSelect()->where(' author_id =  777 '), ['posts']
        );
        $this->assertNull($authorNotInTheTable);
        
        $author2NotInTheTable = $authorsModel->fetchOneRecord(
            $authorsModel->getSelect()->where(' author_id =  77 ')
        );
        $this->assertNull($author2NotInTheTable);
        
        /////////////////////////////
        // Querying an empty table
        $emptyModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        
        $this->assertNull($emptyModel->fetchOneRecord());
    }
    
    public function testThatFetchPairsWorksAsExpected() {
        
        $tagsModel = new LeanOrm\TestObjects\TagsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $pairsOfFirstTwoColumnsTagIdAndName = 
            [1 => 'tag_1', 2 => 'tag_2', 3 => 'tag_3', 4 => 'tag_4'];
        
        $this->assertEquals(
            $pairsOfFirstTwoColumnsTagIdAndName, 
            $tagsModel->fetchPairs()
        );
        
        $pairsOfTwoColumnsNameAndTagId = 
            ['tag_1' => '1', 'tag_2' => '2', 'tag_3' => '3', 'tag_4' => '4'];
        
        $this->assertEquals(
            $pairsOfTwoColumnsNameAndTagId, 
            $tagsModel->fetchPairs(
                $tagsModel->getSelect()->cols(['name', 'tag_id'])
            )
        );
        
        // Query that matches no db rows
        $this->assertEquals(
            [], 
            $tagsModel->fetchPairs(
                $tagsModel->getSelect()->cols(['name', 'tag_id'])->where(' tag_id > 777 ')
            )
        );
        
        // Test with empty table
        $emptyModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        $this->assertEquals([], $emptyModel->fetchPairs());
    }
    
    public function testThatFetchRecordsIntoArrayWorksAsExpected() {
        
        // Test with empty table
        $emptyModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        $this->assertEquals([], $emptyModel->fetchRecordsIntoArray());
        
        ////////////////////////////////////////////////////////////////////////
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $allPosts = $postsModel->fetchRecordsIntoArray();
        $this->assertIsArray($allPosts);
        $this->assertCount(4, $allPosts);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPosts);
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            GenericCollection::makeNew($allPosts)->column('post_id')->toArray()
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            GenericCollection::makeNew($allPosts)->column('author_id')->toArray()
        );
        $this->assertEquals(
            static::POST_TITLES,
            GenericCollection::makeNew($allPosts)->column('title')->toArray()
        );
        $this->assertEquals(
            static::POST_BODIES,
            GenericCollection::makeNew($allPosts)->column('body')->toArray()
        );
        unset($allPosts);
        
        ////////////////////////////////////////////////////////////////////////////
        // Test Query constraint. Filter for first two posts i.e. whose post_id < 3
        ////////////////////////////////////////////////////////////////////////////
        $firstTwoPosts = $postsModel->fetchRecordsIntoArray(
            $postsModel->getSelect()->where(' post_id < 3 ')   
        );
        
        $this->assertIsArray($firstTwoPosts);
        $this->assertCount(2, $firstTwoPosts);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $firstTwoPosts);
        
        // verify that the records contain expected data
        $this->assertEquals(
            ['1', '2'],
            GenericCollection::makeNew($firstTwoPosts)->column('post_id')->toArray()
        );
        $this->assertEquals(
            ['1', '2'],
            GenericCollection::makeNew($firstTwoPosts)->column('author_id')->toArray()
        );
        $this->assertEquals(
            ['Post 1', 'Post 2'],
            GenericCollection::makeNew($firstTwoPosts)->column('title')->toArray()
        );
        $this->assertEquals(
            ['Post Body 1', 'Post Body 2'],
            GenericCollection::makeNew($firstTwoPosts)->column('body')->toArray()
        );
        unset($firstTwoPosts);
        
        ////////////////////////////////////////////////////////////////////////
        // Fetch with all relateds
        ////////////////////////////////////////////////////////////////////////
        $allPostsWithAllRelateds = 
            $postsModel->fetchRecordsIntoArray(
                null, [ 'author', 'comments', 'summary', 'posts_tags', 'tags' ]
            );
        $this->assertIsArray($allPostsWithAllRelateds);
        $this->assertCount(4, $allPostsWithAllRelateds);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithAllRelateds);
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('post_id')->toArray()
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('author_id')->toArray()
        );
        $this->assertEquals(
            static::POST_TITLES,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('title')->toArray()
        );
        $this->assertEquals(
            static::POST_BODIES,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('body')->toArray()
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithAllRelateds as $postRecord) {
            
            // author of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            
            // post's comments
            $this->assertIsArray($postRecord->comments);
            $this->assertCount(1, $postRecord->comments);
            $this->assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            // summary of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            
            // post's posts_tags
            $this->assertIsArray($postRecord->posts_tags);
            $this->assertCount(1, $postRecord->posts_tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            $this->assertIsArray($postRecord->tags);
            $this->assertCount(1, $postRecord->tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
        } // foreach($allPostsWithAllRelateds as $postRecord)
        unset($allPostsWithAllRelateds);
    }
    
    public function testThatFetchRecordsIntoArrayWithoutEagerLoadingWorksAsExpected() {
        
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        // Not eager loading related records during fetch will lead to lots of 
        // queries when looping through records & accessing their related records
        $allPostsWithoutRelateds = $postsModel->fetchRecordsIntoArray();
        $this->assertIsArray($allPostsWithoutRelateds);
        $this->assertCount(4, $allPostsWithoutRelateds);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithoutRelateds);
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('post_id')->toArray()
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('author_id')->toArray()
        );
        $this->assertEquals(
            static::POST_TITLES,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('title')->toArray()
        );
        $this->assertEquals(
            static::POST_BODIES,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('body')->toArray()
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithoutRelateds as $postRecord) {
            
            // author of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            
            // post's comments
            // Because the lazy load is triggered at the record level & not during
            // the fetch, the results of hasMany & hasManyThrough relationships are 
            // placed in a collection instead of an array (if the relations were 
            // eager loaded during the fetch)
            $this->assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments);  
            $this->assertCount(1, $postRecord->comments);
            $this->assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            // summary of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            
            // post's posts_tags
            $this->assertInstanceOf(\LeanOrm\TestObjects\PostsTagsCollection::class, $postRecord->posts_tags);
            $this->assertCount(1, $postRecord->posts_tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            $this->assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags);
            $this->assertCount(1, $postRecord->tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
        } // foreach($allPostsWithAllRelateds as $postRecord)
        
        unset($allPostsWithoutRelateds);
    }
    
    public function testThatFetchRecordsIntoArrayKeyedOnPkValWorksAsExpected() {
        
        // Test with empty table
        $emptyModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        $this->assertEquals([], $emptyModel->fetchRecordsIntoArrayKeyedOnPkVal());
        
        ////////////////////////////////////////////////////////////////////////
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $allPosts = $postsModel->fetchRecordsIntoArrayKeyedOnPkVal();
        $this->assertIsArray($allPosts);
        $this->assertCount(4, $allPosts);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPosts);
        // test that the results are keyed on PK
        $this->assertEquals(static::POST_POST_IDS, array_keys($allPosts));
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            GenericCollection::makeNew($allPosts)->column('post_id')->toArray()
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            GenericCollection::makeNew($allPosts)->column('author_id')->toArray()
        );
        $this->assertEquals(
            static::POST_TITLES,
            GenericCollection::makeNew($allPosts)->column('title')->toArray()
        );
        $this->assertEquals(
            static::POST_BODIES,
            GenericCollection::makeNew($allPosts)->column('body')->toArray()
        );
        unset($allPosts);
        
        ////////////////////////////////////////////////////////////////////////////
        // Test Query constraint. Filter for first two posts i.e. whose post_id < 3
        ////////////////////////////////////////////////////////////////////////////
        $firstTwoPosts = $postsModel->fetchRecordsIntoArrayKeyedOnPkVal(
            $postsModel->getSelect()->where(' post_id < 3 ')   
        );
        
        $this->assertIsArray($firstTwoPosts);
        $this->assertCount(2, $firstTwoPosts);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $firstTwoPosts);
        // test that the results are keyed on PK
        $this->assertEquals(['1', '2'], array_keys($firstTwoPosts));
        
        // verify that the records contain expected data
        $this->assertEquals(
            ['1', '2'],
            GenericCollection::makeNew($firstTwoPosts)->column('post_id')->toArray()
        );
        $this->assertEquals(
            ['1', '2'],
            GenericCollection::makeNew($firstTwoPosts)->column('author_id')->toArray()
        );
        $this->assertEquals(
            ['Post 1', 'Post 2'],
            GenericCollection::makeNew($firstTwoPosts)->column('title')->toArray()
        );
        $this->assertEquals(
            ['Post Body 1', 'Post Body 2'],
            GenericCollection::makeNew($firstTwoPosts)->column('body')->toArray()
        );
        unset($firstTwoPosts);
        
        ////////////////////////////////////////////////////////////////////////
        // Fetch with all relateds
        ////////////////////////////////////////////////////////////////////////
        $allPostsWithAllRelateds = 
            $postsModel->fetchRecordsIntoArrayKeyedOnPkVal(
                null, [ 'author', 'comments', 'summary', 'posts_tags', 'tags' ]
            );
        $this->assertIsArray($allPostsWithAllRelateds);
        $this->assertCount(4, $allPostsWithAllRelateds);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithAllRelateds);
        // test that the results are keyed on PK
        $this->assertEquals(static::POST_POST_IDS, array_keys($allPostsWithAllRelateds));
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('post_id')->toArray()
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('author_id')->toArray()
        );
        $this->assertEquals(
            static::POST_TITLES,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('title')->toArray()
        );
        $this->assertEquals(
            static::POST_BODIES,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('body')->toArray()
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithAllRelateds as $postRecord) {
            
            // author of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            
            // post's comments
            $this->assertIsArray($postRecord->comments);
            $this->assertCount(1, $postRecord->comments);
            $this->assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            // summary of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            
            // post's posts_tags
            $this->assertIsArray($postRecord->posts_tags);
            $this->assertCount(1, $postRecord->posts_tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            $this->assertIsArray($postRecord->tags);
            $this->assertCount(1, $postRecord->tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
        } // foreach($allPostsWithAllRelateds as $postRecord)
        unset($allPostsWithAllRelateds);
    }
    
    public function testThatFetchRecordsIntoArrayKeyedOnPkValWithoutEagerLoadingWorksAsExpected() {
        
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        // Not eager loading related records during fetch will lead to lots of 
        // queries when looping through records & accessing their related records
        $allPostsWithoutRelateds = $postsModel->fetchRecordsIntoArrayKeyedOnPkVal();
        $this->assertIsArray($allPostsWithoutRelateds);
        $this->assertCount(4, $allPostsWithoutRelateds);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithoutRelateds);
        // test that the results are keyed on PK
        $this->assertEquals(static::POST_POST_IDS, array_keys($allPostsWithoutRelateds));
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('post_id')->toArray()
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('author_id')->toArray()
        );
        $this->assertEquals(
            static::POST_TITLES,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('title')->toArray()
        );
        $this->assertEquals(
            static::POST_BODIES,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('body')->toArray()
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithoutRelateds as $postRecord) {
            
            // author of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            
            // post's comments
            // Because the lazy load is triggered at the record level & not during
            // the fetch, the results of hasMany & hasManyThrough relationships are 
            // placed in a collection instead of an array (if the relations were 
            // eager loaded during the fetch)
            $this->assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments);  
            $this->assertCount(1, $postRecord->comments);
            $this->assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            // summary of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            
            // post's posts_tags
            $this->assertInstanceOf(\LeanOrm\TestObjects\PostsTagsCollection::class, $postRecord->posts_tags);
            $this->assertCount(1, $postRecord->posts_tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            $this->assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags);
            $this->assertCount(1, $postRecord->tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
        } // foreach($allPostsWithAllRelateds as $postRecord)
        
        unset($allPostsWithoutRelateds);
    }
    
    public function testThatFetchRecordsIntoCollectionWorksAsExpected() {
        
        // Test with empty table
        $emptyModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        $emptyResult = $emptyModel->fetchRecordsIntoCollection();
        $this->assertInstanceOf(\LeanOrm\Model\Collection::class, $emptyResult);
        $this->assertCount(0, $emptyResult);
        
        ////////////////////////////////////////////////////////////////////////
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $allPosts = $postsModel->fetchRecordsIntoCollection();
        $this->assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $allPosts);
        $this->assertCount(4, $allPosts);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPosts);
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            $allPosts->getColVals('post_id')
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            $allPosts->getColVals('author_id')
        );
        $this->assertEquals(
            static::POST_TITLES,
            $allPosts->getColVals('title')
        );
        $this->assertEquals(
            static::POST_BODIES,
            $allPosts->getColVals('body')
        );
        unset($allPosts);
        
        ////////////////////////////////////////////////////////////////////////////
        // Test Query constraint. Filter for first two posts i.e. whose post_id < 3
        ////////////////////////////////////////////////////////////////////////////
        $firstTwoPosts = $postsModel->fetchRecordsIntoCollection(
            $postsModel->getSelect()->where(' post_id < 3 ')   
        );
        $this->assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $firstTwoPosts);
        $this->assertCount(2, $firstTwoPosts);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $firstTwoPosts);
        
        // verify that the records contain expected data
        $this->assertEquals(
            ['1', '2'],
            $firstTwoPosts->getColVals('post_id')
        );
        $this->assertEquals(
            ['1', '2'],
            $firstTwoPosts->getColVals('author_id')
        );
        $this->assertEquals(
            ['Post 1', 'Post 2'],
            $firstTwoPosts->getColVals('title')
        );
        $this->assertEquals(
            ['Post Body 1', 'Post Body 2'],
            $firstTwoPosts->getColVals('body')
        );
        unset($firstTwoPosts);
        
        ////////////////////////////////////////////////////////////////////////
        // Fetch with all relateds
        ////////////////////////////////////////////////////////////////////////
        $allPostsWithAllRelateds = 
            $postsModel->fetchRecordsIntoCollection(
                null, [ 'author', 'comments', 'summary', 'posts_tags', 'tags' ]
            );
        $this->assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $allPostsWithAllRelateds);
        $this->assertCount(4, $allPostsWithAllRelateds);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithAllRelateds);
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            $allPostsWithAllRelateds->getColVals('post_id')
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            $allPostsWithAllRelateds->getColVals('author_id')
        );
        $this->assertEquals(
            static::POST_TITLES,
            $allPostsWithAllRelateds->getColVals('title')
        );
        $this->assertEquals(
            static::POST_BODIES,
            $allPostsWithAllRelateds->getColVals('body')
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithAllRelateds as $postRecord) {
            
            // author of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            
            // post's comments
            $this->assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments);
            $this->assertCount(1, $postRecord->comments);
            $this->assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            // summary of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            
            // post's posts_tags
            $this->assertInstanceOf(\LeanOrm\TestObjects\PostsTagsCollection::class, $postRecord->posts_tags);
            $this->assertCount(1, $postRecord->posts_tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            $this->assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags);
            $this->assertCount(1, $postRecord->tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
        } // foreach($allPostsWithAllRelateds as $postRecord)
        unset($allPostsWithAllRelateds);
    }
    
    public function testThatFetchRecordsIntoCollectionWithoutEagerLoadingWorksAsExpected() {
        
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        // Not eager loading related records during fetch will lead to lots of 
        // queries when looping through records & accessing their related records
        $allPostsWithoutRelateds = $postsModel->fetchRecordsIntoCollection();
        $this->assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $allPostsWithoutRelateds);
        $this->assertCount(4, $allPostsWithoutRelateds);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithoutRelateds);
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            $allPostsWithoutRelateds->getColVals('post_id')
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            $allPostsWithoutRelateds->getColVals('author_id')
        );
        $this->assertEquals(
            static::POST_TITLES,
            $allPostsWithoutRelateds->getColVals('title')
        );
        $this->assertEquals(
            static::POST_BODIES,
            $allPostsWithoutRelateds->getColVals('body')
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithoutRelateds as $postRecord) {
            
            // author of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            
            // post's comments
            $this->assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments);  
            $this->assertCount(1, $postRecord->comments);
            $this->assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            // summary of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            
            // post's posts_tags
            $this->assertInstanceOf(\LeanOrm\TestObjects\PostsTagsCollection::class, $postRecord->posts_tags);
            $this->assertCount(1, $postRecord->posts_tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            $this->assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags);
            $this->assertCount(1, $postRecord->tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
        } // foreach($allPostsWithAllRelateds as $postRecord)
        
        unset($allPostsWithoutRelateds);
    }
    
    public function testThatFetchRecordsIntoCollectionKeyedOnPkValWorksAsExpected() {
        
        // Test with empty table
        $emptyModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        $emptyResult = $emptyModel->fetchRecordsIntoCollectionKeyedOnPkVal();
        $this->assertInstanceOf(\LeanOrm\Model\Collection::class, $emptyResult);
        $this->assertCount(0, $emptyResult);
        
        ////////////////////////////////////////////////////////////////////////
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $allPosts = $postsModel->fetchRecordsIntoCollectionKeyedOnPkVal();
        $this->assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $allPosts);
        $this->assertCount(4, $allPosts);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPosts);
        // test that the results are keyed on PK
        $this->assertEquals(static::POST_POST_IDS, array_values($allPosts->getKeys()));

        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            array_values($allPosts->getColVals('post_id'))
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            array_values($allPosts->getColVals('author_id'))
        );
        $this->assertEquals(
            static::POST_TITLES,
            array_values($allPosts->getColVals('title'))
        );
        $this->assertEquals(
            static::POST_BODIES,
            array_values($allPosts->getColVals('body'))
        );
        unset($allPosts);
        
        ////////////////////////////////////////////////////////////////////////////
        // Test Query constraint. Filter for first two posts i.e. whose post_id < 3
        ////////////////////////////////////////////////////////////////////////////
        $firstTwoPosts = $postsModel->fetchRecordsIntoCollectionKeyedOnPkVal(
            $postsModel->getSelect()->where(' post_id < 3 ')   
        );
        $this->assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $firstTwoPosts);
        $this->assertCount(2, $firstTwoPosts);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $firstTwoPosts);
        // test that the results are keyed on PK
        $this->assertEquals(['1', '2'], array_values($firstTwoPosts->getKeys()));
        
        // verify that the records contain expected data
        $this->assertEquals(
            ['1', '2'],
            array_values($firstTwoPosts->getColVals('post_id'))
        );
        $this->assertEquals(
            ['1', '2'],
            array_values($firstTwoPosts->getColVals('author_id'))
        );
        $this->assertEquals(
            ['Post 1', 'Post 2'],
            array_values($firstTwoPosts->getColVals('title'))
        );
        $this->assertEquals(
            ['Post Body 1', 'Post Body 2'],
            array_values($firstTwoPosts->getColVals('body'))
        );
        unset($firstTwoPosts);
        
        ////////////////////////////////////////////////////////////////////////
        // Fetch with all relateds
        ////////////////////////////////////////////////////////////////////////
        $allPostsWithAllRelateds = 
            $postsModel->fetchRecordsIntoCollectionKeyedOnPkVal(
                null, [ 'author', 'comments', 'summary', 'posts_tags', 'tags' ]
            );
        $this->assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $allPostsWithAllRelateds);
        $this->assertCount(4, $allPostsWithAllRelateds);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithAllRelateds);
        // test that the results are keyed on PK
        $this->assertEquals(static::POST_POST_IDS, array_values($allPostsWithAllRelateds->getKeys()));
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            array_values($allPostsWithAllRelateds->getColVals('post_id'))
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            array_values($allPostsWithAllRelateds->getColVals('author_id'))
        );
        $this->assertEquals(
            static::POST_TITLES,
            array_values($allPostsWithAllRelateds->getColVals('title'))
        );
        $this->assertEquals(
            static::POST_BODIES,
            array_values($allPostsWithAllRelateds->getColVals('body'))
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithAllRelateds as $postRecord) {
            
            // author of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            
            // post's comments
            $this->assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments);
            $this->assertCount(1, $postRecord->comments);
            $this->assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            // summary of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            
            // post's posts_tags
            $this->assertInstanceOf(\LeanOrm\TestObjects\PostsTagsCollection::class, $postRecord->posts_tags);
            $this->assertCount(1, $postRecord->posts_tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            $this->assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags);
            $this->assertCount(1, $postRecord->tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
        } // foreach($allPostsWithAllRelateds as $postRecord)
        unset($allPostsWithAllRelateds);
    }
    
    public function testThatFetchRecordsIntoCollectionKeyedOnPkValWithoutEagerLoadingWorksAsExpected() {
        
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        // Not eager loading related records during fetch will lead to lots of 
        // queries when looping through records & accessing their related records
        $allPostsWithoutRelateds = $postsModel->fetchRecordsIntoCollectionKeyedOnPkVal();
        $this->assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $allPostsWithoutRelateds);
        $this->assertCount(4, $allPostsWithoutRelateds);
        $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithoutRelateds);
        // test that the results are keyed on PK
        $this->assertEquals(static::POST_POST_IDS, array_values($allPostsWithoutRelateds->getKeys()));
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            array_values($allPostsWithoutRelateds->getColVals('post_id'))
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            array_values($allPostsWithoutRelateds->getColVals('author_id'))
        );
        $this->assertEquals(
            static::POST_TITLES,
            array_values($allPostsWithoutRelateds->getColVals('title'))
        );
        $this->assertEquals(
            static::POST_BODIES,
            array_values($allPostsWithoutRelateds->getColVals('body'))
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithoutRelateds as $postRecord) {
            
            // author of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            
            // post's comments
            $this->assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments);  
            $this->assertCount(1, $postRecord->comments);
            $this->assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            // summary of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            
            // post's posts_tags
            $this->assertInstanceOf(\LeanOrm\TestObjects\PostsTagsCollection::class, $postRecord->posts_tags);
            $this->assertCount(1, $postRecord->posts_tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            $this->assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags);
            $this->assertCount(1, $postRecord->tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
        } // foreach($allPostsWithAllRelateds as $postRecord)
        
        unset($allPostsWithoutRelateds);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    public function testThatFetchRowsIntoArrayWorksAsExpected() {
        
        // Test with empty table
        $emptyModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        $this->assertEquals([], $emptyModel->fetchRowsIntoArray());
        
        ////////////////////////////////////////////////////////////////////////
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $allPosts = $postsModel->fetchRowsIntoArray();
        $this->assertIsArray($allPosts);
        $this->assertCount(4, $allPosts);
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            array_column($allPosts, 'post_id')
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            array_column($allPosts, 'author_id')
        );
        $this->assertEquals(
            static::POST_TITLES,
            array_column($allPosts, 'title')
        );
        $this->assertEquals(
            static::POST_BODIES,
            array_column($allPosts, 'body')
        );
        unset($allPosts);
        
        ////////////////////////////////////////////////////////////////////////////
        // Test Query constraint. Filter for first two posts i.e. whose post_id < 3
        ////////////////////////////////////////////////////////////////////////////
        $firstTwoPosts = $postsModel->fetchRowsIntoArray(
            $postsModel->getSelect()->where(' post_id < 3 ')   
        );
        
        $this->assertIsArray($firstTwoPosts);
        $this->assertCount(2, $firstTwoPosts);
        
        // verify that the records contain expected data
        $this->assertEquals(
            ['1', '2'],
            array_column($firstTwoPosts, 'post_id')
        );
        $this->assertEquals(
            ['1', '2'],
            array_column($firstTwoPosts, 'author_id')
        );
        $this->assertEquals(
            ['Post 1', 'Post 2'],
            array_column($firstTwoPosts, 'title')
        );
        $this->assertEquals(
            ['Post Body 1', 'Post Body 2'],
            array_column($firstTwoPosts, 'body')
        );
        unset($firstTwoPosts);
        
        ////////////////////////////////////////////////////////////////////////
        // Fetch with all relateds
        ////////////////////////////////////////////////////////////////////////
        $allPostsWithAllRelateds = 
            $postsModel->fetchRowsIntoArray(
                null, [ 'author', 'comments', 'summary', 'posts_tags', 'tags' ]
            );
        $this->assertIsArray($allPostsWithAllRelateds);
        $this->assertCount(4, $allPostsWithAllRelateds);
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            array_column($allPostsWithAllRelateds, 'post_id')
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            array_column($allPostsWithAllRelateds, 'author_id')
        );
        $this->assertEquals(
            static::POST_TITLES,
            array_column($allPostsWithAllRelateds, 'title')
        );
        $this->assertEquals(
            static::POST_BODIES,
            array_column($allPostsWithAllRelateds, 'body')
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithAllRelateds as $postRecord) {
            
            $this->assertArrayHasAllKeys(
                $postRecord, 
                ['post_id', 'author_id', 'datetime', 'title', 'body', 'm_timestamp', 'date_created']
            );
            
            // author of the post
            $this->assertIsArray($postRecord['author']);
            
            // post's comments
            $this->assertIsArray($postRecord['comments']);
            $this->assertCount(1, $postRecord['comments']);
            $this->assertContainsOnly('array', $postRecord['comments']);
            
            // summary of the post
            $this->assertIsArray($postRecord['summary']);
            
            // post's posts_tags
            $this->assertIsArray($postRecord['posts_tags']);
            $this->assertCount(1, $postRecord['posts_tags']);
            $this->assertContainsOnly('array', $postRecord['posts_tags']);
            
            // post's tags
            $this->assertIsArray($postRecord['tags']);
            $this->assertCount(1, $postRecord['tags']);
            $this->assertContainsOnly('array', $postRecord['tags']);
        } // foreach($allPostsWithAllRelateds as $postRecord)
        unset($allPostsWithAllRelateds);
    }
    
    public function testThatFetchRowsIntoArrayKeyedOnPkValWorksAsExpected() {
        
        // Test with empty table
        $emptyModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        $this->assertEquals([], $emptyModel->fetchRowsIntoArrayKeyedOnPkVal());
        
        ////////////////////////////////////////////////////////////////////////
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $allPosts = $postsModel->fetchRowsIntoArrayKeyedOnPkVal();
        $this->assertIsArray($allPosts);
        $this->assertCount(4, $allPosts);
        // test that the results are keyed on PK
        $this->assertEquals(static::POST_POST_IDS, array_keys($allPosts));
        
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            array_column($allPosts, 'post_id')
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            array_column($allPosts, 'author_id')
        );
        $this->assertEquals(
            static::POST_TITLES,
            array_column($allPosts, 'title')
        );
        $this->assertEquals(
            static::POST_BODIES,
            array_column($allPosts, 'body')
        );
        unset($allPosts);
        
        ////////////////////////////////////////////////////////////////////////////
        // Test Query constraint. Filter for first two posts i.e. whose post_id < 3
        ////////////////////////////////////////////////////////////////////////////
        $firstTwoPosts = $postsModel->fetchRowsIntoArrayKeyedOnPkVal(
            $postsModel->getSelect()->where(' post_id < 3 ')   
        );
        
        $this->assertIsArray($firstTwoPosts);
        $this->assertCount(2, $firstTwoPosts);
        // test that the results are keyed on PK
        $this->assertEquals(['1', '2'], array_keys($firstTwoPosts));
        
        // verify that the records contain expected data
        $this->assertEquals(
            ['1', '2'],
            array_column($firstTwoPosts, 'post_id')
        );
        $this->assertEquals(
            ['1', '2'],
            array_column($firstTwoPosts, 'author_id')
        );
        $this->assertEquals(
            ['Post 1', 'Post 2'],
            array_column($firstTwoPosts, 'title')
        );
        $this->assertEquals(
            ['Post Body 1', 'Post Body 2'],
            array_column($firstTwoPosts, 'body')
        );
        unset($firstTwoPosts);
        
        ////////////////////////////////////////////////////////////////////////
        // Fetch with all relateds
        ////////////////////////////////////////////////////////////////////////
        $allPostsWithAllRelateds = 
            $postsModel->fetchRowsIntoArrayKeyedOnPkVal(
                null, [ 'author', 'comments', 'summary', 'posts_tags', 'tags' ]
            );
        $this->assertIsArray($allPostsWithAllRelateds);
        $this->assertCount(4, $allPostsWithAllRelateds);
        // test that the results are keyed on PK
        $this->assertEquals(static::POST_POST_IDS, array_keys($allPostsWithAllRelateds));
        
        // verify that the records contain expected data
        $this->assertEquals(
            static::POST_POST_IDS,
            array_column($allPostsWithAllRelateds, 'post_id')
        );
        $this->assertEquals(
            static::POST_AUTHOR_IDS,
            array_column($allPostsWithAllRelateds, 'author_id')
        );
        $this->assertEquals(
            static::POST_TITLES,
            array_column($allPostsWithAllRelateds, 'title')
        );
        $this->assertEquals(
            static::POST_BODIES,
            array_column($allPostsWithAllRelateds, 'body')
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithAllRelateds as $postRecord) {
            
            $this->assertArrayHasAllKeys(
                $postRecord, 
                ['post_id', 'author_id', 'datetime', 'title', 'body', 'm_timestamp', 'date_created']
            );
            
            // author of the post
            $this->assertIsArray($postRecord['author']);
            
            // post's comments
            $this->assertIsArray($postRecord['comments']);
            $this->assertCount(1, $postRecord['comments']);
            $this->assertContainsOnly('array', $postRecord['comments']);
            
            // summary of the post
            $this->assertIsArray($postRecord['summary']);
            
            // post's posts_tags
            $this->assertIsArray($postRecord['posts_tags']);
            $this->assertCount(1, $postRecord['posts_tags']);
            $this->assertContainsOnly('array', $postRecord['posts_tags']);
            
            // post's tags
            $this->assertIsArray($postRecord['tags']);
            $this->assertCount(1, $postRecord['tags']);
            $this->assertContainsOnly('array', $postRecord['tags']);
        } // foreach($allPostsWithAllRelateds as $postRecord)
        unset($allPostsWithAllRelateds);
    }
    
    protected function insertDataIntoTable(string $tableName, array $tableData) {
        
        $insertBuilder = static::$auraQueryFactory->newInsert();
        $insertBuilder->into($tableName)->cols($tableData);
        
        // prepare the statement
        $sth = static::$atlasPdo->getPdo()->prepare($insertBuilder->getStatement());
        
        // execute with bound values
        $sth->execute($insertBuilder->getBindValues());
    }
    
    protected function assertArrayHasAllKeys(array $arrayToTest, array $keysToCheck): void {
        
        foreach ($keysToCheck as $key) {
            
            $this->assertArrayHasKey($key, $arrayToTest);
        }
    }
}
