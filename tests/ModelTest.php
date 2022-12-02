<?php
use Psr\Log\LogLevel;
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
    protected static \Psr\Log\LoggerInterface $psrLogger;
    
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
        
        static::$atlasPdo = $connection;
        $driverName = $connection->getPdo()->getAttribute(
                                            \PDO::ATTR_DRIVER_NAME
                                         );
        static::$auraQueryFactory = (new QueryFactory($driverName));
        
        static::$psrLogger = new class extends \Psr\Log\AbstractLogger {
            
            protected $min_level = LogLevel::DEBUG;
            protected $levels = [
                LogLevel::DEBUG,
                LogLevel::INFO,
                LogLevel::NOTICE,
                LogLevel::WARNING,
                LogLevel::ERROR,
                LogLevel::CRITICAL,
                LogLevel::ALERT,
                LogLevel::EMERGENCY
            ];

            public function __construct($min_level = LogLevel::DEBUG)
            {
                $this->min_level = $min_level;
            }

            public function log($level, $message, array $context = array())
            {
                if (!$this->min_level_reached($level)) {
                    return;
                }
                echo $this->format($level, $message, $context);
            }

            /**
             * @param string $level
             * @return boolean
             */
            protected function min_level_reached($level)
            {
                return \array_search($level, $this->levels) >= \array_search($this->min_level, $this->levels);
            }

            /**
             * Interpolates context values into the message placeholders.
             *
             * @author PHP Framework Interoperability Group
             *
             * @param string $message
             * @param array $context
             * @return string
             */
            protected function interpolate($message, array $context)
            {
                if (false === strpos($message, '{')) {
                    return $message;
                }

                $replacements = array();
                foreach ($context as $key => $val) {
                    if (null === $val || is_scalar($val) || (\is_object($val) && method_exists($val, '__toString'))) {
                        $replacements["{{$key}}"] = $val;
                    } elseif ($val instanceof \DateTimeInterface) {
                        $replacements["{{$key}}"] = $val->format(\DateTime::RFC3339);
                    } elseif (\is_object($val)) {
                        $replacements["{{$key}}"] = '[object '.\get_class($val).']';
                    } else {
                        $replacements["{{$key}}"] = '['.\gettype($val).']';
                    }
                }

                return strtr($message, $replacements);
            }

            /**
             * @param string $level
             * @param string $message
             * @param array $context
             * @param string|null $timestamp A Timestamp string in format 'Y-m-d H:i:s', defaults to current time
             * @return string
             */
            protected function format($level, $message, $context, $timestamp = null)
            {
                if ($timestamp === null) $timestamp = date('Y-m-d H:i:s');
                return PHP_EOL . '[' . $timestamp . '] ' . strtoupper($level) . ': ' . $this->interpolate($message, $context) . PHP_EOL;
            }
        };
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
    
    public function testThatConstructorWithNonExistentTableNameWorksAsExpected() {
        
        $this->expectException(\LeanOrm\BadModelTableNameException::class);

        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'id','non_existent_table');
    }
    
    public function testThatConstructorWithNonExistentPrimaryColumnNameWorksAsExpected() {
        
        $this->expectException(\LeanOrm\BadModelPrimaryColumnNameException::class);

        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'non_existent_column','authors');
    }
    
    public function testThatConstructorWithNoPrimaryColWorksAsExpected() {
        
        $this->expectException(\GDAO\ModelPrimaryColNameNotSetDuringConstructionException::class);

        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'','v_authors');
    }
    
    public function testThatConstructorWithNoTableNameWorksAsExpected() {
        
        $this->expectException(\GDAO\ModelTableNameNotSetDuringConstructionException::class);

        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','');
    }
    
    public function testThatConstructorWorksAsExpected() {
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        
        $this->assertEquals(static::$dsn, $model->getDsn());
        $this->assertEquals(static::$username ?? "", $model->getUsername());
        $this->assertEquals(static::$password ?? "", $model->getPasswd());
        $this->assertEquals([], $model->getPdoDriverOpts());
        $this->assertEquals('author_id', $model->getPrimaryCol());
        $this->assertEquals('authors', $model->getTableName());
        
        $this->assertContains('author_id', $model->getTableColNames());
        $this->assertContains('name', $model->getTableColNames());
        $this->assertContains('m_timestamp', $model->getTableColNames());
        $this->assertContains('date_created', $model->getTableColNames());
        
        // Test that the schema query gets & sets the primary key col
        // when an empty primary key value is passed to the constructor
        $model = new LeanOrm\Model(
            static::$dsn, static::$username ?? "", static::$password ?? "", [PDO::ATTR_PERSISTENT => true], '', 'authors'
        );
        $this->assertEquals('author_id', $model->getPrimaryCol());
        $this->assertEquals([PDO::ATTR_PERSISTENT => true], $model->getPdoDriverOpts());
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
        
        $this->assertCount(2, $collection1WithRecords);
        $this->assertCount(3, $collection2WithRecords);
        
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
    
    public function testThatBelongsToThrowsExceptionWithInvalidForeignModelClassName() {
        
        $this->expectException(\LeanOrm\BadModelClassNameForFetchingRelatedDataException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        // relation name with the same name as p key column
        $model->belongsTo(
            'post', 
            'post_id', 
            'posts', 
            'post_id', 
            'post_id',
            \PDO::class, // bad Model class name
            LeanOrm\TestObjects\PostRecord::class,
            \LeanOrm\TestObjects\PostsCollection::class
        );
    }
    
    public function testThatBelongsToThrowsExceptionWithInvalidForeignRecordClassName() {
        
        $this->expectException(\LeanOrm\BadRecordClassNameForFetchingRelatedDataException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        // relation name with the same name as p key column
        $model->belongsTo(
            'post', 
            'post_id', 
            'posts', 
            'post_id', 
            'post_id',
            \LeanOrm\TestObjects\PostsModel::class,
            \PDO::class, // bad Record class name
            \LeanOrm\TestObjects\PostsCollection::class
        );
    }
    
    public function testThatBelongsToThrowsExceptionWithInvalidForeignCollectionClassName() {
        
        $this->expectException(\LeanOrm\BadCollectionClassNameForFetchingRelatedDataException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        // relation name with the same name as p key column
        $model->belongsTo(
            'post', 
            'post_id', 
            'posts', 
            'post_id', 
            'post_id',
            \LeanOrm\TestObjects\PostsModel::class,
            \LeanOrm\TestObjects\PostRecord::class,
            \PDO::class  // bad Collection class name
        );
    }
    
    public function testThatBelongsToThrowsExceptionWithNonExistentForeignTableName() {
        
        $this->expectException(\LeanOrm\BadModelTableNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->belongsTo(
            'post', 
            'post_id', 
            'non_existent_foreign_table', // Non-existent foreign table
            'post_id', 
            'post_id'
        );
    }
    
    public function testThatBelongsToThrowsExceptionWithNonExistentCol1() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        // relation name with the same name as p key column
        $model->belongsTo(
            'post', 
            'non_existent', 
            'posts', 
            'post_id', 
            'post_id'
        );
    }
    
    public function testThatBelongsToThrowsExceptionWithNonExistentCol2() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        // relation name with the same name as p key column
        $model->belongsTo(
            'post', 
            'post_id', 
            'posts', 
            'non_existent', 
            'post_id'
        );
    }
    
    public function testThatBelongsToThrowsExceptionWithNonExistentCol3() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        // relation name with the same name as p key column
        $model->belongsTo(
            'post', 
            'post_id', 
            'posts', 
            'post_id', 
            'non_existent'
        );
    }
    
    public function testThatBelongsToWorksAsExpected() {

        $postsModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        $postsModel->belongsTo(
            'author', 'author_id', 'authors', 'author_id', 'author_id', \LeanOrm\Model::class,
            \LeanOrm\Model\Record::class, \LeanOrm\Model\Collection::class, null
        );
        $this->assertEquals(['author'], $postsModel->getRelationNames());
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
        
        $this->assertEquals(\GDAO\Model::RELATION_TYPE_BELONGS_TO, $relations['author']['relation_type']);
        $this->assertEquals('author_id', $relations['author']['foreign_key_col_in_my_table']);
        $this->assertEquals('authors', $relations['author']['foreign_table']);
        $this->assertEquals('author_id', $relations['author']['foreign_key_col_in_foreign_table']);
        $this->assertEquals('author_id', $relations['author']['primary_key_col_in_foreign_table']);
        $this->assertEquals(\LeanOrm\Model::class, $relations['author']['foreign_models_class_name']);
        $this->assertEquals(\LeanOrm\Model\Record::class, $relations['author']['foreign_models_record_class_name']);
        $this->assertEquals(\LeanOrm\Model\Collection::class, $relations['author']['foreign_models_collection_class_name']);
        $this->assertNull($relations['author']['sql_query_modifier']);
        
        $callback = fn(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select => $selectObj;
        $postsModel->belongsTo(
            'author2', 'author_id', 'authors', 'author_id', 'author_id', \LeanOrm\Model::class,
            \LeanOrm\Model\Record::class, \LeanOrm\Model\Collection::class, 
            $callback
        );
        $this->assertEquals(['author', 'author2'], $postsModel->getRelationNames());
        $this->assertEquals($callback, $postsModel->getRelations()['author2']['sql_query_modifier']);
    }
    
    public function testThatHasOneThrowsExceptionWhenRelationNameCollidesWithColumnName() {
        
        $this->expectException(\GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        // relation name with the same name as p key column
        $model->hasOne('author_id', '', '', '', '');
    }
    
    public function testThatHasOneThrowsExceptionWithInvalidForeignModelClassName() {
        
        $this->expectException(\LeanOrm\BadModelClassNameForFetchingRelatedDataException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasOne(
            'post', 
            'post_id', 
            'posts', 
            'post_id', 
            'post_id',
            \PDO::class, // bad Model class name
            LeanOrm\TestObjects\PostRecord::class,
            \LeanOrm\TestObjects\PostsCollection::class
        );
    }
    
    public function testThatHasOneThrowsExceptionWithInvalidForeignRecordClassName() {
        
        $this->expectException(\LeanOrm\BadRecordClassNameForFetchingRelatedDataException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasOne(
            'post', 
            'post_id', 
            'posts', 
            'post_id', 
            'post_id',
            \LeanOrm\TestObjects\PostsModel::class,
            \PDO::class, // bad Record class name
            \LeanOrm\TestObjects\PostsCollection::class
        );
    }
    
    public function testThatHasOneThrowsExceptionWithNonExistentForeignTableName() {
        
        $this->expectException(\LeanOrm\BadModelTableNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasOne(
            'post', 
            'post_id', 
            'non_existent_foreign_table', // Non-existent foreign table
            'post_id', 
            'post_id'
        );
    }
    
    public function testThatHasOneThrowsExceptionWithInvalidForeignCollectionClassName() {
        
        $this->expectException(\LeanOrm\BadCollectionClassNameForFetchingRelatedDataException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasOne(
            'post', 
            'post_id', 
            'posts', 
            'post_id', 
            'post_id',
            \LeanOrm\TestObjects\PostsModel::class,
            \LeanOrm\TestObjects\PostRecord::class,
            \PDO::class  // bad Collection class name
        );
    }
    
    public function testThatHasOneThrowsExceptionWithNonExistentCol1() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasOne(
            'post', 
            'non_existent', 
            'posts', 
            'post_id', 
            'post_id'
        );
    }
    
    public function testThatHasOneThrowsExceptionWithNonExistentCol2() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasOne(
            'post', 
            'post_id', 
            'posts', 
            'non_existent', 
            'post_id'
        );
    }
    
    public function testThatHasOneThrowsExceptionWithNonExistentCol3() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasOne(
            'post', 
            'post_id', 
            'posts', 
            'post_id', 
            'non_existent'
        );
    }
    
    public function testThatHasOneWorksAsExpected() {

        $postsModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        $postsModel->hasOne(
            'author', 'author_id', 'authors', 'author_id', 'author_id', \LeanOrm\Model::class,
            \LeanOrm\Model\Record::class, \LeanOrm\Model\Collection::class, null
        );
        $this->assertEquals(['author'], $postsModel->getRelationNames());
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
        
        $this->assertEquals(\GDAO\Model::RELATION_TYPE_HAS_ONE, $relations['author']['relation_type']);
        $this->assertEquals('author_id', $relations['author']['foreign_key_col_in_my_table']);
        $this->assertEquals('authors', $relations['author']['foreign_table']);
        $this->assertEquals('author_id', $relations['author']['foreign_key_col_in_foreign_table']);
        $this->assertEquals('author_id', $relations['author']['primary_key_col_in_foreign_table']);
        $this->assertEquals(\LeanOrm\Model::class, $relations['author']['foreign_models_class_name']);
        $this->assertEquals(\LeanOrm\Model\Record::class, $relations['author']['foreign_models_record_class_name']);
        $this->assertEquals(\LeanOrm\Model\Collection::class, $relations['author']['foreign_models_collection_class_name']);
        $this->assertNull($relations['author']['sql_query_modifier']);
        
        $callback = fn(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select => $selectObj;
        $postsModel->hasOne(
            'author2', 'author_id', 'authors', 'author_id', 'author_id', \LeanOrm\Model::class,
            \LeanOrm\Model\Record::class, \LeanOrm\Model\Collection::class, 
            $callback
        );
        $this->assertEquals(['author', 'author2'], $postsModel->getRelationNames());
        $this->assertEquals($callback, $postsModel->getRelations()['author2']['sql_query_modifier']);
    }
    
    public function testThatHasManyThrowsExceptionWhenRelationNameCollidesWithColumnName() {
        
        $this->expectException(\GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        // relation name with the same name as p key column
        $model->hasMany('author_id', '', '', '', '');
    }
    
    public function testThatHasManyThrowsExceptionWithInvalidForeignModelClassName() {
        
        $this->expectException(\LeanOrm\BadModelClassNameForFetchingRelatedDataException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        
        $model->hasMany(
            'posts', 
            'post_id', 
            'posts', 
            'post_id', 
            'post_id',
            \PDO::class, // bad Model class name
            LeanOrm\TestObjects\PostRecord::class,
            \LeanOrm\TestObjects\PostsCollection::class
        );
    }
    
    public function testThatHasManyThrowsExceptionWithInvalidForeignRecordClassName() {
        
        $this->expectException(\LeanOrm\BadRecordClassNameForFetchingRelatedDataException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasMany(
            'post', 
            'post_id', 
            'posts', 
            'post_id', 
            'post_id',
            \LeanOrm\TestObjects\PostsModel::class,
            \PDO::class, // bad Record class name
            \LeanOrm\TestObjects\PostsCollection::class
        );
    }
    
    public function testThatHasManyThrowsExceptionWithNonExistentForeignTableName() {
        
        $this->expectException(\LeanOrm\BadModelTableNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasMany(
            'post', 
            'post_id', 
            'non_existent', // non-existent foreign table
            'post_id', 
            'post_id'
        );
    }
    
    public function testThatHasManyThrowsExceptionWithInvalidForeignCollectionClassName() {
        
        $this->expectException(\LeanOrm\BadCollectionClassNameForFetchingRelatedDataException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasMany(
            'post', 
            'post_id', 
            'posts', 
            'post_id', 
            'post_id',
            \LeanOrm\TestObjects\PostsModel::class,
            \LeanOrm\TestObjects\PostRecord::class,
            \PDO::class  // bad Collection class name
        );
    }
    
    public function testThatHasManyThrowsExceptionWithNonExistentCol1() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasMany(
            'post', 
            'non_existent', 
            'posts', 
            'post_id', 
            'post_id'
        );
    }
    
    public function testThatHasManyThrowsExceptionWithNonExistentCol2() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasMany(
            'post', 
            'post_id', 
            'posts', 
            'non_existent', 
            'post_id'
        );
    }
    
    public function testThatHasManyThrowsExceptionWithNonExistentCol3() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasMany(
            'post', 
            'post_id', 
            'posts', 
            'post_id', 
            'non_existent'
        );
    }
    
    public function testThatHasManyWorksAsExpected() {

        $postsModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        $postsModel->hasMany(
            'comments', 
            'post_id', 
            'comments', 
            'post_id', 
            'comment_id',
            LeanOrm\TestObjects\CommentsModel::class,
            LeanOrm\TestObjects\CommentRecord::class,
            LeanOrm\TestObjects\CommentsCollection::class
        );
        $this->assertEquals(['comments'], $postsModel->getRelationNames());
        $relations = $postsModel->getRelations();
        $this->assertArrayHasKey('comments', $relations);
        $this->assertArrayHasKey('relation_type', $relations['comments']);
        $this->assertArrayHasKey('foreign_key_col_in_my_table', $relations['comments']);
        $this->assertArrayHasKey('foreign_table', $relations['comments']);
        $this->assertArrayHasKey('foreign_key_col_in_foreign_table', $relations['comments']);
        $this->assertArrayHasKey('primary_key_col_in_foreign_table', $relations['comments']);
        $this->assertArrayHasKey('foreign_models_class_name', $relations['comments']);
        $this->assertArrayHasKey('foreign_models_record_class_name', $relations['comments']);
        $this->assertArrayHasKey('foreign_models_collection_class_name', $relations['comments']);
        $this->assertArrayHasKey('sql_query_modifier', $relations['comments']);
        
        $this->assertEquals(\GDAO\Model::RELATION_TYPE_HAS_MANY, $relations['comments']['relation_type']);
        $this->assertEquals('post_id', $relations['comments']['foreign_key_col_in_my_table']);
        $this->assertEquals('comments', $relations['comments']['foreign_table']);
        $this->assertEquals('post_id', $relations['comments']['foreign_key_col_in_foreign_table']);
        $this->assertEquals('comment_id', $relations['comments']['primary_key_col_in_foreign_table']);
        $this->assertEquals(LeanOrm\TestObjects\CommentsModel::class, $relations['comments']['foreign_models_class_name']);
        $this->assertEquals(LeanOrm\TestObjects\CommentRecord::class, $relations['comments']['foreign_models_record_class_name']);
        $this->assertEquals(LeanOrm\TestObjects\CommentsCollection::class, $relations['comments']['foreign_models_collection_class_name']);
        $this->assertNull($relations['comments']['sql_query_modifier']);
        
        $callback = fn(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select => $selectObj;
        $postsModel->hasMany(
            'comments2', 'post_id', 'comments', 'post_id', 'comment_id', \LeanOrm\Model::class,
            \LeanOrm\Model\Record::class, \LeanOrm\Model\Collection::class, 
            $callback
        );
        $this->assertEquals(['comments', 'comments2'], $postsModel->getRelationNames());
        $this->assertEquals($callback, $postsModel->getRelations()['comments2']['sql_query_modifier']);
    }
    
    public function testThatHasManyThroughThrowsExceptionWhenRelationNameCollidesWithColumnName() {
        
        $this->expectException(\GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        // relation name with the same name as p key column
        $model->hasManyThrough('author_id', '', '', '', '', '', '', '');
    }
    
    public function testThatHasManyThroughThrowsExceptionWithInvalidForeignModelClassName() {
        
        $this->expectException(\LeanOrm\BadModelClassNameForFetchingRelatedDataException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags',
            'post_id',
            'posts_tags',
            'post_id',
            'tag_id',
            'tags',
            'tag_id',
            'tag_id',
            \PDO::class, // bad Model class name
            LeanOrm\TestObjects\TagRecord::class,
            LeanOrm\TestObjects\TagsCollection::class
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithInvalidForeignRecordClassName() {
        
        $this->expectException(\LeanOrm\BadRecordClassNameForFetchingRelatedDataException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags',
            'post_id',
            'posts_tags',
            'post_id',
            'tag_id',
            'tags',
            'tag_id',
            'tag_id',
            LeanOrm\TestObjects\TagsModel::class,
            \PDO::class, // bad Record class name
            LeanOrm\TestObjects\TagsCollection::class
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithNonExistentForeignTableName() {
        
        $this->expectException(\LeanOrm\BadModelTableNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags',
            'post_id',
            'posts_tags',
            'post_id',
            'tag_id',
            'non_existent_table', // non-existent foreign table
            'tag_id',
            'tag_id'
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithNonExistentJoinTableName() {
        
        $this->expectException(\LeanOrm\BadModelTableNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags',
            'post_id',
            'non_existent', // non-existent join table
            'post_id',
            'tag_id',
            'tags',
            'tag_id',
            'tag_id'
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithInvalidForeignCollectionClassName() {
        
        $this->expectException(\LeanOrm\BadCollectionClassNameForFetchingRelatedDataException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags',
            'post_id',
            'posts_tags',
            'post_id',
            'tag_id',
            'tags',
            'tag_id',
            'tag_id',
            LeanOrm\TestObjects\TagsModel::class,
            LeanOrm\TestObjects\TagRecord::class,
            \PDO::class  // bad Collection class name
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithNonExistentCol1() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags',
            'non_existent', // post_id
            'posts_tags',
            'post_id',
            'tag_id',
            'tags',
            'tag_id',
            'tag_id'
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithNonExistentCol2() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags',
            'post_id',
            'posts_tags',
            'non_existent', // post_id
            'tag_id',
            'tags',
            'tag_id',
            'tag_id'
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithNonExistentCol3() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags',
            'post_id',
            'posts_tags',
            'post_id',
            'non_existent', // tag_id
            'tags',
            'tag_id',
            'tag_id'
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithNonExistentCol4() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags',
            'post_id',
            'posts_tags',
            'post_id',
            'tag_id',
            'tags',
            'non_existent', // tag_id
            'tag_id'
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithNonExistentCol5() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags',
            'post_id',
            'posts_tags',
            'post_id',
            'tag_id',
            'tags',
            'tag_id',
            'non_existent'
        );
    }
    
    public function testThatHasManyThroughWorksAsExpected() {

        $postsModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        $postsModel->hasManyThrough(
            'tags', 'post_id', 'posts_tags', 'post_id', 'tag_id', 'tags', 'tag_id', 'tag_id',
            LeanOrm\TestObjects\TagsModel::class, LeanOrm\TestObjects\TagRecord::class, LeanOrm\TestObjects\TagsCollection::class
        );
        $this->assertEquals(['tags'], $postsModel->getRelationNames());
        $relations = $postsModel->getRelations();
        $this->assertArrayHasKey('tags', $relations);
        $this->assertArrayHasKey('relation_type', $relations['tags']);
        $this->assertArrayHasKey('col_in_my_table_linked_to_join_table', $relations['tags']);
        $this->assertArrayHasKey('join_table', $relations['tags']);
        $this->assertArrayHasKey('col_in_join_table_linked_to_my_table', $relations['tags']);
        $this->assertArrayHasKey('col_in_join_table_linked_to_foreign_table', $relations['tags']);
        $this->assertArrayHasKey('foreign_table', $relations['tags']);
        $this->assertArrayHasKey('col_in_foreign_table_linked_to_join_table', $relations['tags']);
        $this->assertArrayHasKey('primary_key_col_in_foreign_table', $relations['tags']);
        $this->assertArrayHasKey('foreign_models_class_name', $relations['tags']);
        $this->assertArrayHasKey('foreign_models_record_class_name', $relations['tags']);
        $this->assertArrayHasKey('foreign_models_collection_class_name', $relations['tags']);
        $this->assertArrayHasKey('sql_query_modifier', $relations['tags']);
        
        $this->assertEquals(\GDAO\Model::RELATION_TYPE_HAS_MANY_THROUGH, $relations['tags']['relation_type']);
        $this->assertEquals('post_id', $relations['tags']['col_in_my_table_linked_to_join_table']);
        $this->assertEquals('posts_tags', $relations['tags']['join_table']);
        $this->assertEquals('post_id', $relations['tags']['col_in_join_table_linked_to_my_table']);
        $this->assertEquals('tag_id', $relations['tags']['col_in_join_table_linked_to_foreign_table']);
        $this->assertEquals('tags', $relations['tags']['foreign_table']);
        $this->assertEquals('tag_id', $relations['tags']['col_in_foreign_table_linked_to_join_table']);
        $this->assertEquals('tag_id', $relations['tags']['primary_key_col_in_foreign_table']);
        $this->assertEquals(LeanOrm\TestObjects\TagsModel::class, $relations['tags']['foreign_models_class_name']);
        $this->assertEquals(LeanOrm\TestObjects\TagRecord::class, $relations['tags']['foreign_models_record_class_name']);
        $this->assertEquals(LeanOrm\TestObjects\TagsCollection::class, $relations['tags']['foreign_models_collection_class_name']);
        $this->assertNull($relations['tags']['sql_query_modifier']);
        
        $callback = fn(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select => $selectObj;
        $postsModel->hasManyThrough(
            'tags2',  'post_id', 'posts_tags', 'post_id', 'tag_id', 'tags', 'tag_id', 'tag_id',
            \LeanOrm\Model::class, \LeanOrm\Model\Record::class, \LeanOrm\Model\Collection::class, $callback
        );
        $this->assertEquals(['tags', 'tags2'], $postsModel->getRelationNames());
        $this->assertEquals($callback, $postsModel->getRelations()['tags2']['sql_query_modifier']);
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
            ['key_name'=> 'key 1', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+2 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+1 minutes"))],
            ['key_name'=> 'key 2', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+3 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+2 minutes"))],
            ['key_name'=> 'key 3', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+4 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+3 minutes"))],
        ];
        
        $this->insertDataIntoTable('key_value', $recordsToInsert[0]);
        $this->insertDataIntoTable('key_value', $recordsToInsert[1]);
        $this->insertDataIntoTable('key_value', $recordsToInsert[2]);
        
        // only one matching record
        $this->assertEquals(1, $keyValueModel->deleteMatchingDbTableRows(['key_name'=> 'key 1']));
        
        // two matching records
        $this->assertEquals( 2, $keyValueModel->deleteMatchingDbTableRows(['key_name'=> ['key 2', 'key 3']]) );
        
        // no matching record
        $this->assertEquals( 0, $keyValueModel->deleteMatchingDbTableRows(['key_name'=> 'key 55']) );
    }
    
    public function testThatDeleteSpecifiedRecordWorksAsExpected() {
        
        $keyValueModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id','key_value');
        $recordsToInsert = [
            ['key_name'=> 'key 1', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+2 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+1 minutes"))],
            ['key_name'=> 'key 2', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+3 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+2 minutes"))],
            ['key_name'=> 'key 3', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+4 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+3 minutes"))],
        ];
        
        // Deleting a record that has never been saved to the DB
        $this->assertNull( $keyValueModel->deleteSpecifiedRecord($keyValueModel->createNewRecord()));
        $this->assertNull( $keyValueModel->deleteSpecifiedRecord($keyValueModel->createNewRecord($recordsToInsert[0])));
        
        $this->insertDataIntoTable('key_value', $recordsToInsert[0]);
        $this->insertDataIntoTable('key_value', $recordsToInsert[1]);
        $this->insertDataIntoTable('key_value', $recordsToInsert[2]);

        // see \TestSchemaCreatorAndSeeder::populateTables();
        // [ 'author_id'=> 1, 'name'=> 'user_1']
        $aRecord = $keyValueModel->fetchOneRecord($keyValueModel->getSelect()->where('key_name = ? ', 'key 1'));
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
    
    public function testThatDeleteSpecifiedRecordThrowsExceptionForRecordBelongingToADifferentModelClassButSameDbTable()  {
        
        $this->expectException(LeanOrm\InvalidArgumentException::class);
        
        $authorsModel = new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        // should throw exception
        $authorsModel->deleteSpecifiedRecord(
            $this->testModelObjects['authors_with_specialized_collection_and_record']->fetchOneRecord()
        );
    }
    
    public function testThatDeleteSpecifiedRecordThrowsExceptionForRecordBelongingToADifferentDbTable()  {
        
        $this->expectException(LeanOrm\InvalidArgumentException::class);
        
        $authorsModel = new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        // should throw exception
        $authorsModel->deleteSpecifiedRecord(
            (new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? ""))->fetchOneRecord()
        );
    }
    
    public function testThatFetchWorksAsExpected() {
        
        $authorsModel = new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        //empty array with no ids for fetch returns empty array
        $this->assertEquals([], $authorsModel->fetch([]));
         
        //empty array with no ids for fetch returns empty collection
        $potentiallyEmptyCollection = $authorsModel->fetch([], null, [], false, true);
        $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorsCollection::class, $potentiallyEmptyCollection);
        $this->assertCount(0, $potentiallyEmptyCollection);
        
        // fetch rows as arrays into an array not keyed on primary key
        $fiveRecords = $authorsModel->fetch([1,2,3,4,5]);
        
        // fetch rows as arrays into an array keyed on primary key
        $fiveRecords2 = $authorsModel->fetch([1,2,3,4,5], null, [], false, false, true);
        
        // test keyed on PK
        $this->assertEquals([1, 2, 3, 4, 5], \array_keys($fiveRecords2));

        $this->assertIsArray($fiveRecords);
        $this->assertCount(5, $fiveRecords);
        
        $nameColumnValues = ['user_1', 'user_2', 'user_3', 'user_4', 'user_5'];
        
        foreach ($fiveRecords as $record) { $this->assertContains($record["name"], $nameColumnValues); }
        foreach ($fiveRecords2 as $record) { $this->assertContains($record["name"], $nameColumnValues); }
        
        // fetch rows as records into a collection not keyed on primary key
        $fiveRecordsCollection = $authorsModel->fetch([1,2,3,4,5], null, [], false, true);
        
        // fetch rows as records into a collection keyed on primary key
        $fiveRecords2Collection = $authorsModel->fetch([1,2,3,4,5], null, [], false, true, true);

        $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorsCollection::class, $fiveRecordsCollection);
        $this->assertCount(5, $fiveRecordsCollection);
        
        $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorsCollection::class, $fiveRecords2Collection);
        $this->assertCount(5, $fiveRecords2Collection);
        // test keyed on PK
        $this->assertEquals([1, 2, 3, 4, 5], $fiveRecords2Collection->getKeys());

        foreach ($fiveRecordsCollection as $record) { $this->assertContains($record["name"], $nameColumnValues); }
        foreach ($fiveRecords2Collection as $record) { $this->assertContains($record["name"], $nameColumnValues); }
        
        // fetch rows as records into an array not keyed on primary key
        $fiveRecordsArrayOfRecords = $authorsModel->fetch([1,2,3,4,5], null, [], true, false);
        
        // fetch rows as records into an array keyed on primary key
        $fiveRecords2ArrayOfRecords = $authorsModel->fetch([1,2,3,4,5], null, [], true, false, true);
        
        $this->assertIsArray($fiveRecordsArrayOfRecords);
        $this->assertCount(5, $fiveRecordsArrayOfRecords);
        
        $this->assertIsArray($fiveRecords2ArrayOfRecords);
        $this->assertCount(5, $fiveRecords2ArrayOfRecords);
        // test keyed on PK
        $this->assertEquals([1, 2, 3, 4, 5], \array_keys($fiveRecords2ArrayOfRecords));
        
        foreach ($fiveRecordsArrayOfRecords as $record) {

            $this->assertContains($record["name"], $nameColumnValues); 
            $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $record);
        }
        
        foreach ($fiveRecords2ArrayOfRecords as $record) {
            
            $this->assertContains($record["name"], $nameColumnValues); 
            $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $record);
        }
        
        ////////////////////////////////////////////////////////////////////////
        //test relationship inclusion & the use of a query object
        // fetch rows as arrays into an array
        $threeRecordsArrayOfRecords = $authorsModel->fetch(
            [1,2,3,4,5], $authorsModel->getSelect()->where(' author_id <= 3 '), 
            ['posts'], false, false
        );
        
        $this->assertIsArray($threeRecordsArrayOfRecords);
        $this->assertCount(3, $threeRecordsArrayOfRecords);
        $this->assertEquals(['user_1', 'user_2', 'user_3'], array_column($threeRecordsArrayOfRecords, 'name'));
        
        foreach($threeRecordsArrayOfRecords as $authorRecord) {
            
            $this->assertIsArray($authorRecord['posts']);
            
            foreach($authorRecord['posts'] as $post) {
                
                $this->assertEquals($post['author_id'], $authorRecord['author_id']);
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

        $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorsCollection::class, $threeRecordsCollectionOfRecords);
        $this->assertCount(3, $threeRecordsCollectionOfRecords);
        $this->assertEquals(['user_1', 'user_2', 'user_3'], $threeRecordsCollectionOfRecords->getColVals('name'));
        
        foreach($threeRecordsCollectionOfRecords as $authorRecord) {
            
            $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $authorRecord);
            $this->assertInstanceOf(LeanOrm\TestObjects\PostsCollection::class, $authorRecord->posts);
            $this->assertInstanceOf(LeanOrm\TestObjects\PostsCollection::class, $authorRecord['posts']);
            
            foreach($authorRecord['posts'] as $post) {
                
                $this->assertInstanceOf(\LeanOrm\TestObjects\PostRecord::class, $post);
                $this->assertEquals($post['author_id'], $authorRecord['author_id']);
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
        $this->assertEquals([], $emptyModel->fetchCol());
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
        $this->assertCount(1, $secondAuthorInTheTable->one_post);
        
        $this->assertEquals(['2', '4'], $secondAuthorInTheTable->posts->getColVals('post_id'));
        $this->assertEquals(['2', '2'], $secondAuthorInTheTable->posts->getColVals('author_id'));
        $this->assertEquals(['Post 2', 'Post 4'], $secondAuthorInTheTable->posts->getColVals('title'));
        $this->assertEquals(['Post Body 2', 'Post Body 4'], $secondAuthorInTheTable->posts->getColVals('body'));
        
        $this->assertEquals(['2'], $secondAuthorInTheTable->one_post->getColVals('post_id'));
        $this->assertEquals(['2'], $secondAuthorInTheTable->one_post->getColVals('author_id'));
        $this->assertEquals(['Post 2'], $secondAuthorInTheTable->one_post->getColVals('title'));
        $this->assertEquals(['Post Body 2'], $secondAuthorInTheTable->one_post->getColVals('body'));
        
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
                null, 
                [
                    'author', 'author_with_callback', 
                    'comments', 'comments_with_callback', 
                    'summary', 'summary_with_callback', 
                    'posts_tags', 'tags', 'tags_with_callback'
                ]
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
            $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author_with_callback);
            $this->assertEquals($postRecord->author->getData(), $postRecord->author_with_callback->getData());
            
            // post's comments
            $this->assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments);
            $this->assertCount(1, $postRecord->comments);
            $this->assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            $this->assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments_with_callback);
            $this->assertCount(1, $postRecord->comments_with_callback);
            $this->assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments_with_callback);
            
            // summary of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            $this->assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary_with_callback);
            $this->assertEquals($postRecord->summary->getData(), $postRecord->summary_with_callback->getData());
            
            // post's posts_tags
            $this->assertInstanceOf(\LeanOrm\TestObjects\PostsTagsCollection::class, $postRecord->posts_tags);
            $this->assertCount(1, $postRecord->posts_tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            $this->assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags);
            $this->assertCount(1, $postRecord->tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
            
            $this->assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags_with_callback);
            $this->assertCount(1, $postRecord->tags_with_callback);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags_with_callback);
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
            $this->assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author_with_callback);
            $this->assertEquals($postRecord->author->getData(), $postRecord->author_with_callback->getData());
            
            // post's comments
            $this->assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments);  
            $this->assertCount(1, $postRecord->comments);
            $this->assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            $this->assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments_with_callback);  
            $this->assertCount(1, $postRecord->comments_with_callback);
            $this->assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments_with_callback);
            
            // summary of the post
            $this->assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            $this->assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary_with_callback);
            $this->assertEquals($postRecord->summary->getData(), $postRecord->summary_with_callback->getData());
            
            // post's posts_tags
            $this->assertInstanceOf(\LeanOrm\TestObjects\PostsTagsCollection::class, $postRecord->posts_tags);
            $this->assertCount(1, $postRecord->posts_tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            $this->assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags);
            $this->assertCount(1, $postRecord->tags);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
            
            $this->assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags_with_callback);
            $this->assertCount(1, $postRecord->tags_with_callback);
            $this->assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags_with_callback);
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
    
    public function testThatFetchValueWorksAsExpected() {
        
        $authorsModel = new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        // test without any agurments, should return the value in the first row
        // & first column (in this case author_id) of the authors table 
        // associated with the \LeanOrm\TestObjects\AuthorsModel
        $this->assertEquals('1', $authorsModel->fetchValue().'');
        
        // test with a query that matches more than one row, should return the
        // value in the first row & column of the result set.
        $this->assertEquals(
            '6', 
            $authorsModel->fetchValue(
                $authorsModel->getSelect()->where(' author_id > 5 ')
            ).''
        );
        
        $this->assertEquals(
            '10', 
            $authorsModel->fetchValue( $authorsModel->getSelect()->where(' author_id > 9 ') ).''
        );
        
        // test with a query that matches no row, should return null
        $this->assertNull($authorsModel->fetchValue($authorsModel->getSelect()->where(' author_id > 777 ')));
        
        // test with a query that returns the result of an aggregate function
        $this->assertEquals(
            '10', 
            $authorsModel->fetchValue( $authorsModel->getSelect()->cols([' MAX(author_id) ']) ).''
        );
        
        // Test with empty table
        $emptyModel = new LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        $this->assertNull($emptyModel->fetchValue());
    }
    
    public function testThatGetCurrentConnectionInfoWorksAsExpected() {
        
        $authorsModel = new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $this->assertArrayHasAllKeys(
            $authorsModel->getCurrentConnectionInfo(), 
            [
                'database_server_info', 'connection_is_persistent',
                'pdo_client_version', 'database_server_version', 
                'connection_status', 'driver_name', 
            ]
        );
    }
    
    public function testThatGetDefaultColValsWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $this->assertArrayHasAllKeys(
            $commentsModel->getDefaultColVals(), 
            $commentsModel->getTableColNames()
        );
    }
    
    public function testThatGetLoggerWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $this->assertNull($commentsModel->getLogger());
        
        $commentsModel->setLogger(static::$psrLogger);
        $this->assertSame(static::$psrLogger, $commentsModel->getLogger());
    }
    
    public function testThatGetPDOWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $this->assertInstanceOf(\PDO::class, $commentsModel->getPDO());
    }
    
    public function testThatGetPdoDriverNameWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $this->assertEquals(
            static::$atlasPdo->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME), 
            $commentsModel->getPdoDriverName()
        );
    }
    
    public function testThatGetQueryLogWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        //$commentsModel->setLogger(static::$psrLogger);
        $commentsModel->enableQueryLogging();
        $postsModel->disableQueryLogging();
        
        $commentsModel->fetchCol();
        $commentsModel->fetchValue();
        $commentsModel->fetchRecordsIntoCollection();
        
        $postsModel->fetchCol();
        $postsModel->fetchValue();
        $postsModel->fetchRecordsIntoCollection();
        
        foreach($commentsModel->getQueryLog() as $commentQueryLogEntry){
            
            $this->assertArrayHasAllKeys(
                $commentQueryLogEntry, 
                ['sql', 'bind_params', 'date_executed', 'class_method', 'line_of_execution']
            );
        }
        
        $this->assertEquals([], $postsModel->getQueryLog());
        \LeanOrm\Model::clearQueryLogForAllInstances();
    }
    
    public function testThatGetQueryLogForAllInstancesWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $tagsModel = new LeanOrm\TestObjects\TagsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        //$commentsModel->setLogger(static::$psrLogger);
        $commentsModel->enableQueryLogging();
        $postsModel->enableQueryLogging();
        $tagsModel->disableQueryLogging();
        
        $commentsModel->fetchCol();
        $commentsModel->fetchRecordsIntoCollection();
        
        $postsModel->fetchCol();
        $postsModel->fetchRecordsIntoCollection();
        
        $tagsModel->fetchCol();
        $tagsModel->fetchRecordsIntoCollection();
        
        ////////////////////////////////////////////////////////////////////////
        $logForAllInstances = \LeanOrm\Model::getQueryLogForAllInstances();
        $keysToCheck = [
            static::$dsn . '::' . get_class($commentsModel),
            static::$dsn . '::' . get_class($postsModel),
        ];
        $this->assertArrayHasAllKeys($logForAllInstances, $keysToCheck);
        $this->assertArrayNotHasKey(static::$dsn . '::' . get_class($tagsModel), $logForAllInstances);
        
        foreach($logForAllInstances as $dsnModelNameKey => $queryLogEntriesForDsnAndModelName){
            
            foreach($queryLogEntriesForDsnAndModelName as $queryLogEntry) {
                $this->assertArrayHasAllKeys(
                    $queryLogEntry,
                    ['sql', 'bind_params', 'date_executed', 'class_method', 'line_of_execution']
                );
            }
        }
        
        ////////////////////////////////////////////////////////////////////////
        $logForCommentsModel = 
            \LeanOrm\Model::getQueryLogForAllInstances(static::$dsn, $commentsModel);
        
        foreach($logForCommentsModel as $queryLogEntry) {
            $this->assertArrayHasAllKeys(
                $queryLogEntry,
                ['sql', 'bind_params', 'date_executed', 'class_method', 'line_of_execution']
            );
        }
        
        ////////////////////////////////////////////////////////////////////////
        $logForPostsModel = 
            \LeanOrm\Model::getQueryLogForAllInstances(static::$dsn, $postsModel);
        
        foreach($logForPostsModel as $queryLogEntry) {
            $this->assertArrayHasAllKeys(
                $queryLogEntry,
                ['sql', 'bind_params', 'date_executed', 'class_method', 'line_of_execution']
            );
        }
        
        ////////////////////////////////////////////////////////////////////////
        $this->assertEquals([], \LeanOrm\Model::getQueryLogForAllInstances(static::$dsn, $tagsModel));

        ////////////////////////////////////////////////////////////////////////
        \LeanOrm\Model::clearQueryLogForAllInstances();
    }
    
    public function testThatClearQueryLogForAllInstancesWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $tagsModel = new LeanOrm\TestObjects\TagsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $commentsModel->enableQueryLogging();
        $postsModel->enableQueryLogging();
        $tagsModel->disableQueryLogging();
        
        $commentsModel->fetchCol();
        $commentsModel->fetchRecordsIntoCollection();
        
        $postsModel->fetchCol();
        $postsModel->fetchRecordsIntoCollection();
        
        $tagsModel->fetchCol();
        $tagsModel->fetchRecordsIntoCollection();
        
        ////////////////////////////////////////////////////////////////////////
        $logForAllInstances = \LeanOrm\Model::getQueryLogForAllInstances();
        $this->assertCount(2, $logForAllInstances);
        
        ////////////////////////////////////////////////////////////////////////
        \LeanOrm\Model::clearQueryLogForAllInstances();
        $this->assertCount(0, \LeanOrm\Model::getQueryLogForAllInstances());
    }
    
    public function testThatsetLoggerWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $this->assertNull($commentsModel->getLogger());
        
        // test fluent return
        $this->assertSame($commentsModel, $commentsModel->setLogger(static::$psrLogger));
        
        // test that the setter worked as expected
        $this->assertSame(static::$psrLogger, $commentsModel->getLogger());
    }
    
    public function testThatGetSelectWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $this->assertInstanceOf(
            get_class(static::$auraQueryFactory->newSelect()),
            $commentsModel->getSelect()
        );
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
