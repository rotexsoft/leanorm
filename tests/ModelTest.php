<?php
use Atlas\Pdo\Connection;
use Aura\SqlQuery\QueryFactory;

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
            ->setCollectionClassName(\CollectionForTestingPublicAndProtectedMethods::class)
            ->setRecordClassName(\RecordForTestingPublicAndProtectedMethods::class);

        $this->testModelObjects['authors'] = 
            new \ModelForTestingPublicAndProtectedMethods(
                static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors'
            );

        $this->testModelObjects['comments'] = 
            new \ModelForTestingPublicAndProtectedMethods(
                static::$dsn, static::$username ?? "", static::$password ?? "", [], 'comment_id', 'comments'
            );

        $this->testModelObjects['posts'] = 
            new \ModelForTestingPublicAndProtectedMethods(
                static::$dsn, static::$username ?? "", static::$password ?? "", [], 'post_id', 'posts'
            );

        $this->testModelObjects['posts_tags'] = 
            new \ModelForTestingPublicAndProtectedMethods(
                static::$dsn, static::$username ?? "", static::$password ?? "", [], 'posts_tags_id', 'posts_tags'
            );

        $this->testModelObjects['summaries'] = 
            new \ModelForTestingPublicAndProtectedMethods(
                static::$dsn, static::$username ?? "", static::$password ?? "", [], 'summary_id', 'summaries'
            );

        $this->testModelObjects['tags'] = 
            new \ModelForTestingPublicAndProtectedMethods(
                static::$dsn, static::$username ?? "", static::$password ?? "", [], 'tag_id', 'tags'
            );
    }
    
    public function testThatConstructorWithNoArgsWorksAsExpected() {
        
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage("invalid data source name");

        $model = new LeanOrm\Model();
    }
    
    public function testThatConstructorWithNoPrimaryColWorksAsExpected() {
        
        $this->expectException(\GDAO\ModelPrimaryColNameNotSetDuringConstructionException::class);
        $this->expectExceptionMessage("Primary Key Column name not set for LeanOrm\\Model");

        $model = new LeanOrm\Model('sqlite::memory:');
    }
    
    public function testThatConstructorWithNoTableNameWorksAsExpected() {
        
        $this->expectException(\GDAO\ModelTableNameNotSetDuringConstructionException::class);
        $this->expectExceptionMessage("Table name not set for LeanOrm\\Model");

        $model = new LeanOrm\Model('sqlite::memory:','','',[],'id','');
    }
    
    public function testThatConstructorWorksAsExpected() {
        
        $model = new LeanOrm\Model(static::$dsn,'','',[],'author_id','authors');
        
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
            static::$dsn,'','', [PDO::ATTR_PERSISTENT => true], '', 'authors'
        );
        $this->assertTrue($model->getPrimaryCol() === 'author_id');
        $this->assertTrue($model->getPdoDriverOpts() === [PDO::ATTR_PERSISTENT => true]);
    }

    public function testCreateNewCollection() {
        
        $modelWithMockCollAndRec = $this->testModelObjects['authors_with_specialized_collection_and_record'];
        $coll_mock = $modelWithMockCollAndRec->createNewCollection();
        
        //exact class
        $this->assertEquals(\CollectionForTestingPublicAndProtectedMethods::class, get_class($coll_mock));
        
        //has the right parent class
        $this->assertInstanceOf(\LeanOrm\Model\Collection::class, $coll_mock);
        $this->assertInstanceOf(\GDAO\Model\CollectionInterface::class, $coll_mock);
        
        //exact class test
        $modelWithLeanormCollAndRec = $this->testModelObjects['authors'];
        $coll_generic = $modelWithLeanormCollAndRec->createNewCollection();
        $this->assertEquals(\LeanOrm\Model\Collection::class, get_class($coll_generic));
        
        // Test creating collection with some records
        $collection1WithRecords = 
            $modelWithLeanormCollAndRec
                ->createNewCollection(...[$modelWithLeanormCollAndRec->createNewRecord(), $modelWithLeanormCollAndRec->createNewRecord(),]);
        
        $collection2WithRecords = 
            $modelWithMockCollAndRec
                ->createNewCollection(...[
                    $modelWithMockCollAndRec->createNewRecord(),
                    $modelWithMockCollAndRec->createNewRecord(),
                    $modelWithMockCollAndRec->createNewRecord(),
                ]);
        
        $this->assertTrue($collection1WithRecords->count() === 2);
        $this->assertTrue($collection2WithRecords->count() === 3);
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
        $modelWithLeanormCollAndRec = $this->testModelObjects['authors'];
        $leanOrmRecord = $modelWithLeanormCollAndRec->createNewRecord();
        $this->assertEquals(\LeanOrm\Model\Record::class, get_class($leanOrmRecord));
    }
    
    public function testThatBelongsToThrowsExceptionWhenRelationNameCollidesWithColumnName() {
        
        $this->expectException(\GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn,'','',[],'author_id','authors');
        // relation name with the same name as p key column
        $model->belongsTo('author_id', '', '', '', '');
    }
    
    public function testThatBelongsToWorksAsExpected() {

        $postsModel = new LeanOrm\Model(static::$dsn,'','',[],'post_id','posts');
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
        
        $postsModel = new LeanOrm\Model(static::$dsn,'','',[],'post_id','posts');
        
        $this->assertFalse($postsModel->canLogQueries());
        $this->assertTrue($postsModel->enableQueryLogging()->canLogQueries());
    }
    
    public function testThatDeleteMatchingDbTableRowsWorksAsExpected() {
        
        $authorsModel = new LeanOrm\Model(static::$dsn,'','',[],'author_id','authors');
        $recordsToInsert = [
            ['name'=> 'author 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+2 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+1 minutes"))],
            ['name'=> 'author 2', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+3 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+2 minutes"))],
            ['name'=> 'author 3', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+4 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+3 minutes"))],
        ];
        
        $this->insertDataIntoTable('authors', $recordsToInsert[0]);
        $this->insertDataIntoTable('authors', $recordsToInsert[1]);
        $this->insertDataIntoTable('authors', $recordsToInsert[2]);
        
        // only one matching record
        $this->assertTrue( $authorsModel->deleteMatchingDbTableRows(['name'=> 'author 1']) === 1 );
        
        // two matching records
        $this->assertTrue( $authorsModel->deleteMatchingDbTableRows(['name'=> ['author 2', 'author 3']]) === 2 );
        
        // no matching record
        $this->assertTrue( $authorsModel->deleteMatchingDbTableRows(['name'=> 'author 55']) === 0 );
    }
    
    public function testThatDeleteSpecifiedRecordWorksAsExpected() {
        
        $authorsModel = new LeanOrm\Model(static::$dsn,'','',[],'author_id','authors');
        $recordsToInsert = [
            ['name'=> 'author 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+2 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+1 minutes"))],
            ['name'=> 'author 2', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+3 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+2 minutes"))],
            ['name'=> 'author 3', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+4 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+3 minutes"))],
        ];
        
        // Deleting a record that has never been saved to the DB
        $this->assertNull( $authorsModel->deleteSpecifiedRecord($authorsModel->createNewRecord()));
        $this->assertNull( $authorsModel->deleteSpecifiedRecord($authorsModel->createNewRecord($recordsToInsert[0])));
        
        $this->insertDataIntoTable('authors', $recordsToInsert[0]);
        $this->insertDataIntoTable('authors', $recordsToInsert[1]);
        $this->insertDataIntoTable('authors', $recordsToInsert[2]);

        // see \TestSchemaCreatorAndSeeder::populateTables();
        // [ 'author_id'=> 1, 'name'=> 'user_1']
        $firstRecord = $authorsModel->fetchOneRecord();
        $this->assertFalse($firstRecord->isNew());
        $this->assertTrue($authorsModel->deleteSpecifiedRecord($firstRecord));
        // record is new after being deleted
        $this->assertTrue($firstRecord->isNew());
    }
    
    public function testThatDeleteSpecifiedRecordThrowsExceptionForReadOnlyRecords()  {
        
        $this->expectException(\LeanOrm\CantDeleteReadOnlyRecordFromDBException::class);
        
        $authorsModel = new LeanOrm\Model(static::$dsn,'','',[],'author_id','authors');
        
        $authorsModel->setRecordClassName(LeanOrm\Model\ReadOnlyRecord::class);
        
        // should throw exception
        $authorsModel->deleteSpecifiedRecord($authorsModel->createNewRecord());
    }
    
    protected function insertDataIntoTable(string $tableName, array $tableData) {
        
        $insertBuilder = static::$auraQueryFactory->newInsert();
        
        $insertBuilder->into($tableName)
                      ->cols($tableData);
        
        // prepare the statement
        $sth = static::$atlasPdo->getPdo()->prepare($insertBuilder->getStatement());
        
        // execute with bound values
        $sth->execute($insertBuilder->getBindValues());
    }
}
