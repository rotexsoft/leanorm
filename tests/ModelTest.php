<?php
use VersatileCollections\GenericCollection;

/**
 * Description of ModelTest
 *
 * @author Rotimi Adegbamigbe
 */
class ModelTest extends \PHPUnit\Framework\TestCase {
    
    use CommonPropertiesAndMethodsTrait;
    
    protected string $modelClass = \LeanOrm\Model::class;


    protected const POST_POST_IDS =     [ '1', '2', '3', '4', ];
    protected const POST_AUTHOR_IDS =   [ '1', '2', '1', '2', ];
    protected const POST_TITLES =       [ 'Post 1', 'Post 2', 'Post 3', 'Post 4', ];
    protected const POST_BODIES = 
        [ 'Post Body 1', 'Post Body 2', 'Post Body 3', 'Post Body 4', ];

    public function testThatConstructorWithNoArgsWorksAsExpected() {
        
        $this->expectException(\PDOException::class);

        $model = new $this->modelClass();
    }
    
    public function testThatConstructorWithNonExistentTableNameWorksAsExpected() {
        
        $this->expectException(\LeanOrm\BadModelTableNameException::class);

        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'id','non_existent_table');
    }
    
    public function testThatConstructorWithNonExistentPrimaryColumnNameWorksAsExpected() {
        
        $this->expectException(\LeanOrm\BadModelPrimaryColumnNameException::class);

        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'non_existent_column','authors');
    }
    
    public function testThatConstructorWithNoPrimaryColWorksAsExpected() {
        
        $this->expectException(\GDAO\ModelPrimaryColNameNotSetDuringConstructionException::class);

        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'','v_authors');
    }
    
    public function testThatConstructorWithNoTableNameWorksAsExpected() {
        
        $this->expectException(\GDAO\ModelTableNameNotSetDuringConstructionException::class);

        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','');
    }
    
    public function testThatConstructorWorksAsExpected() {
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        
        self::assertEquals(static::$dsn, $model->getDsn());
        self::assertEquals(static::$username ?? "", $model->getUsername());
        self::assertEquals(static::$password ?? "", $model->getPasswd());
        self::assertEquals([], $model->getPdoDriverOpts());
        self::assertEquals('author_id', $model->getPrimaryCol());
        self::assertEquals('authors', $model->getTableName());
        
        self::assertContains('author_id', $model->getTableColNames());
        self::assertContains('name', $model->getTableColNames());
        self::assertContains('m_timestamp', $model->getTableColNames());
        self::assertContains('date_created', $model->getTableColNames());
        
        // Test that the schema query gets & sets the primary key col
        // when an empty primary key value is passed to the constructor
        $model = new $this->modelClass(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION], '', 'authors'
        );
        self::assertEquals('author_id', $model->getPrimaryCol());
        self::assertEquals([PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION], $model->getPdoDriverOpts());
        
        // Test that primary key val is computed properly for
        // a model that has hard-coded table col metadata
        $postTagsModel = new \LeanOrm\TestObjects\PostsTagsModel(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION], '', ''
        );
        self::assertEquals('posts_tags_id', $postTagsModel->getPrimaryCol());
    }
    
    public function testCreateNewCollection() {
        
        $modelWithMockCollAndRec = $this->testModelObjects['authors_with_specialized_collection_and_record'];
        $nonGenericCollection = $modelWithMockCollAndRec->createNewCollection();
        
        //exact class
        self::assertEquals(\CollectionForTestingPublicAndProtectedMethods::class, get_class($nonGenericCollection));
        
        //has the right parent class
        self::assertInstanceOf(\LeanOrm\Model\Collection::class, $nonGenericCollection);
        self::assertInstanceOf(\GDAO\Model\CollectionInterface::class, $nonGenericCollection);
        
        //exact class test
        $modelWithAuthorCollAndRec = new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $coll_generic = $modelWithAuthorCollAndRec->createNewCollection();
        self::assertEquals(LeanOrm\TestObjects\AuthorsCollection::class, get_class($coll_generic));
        
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
        
        self::assertCount(2, $collection1WithRecords);
        self::assertCount(3, $collection2WithRecords);
        
        //generic model
        $genericModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'author_id','authors');
        $genericModel->setCollectionClassName('');
        self::assertEquals(\LeanOrm\Model\Collection::class, get_class($genericModel->createNewCollection()));
    }

    public function testCreateNewRecord() {
        
        $modelWithMockCollAndRec =
            $this->testModelObjects['authors_with_specialized_collection_and_record'];
        $specializedRecord = $modelWithMockCollAndRec->createNewRecord();
        
        //exact class
        self::assertEquals(
            \RecordForTestingPublicAndProtectedMethods::class, 
            get_class($specializedRecord)
        );
        
        //has the right parent class
        self::assertInstanceOf(\LeanOrm\Model\Record::class, $specializedRecord);
        self::assertInstanceOf(\GDAO\Model\RecordInterface::class, $specializedRecord);
        
        ////////////////////////////////////////////////////////////////////////
        // exact class test
        $modelWithLeanormCollAndRec = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        $modelWithLeanormCollAndRec->setRecordClassName('');
        $leanOrmRecord = $modelWithLeanormCollAndRec->createNewRecord();
        self::assertEquals(\LeanOrm\Model\Record::class, get_class($leanOrmRecord));
        
        // exact class test
        $modelWithAuthorCollAndRec =  new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        self::assertEquals(LeanOrm\TestObjects\AuthorRecord::class, get_class($modelWithAuthorCollAndRec->createNewRecord()));
    }
    
    public function testThatBelongsToThrowsExceptionWhenRelationNameCollidesWithColumnName() {
        
        $this->expectException(\GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        // relation name with the same name as p key column
        $model->belongsTo('author_id', '', '', '', '');
    }
    
    public function testThatBelongsToThrowsExceptionWithInvalidForeignModelClassName() {
        
        $this->expectException(\LeanOrm\BadModelClassNameForFetchingRelatedDataException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        // relation name with the same name as p key column
        $model->belongsTo(
            'post', 'post_id', 'posts', 'post_id', 'post_id',
            \PDO::class, // bad Model class name
            LeanOrm\TestObjects\PostRecord::class,
            \LeanOrm\TestObjects\PostsCollection::class
        );
    }
    
    public function testThatBelongsToThrowsExceptionWithInvalidForeignRecordClassName() {
        
        $this->expectException(\LeanOrm\BadRecordClassNameForFetchingRelatedDataException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        // relation name with the same name as p key column
        $model->belongsTo(
            'post', 'post_id', 'posts', 'post_id', 'post_id',
            \LeanOrm\TestObjects\PostsModel::class,
            \PDO::class, // bad Record class name
            \LeanOrm\TestObjects\PostsCollection::class
        );
    }
    
    public function testThatBelongsToThrowsExceptionWithInvalidForeignCollectionClassName() {
        
        $this->expectException(\LeanOrm\BadCollectionClassNameForFetchingRelatedDataException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        // relation name with the same name as p key column
        $model->belongsTo(
            'post', 'post_id', 'posts', 'post_id', 'post_id',
            \LeanOrm\TestObjects\PostsModel::class,
            \LeanOrm\TestObjects\PostRecord::class,
            \PDO::class  // bad Collection class name
        );
    }
    
    public function testThatBelongsToThrowsExceptionWithNonExistentForeignTableName() {
        
        $this->expectException(\LeanOrm\BadModelTableNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->belongsTo(
            'post', 'post_id', 
            'non_existent_foreign_table', // Non-existent foreign table
            'post_id', 'post_id'
        );
    }
    
    public function testThatBelongsToThrowsExceptionWithNonExistentCol1() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        // relation name with the same name as p key column
        $model->belongsTo('post', 'non_existent', 'posts', 'post_id', 'post_id');
    }
    
    public function testThatBelongsToThrowsExceptionWithNonExistentCol2() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        
        // relation name with the same name as p key column
        $model->belongsTo('post', 'post_id', 'posts', 'non_existent', 'post_id');
    }
    
    public function testThatBelongsToThrowsExceptionWithNonExistentCol3() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        
        // relation name with the same name as p key column
        $model->belongsTo('post', 'post_id', 'posts', 'post_id', 'non_existent');
    }
    
    public function testThatBelongsToWorksAsExpected() {

        $postsModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        $postsModel->belongsTo(
            'author', 'author_id', 'authors', 'author_id', 'author_id', $this->modelClass,
            \LeanOrm\Model\Record::class, \LeanOrm\Model\Collection::class, null
        );
        self::assertEquals(['author'], $postsModel->getRelationNames());
        
        $relations = $postsModel->getRelations();
        self::assertArrayHasKey('author', $relations);
        self::assertArrayHasKey('relation_type', $relations['author']);
        self::assertArrayHasKey('foreign_key_col_in_my_table', $relations['author']);
        self::assertArrayHasKey('foreign_table', $relations['author']);
        self::assertArrayHasKey('foreign_key_col_in_foreign_table', $relations['author']);
        self::assertArrayHasKey('primary_key_col_in_foreign_table', $relations['author']);
        self::assertArrayHasKey('foreign_models_class_name', $relations['author']);
        self::assertArrayHasKey('foreign_models_record_class_name', $relations['author']);
        self::assertArrayHasKey('foreign_models_collection_class_name', $relations['author']);
        self::assertArrayHasKey('sql_query_modifier', $relations['author']);
        
        self::assertEquals(\GDAO\Model::RELATION_TYPE_BELONGS_TO, $relations['author']['relation_type']);
        self::assertEquals('author_id', $relations['author']['foreign_key_col_in_my_table']);
        self::assertEquals('authors', $relations['author']['foreign_table']);
        self::assertEquals('author_id', $relations['author']['foreign_key_col_in_foreign_table']);
        self::assertEquals('author_id', $relations['author']['primary_key_col_in_foreign_table']);
        self::assertEquals($this->modelClass, $relations['author']['foreign_models_class_name']);
        self::assertEquals(\LeanOrm\Model\Record::class, $relations['author']['foreign_models_record_class_name']);
        self::assertEquals(\LeanOrm\Model\Collection::class, $relations['author']['foreign_models_collection_class_name']);
        self::assertNull($relations['author']['sql_query_modifier']);
        
        $callback = fn(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select => $selectObj;
        $postsModel->belongsTo(
            'author2', 'author_id', 'authors', 'author_id', 'author_id', $this->modelClass,
            \LeanOrm\TestObjects\PostRecord::class, \LeanOrm\TestObjects\PostsCollection::class, $callback
        );
        self::assertEquals(['author', 'author2'], $postsModel->getRelationNames());
        self::assertEquals($callback, $postsModel->getRelations()['author2']['sql_query_modifier']);
        self::assertEquals(\LeanOrm\TestObjects\PostRecord::class, $postsModel->getRelations()['author2']['foreign_models_record_class_name']);
        self::assertEquals(\LeanOrm\TestObjects\PostsCollection::class, $postsModel->getRelations()['author2']['foreign_models_collection_class_name']);
    }
    
    public function testThatHasOneThrowsExceptionWhenRelationNameCollidesWithColumnName() {
        
        $this->expectException(\GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        // relation name with the same name as p key column
        $model->hasOne('author_id', '', '', '', '');
    }
    
    public function testThatHasOneThrowsExceptionWithInvalidForeignModelClassName() {
        
        $this->expectException(\LeanOrm\BadModelClassNameForFetchingRelatedDataException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasOne(
            'post', 'post_id', 'posts', 'post_id', 'post_id',
            \PDO::class, // bad Model class name
            LeanOrm\TestObjects\PostRecord::class,
            \LeanOrm\TestObjects\PostsCollection::class
        );
    }
    
    public function testThatHasOneThrowsExceptionWithInvalidForeignRecordClassName() {
        
        $this->expectException(\LeanOrm\BadRecordClassNameForFetchingRelatedDataException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasOne(
            'post', 'post_id', 'posts', 'post_id', 'post_id',
            \LeanOrm\TestObjects\PostsModel::class,
            \PDO::class, // bad Record class name
            \LeanOrm\TestObjects\PostsCollection::class
        );
    }
    
    public function testThatHasOneThrowsExceptionWithNonExistentForeignTableName() {
        
        $this->expectException(\LeanOrm\BadModelTableNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasOne(
            'post', 'post_id', 
            'non_existent_foreign_table', // Non-existent foreign table
            'post_id', 'post_id'
        );
    }
    
    public function testThatHasOneThrowsExceptionWithInvalidForeignCollectionClassName() {
        
        $this->expectException(\LeanOrm\BadCollectionClassNameForFetchingRelatedDataException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasOne(
            'post', 'post_id', 'posts', 'post_id', 'post_id',
            \LeanOrm\TestObjects\PostsModel::class,
            \LeanOrm\TestObjects\PostRecord::class,
            \PDO::class  // bad Collection class name
        );
    }
    
    public function testThatHasOneThrowsExceptionWithNonExistentCol1() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasOne('post', 'non_existent', 'posts', 'post_id', 'post_id');
    }
    
    public function testThatHasOneThrowsExceptionWithNonExistentCol2() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasOne('post', 'post_id', 'posts', 'non_existent', 'post_id');
    }
    
    public function testThatHasOneThrowsExceptionWithNonExistentCol3() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasOne('post', 'post_id', 'posts', 'post_id', 'non_existent');
    }
    
    public function testThatHasOneWorksAsExpected() {

        $postsModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        $postsModel->hasOne(
            'author', 'author_id', 'authors', 'author_id', 'author_id', $this->modelClass,
            \LeanOrm\Model\Record::class, \LeanOrm\Model\Collection::class, null
        );
        self::assertEquals(['author'], $postsModel->getRelationNames());
        $relations = $postsModel->getRelations();
        self::assertArrayHasKey('author', $relations);
        self::assertArrayHasKey('relation_type', $relations['author']);
        self::assertArrayHasKey('foreign_key_col_in_my_table', $relations['author']);
        self::assertArrayHasKey('foreign_table', $relations['author']);
        self::assertArrayHasKey('foreign_key_col_in_foreign_table', $relations['author']);
        self::assertArrayHasKey('primary_key_col_in_foreign_table', $relations['author']);
        self::assertArrayHasKey('foreign_models_class_name', $relations['author']);
        self::assertArrayHasKey('foreign_models_record_class_name', $relations['author']);
        self::assertArrayHasKey('foreign_models_collection_class_name', $relations['author']);
        self::assertArrayHasKey('sql_query_modifier', $relations['author']);
        
        self::assertEquals(\GDAO\Model::RELATION_TYPE_HAS_ONE, $relations['author']['relation_type']);
        self::assertEquals('author_id', $relations['author']['foreign_key_col_in_my_table']);
        self::assertEquals('authors', $relations['author']['foreign_table']);
        self::assertEquals('author_id', $relations['author']['foreign_key_col_in_foreign_table']);
        self::assertEquals('author_id', $relations['author']['primary_key_col_in_foreign_table']);
        self::assertEquals($this->modelClass, $relations['author']['foreign_models_class_name']);
        self::assertEquals(\LeanOrm\Model\Record::class, $relations['author']['foreign_models_record_class_name']);
        self::assertEquals(\LeanOrm\Model\Collection::class, $relations['author']['foreign_models_collection_class_name']);
        self::assertNull($relations['author']['sql_query_modifier']);
        
        $callback = fn(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select => $selectObj;
        $postsModel->hasOne(
            'author2', 'author_id', 'authors', 'author_id', 'author_id', $this->modelClass,
            \LeanOrm\TestObjects\AuthorRecord::class, \LeanOrm\TestObjects\AuthorsCollection::class, $callback
        );
        self::assertEquals(['author', 'author2'], $postsModel->getRelationNames());
        self::assertEquals($callback, $postsModel->getRelations()['author2']['sql_query_modifier']);
        self::assertEquals(\LeanOrm\TestObjects\AuthorRecord::class, $postsModel->getRelations()['author2']['foreign_models_record_class_name']);
        self::assertEquals(\LeanOrm\TestObjects\AuthorsCollection::class, $postsModel->getRelations()['author2']['foreign_models_collection_class_name']);
    }
    
    public function testThatHasManyThrowsExceptionWhenRelationNameCollidesWithColumnName() {
        
        $this->expectException(\GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        // relation name with the same name as p key column
        $model->hasMany('author_id', '', '', '', '');
    }
    
    public function testThatHasManyThrowsExceptionWithInvalidForeignModelClassName() {
        
        $this->expectException(\LeanOrm\BadModelClassNameForFetchingRelatedDataException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        
        $model->hasMany(
            'posts', 'post_id', 'posts', 'post_id', 'post_id',
            \PDO::class, // bad Model class name
            LeanOrm\TestObjects\PostRecord::class,
            \LeanOrm\TestObjects\PostsCollection::class
        );
    }
    
    public function testThatHasManyThrowsExceptionWithInvalidForeignRecordClassName() {
        
        $this->expectException(\LeanOrm\BadRecordClassNameForFetchingRelatedDataException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasMany(
            'post', 'post_id', 'posts', 'post_id', 'post_id',
            \LeanOrm\TestObjects\PostsModel::class,
            \PDO::class, // bad Record class name
            \LeanOrm\TestObjects\PostsCollection::class
        );
    }
    
    public function testThatHasManyThrowsExceptionWithNonExistentForeignTableName() {
        
        $this->expectException(\LeanOrm\BadModelTableNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasMany(
            'post', 'post_id', 
            'non_existent', // non-existent foreign table
            'post_id', 'post_id'
        );
    }
    
    public function testThatHasManyThrowsExceptionWithInvalidForeignCollectionClassName() {
        
        $this->expectException(\LeanOrm\BadCollectionClassNameForFetchingRelatedDataException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasMany(
            'post', 'post_id', 'posts', 'post_id', 'post_id',
            \LeanOrm\TestObjects\PostsModel::class,
            \LeanOrm\TestObjects\PostRecord::class,
            \PDO::class  // bad Collection class name
        );
    }
    
    public function testThatHasManyThrowsExceptionWithNonExistentCol1() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasMany('post', 'non_existent', 'posts', 'post_id', 'post_id');
    }
    
    public function testThatHasManyThrowsExceptionWithNonExistentCol2() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasMany('post', 'post_id', 'posts', 'non_existent', 'post_id');
    }
    
    public function testThatHasManyThrowsExceptionWithNonExistentCol3() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'comment_id', 'comments');
        $model->hasMany('post', 'post_id', 'posts', 'post_id', 'non_existent');
    }
    
    public function testThatHasManyWorksAsExpected() {

        $postsModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        $postsModel->hasMany(
            'comments', 'post_id', 'comments', 'post_id', 'comment_id',
            LeanOrm\TestObjects\CommentsModel::class,
            LeanOrm\TestObjects\CommentRecord::class,
            LeanOrm\TestObjects\CommentsCollection::class
        );
        self::assertEquals(['comments'], $postsModel->getRelationNames());
        $relations = $postsModel->getRelations();
        self::assertArrayHasKey('comments', $relations);
        self::assertArrayHasKey('relation_type', $relations['comments']);
        self::assertArrayHasKey('foreign_key_col_in_my_table', $relations['comments']);
        self::assertArrayHasKey('foreign_table', $relations['comments']);
        self::assertArrayHasKey('foreign_key_col_in_foreign_table', $relations['comments']);
        self::assertArrayHasKey('primary_key_col_in_foreign_table', $relations['comments']);
        self::assertArrayHasKey('foreign_models_class_name', $relations['comments']);
        self::assertArrayHasKey('foreign_models_record_class_name', $relations['comments']);
        self::assertArrayHasKey('foreign_models_collection_class_name', $relations['comments']);
        self::assertArrayHasKey('sql_query_modifier', $relations['comments']);
        
        self::assertEquals(\GDAO\Model::RELATION_TYPE_HAS_MANY, $relations['comments']['relation_type']);
        self::assertEquals('post_id', $relations['comments']['foreign_key_col_in_my_table']);
        self::assertEquals('comments', $relations['comments']['foreign_table']);
        self::assertEquals('post_id', $relations['comments']['foreign_key_col_in_foreign_table']);
        self::assertEquals('comment_id', $relations['comments']['primary_key_col_in_foreign_table']);
        self::assertEquals(LeanOrm\TestObjects\CommentsModel::class, $relations['comments']['foreign_models_class_name']);
        self::assertEquals(LeanOrm\TestObjects\CommentRecord::class, $relations['comments']['foreign_models_record_class_name']);
        self::assertEquals(LeanOrm\TestObjects\CommentsCollection::class, $relations['comments']['foreign_models_collection_class_name']);
        self::assertNull($relations['comments']['sql_query_modifier']);
        
        $callback = fn(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select => $selectObj;
        $postsModel->hasMany(
            'comments2', 'post_id', 'comments', 'post_id', 'comment_id', $this->modelClass,
            \LeanOrm\TestObjects\CommentRecord::class, \LeanOrm\TestObjects\CommentsCollection::class, 
            $callback
        );
        self::assertEquals(['comments', 'comments2'], $postsModel->getRelationNames());
        self::assertEquals($callback, $postsModel->getRelations()['comments2']['sql_query_modifier']);
        self::assertEquals(\LeanOrm\TestObjects\CommentRecord::class, $postsModel->getRelations()['comments2']['foreign_models_record_class_name']);
        self::assertEquals(\LeanOrm\TestObjects\CommentsCollection::class, $postsModel->getRelations()['comments2']['foreign_models_collection_class_name']);
    }
    
    public function testThatHasManyThroughThrowsExceptionWhenRelationNameCollidesWithColumnName() {
        
        $this->expectException(\GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        // relation name with the same name as p key column
        $model->hasManyThrough('author_id', '', '', '', '', '', '', '');
    }
    
    public function testThatHasManyThroughThrowsExceptionWithInvalidForeignModelClassName() {
        
        $this->expectException(\LeanOrm\BadModelClassNameForFetchingRelatedDataException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags', 'post_id', 'posts_tags', 'post_id', 'tag_id', 'tags', 'tag_id', 'tag_id',
            \PDO::class, // bad Model class name
            LeanOrm\TestObjects\TagRecord::class,
            LeanOrm\TestObjects\TagsCollection::class
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithInvalidForeignRecordClassName() {
        
        $this->expectException(\LeanOrm\BadRecordClassNameForFetchingRelatedDataException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags', 'post_id', 'posts_tags', 'post_id', 'tag_id', 'tags', 'tag_id', 'tag_id',
            LeanOrm\TestObjects\TagsModel::class,
            \PDO::class, // bad Record class name
            LeanOrm\TestObjects\TagsCollection::class
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithNonExistentForeignTableName() {
        
        $this->expectException(\LeanOrm\BadModelTableNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags', 'post_id', 'posts_tags', 'post_id', 'tag_id',
            'non_existent_table', // non-existent foreign table
            'tag_id', 'tag_id'
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithNonExistentJoinTableName() {
        
        $this->expectException(\LeanOrm\BadModelTableNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags', 'post_id',
            'non_existent', // non-existent join table
            'post_id', 'tag_id', 'tags', 'tag_id', 'tag_id'
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithInvalidForeignCollectionClassName() {
        
        $this->expectException(\LeanOrm\BadCollectionClassNameForFetchingRelatedDataException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags', 'post_id', 'posts_tags', 'post_id', 'tag_id', 'tags', 'tag_id', 'tag_id',
            LeanOrm\TestObjects\TagsModel::class,
            LeanOrm\TestObjects\TagRecord::class,
            \PDO::class  // bad Collection class name
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithNonExistentCol1() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags', 
            'non_existent', // post_id
            'posts_tags', 'post_id', 'tag_id', 'tags', 'tag_id', 'tag_id'
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithNonExistentCol2() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags', 'post_id', 'posts_tags',
            'non_existent', // post_id
            'tag_id', 'tags', 'tag_id', 'tag_id'
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithNonExistentCol3() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags', 'post_id', 'posts_tags', 'post_id',
            'non_existent', // tag_id
            'tags', 'tag_id', 'tag_id'
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithNonExistentCol4() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags', 'post_id', 'posts_tags', 'post_id', 'tag_id', 'tags',
            'non_existent', // tag_id
            'tag_id'
        );
    }
    
    public function testThatHasManyThroughThrowsExceptionWithNonExistentCol5() {
        
        $this->expectException(\LeanOrm\BadModelColumnNameException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [],'post_id', 'posts');
        $model->hasManyThrough(
            'tags', 'post_id', 'posts_tags', 'post_id', 'tag_id', 'tags', 'tag_id',
            'non_existent' // bad col name
        );
    }
    
    public function testThatHasManyThroughWorksAsExpected() {

        $postsModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        $postsModel->hasManyThrough(
            'tags', 'post_id', 'posts_tags', 'post_id', 'tag_id', 'tags', 'tag_id', 'tag_id',
            LeanOrm\TestObjects\TagsModel::class, LeanOrm\TestObjects\TagRecord::class, LeanOrm\TestObjects\TagsCollection::class
        );
        self::assertEquals(['tags'], $postsModel->getRelationNames());
        $relations = $postsModel->getRelations();
        self::assertArrayHasKey('tags', $relations);
        self::assertArrayHasKey('relation_type', $relations['tags']);
        self::assertArrayHasKey('col_in_my_table_linked_to_join_table', $relations['tags']);
        self::assertArrayHasKey('join_table', $relations['tags']);
        self::assertArrayHasKey('col_in_join_table_linked_to_my_table', $relations['tags']);
        self::assertArrayHasKey('col_in_join_table_linked_to_foreign_table', $relations['tags']);
        self::assertArrayHasKey('foreign_table', $relations['tags']);
        self::assertArrayHasKey('col_in_foreign_table_linked_to_join_table', $relations['tags']);
        self::assertArrayHasKey('primary_key_col_in_foreign_table', $relations['tags']);
        self::assertArrayHasKey('foreign_models_class_name', $relations['tags']);
        self::assertArrayHasKey('foreign_models_record_class_name', $relations['tags']);
        self::assertArrayHasKey('foreign_models_collection_class_name', $relations['tags']);
        self::assertArrayHasKey('sql_query_modifier', $relations['tags']);
        
        self::assertEquals(\GDAO\Model::RELATION_TYPE_HAS_MANY_THROUGH, $relations['tags']['relation_type']);
        self::assertEquals('post_id', $relations['tags']['col_in_my_table_linked_to_join_table']);
        self::assertEquals('posts_tags', $relations['tags']['join_table']);
        self::assertEquals('post_id', $relations['tags']['col_in_join_table_linked_to_my_table']);
        self::assertEquals('tag_id', $relations['tags']['col_in_join_table_linked_to_foreign_table']);
        self::assertEquals('tags', $relations['tags']['foreign_table']);
        self::assertEquals('tag_id', $relations['tags']['col_in_foreign_table_linked_to_join_table']);
        self::assertEquals('tag_id', $relations['tags']['primary_key_col_in_foreign_table']);
        self::assertEquals(LeanOrm\TestObjects\TagsModel::class, $relations['tags']['foreign_models_class_name']);
        self::assertEquals(LeanOrm\TestObjects\TagRecord::class, $relations['tags']['foreign_models_record_class_name']);
        self::assertEquals(LeanOrm\TestObjects\TagsCollection::class, $relations['tags']['foreign_models_collection_class_name']);
        self::assertNull($relations['tags']['sql_query_modifier']);
        
        $callback = fn(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select => $selectObj;
        $postsModel->hasManyThrough(
            'tags2',  'post_id', 'posts_tags', 'post_id', 'tag_id', 'tags', 'tag_id', 'tag_id',
            \LeanOrm\TestObjects\TagsModel::class, \LeanOrm\TestObjects\TagRecord::class, 
            \LeanOrm\TestObjects\TagsCollection::class, $callback
        );
        self::assertEquals(['tags', 'tags2'], $postsModel->getRelationNames());
        self::assertEquals($callback, $postsModel->getRelations()['tags2']['sql_query_modifier']);
        self::assertEquals(\LeanOrm\TestObjects\TagRecord::class, $postsModel->getRelations()['tags2']['foreign_models_record_class_name']);
        self::assertEquals(\LeanOrm\TestObjects\TagsCollection::class, $postsModel->getRelations()['tags2']['foreign_models_collection_class_name']);
    }
    
    public function testThatCanLogQueriesWorksAsExpected() {
        
        $postsModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        
        self::assertFalse($postsModel->canLogQueries());
        self::assertTrue($postsModel->enableQueryLogging()->canLogQueries());
    }
    
    public function testThatDisableQueryLoggingWorksAsExpected() {
        
        $postsModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        
        self::assertFalse($postsModel->canLogQueries());
        self::assertSame($postsModel->disableQueryLogging(), $postsModel);
        self::assertFalse($postsModel->canLogQueries());
    }
    
    public function testThatEnableQueryLoggingWorksAsExpected() {
        
        $postsModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'post_id','posts');
        
        self::assertFalse($postsModel->canLogQueries());
        self::assertSame($postsModel->enableQueryLogging(), $postsModel);
        self::assertTrue($postsModel->canLogQueries());
    }
    
    public function testThatDeleteMatchingDbTableRowsWorksAsExpected() {
        
        $keyValueModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id','key_value');
        $recordsToInsert = [
            ['key_name'=> 'key 1', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+2 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+1 minutes"))],
            ['key_name'=> 'key 2', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+3 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+2 minutes"))],
            ['key_name'=> 'key 3', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+4 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+3 minutes"))],
        ];
        
        $this->insertDataIntoTable('key_value', $recordsToInsert[0], $keyValueModel->getPDO());
        $this->insertDataIntoTable('key_value', $recordsToInsert[1], $keyValueModel->getPDO());
        $this->insertDataIntoTable('key_value', $recordsToInsert[2], $keyValueModel->getPDO());

        // only one matching record
        self::assertEquals(1, $keyValueModel->deleteMatchingDbTableRows(['key_name'=> 'key 1']));
        
        // two matching records
        self::assertEquals( 2, $keyValueModel->deleteMatchingDbTableRows(['key_name'=> ['key 2', 'key 3']]) );
        
        // no matching record
        self::assertEquals( 0, $keyValueModel->deleteMatchingDbTableRows(['key_name'=> 'key 55']) );
        
        self::assertEquals( 0, $keyValueModel->deleteMatchingDbTableRows(['key_name'=> ""]) );
        self::assertEquals( 0, $keyValueModel->deleteMatchingDbTableRows(['key_name'=> "''"]) );
        self::assertEquals( 0, $keyValueModel->deleteMatchingDbTableRows(['key_name'=> [""]]) );
        self::assertEquals( 0, $keyValueModel->deleteMatchingDbTableRows(['key_name'=> ["''"]]) );
        self::assertEquals( 0, $keyValueModel->deleteMatchingDbTableRows(['non_existent_col'=> 'some_val', 'non_existent_col2'=> ['some_val']]) );
    }
    
    public function testThatDeleteMatchingDbTableThrowsExceptionWithUnacceptableDeleteWhereParam() {
        
        // acceptable insert values are
        // *bool, *null, *number, *string, *object with __toString
        // Any value outside of these is considered invalid for insert
        $this->expectException(\LeanOrm\InvalidArgumentException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'key_value');
        $delete_query = [
            'key_name'          => 'Test Key',
            'value'             => 'Test Value',
            'blankable_value'   => [
                                        'Test Blankable Value', 
                                        [], // invalid sub array value
                                   ], 
        ];
        $model->deleteMatchingDbTableRows($delete_query); // will throw exception
    }
    
    public function testThatDeleteMatchingDbTableThrowsExceptionWithUnacceptableDeleteWhereParam2() {
        
        // acceptable insert values are
        // *bool, *null, *number, *string, *object with __toString
        // Any value outside of these is considered invalid for insert
        $this->expectException(\LeanOrm\InvalidArgumentException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'key_value');
        $delete_query = [
            'key_name'          => 'Test Key',
            'value'             => 'Test Value',
            'blankable_value'   => function(){}, // invalid value 
        ];
        $model->deleteMatchingDbTableRows($delete_query); // will throw exception
    }
    
    public function testThatDeleteSpecifiedRecordWorksAsExpected() {
        
        $keyValueModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id','key_value');
        $recordsToInsert = [
            ['key_name'=> 'key 1', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+2 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+1 minutes"))],
            ['key_name'=> 'key 2', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+3 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+2 minutes"))],
            ['key_name'=> 'key 3', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+4 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+3 minutes"))],
        ];
        
        // Deleting a record that has never been saved to the DB
        self::assertNull( $keyValueModel->deleteSpecifiedRecord($keyValueModel->createNewRecord()));
        self::assertNull( $keyValueModel->deleteSpecifiedRecord($keyValueModel->createNewRecord($recordsToInsert[0])));
        
        $this->insertDataIntoTable('key_value', $recordsToInsert[0], $keyValueModel->getPDO());
        $this->insertDataIntoTable('key_value', $recordsToInsert[1], $keyValueModel->getPDO());
        $this->insertDataIntoTable('key_value', $recordsToInsert[2], $keyValueModel->getPDO());

        $aRecord = $keyValueModel->fetchOneRecord($keyValueModel->getSelect()->where('key_name = ? ', 'key 1'));
        
        self::assertFalse($aRecord->isNew());
        self::assertTrue($keyValueModel->deleteSpecifiedRecord($aRecord));
        // record is new after being deleted
        self::assertTrue($aRecord->isNew());
    }
    
    public function testThatDeleteSpecifiedRecordThrowsExceptionForReadOnlyRecords()  {
        
        $this->expectException(\LeanOrm\CantDeleteReadOnlyRecordFromDBException::class);
        
        $authorsModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
        
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
        self::assertEquals([], $authorsModel->fetch([]));
         
        //empty array with no ids for fetch returns empty collection
        $potentiallyEmptyCollection = $authorsModel->fetch([], null, [], false, true);
        self::assertInstanceOf(\LeanOrm\TestObjects\AuthorsCollection::class, $potentiallyEmptyCollection);
        self::assertCount(0, $potentiallyEmptyCollection);
        
        // fetch rows as arrays into an array not keyed on primary key
        $fiveRecords = $authorsModel->fetch([1,2,3,4,5]);
        
        // fetch rows as arrays into an array keyed on primary key
        $fiveRecords2 = $authorsModel->fetch([1,2,3,4,5], null, [], false, false, true);
        
        // test keyed on PK
        self::assertEquals([1, 2, 3, 4, 5], \array_keys($fiveRecords2));

        self::assertIsArray($fiveRecords);
        self::assertCount(5, $fiveRecords);
        
        $nameColumnValues = ['user_1', 'user_2', 'user_3', 'user_4', 'user_5'];
        
        foreach ($fiveRecords as $record) { self::assertContains($record["name"], $nameColumnValues); }
        foreach ($fiveRecords2 as $record) { self::assertContains($record["name"], $nameColumnValues); }
        
        // fetch rows as records into a collection not keyed on primary key
        $fiveRecordsCollection = $authorsModel->fetch([1,2,3,4,5], null, [], false, true);
        
        // fetch rows as records into a collection keyed on primary key
        $fiveRecords2Collection = $authorsModel->fetch([1,2,3,4,5], null, [], false, true, true);

        self::assertInstanceOf(\LeanOrm\TestObjects\AuthorsCollection::class, $fiveRecordsCollection);
        self::assertCount(5, $fiveRecordsCollection);
        
        self::assertInstanceOf(\LeanOrm\TestObjects\AuthorsCollection::class, $fiveRecords2Collection);
        self::assertCount(5, $fiveRecords2Collection);
        // test keyed on PK
        self::assertEquals([1, 2, 3, 4, 5], $fiveRecords2Collection->getKeys());

        foreach ($fiveRecordsCollection as $record) { self::assertContains($record["name"], $nameColumnValues); }
        foreach ($fiveRecords2Collection as $record) { self::assertContains($record["name"], $nameColumnValues); }
        
        // fetch rows as records into an array not keyed on primary key
        $fiveRecordsArrayOfRecords = $authorsModel->fetch([1,2,3,4,5], null, [], true, false);
        
        // fetch rows as records into an array keyed on primary key
        $fiveRecords2ArrayOfRecords = $authorsModel->fetch([1,2,3,4,5], null, [], true, false, true);
        
        self::assertIsArray($fiveRecordsArrayOfRecords);
        self::assertCount(5, $fiveRecordsArrayOfRecords);
        
        self::assertIsArray($fiveRecords2ArrayOfRecords);
        self::assertCount(5, $fiveRecords2ArrayOfRecords);
        // test keyed on PK
        self::assertEquals([1, 2, 3, 4, 5], \array_keys($fiveRecords2ArrayOfRecords));
        
        foreach ($fiveRecordsArrayOfRecords as $record) {

            self::assertContains($record["name"], $nameColumnValues); 
            self::assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $record);
        }
        
        foreach ($fiveRecords2ArrayOfRecords as $record) {
            
            self::assertContains($record["name"], $nameColumnValues); 
            self::assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $record);
        }
        
        ////////////////////////////////////////////////////////////////////////
        //test relationship inclusion & the use of a query object
        // fetch rows as arrays into an array
        $threeRecordsArrayOfRecords = $authorsModel->fetch(
            [1,2,3,4,5], 
            $authorsModel->getSelect()->where(' author_id <= 3 '), 
            ['posts'], false, false
        );
        
        self::assertIsArray($threeRecordsArrayOfRecords);
        self::assertCount(3, $threeRecordsArrayOfRecords);
        self::assertEquals(['user_1', 'user_2', 'user_3'], array_column($threeRecordsArrayOfRecords, 'name'));
        
        foreach($threeRecordsArrayOfRecords as $authorRecord) {
            
            self::assertIsArray($authorRecord['posts']);
            
            foreach($authorRecord['posts'] as $post) {
                
                self::assertEquals($post['author_id'], $authorRecord['author_id']);
                self::assertArrayHasKey('post_id', $post);
                self::assertArrayHasKey('title', $post);
                self::assertArrayHasKey('body', $post);
                self::assertArrayHasKey('m_timestamp', $post);
                self::assertArrayHasKey('date_created', $post);
            }
        }
        
        ////////////////////////////////////////////////////////////////////////
        // test relationship inclusion & the use of a query object
        // fetch rows as records into a collection
        $threeRecordsCollectionOfRecords = $authorsModel->fetch(
            [1,2,3,4,5], $authorsModel->getSelect()->where(' author_id <= 3 '), 
            ['posts'], false, true
        );

        self::assertInstanceOf(\LeanOrm\TestObjects\AuthorsCollection::class, $threeRecordsCollectionOfRecords);
        self::assertCount(3, $threeRecordsCollectionOfRecords);
        self::assertEquals(['user_1', 'user_2', 'user_3'], $threeRecordsCollectionOfRecords->getColVals('name'));
        
        foreach($threeRecordsCollectionOfRecords as $authorRecord) {
            
            self::assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $authorRecord);
            self::assertInstanceOf(LeanOrm\TestObjects\PostsCollection::class, $authorRecord->posts);
            self::assertInstanceOf(LeanOrm\TestObjects\PostsCollection::class, $authorRecord['posts']);
            
            foreach($authorRecord['posts'] as $post) {
                
                self::assertInstanceOf(\LeanOrm\TestObjects\PostRecord::class, $post);
                self::assertEquals($post['author_id'], $authorRecord['author_id']);
                self::assertArrayHasKey('post_id', $post->getData());
                self::assertArrayHasKey('title', $post->getData());
                self::assertArrayHasKey('body', $post->getData());
                self::assertArrayHasKey('m_timestamp', $post->getData());
                self::assertArrayHasKey('date_created', $post->getData());
            }
        }
        
        // Test with empty table
        $emptyModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        
        $expectedEmptyCollection = $emptyModel->fetch([1,2,3], null, [], true, true);
        self::assertInstanceOf(GDAO\Model\CollectionInterface::class, $expectedEmptyCollection);
        self::assertCount(0, $expectedEmptyCollection);
        
        $expectedEmptyArray = $emptyModel->fetch([1,2,3], null, [], false, false);
        self::assertIsArray($expectedEmptyArray);
        self::assertCount(0, $expectedEmptyArray);
    }
    
    public function testThatFetchColWorksAsExpected() {
        
        $tagsModel = new LeanOrm\TestObjects\TagsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        // return all the values under the first column in the table which is 
        // the tag_id column 
        $cols = $tagsModel->fetchCol();
        self::assertEquals(['1', '2', '3', '4'], $cols);
        
        $cols2 = $tagsModel->fetchCol($tagsModel->getSelect()->cols(['name']));
        self::assertEquals(['tag_1', 'tag_2', 'tag_3', 'tag_4'], $cols2);
        
        $cols3 = $tagsModel->fetchCol(
                    $tagsModel->getSelect()->cols(['name'])->where(' tag_id < 3 ')
                );
        self::assertEquals(['tag_1', 'tag_2'], $cols3);
        
        // Test with empty table
        $emptyModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        self::assertEquals([], $emptyModel->fetchCol());
    }
    
    public function testThatFetchOneRecordWorksAsExpected() {
        
        $authorsModel = new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        ////////////////////////////////////////////////////////////////////////
        // fetch first record in the table
        $firstAuthorInTheTable = $authorsModel->fetchOneRecord();
        self::assertInstanceOf(GDAO\Model\RecordInterface::class, $firstAuthorInTheTable);
        self::assertArrayHasKey('author_id', $firstAuthorInTheTable->getData());
        self::assertArrayHasKey('name', $firstAuthorInTheTable->getData());
        self::assertArrayHasKey('m_timestamp', $firstAuthorInTheTable->getData());
        self::assertArrayHasKey('date_created', $firstAuthorInTheTable->getData());
        
        // test that lazy loaded relationship data works from a fetch without
        // relations to include supplied
        self::assertInstanceOf(GDAO\Model\CollectionInterface::class, $firstAuthorInTheTable->posts);
        self::assertCount(2, $firstAuthorInTheTable->posts);
        self::assertEquals(['1', '3'], $firstAuthorInTheTable->posts->getColVals('post_id'));
        self::assertEquals(['1', '1'], $firstAuthorInTheTable->posts->getColVals('author_id'));
        self::assertEquals(['Post 1', 'Post 3'], $firstAuthorInTheTable->posts->getColVals('title'));
        self::assertEquals(['Post Body 1', 'Post Body 3'], $firstAuthorInTheTable->posts->getColVals('body'));
        
        ////////////////////////////////////////////////////////////////////////
        // fetch Author with author_id = 2 & include posts during the fetch
        // fetch first record in the table
        $secondAuthorInTheTable = $authorsModel->fetchOneRecord(
            $authorsModel->getSelect()->where(' author_id =  2 '), ['posts']
        );
        self::assertInstanceOf(GDAO\Model\RecordInterface::class, $secondAuthorInTheTable);
        self::assertArrayHasKey('author_id', $secondAuthorInTheTable->getData());
        self::assertArrayHasKey('name', $secondAuthorInTheTable->getData());
        self::assertArrayHasKey('m_timestamp', $secondAuthorInTheTable->getData());
        self::assertArrayHasKey('date_created', $secondAuthorInTheTable->getData());
        
        // test that eager loaded relationship data works
        self::assertInstanceOf(GDAO\Model\CollectionInterface::class, $secondAuthorInTheTable->posts);
        self::assertCount(2, $secondAuthorInTheTable->posts);
        self::assertCount(1, $secondAuthorInTheTable->one_post);
        
        self::assertEquals(['2', '4'], $secondAuthorInTheTable->posts->getColVals('post_id'));
        self::assertEquals(['2', '2'], $secondAuthorInTheTable->posts->getColVals('author_id'));
        self::assertEquals(['Post 2', 'Post 4'], $secondAuthorInTheTable->posts->getColVals('title'));
        self::assertEquals(['Post Body 2', 'Post Body 4'], $secondAuthorInTheTable->posts->getColVals('body'));
        
        self::assertEquals(['2'], $secondAuthorInTheTable->one_post->getColVals('post_id'));
        self::assertEquals(['2'], $secondAuthorInTheTable->one_post->getColVals('author_id'));
        self::assertEquals(['Post 2'], $secondAuthorInTheTable->one_post->getColVals('title'));
        self::assertEquals(['Post Body 2'], $secondAuthorInTheTable->one_post->getColVals('body'));
        
        ///////////////////////////////////////////////////////////////////////
        // Test that record not in db returns null
        $authorNotInTheTable = $authorsModel->fetchOneRecord(
            $authorsModel->getSelect()->where(' author_id =  777 '), ['posts']
        );
        self::assertNull($authorNotInTheTable);
        
        $author2NotInTheTable = $authorsModel->fetchOneRecord(
            $authorsModel->getSelect()->where(' author_id =  77 ')
        );
        self::assertNull($author2NotInTheTable);
        
        self::assertNull(
            $authorsModel->fetchOneRecord(
                $authorsModel->getSelect()->where(" name =  '' "), ['posts']
            )
        );
        self::assertNull(
            $authorsModel->fetchOneRecord(
                $authorsModel->getSelect()->where(" name =  ? ", "''"), ['posts']
            )
        );

        /////////////////////////////
        // Querying an empty table
        $emptyModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        
        self::assertNull($emptyModel->fetchOneRecord());
    }
    
    public function testThatFetchOneByPkeyWorksAsExpected() {
        
        $authorsModel = new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        ////////////////////////////////////////////////////////////////////////
        // fetch Author with author_id = 2 & include posts during the fetch
        // fetch first record in the table
        $secondAuthorInTheTable = $authorsModel->fetchOneByPkey(2, ['posts']);
        self::assertInstanceOf(GDAO\Model\RecordInterface::class, $secondAuthorInTheTable);
        self::assertEquals('2', $secondAuthorInTheTable->getPrimaryVal().'');
        self::assertArrayHasKey('author_id', $secondAuthorInTheTable->getData());
        self::assertArrayHasKey('name', $secondAuthorInTheTable->getData());
        self::assertArrayHasKey('m_timestamp', $secondAuthorInTheTable->getData());
        self::assertArrayHasKey('date_created', $secondAuthorInTheTable->getData());
        
        // test that eager loaded relationship data works
        self::assertInstanceOf(GDAO\Model\CollectionInterface::class, $secondAuthorInTheTable->posts);
        self::assertCount(2, $secondAuthorInTheTable->posts);
        self::assertCount(1, $secondAuthorInTheTable->one_post);
        
        self::assertEquals(['2', '4'], $secondAuthorInTheTable->posts->getColVals('post_id'));
        self::assertEquals(['2', '2'], $secondAuthorInTheTable->posts->getColVals('author_id'));
        self::assertEquals(['Post 2', 'Post 4'], $secondAuthorInTheTable->posts->getColVals('title'));
        self::assertEquals(['Post Body 2', 'Post Body 4'], $secondAuthorInTheTable->posts->getColVals('body'));
        
        self::assertEquals(['2'], $secondAuthorInTheTable->one_post->getColVals('post_id'));
        self::assertEquals(['2'], $secondAuthorInTheTable->one_post->getColVals('author_id'));
        self::assertEquals(['Post 2'], $secondAuthorInTheTable->one_post->getColVals('title'));
        self::assertEquals(['Post Body 2'], $secondAuthorInTheTable->one_post->getColVals('body'));
        
        ///////////////////////////////////////////////////////////////////////
        // Test that record not in db returns null
        $authorNotInTheTable = $authorsModel->fetchOneByPkey(777, ['posts']);
        self::assertNull($authorNotInTheTable);
        
        $author2NotInTheTable = $authorsModel->fetchOneByPkey(77);
        self::assertNull($author2NotInTheTable);

        /////////////////////////////
        // Querying an empty table
        $emptyModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        
        self::assertNull($emptyModel->fetchOneByPkey(777));
    }
    
    public function testThatFetchPairsWorksAsExpected() {
        
        $tagsModel = new LeanOrm\TestObjects\TagsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $pairsOfFirstTwoColumnsTagIdAndName = 
            [1 => 'tag_1', 2 => 'tag_2', 3 => 'tag_3', 4 => 'tag_4'];
        
        self::assertEquals(
            $pairsOfFirstTwoColumnsTagIdAndName, $tagsModel->fetchPairs()
        );
        
        $pairsOfTwoColumnsNameAndTagId = 
            ['tag_1' => '1', 'tag_2' => '2', 'tag_3' => '3', 'tag_4' => '4'];
        
        self::assertEquals(
            $pairsOfTwoColumnsNameAndTagId, 
            $tagsModel->fetchPairs(
                $tagsModel->getSelect()->cols(['name', 'tag_id'])
            )
        );
        
        // Query that matches no db rows
        self::assertEquals(
            [], 
            $tagsModel->fetchPairs(
                $tagsModel->getSelect()->cols(['name', 'tag_id'])->where(' tag_id > 777 ')
            )
        );
        
        // Test with empty table
        $emptyModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        self::assertEquals([], $emptyModel->fetchPairs());
    }
    
    public function testThatFetchRecordsIntoArrayWorksAsExpected() {
        
        // Test with empty table
        $emptyModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        self::assertEquals([], $emptyModel->fetchRecordsIntoArray());
        
        ////////////////////////////////////////////////////////////////////////
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $allPosts = $postsModel->fetchRecordsIntoArray();
        self::assertIsArray($allPosts);
        self::assertCount(4, $allPosts);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPosts);
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            GenericCollection::makeNew($allPosts)->column('post_id')->toArray()
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            GenericCollection::makeNew($allPosts)->column('author_id')->toArray()
        );
        self::assertEquals(
            static::POST_TITLES,
            GenericCollection::makeNew($allPosts)->column('title')->toArray()
        );
        self::assertEquals(
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
        
        self::assertIsArray($firstTwoPosts);
        self::assertCount(2, $firstTwoPosts);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $firstTwoPosts);
        
        // verify that the records contain expected data
        self::assertEquals(
            ['1', '2'],
            GenericCollection::makeNew($firstTwoPosts)->column('post_id')->toArray()
        );
        self::assertEquals(
            ['1', '2'],
            GenericCollection::makeNew($firstTwoPosts)->column('author_id')->toArray()
        );
        self::assertEquals(
            ['Post 1', 'Post 2'],
            GenericCollection::makeNew($firstTwoPosts)->column('title')->toArray()
        );
        self::assertEquals(
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
        self::assertIsArray($allPostsWithAllRelateds);
        self::assertCount(4, $allPostsWithAllRelateds);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithAllRelateds);
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('post_id')->toArray()
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('author_id')->toArray()
        );
        self::assertEquals(
            static::POST_TITLES,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('title')->toArray()
        );
        self::assertEquals(
            static::POST_BODIES,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('body')->toArray()
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithAllRelateds as $postRecord) {
            
            // author of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            
            // post's comments
            self::assertIsArray($postRecord->comments);
            self::assertCount(1, $postRecord->comments);
            self::assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            // summary of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            
            // post's posts_tags
            self::assertIsArray($postRecord->posts_tags);
            self::assertCount(1, $postRecord->posts_tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            self::assertIsArray($postRecord->tags);
            self::assertCount(1, $postRecord->tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
            
        } // foreach($allPostsWithAllRelateds as $postRecord)
        unset($allPostsWithAllRelateds);
    }
    
    public function testThatFetchRecordsIntoArrayWithoutEagerLoadingWorksAsExpected() {
        
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        // Not eager loading related records during fetch will lead to lots of 
        // queries when looping through records & accessing their related records
        $allPostsWithoutRelateds = $postsModel->fetchRecordsIntoArray();
        self::assertIsArray($allPostsWithoutRelateds);
        self::assertCount(4, $allPostsWithoutRelateds);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithoutRelateds);
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('post_id')->toArray()
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('author_id')->toArray()
        );
        self::assertEquals(
            static::POST_TITLES,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('title')->toArray()
        );
        self::assertEquals(
            static::POST_BODIES,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('body')->toArray()
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithoutRelateds as $postRecord) {
            
            // author of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            
            // post's comments
            // Because the lazy load is triggered at the record level & not during
            // the fetch, the results of hasMany & hasManyThrough relationships are 
            // placed in a collection instead of an array (if the relations were 
            // eager loaded during the fetch)
            self::assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments);  
            self::assertCount(1, $postRecord->comments);
            self::assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            // summary of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            
            // post's posts_tags
            self::assertInstanceOf(\LeanOrm\TestObjects\PostsTagsCollection::class, $postRecord->posts_tags);
            self::assertCount(1, $postRecord->posts_tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            self::assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags);
            self::assertCount(1, $postRecord->tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
            
        } // foreach($allPostsWithAllRelateds as $postRecord)
        
        unset($allPostsWithoutRelateds);
    }
    
    public function testThatFetchRecordsIntoArrayKeyedOnPkValWorksAsExpected() {
        
        // Test with empty table
        $emptyModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        self::assertEquals([], $emptyModel->fetchRecordsIntoArrayKeyedOnPkVal());
        
        ////////////////////////////////////////////////////////////////////////
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $allPosts = $postsModel->fetchRecordsIntoArrayKeyedOnPkVal();
        self::assertIsArray($allPosts);
        self::assertCount(4, $allPosts);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPosts);
        // test that the results are keyed on PK
        self::assertEquals(static::POST_POST_IDS, array_keys($allPosts));
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            GenericCollection::makeNew($allPosts)->column('post_id')->toArray()
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            GenericCollection::makeNew($allPosts)->column('author_id')->toArray()
        );
        self::assertEquals(
            static::POST_TITLES,
            GenericCollection::makeNew($allPosts)->column('title')->toArray()
        );
        self::assertEquals(
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
        
        self::assertIsArray($firstTwoPosts);
        self::assertCount(2, $firstTwoPosts);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $firstTwoPosts);
        // test that the results are keyed on PK
        self::assertEquals(['1', '2'], array_keys($firstTwoPosts));
        
        // verify that the records contain expected data
        self::assertEquals(
            ['1', '2'],
            GenericCollection::makeNew($firstTwoPosts)->column('post_id')->toArray()
        );
        self::assertEquals(
            ['1', '2'],
            GenericCollection::makeNew($firstTwoPosts)->column('author_id')->toArray()
        );
        self::assertEquals(
            ['Post 1', 'Post 2'],
            GenericCollection::makeNew($firstTwoPosts)->column('title')->toArray()
        );
        self::assertEquals(
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
        self::assertIsArray($allPostsWithAllRelateds);
        self::assertCount(4, $allPostsWithAllRelateds);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithAllRelateds);
        // test that the results are keyed on PK
        self::assertEquals(static::POST_POST_IDS, array_keys($allPostsWithAllRelateds));
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('post_id')->toArray()
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('author_id')->toArray()
        );
        self::assertEquals(
            static::POST_TITLES,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('title')->toArray()
        );
        self::assertEquals(
            static::POST_BODIES,
            GenericCollection::makeNew($allPostsWithAllRelateds)->column('body')->toArray()
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithAllRelateds as $postRecord) {
            
            // author of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            
            // post's comments
            self::assertIsArray($postRecord->comments);
            self::assertCount(1, $postRecord->comments);
            self::assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            // summary of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            
            // post's posts_tags
            self::assertIsArray($postRecord->posts_tags);
            self::assertCount(1, $postRecord->posts_tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            self::assertIsArray($postRecord->tags);
            self::assertCount(1, $postRecord->tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
            
        } // foreach($allPostsWithAllRelateds as $postRecord)
        unset($allPostsWithAllRelateds);
    }
    
    public function testThatFetchRecordsIntoArrayKeyedOnPkValWithoutEagerLoadingWorksAsExpected() {
        
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        // Not eager loading related records during fetch will lead to lots of 
        // queries when looping through records & accessing their related records
        $allPostsWithoutRelateds = $postsModel->fetchRecordsIntoArrayKeyedOnPkVal();
        self::assertIsArray($allPostsWithoutRelateds);
        self::assertCount(4, $allPostsWithoutRelateds);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithoutRelateds);
        // test that the results are keyed on PK
        self::assertEquals(static::POST_POST_IDS, array_keys($allPostsWithoutRelateds));
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('post_id')->toArray()
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('author_id')->toArray()
        );
        self::assertEquals(
            static::POST_TITLES,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('title')->toArray()
        );
        self::assertEquals(
            static::POST_BODIES,
            GenericCollection::makeNew($allPostsWithoutRelateds)->column('body')->toArray()
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithoutRelateds as $postRecord) {
            
            // author of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            
            // post's comments
            // Because the lazy load is triggered at the record level & not during
            // the fetch, the results of hasMany & hasManyThrough relationships are 
            // placed in a collection instead of an array (if the relations were 
            // eager loaded during the fetch)
            self::assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments);  
            self::assertCount(1, $postRecord->comments);
            self::assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            // summary of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            
            // post's posts_tags
            self::assertInstanceOf(\LeanOrm\TestObjects\PostsTagsCollection::class, $postRecord->posts_tags);
            self::assertCount(1, $postRecord->posts_tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            self::assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags);
            self::assertCount(1, $postRecord->tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
            
        } // foreach($allPostsWithAllRelateds as $postRecord)
        
        unset($allPostsWithoutRelateds);
    }
    
    public function testThatFetchRecordsIntoCollectionWorksAsExpected() {
        
        // Test with empty table
        $emptyModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        $emptyResult = $emptyModel->fetchRecordsIntoCollection();
        self::assertInstanceOf(\LeanOrm\Model\Collection::class, $emptyResult);
        self::assertCount(0, $emptyResult);
        
        ////////////////////////////////////////////////////////////////////////
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $allPosts = $postsModel->fetchRecordsIntoCollection();
        self::assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $allPosts);
        self::assertCount(4, $allPosts);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPosts);
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            $allPosts->getColVals('post_id')
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            $allPosts->getColVals('author_id')
        );
        self::assertEquals(
            static::POST_TITLES,
            $allPosts->getColVals('title')
        );
        self::assertEquals(
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
        self::assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $firstTwoPosts);
        self::assertCount(2, $firstTwoPosts);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $firstTwoPosts);
        
        // verify that the records contain expected data
        self::assertEquals(
            ['1', '2'],
            $firstTwoPosts->getColVals('post_id')
        );
        self::assertEquals(
            ['1', '2'],
            $firstTwoPosts->getColVals('author_id')
        );
        self::assertEquals(
            ['Post 1', 'Post 2'],
            $firstTwoPosts->getColVals('title')
        );
        self::assertEquals(
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
        self::assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $allPostsWithAllRelateds);
        self::assertCount(4, $allPostsWithAllRelateds);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithAllRelateds);
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            $allPostsWithAllRelateds->getColVals('post_id')
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            $allPostsWithAllRelateds->getColVals('author_id')
        );
        self::assertEquals(
            static::POST_TITLES,
            $allPostsWithAllRelateds->getColVals('title')
        );
        self::assertEquals(
            static::POST_BODIES,
            $allPostsWithAllRelateds->getColVals('body')
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithAllRelateds as $postRecord) {
            
            // author of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            self::assertInstanceOf(\RecordForTestingPublicAndProtectedMethods::class, $postRecord->author_with_callback);
            self::assertEquals($postRecord->author->getData(), $postRecord->author_with_callback->getData());
            
            // post's comments
            self::assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments);
            self::assertCount(1, $postRecord->comments);
            self::assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            self::assertInstanceOf(\CollectionForTestingPublicAndProtectedMethods::class, $postRecord->comments_with_callback);
            self::assertCount(1, $postRecord->comments_with_callback);
            self::assertContainsOnlyInstancesOf(\RecordForTestingPublicAndProtectedMethods::class, $postRecord->comments_with_callback);
            
            // summary of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            self::assertInstanceOf(\RecordForTestingPublicAndProtectedMethods::class, $postRecord->summary_with_callback);
            self::assertEquals($postRecord->summary->getData(), $postRecord->summary_with_callback->getData());
            
            // post's posts_tags
            self::assertInstanceOf(\LeanOrm\TestObjects\PostsTagsCollection::class, $postRecord->posts_tags);
            self::assertCount(1, $postRecord->posts_tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            self::assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags);
            self::assertCount(1, $postRecord->tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
            
            self::assertInstanceOf(\CollectionForTestingPublicAndProtectedMethods::class, $postRecord->tags_with_callback);
            self::assertCount(1, $postRecord->tags_with_callback);
            self::assertContainsOnlyInstancesOf(\RecordForTestingPublicAndProtectedMethods::class, $postRecord->tags_with_callback);
            
        } // foreach($allPostsWithAllRelateds as $postRecord)
        unset($allPostsWithAllRelateds);
    }
    
    public function testThatFetchRecordsIntoCollectionWithoutEagerLoadingWorksAsExpected() {
        
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        // Not eager loading related records during fetch will lead to lots of 
        // queries when looping through records & accessing their related records
        $allPostsWithoutRelateds = $postsModel->fetchRecordsIntoCollection();
        self::assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $allPostsWithoutRelateds);
        self::assertCount(4, $allPostsWithoutRelateds);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithoutRelateds);
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            $allPostsWithoutRelateds->getColVals('post_id')
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            $allPostsWithoutRelateds->getColVals('author_id')
        );
        self::assertEquals(
            static::POST_TITLES,
            $allPostsWithoutRelateds->getColVals('title')
        );
        self::assertEquals(
            static::POST_BODIES,
            $allPostsWithoutRelateds->getColVals('body')
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithoutRelateds as $postRecord) {
            
            // author of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            self::assertInstanceOf(\RecordForTestingPublicAndProtectedMethods::class, $postRecord->author_with_callback);
            self::assertEquals($postRecord->author->getData(), $postRecord->author_with_callback->getData());
            
            // post's comments
            self::assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments);  
            self::assertCount(1, $postRecord->comments);
            self::assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            self::assertInstanceOf(\CollectionForTestingPublicAndProtectedMethods::class, $postRecord->comments_with_callback);  
            self::assertCount(1, $postRecord->comments_with_callback);
            self::assertContainsOnlyInstancesOf(\RecordForTestingPublicAndProtectedMethods::class, $postRecord->comments_with_callback);
            
            // summary of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            self::assertInstanceOf(\RecordForTestingPublicAndProtectedMethods::class, $postRecord->summary_with_callback);
            self::assertEquals($postRecord->summary->getData(), $postRecord->summary_with_callback->getData());
            
            // post's posts_tags
            self::assertInstanceOf(\LeanOrm\TestObjects\PostsTagsCollection::class, $postRecord->posts_tags);
            self::assertCount(1, $postRecord->posts_tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            self::assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags);
            self::assertCount(1, $postRecord->tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
            
            self::assertInstanceOf(\CollectionForTestingPublicAndProtectedMethods::class, $postRecord->tags_with_callback);
            self::assertCount(1, $postRecord->tags_with_callback);
            self::assertContainsOnlyInstancesOf(\RecordForTestingPublicAndProtectedMethods::class, $postRecord->tags_with_callback);
            
        } // foreach($allPostsWithAllRelateds as $postRecord)
        
        unset($allPostsWithoutRelateds);
    }
    
    public function testThatFetchRecordsIntoCollectionKeyedOnPkValWorksAsExpected() {
        
        // Test with empty table
        $emptyModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        $emptyResult = $emptyModel->fetchRecordsIntoCollectionKeyedOnPkVal();
        self::assertInstanceOf(\LeanOrm\Model\Collection::class, $emptyResult);
        self::assertCount(0, $emptyResult);
        
        ////////////////////////////////////////////////////////////////////////
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $allPosts = $postsModel->fetchRecordsIntoCollectionKeyedOnPkVal();
        self::assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $allPosts);
        self::assertCount(4, $allPosts);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPosts);
        // test that the results are keyed on PK
        self::assertEquals(static::POST_POST_IDS, array_values($allPosts->getKeys()));

        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            array_values($allPosts->getColVals('post_id'))
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            array_values($allPosts->getColVals('author_id'))
        );
        self::assertEquals(
            static::POST_TITLES,
            array_values($allPosts->getColVals('title'))
        );
        self::assertEquals(
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
        self::assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $firstTwoPosts);
        self::assertCount(2, $firstTwoPosts);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $firstTwoPosts);
        // test that the results are keyed on PK
        self::assertEquals(['1', '2'], array_values($firstTwoPosts->getKeys()));
        
        // verify that the records contain expected data
        self::assertEquals(
            ['1', '2'],
            array_values($firstTwoPosts->getColVals('post_id'))
        );
        self::assertEquals(
            ['1', '2'],
            array_values($firstTwoPosts->getColVals('author_id'))
        );
        self::assertEquals(
            ['Post 1', 'Post 2'],
            array_values($firstTwoPosts->getColVals('title'))
        );
        self::assertEquals(
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
        self::assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $allPostsWithAllRelateds);
        self::assertCount(4, $allPostsWithAllRelateds);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithAllRelateds);
        // test that the results are keyed on PK
        self::assertEquals(static::POST_POST_IDS, array_values($allPostsWithAllRelateds->getKeys()));
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            array_values($allPostsWithAllRelateds->getColVals('post_id'))
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            array_values($allPostsWithAllRelateds->getColVals('author_id'))
        );
        self::assertEquals(
            static::POST_TITLES,
            array_values($allPostsWithAllRelateds->getColVals('title'))
        );
        self::assertEquals(
            static::POST_BODIES,
            array_values($allPostsWithAllRelateds->getColVals('body'))
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithAllRelateds as $postRecord) {
            
            // author of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            
            // post's comments
            self::assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments);
            self::assertCount(1, $postRecord->comments);
            self::assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            // summary of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            
            // post's posts_tags
            self::assertInstanceOf(\LeanOrm\TestObjects\PostsTagsCollection::class, $postRecord->posts_tags);
            self::assertCount(1, $postRecord->posts_tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            self::assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags);
            self::assertCount(1, $postRecord->tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
            
        } // foreach($allPostsWithAllRelateds as $postRecord)
        unset($allPostsWithAllRelateds);
    }
    
    public function testThatFetchRecordsIntoCollectionKeyedOnPkValWithoutEagerLoadingWorksAsExpected() {
        
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        // Not eager loading related records during fetch will lead to lots of 
        // queries when looping through records & accessing their related records
        $allPostsWithoutRelateds = $postsModel->fetchRecordsIntoCollectionKeyedOnPkVal();
        self::assertInstanceOf(\LeanOrm\TestObjects\PostsCollection::class, $allPostsWithoutRelateds);
        self::assertCount(4, $allPostsWithoutRelateds);
        self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostRecord::class, $allPostsWithoutRelateds);
        // test that the results are keyed on PK
        self::assertEquals(static::POST_POST_IDS, array_values($allPostsWithoutRelateds->getKeys()));
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            array_values($allPostsWithoutRelateds->getColVals('post_id'))
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            array_values($allPostsWithoutRelateds->getColVals('author_id'))
        );
        self::assertEquals(
            static::POST_TITLES,
            array_values($allPostsWithoutRelateds->getColVals('title'))
        );
        self::assertEquals(
            static::POST_BODIES,
            array_values($allPostsWithoutRelateds->getColVals('body'))
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithoutRelateds as $postRecord) {
            
            // author of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\AuthorRecord::class, $postRecord->author);
            
            // post's comments
            self::assertInstanceOf(\LeanOrm\TestObjects\CommentsCollection::class, $postRecord->comments);  
            self::assertCount(1, $postRecord->comments);
            self::assertContainsOnlyInstancesOf(LeanOrm\TestObjects\CommentRecord::class, $postRecord->comments);
            
            // summary of the post
            self::assertInstanceOf(\LeanOrm\TestObjects\SummaryRecord::class, $postRecord->summary);
            
            // post's posts_tags
            self::assertInstanceOf(\LeanOrm\TestObjects\PostsTagsCollection::class, $postRecord->posts_tags);
            self::assertCount(1, $postRecord->posts_tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\PostTagRecord::class, $postRecord->posts_tags);
            
            // post's tags
            self::assertInstanceOf(LeanOrm\TestObjects\TagsCollection::class, $postRecord->tags);
            self::assertCount(1, $postRecord->tags);
            self::assertContainsOnlyInstancesOf(\LeanOrm\TestObjects\TagRecord::class, $postRecord->tags);
            
        } // foreach($allPostsWithAllRelateds as $postRecord)
        
        unset($allPostsWithoutRelateds);
    }

    public function testThatFetchRowsIntoArrayWorksAsExpected() {
        
        // Test with empty table
        $emptyModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        self::assertEquals([], $emptyModel->fetchRowsIntoArray());
        
        ////////////////////////////////////////////////////////////////////////
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $allPosts = $postsModel->fetchRowsIntoArray();
        self::assertIsArray($allPosts);
        self::assertCount(4, $allPosts);
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            array_column($allPosts, 'post_id')
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            array_column($allPosts, 'author_id')
        );
        self::assertEquals(
            static::POST_TITLES,
            array_column($allPosts, 'title')
        );
        self::assertEquals(
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
        
        self::assertIsArray($firstTwoPosts);
        self::assertCount(2, $firstTwoPosts);
        
        // verify that the records contain expected data
        self::assertEquals(
            ['1', '2'],
            array_column($firstTwoPosts, 'post_id')
        );
        self::assertEquals(
            ['1', '2'],
            array_column($firstTwoPosts, 'author_id')
        );
        self::assertEquals(
            ['Post 1', 'Post 2'],
            array_column($firstTwoPosts, 'title')
        );
        self::assertEquals(
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
        self::assertIsArray($allPostsWithAllRelateds);
        self::assertCount(4, $allPostsWithAllRelateds);
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            array_column($allPostsWithAllRelateds, 'post_id')
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            array_column($allPostsWithAllRelateds, 'author_id')
        );
        self::assertEquals(
            static::POST_TITLES,
            array_column($allPostsWithAllRelateds, 'title')
        );
        self::assertEquals(
            static::POST_BODIES,
            array_column($allPostsWithAllRelateds, 'body')
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithAllRelateds as $postRecord) {
            
            self::assertArrayHasAllKeys(
                $postRecord, 
                ['post_id', 'author_id', 'datetime', 'title', 'body', 'm_timestamp', 'date_created']
            );
            
            // author of the post
            self::assertIsArray($postRecord['author']);
            
            // post's comments
            self::assertIsArray($postRecord['comments']);
            self::assertCount(1, $postRecord['comments']);
            self::assertContainsOnly('array', $postRecord['comments']);
            
            // summary of the post
            self::assertIsArray($postRecord['summary']);
            
            // post's posts_tags
            self::assertIsArray($postRecord['posts_tags']);
            self::assertCount(1, $postRecord['posts_tags']);
            self::assertContainsOnly('array', $postRecord['posts_tags']);
            
            // post's tags
            self::assertIsArray($postRecord['tags']);
            self::assertCount(1, $postRecord['tags']);
            self::assertContainsOnly('array', $postRecord['tags']);
            
        } // foreach($allPostsWithAllRelateds as $postRecord)
        unset($allPostsWithAllRelateds);
    }
    
    public function testThatFetchRowsIntoArrayKeyedOnPkValWorksAsExpected() {
        
        // Test with empty table
        $emptyModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        self::assertEquals([], $emptyModel->fetchRowsIntoArrayKeyedOnPkVal());
        
        ////////////////////////////////////////////////////////////////////////
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        $allPosts = $postsModel->fetchRowsIntoArrayKeyedOnPkVal();
        self::assertIsArray($allPosts);
        self::assertCount(4, $allPosts);
        // test that the results are keyed on PK
        self::assertEquals(static::POST_POST_IDS, array_keys($allPosts));
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            array_column($allPosts, 'post_id')
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            array_column($allPosts, 'author_id')
        );
        self::assertEquals(
            static::POST_TITLES,
            array_column($allPosts, 'title')
        );
        self::assertEquals(
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
        
        self::assertIsArray($firstTwoPosts);
        self::assertCount(2, $firstTwoPosts);
        // test that the results are keyed on PK
        self::assertEquals(['1', '2'], array_keys($firstTwoPosts));
        
        // verify that the records contain expected data
        self::assertEquals(
            ['1', '2'],
            array_column($firstTwoPosts, 'post_id')
        );
        self::assertEquals(
            ['1', '2'],
            array_column($firstTwoPosts, 'author_id')
        );
        self::assertEquals(
            ['Post 1', 'Post 2'],
            array_column($firstTwoPosts, 'title')
        );
        self::assertEquals(
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
        self::assertIsArray($allPostsWithAllRelateds);
        self::assertCount(4, $allPostsWithAllRelateds);
        // test that the results are keyed on PK
        self::assertEquals(static::POST_POST_IDS, array_keys($allPostsWithAllRelateds));
        
        // verify that the records contain expected data
        self::assertEquals(
            static::POST_POST_IDS,
            array_column($allPostsWithAllRelateds, 'post_id')
        );
        self::assertEquals(
            static::POST_AUTHOR_IDS,
            array_column($allPostsWithAllRelateds, 'author_id')
        );
        self::assertEquals(
            static::POST_TITLES,
            array_column($allPostsWithAllRelateds, 'title')
        );
        self::assertEquals(
            static::POST_BODIES,
            array_column($allPostsWithAllRelateds, 'body')
        );
        
        // verify the related data
        /** @var LeanOrm\TestObjects\PostRecord $postRecord */
        foreach($allPostsWithAllRelateds as $postRecord) {
            
            self::assertArrayHasAllKeys(
                $postRecord, 
                ['post_id', 'author_id', 'datetime', 'title', 'body', 'm_timestamp', 'date_created']
            );
            
            // author of the post
            self::assertIsArray($postRecord['author']);
            
            // post's comments
            self::assertIsArray($postRecord['comments']);
            self::assertCount(1, $postRecord['comments']);
            self::assertContainsOnly('array', $postRecord['comments']);
            
            // summary of the post
            self::assertIsArray($postRecord['summary']);
            
            // post's posts_tags
            self::assertIsArray($postRecord['posts_tags']);
            self::assertCount(1, $postRecord['posts_tags']);
            self::assertContainsOnly('array', $postRecord['posts_tags']);
            
            // post's tags
            self::assertIsArray($postRecord['tags']);
            self::assertCount(1, $postRecord['tags']);
            self::assertContainsOnly('array', $postRecord['tags']);
            
        } // foreach($allPostsWithAllRelateds as $postRecord)
        unset($allPostsWithAllRelateds);
    }
    
    public function testThatFetchValueWorksAsExpected() {
        
        $authorsModel = new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        // test without any agurments, should return the value in the first row
        // & first column (in this case author_id) of the authors table 
        // associated with the \LeanOrm\TestObjects\AuthorsModel
        self::assertEquals('1', $authorsModel->fetchValue().'');
        
        // test with a query that matches more than one row, should return the
        // value in the first row & column of the result set.
        self::assertEquals(
            '6', 
            $authorsModel->fetchValue(
                $authorsModel->getSelect()->where(' author_id > 5 ')
            ).''
        );
        
        self::assertEquals(
            '10', 
            $authorsModel->fetchValue( $authorsModel->getSelect()->where(' author_id > 9 ') ).''
        );
        
        // test with a query that matches no row, should return null
        self::assertNull($authorsModel->fetchValue($authorsModel->getSelect()->where(' author_id > 777 ')));
        
        // test with a query that returns the result of an aggregate function
        self::assertEquals(
            '10', 
            $authorsModel->fetchValue( $authorsModel->getSelect()->cols([' MAX(author_id) ']) ).''
        );
        
        // Test with empty table
        $emptyModel = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'empty_data' );
        self::assertNull($emptyModel->fetchValue());
    }
    
    public function testThatGetCurrentConnectionInfoWorksAsExpected() {
        
        $authorsModel = new LeanOrm\TestObjects\AuthorsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        
        self::assertArrayHasAllKeys(
            $authorsModel->getCurrentConnectionInfo(), 
            [
                'database_server_info', 'connection_is_persistent', 'driver_name',
                'pdo_client_version', 'database_server_version', 'connection_status',
            ]
        );
    }
    
    public function testThatGetDefaultColValsWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        self::assertArrayHasAllKeys(
            $commentsModel->getDefaultColVals(), 
            $commentsModel->getTableColNames()
        );
    }
    
    public function testThatGetLoggerWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        self::assertNull($commentsModel->getLogger());
        
        $commentsModel->setLogger(static::$psrLogger);
        self::assertSame(static::$psrLogger, $commentsModel->getLogger());
    }
    
    public function testThatGetPDOWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        self::assertInstanceOf(\PDO::class, $commentsModel->getPDO());
    }
    
    public function testThatGetPdoDriverNameWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        self::assertEquals(
            $commentsModel->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME), 
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
            
            self::assertArrayHasAllKeys(
                $commentQueryLogEntry, 
                ['sql', 'bind_params', 'date_executed', 'class_method', 'line_of_execution']
            );
        }
        
        self::assertEquals([], $postsModel->getQueryLog());
        $this->modelClass::clearQueryLogForAllInstances();
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
        $logForAllInstances = $this->modelClass::getQueryLogForAllInstances();
        $keysToCheck = [
            $commentsModel->getDsn() . '::' . get_class($commentsModel),
            $postsModel->getDsn() . '::' . get_class($postsModel),
        ];
        self::assertArrayHasAllKeys($logForAllInstances, $keysToCheck);
        self::assertArrayNotHasKey(static::$dsn . '::' . get_class($tagsModel), $logForAllInstances);
        
        foreach($logForAllInstances as $queryLogEntriesForDsnAndModelName){
            
            foreach($queryLogEntriesForDsnAndModelName as $queryLogEntry) {
                self::assertArrayHasAllKeys(
                    $queryLogEntry,
                    ['sql', 'bind_params', 'date_executed', 'class_method', 'line_of_execution']
                );
            } // foreach($queryLogEntriesForDsnAndModelName as $queryLogEntry)
        } // foreach($logForAllInstances as $queryLogEntriesForDsnAndModelName)
        
        ////////////////////////////////////////////////////////////////////////
        $logForCommentsModel = 
            $this->modelClass::getQueryLogForAllInstances($commentsModel);
        
        foreach($logForCommentsModel as $queryLogEntry) {
            self::assertArrayHasAllKeys(
                $queryLogEntry,
                ['sql', 'bind_params', 'date_executed', 'class_method', 'line_of_execution']
            );
        } // foreach($logForCommentsModel as $queryLogEntry)
        
        ////////////////////////////////////////////////////////////////////////
        $logForPostsModel = 
            $this->modelClass::getQueryLogForAllInstances($postsModel);
        
        foreach($logForPostsModel as $queryLogEntry) {
            self::assertArrayHasAllKeys(
                $queryLogEntry,
                ['sql', 'bind_params', 'date_executed', 'class_method', 'line_of_execution']
            );
        } // foreach($logForPostsModel as $queryLogEntry)
        
        ////////////////////////////////////////////////////////////////////////
        self::assertEquals([], $this->modelClass::getQueryLogForAllInstances($tagsModel));

        ////////////////////////////////////////////////////////////////////////
        $this->modelClass::clearQueryLogForAllInstances();
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
        $logForAllInstances = $this->modelClass::getQueryLogForAllInstances();
        self::assertCount(2, $logForAllInstances);
        
        ////////////////////////////////////////////////////////////////////////
        $this->modelClass::clearQueryLogForAllInstances();
        self::assertCount(0, $this->modelClass::getQueryLogForAllInstances());
    }
    
    public function testThatsetLoggerWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        self::assertNull($commentsModel->getLogger());
        
        // test fluent return
        self::assertSame($commentsModel, $commentsModel->setLogger(static::$psrLogger));
        
        // test that the setter worked as expected
        self::assertSame(static::$psrLogger, $commentsModel->getLogger());
    }
    
    public function testThatGetSelectWorksAsExpected() {
        
        $commentsModel = new LeanOrm\TestObjects\CommentsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        self::assertInstanceOf(
            get_class(static::$auraQueryFactory->newSelect()),
            $commentsModel->getSelect()
        );
    }
    
    public function testThatProtectedFetchTableListFromDBWorksAsExpected() {
        
        $commentsModel = new \ModelForTestingPublicAndProtectedMethods(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'comment_id', 'comments');

        $list_of_tables_and_views_in_test_db = [
            'authors', 'comments', 'empty_data', 
            'key_value', 'posts', 'posts_tags', 
            'summaries', 'tags', 'v_authors'
        ];
        
        $list_of_tables_and_views_as_keys_from_fetch = array_flip($commentsModel->fetchTableListFromDBPublic());
        
        // make sure every expected table and view was returned by fetchTableListFromDBPublic
        self::assertArrayHasAllKeys($list_of_tables_and_views_as_keys_from_fetch, $list_of_tables_and_views_in_test_db);
    }
    
    public function testThatProtectedFetchTableColsFromDBWorksAsExpected() {
        
        $authorsModel = new \ModelForTestingPublicAndProtectedMethods(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'author_id', 'authors');

        $authors_table_col_names = 
            [ 'author_id', 'name', 'm_timestamp', 'date_created' ];

        $v_authors_view_col_names = 
            [ 'author_id', 'name', 'm_timestamp', 'date_created' ];
        
        // make sure fetchTableColsFromDBPublic works for both tables & views
        self::assertArrayHasAllKeys($authorsModel->fetchTableColsFromDBPublic('authors'), $authors_table_col_names);
        self::assertArrayHasAllKeys($authorsModel->fetchTableColsFromDBPublic('v_authors'), $v_authors_view_col_names);
    }
    
    public function testThatInsertWorksAsExpected() {
        
        $model = new $this->modelClass(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'key_value'
        );

        //$isRunningOnSqlite = strtolower($model->getPdoDriverName()) === 'sqlite';
        $isRunningOnPostgresql = strtolower($model->getPdoDriverName()) === 'pgsql';

        // Empty array should return false
        self::assertFalse($model->insert());
        self::assertFalse($model->insert([]));
        
        // None of the keys in the data array is an actual table col should return false
        self::assertFalse($model->insert(['non_existent_col'=>'some value']));
        
        ///////////////////////////////////////////////////////////////////////////
        // Some of the keys in the data array are actual table cols are table cols
        $dateTime = date('Y-m-d H:i:s');
        $data2Save = [
            'key_name'          => 'Test Key',
            'value'             => 'Test Value',
            'blankable_value'   => 'Test Blankable Value',
            'm_timestamp'       => $dateTime,
            'date_created'      => $dateTime,
            'non_existent_col'  => 'some value'
        ];
        $result1 = $model->insert($data2Save);

        // make sure all valid column names were returned
        self::assertArrayHasAllKeys(
            $result1,
            ['key_name', 'value', 'blankable_value', 'm_timestamp', 'date_created']
        );
        
        // make sure correct column values were returned
        self::assertArrayHasAllKeys(
            array_flip($result1),
            ['Test Key', 'Test Value', 'Test Blankable Value', $dateTime, $dateTime]
        );
        
        // make sure non-existent column was not returned
        self::assertArrayNotHasKey('non_existent_col', $result1);

        // Check that the primary key value was returned in the result
        self::assertArrayHasKey('id', $result1);
        
        ////////////////////////////////////////////////////////////////////////////////////////////
        // test bool, null and number values & a stringable object's string value are saved properly
        $stringableObj = new StringableObject();
        $stringValOfObj = $stringableObj->__toString();
        $data2Save = [
            'key_name'          => $stringableObj,  // stringable object
            'value'             => 777,             // number integer
            'blankable_value'   => null,            // null
            'm_timestamp'       => $dateTime,
            'date_created'      => $dateTime,
            'non_existent_col'  => 'some value'
        ];
        $result2 = $model->insert($data2Save);
        
        // make sure correct column values were returned
        
        // was stringable object saved as string
        self::assertContains( $stringValOfObj, $result2 );
        
        // was integer value saved properly
        self::assertContains( '777', $result2 );
        
        // was null value saved properly
        self::assertContains( null, $result2 );
        
        $data2Save = [
            'key_name'          => 'Another Key',
            'value'             => 777.777,         // number float
            'blankable_value'   => true,            // boolean true
            'm_timestamp'       => $dateTime,
            'date_created'      => $dateTime,
            'non_existent_col'  => 'some value'
        ];
        $result3 = $model->insert($data2Save);

        // was float value saved properly
        self::assertContains( '777.777', $result3 );
        
        if($isRunningOnPostgresql) {
            
            // was boolean true value saved properly
            self::assertContains( 't', $result3 );
            
        } else {
            // was boolean true value saved properly
            self::assertContains( '1', $result3 );
        }
        
        $data2Save = [
            'key_name'          => 'Another Key',
            'value'             => 777.888,
            'blankable_value'   => false,            // boolean false
            'm_timestamp'       => $dateTime,
            'date_created'      => $dateTime,
            'non_existent_col'  => 'some value'
        ];
        $result4 = $model->insert($data2Save);
        
        if($isRunningOnPostgresql) {
            
            // was boolean false value saved properly
            self::assertContains( 'f', $result4 );
            
        } else {
            
            // was boolean false value saved properly
            self::assertContains( '0', $result4 );
        }
        
        // Check that the primary key value is returned in the result
        // and that it's not null when a null primary key value is supplied
        $data2Save = [
            'id'                => null, // null id value
            'key_name'          => 'Test Key 2',
            'value'             => 'Test Value 2',
            'blankable_value'   => 'Test Blankable Value 2',
            'm_timestamp'       => $dateTime,
            'date_created'      => $dateTime,
            'non_existent_col'  => 'some value'
        ];
        $result5 = $model->insert($data2Save);
        self::assertArrayHasKey('id', $result5);
        self::assertNotNull($result5['id']);
        self::assertIsNumeric($result5['id']);
        self::assertEquals(
            $model->fetchValue($model->getSelect()->cols([' MAX(id) as max_id '])) , 
            $result5['id']
        );
        
        // Check that the primary key value is returned in the result
        // and that it's not null when a null primary key value is supplied
        $model2 = new $this->modelClass(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 
            'id', 'key_value_no_auto_inc_pk'
        );
        $data2Save = [
            'id'                => 25, // null id value
            'key_name'          => 'Test Key 23',
            'value'             => 'Test Value 23',
            'blankable_value'   => 'Test Blankable Value 23',
            'm_timestamp'       => $dateTime,
            'date_created'      => $dateTime,
            'non_existent_col'  => 'some value'
        ];
        $result6 = $model2->insert($data2Save);
        self::assertArrayHasKey('id', $result6);
        self::assertEquals('25', ''.$result6['id']);
        
        ////////////////////////////////////////////////////////////////////////
        // Test the created_timestamp_column_name & updated_timestamp_column_name
        // auto timestamp addition to insert data works as expected
        $data2Save = [
            'key_name'          => 'Test Key 222',
            'value'             => 'Test Value 222',
            'blankable_value'   => 'Test Blankable Value 222',
            //'m_timestamp'       => $dateTime,
            //'date_created'      => $dateTime,
            'non_existent_col'  => 'some value'
        ];
        $model->setCreatedTimestampColumnName('date_created');
        $model->setUpdatedTimestampColumnName('m_timestamp');
        
        // saving data without specified values for the 
        // created_timestamp_column_name & updated_timestamp_column_name
        $result7 = $model->insert($data2Save);
        
        // make sure that the insert method auto populated those fields
        self::assertArrayHasKey('date_created', $result7);
        self::assertArrayHasKey('m_timestamp', $result7);
        self::assertIsString($result7['date_created']);
        self::assertIsString($result7['m_timestamp']);
        self::assertNotEmpty($result7['date_created']);
        self::assertNotEmpty($result7['m_timestamp']);
        
        // saving data with specified values for the 
        // created_timestamp_column_name & updated_timestamp_column_name
        // saves the specified timestamp values, meaning that auto-population
        // of those fields did not kick in as expected.
        $data2Save = [
            'key_name'          => 'Test Key 222',
            'value'             => 'Test Value 222',
            'blankable_value'   => 'Test Blankable Value 222',
            'm_timestamp'       => $dateTime,
            'date_created'      => $dateTime,
            'non_existent_col'  => 'some value'
        ];
        $result8 = $model->insert($data2Save);
        self::assertArrayHasKey('date_created', $result8);
        self::assertArrayHasKey('m_timestamp', $result8);
        // make sure that the insert method used specified values instead of
        // auto populated values
        self::assertEquals($dateTime, $result8['date_created']);
        self::assertEquals($dateTime, $result8['m_timestamp']);
        
        // saving data with empty specified values for the 
        // created_timestamp_column_name & updated_timestamp_column_name
        // saves with auto-populated timestamp values, meaning that 
        // auto-population of those fields kicked in as expected.
        $data2Save = [
            'key_name'          => 'Test Key 222',
            'value'             => 'Test Value 222',
            'blankable_value'   => 'Test Blankable Value 222',
            'm_timestamp'       => null,
            'date_created'      => null,
            'non_existent_col'  => 'some value'
        ];
        $result9 = $model->insert($data2Save);
        self::assertArrayHasKey('date_created', $result9);
        self::assertArrayHasKey('m_timestamp', $result9);
        self::assertIsString($result9['date_created']);
        self::assertIsString($result9['m_timestamp']);
        self::assertNotEmpty($result9['date_created']);
        self::assertNotEmpty($result9['m_timestamp']);
    }
    
    public function testThatUpdateMatchingDbTableRowsWorksAsExpected() {
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'key_value');
        $isRunningOnPostgresql = strtolower($model->getPdoDriverName()) === 'pgsql';
        
        $this->clearDbTable($model);
        
        self::assertSame($model, $model->updateMatchingDbTableRows([]));
        self::assertSame($model, $model->updateMatchingDbTableRows([], []));
        self::assertSame(
            $model,
            $model->updateMatchingDbTableRows(
                ['non_existent_col1'  => 'some value1', 'non_existent_col2'  => 'some value1'], 
                []
            )
        );
        self::assertSame(
            $model, 
            $model->updateMatchingDbTableRows(
                ['non_existent_col1'  => 'some value1', 'non_existent_col2'  => 'some value1'], 
                ['key_name' => ['Test Key'],]
            )
        );
        self::assertSame(
            $model,
            $model->updateMatchingDbTableRows(
                ['non_existent_col1'  => 'some value1', 'non_existent_col2'  => 'some value1'], 
                ['key_name' => 'Test Key',]
            )
        );
        self::assertSame(
            $model,
            $model->updateMatchingDbTableRows(
                ['key_name' => 'Test Key',],
                ['non_existent_col1'  => 'some value1', 'non_existent_col2'  => 'some value1']                
            )
        );
        
        //////////////////
        // Add some data
        //////////////////
        $dateTime = date('Y-m-d H:i:s', strtotime("+4 minutes"));
        $dataPrototype = [
            'key_name' => 'Test Key', 
            'value' => 'Test Value', 
            'blankable_value' => 'Test Blankable Value',
            'm_timestamp' => $dateTime,
            'date_created' => $dateTime,
            'non_existent_col' => 'some value'
        ];
        $stringableObj = new \StringableObject();
        
        $i = 0;
        $dataToInsert = [
            $dataPrototype, $dataPrototype, $dataPrototype, 
            $dataPrototype, $dataPrototype, $dataPrototype,
        ];
        
        foreach (array_keys($dataToInsert) as $key) {
            
            $dataToInsert[$key]['key_name'] .= ''.$i;
            $dataToInsert[$key]['value'] .= ''.$i;
            $i++;
        }
        
        $model->insertMany($dataToInsert);
        
        //////////////////////////////////////
        // Test that if empty array is passed as 2nd arg
        // with valid update data as first arg, all rows
        // get updated.
        $newDateTime = date('Y-m-d H:i:s', strtotime("+20 minutes"));
        $newDateTime2 = date('Y-m-d H:i:s', strtotime("+40 minutes"));
        $newDateTime3 = date('Y-m-d H:i:s', strtotime("+50 minutes"));
        self::assertSame(
            $model, 
            $model->updateMatchingDbTableRows(['m_timestamp' => $newDateTime])
        );
        
        foreach ($model->fetchRowsIntoArray() as $dbRecord) {
            
            self::assertEquals($newDateTime, $dbRecord['m_timestamp']);
        }
        
        self::assertSame(
            $model, 
            $model->updateMatchingDbTableRows(['m_timestamp' => $newDateTime2], [])
        );
        
        foreach ($model->fetchRowsIntoArray() as $dbRecord) {
            
            self::assertEquals($newDateTime2, $dbRecord['m_timestamp']);
        }
        
        ////////////////////////////////////////////////////////////////////////
        // Make sure primary key value never gets updated even if new primary key 
        // value is supplied
        self::assertSame(
            $model,
            $model->updateMatchingDbTableRows(
                ['m_timestamp' => $newDateTime3, 'id' => 777]
            )
        );
        
        foreach ($model->fetchRowsIntoArray() as $dbRecord) {
            
            self::assertEquals($newDateTime3, $dbRecord['m_timestamp']);
            self::assertNotEquals('777', ''.$dbRecord['id']);
        }
        
        ////////////////////////////////////////////////////////////////////////
        // Test updating a column to 
        // bool, null, number, *object with __toString
        
        // bool true
        self::assertSame(
            $model, 
            $model->updateMatchingDbTableRows(['blankable_value' => true])
        );
        
        $idsOfAllRecords = [null, null];
        
        foreach ($model->fetchRowsIntoArray() as $dbRecord) {
            
            $idsOfAllRecords[] = $dbRecord['id'];
            
            self::assertEquals(
                $isRunningOnPostgresql ? 't' : '1' , 
                $dbRecord['blankable_value'].''
            );
        }
        
        // bool false
        self::assertSame(
            $model,
            $model->updateMatchingDbTableRows(
                ['blankable_value' => false],
                ['id' => $idsOfAllRecords] // valid ids mixed with nulls
            )
        );
        
        foreach ($model->fetchRowsIntoArray() as $dbRecord) {
            
            self::assertEquals(
                $isRunningOnPostgresql ? 'f' : '0' , 
                $dbRecord['blankable_value'].''
            );
        }
        
        // null
        self::assertSame(
            $model,
            $model->updateMatchingDbTableRows(['blankable_value' => null])
        );
        
        foreach ($model->fetchRowsIntoArray() as $dbRecord) {
            
            self::assertEquals(
                null, 
                $dbRecord['blankable_value']
            );
        }
        
        // integer
        self::assertSame(
            $model,
            $model->updateMatchingDbTableRows(['blankable_value' => 777])
        );
        
        foreach ($model->fetchRowsIntoArray() as $dbRecord) {
            
            self::assertEquals(
                777, 
                $dbRecord['blankable_value']
            );
        }
        
        // float
        self::assertSame(
            $model,
            $model->updateMatchingDbTableRows(['blankable_value' => 777.888])
        );
        
        foreach ($model->fetchRowsIntoArray() as $dbRecord) {
            
            self::assertEquals(
                777.888, 
                $dbRecord['blankable_value']
            );
        }
        
        // stringable object
        self::assertSame(
            $model, 
            $model->updateMatchingDbTableRows(['blankable_value' => $stringableObj])
        );
        
        foreach ($model->fetchRowsIntoArray() as $dbRecord) {
            
            self::assertIsString( 
                $dbRecord['blankable_value']
            );
            
            self::assertFalse( 
                \LeanOrm\Utils::isEmptyString($dbRecord['blankable_value'])
            );
        }

        ////////////////////////////////////////////////////////////////////////
        // Test updating a column using  
        // bool, null, number, *object with __toString
        // in the WHERE clause, " WHERE Col IN " or " WHERE Col = ? "
        $this->runScalarWhereParamValsTestsForUpdateMatchingDbTableRows($model, true);
        $this->runScalarWhereParamValsTestsForUpdateMatchingDbTableRows($model, false);
        $this->runScalarWhereParamValsTestsForUpdateMatchingDbTableRows($model, null);
        $this->runScalarWhereParamValsTestsForUpdateMatchingDbTableRows($model, 777);
        $this->runScalarWhereParamValsTestsForUpdateMatchingDbTableRows($model, 777.888);
        $this->runScalarWhereParamValsTestsForUpdateMatchingDbTableRows($model, $stringableObj);
        
        // Test that the updated_timestamp_column_name functionality works as expected
        //$dateTime = date('Y-m-d H:i:s', strtotime("+4 minutes"));
        $dateTimeTenMinsAgo = date('Y-m-d H:i:s', strtotime("-10 minutes")); // e.g "2022-12-05 07:30:57"
        $aRecordFromDB = $model->fetchOneRecord();
        $aRecordFromDB->m_timestamp = $dateTimeTenMinsAgo;
        $aRecordFromDB->save();
        
        $refreshRecord = function($aRecordFromDB) use ($model): \LeanOrm\Model\Record {
            return $model->fetchOneRecord(
                        $model->getSelect()
                              ->where(
                                    " {$aRecordFromDB->getPrimaryCol()} = ? ", 
                                    $aRecordFromDB->getPrimaryVal()
                                )
                    );
        };
        self::assertEquals($dateTimeTenMinsAgo, $refreshRecord($aRecordFromDB)->m_timestamp);
        $model->setUpdatedTimestampColumnName('m_timestamp');
        
        // Now update a column of the DB table without explicitly specifying a value
        // for m_timestamp. Result: m_timestamp should be automatically updated too
        $model->updateMatchingDbTableRows(['blankable_value' => 'A new value']);
        self::assertNotEquals($dateTimeTenMinsAgo, $refreshRecord($aRecordFromDB)->m_timestamp);
        
        $dateTimeFromLastRefreshedRecord = 
            DateTime::createFromFormat('Y-m-d H:i:s', $refreshRecord($aRecordFromDB)->m_timestamp);
                
        self::assertTrue(
            DateTime::createFromFormat('Y-m-d H:i:s', $dateTimeTenMinsAgo)
            <  $dateTimeFromLastRefreshedRecord
        );
        
        // Do another update, this time include m_timestamp with a null & '' value
        sleep(1); // sleep for one second to make sure updated timestamp is different
        $model->updateMatchingDbTableRows(
            ['blankable_value' => 'A new value', 'm_timestamp' => null ]
        ); // m_timestamp should be auto populated under the hood
        
        self::assertTrue(
            $dateTimeFromLastRefreshedRecord
            <  DateTime::createFromFormat('Y-m-d H:i:s', $refreshRecord($aRecordFromDB)->m_timestamp)
        );
        
        $dateTimeFromLastRefreshedRecord = 
            DateTime::createFromFormat('Y-m-d H:i:s', $refreshRecord($aRecordFromDB)->m_timestamp);
        
        ////////////////////////////////////////////////////////////////////////
        sleep(1); // sleep for one second to make sure updated timestamp is different
        $model->updateMatchingDbTableRows(
            ['blankable_value' => 'A new value', 'm_timestamp' => '' ]
        ); // m_timestamp should be auto populated under the hood
        
        self::assertTrue(
            $dateTimeFromLastRefreshedRecord
            <  DateTime::createFromFormat('Y-m-d H:i:s', $refreshRecord($aRecordFromDB)->m_timestamp)
        );
        
        $dateTimeFromLastRefreshedRecord = 
            DateTime::createFromFormat('Y-m-d H:i:s', $refreshRecord($aRecordFromDB)->m_timestamp);
        
        ////////////////////////////////////////////////////////////////////////
        $dateTimeTwentyMinsAgo = date('Y-m-d H:i:s', strtotime("-20 minutes"));
        
        $model->updateMatchingDbTableRows(
            [
                'blankable_value' => 'A new value', 
                'm_timestamp' => $dateTimeTwentyMinsAgo 
            ]
        ); // m_timestamp should not be auto populated under the hood
           // because we have explicitly set it to a non-empty value
        
        self::assertTrue(
            $dateTimeFromLastRefreshedRecord
             >  DateTime::createFromFormat('Y-m-d H:i:s', $refreshRecord($aRecordFromDB)->m_timestamp)
        ); 
    }
    
    public function testThatUpdateSpecifiedRecordWorksAsExpected() {
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'key_value');
        
        $recordsToInsert = [
            ['key_name'=> 'key 1', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+2 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+1 minutes"))],
            ['key_name'=> 'key 2', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+3 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+2 minutes"))],
            ['key_name'=> 'key 3', 'value'=> 'value 1', 'm_timestamp' => date('Y-m-d H:i:s',  strtotime("+4 minutes")), 'date_created'=> date('Y-m-d H:i:s',  strtotime("+3 minutes"))],
        ];
        
        $this->insertDataIntoTable('key_value', $recordsToInsert[0], $model->getPDO());
        $this->insertDataIntoTable('key_value', $recordsToInsert[1], $model->getPDO());
        $this->insertDataIntoTable('key_value', $recordsToInsert[2], $model->getPDO());
        
        // Injecting a newly Created & never saved record
        self::assertSame($model, $model->updateSpecifiedRecord($model->createNewRecord()));
        
        // Injecting an existing record without any changes
        self::assertSame($model, $model->updateSpecifiedRecord($model->fetchOneRecord()));

        $existingRecord = $model->fetchOneRecord();
        $existingRecord->value = 'A New Value';
        $existingRecord->blankable_value = 'A New Blankable Value';
        
        // Injecting an existing record with changes should return same model object
        self::assertSame($model, $model->updateSpecifiedRecord($existingRecord));
        
        // verify that the just updated record was properly updated
        $refetchedRecord = 
            $model->fetchOneRecord(
                        $model->getSelect()
                              ->where(
                                    " {$existingRecord->getPrimaryCol()} = ? ", 
                                    $existingRecord->getPrimaryVal()
                                )
                    );
                                    
        self::assertEquals('A New Value', $refetchedRecord->value);
        self::assertEquals('A New Blankable Value', $refetchedRecord->blankable_value);
        
        // Test updated timestamp was correctly auto-updated
        $model->setUpdatedTimestampColumnName('m_timestamp');
        $existingRecord->value = 'A New Value 2';
        $existingRecord->blankable_value = 'A New Blankable Value 2';
        $initialTimestampValue = $existingRecord->m_timestamp;
        
        // 2 seconds to elapse so that updated timestamp will be 2 seconds from now
        // after the update
        sleep(2);
        
        // Injecting an existing record with changes
        self::assertSame($model, $model->updateSpecifiedRecord($existingRecord));
        
        // The m_timestamp value will have been updated in $existingRecord
        // and have a more recent value than $initialTimestampValue
        self::assertNotEquals($initialTimestampValue, $existingRecord->m_timestamp);

        /////////////////////////////////////////////////////////////////////////////////
        // Updating an unchanged record should have no effect. Data should stay the same.
        $preUpdateData = $existingRecord->getData();
        sleep(2);
        self::assertSame($model, $model->updateSpecifiedRecord($existingRecord));
        
        foreach ($existingRecord as $col_name=>$col_val) {
            
            $this->assertSame($preUpdateData[$col_name], $col_val);
        } // foreach ($existingRecord as $col_name=>$col_val)
    }
    
    public function testThatUpdateSpecifiedRecordThrowsExceptionWithReadOnlyRecord() {
        
        // acceptable insert values are
        // *bool, *null, *number, *string, *object with __toString
        // Any value outside of these is considered invalid for insert
        $this->expectException(\LeanOrm\CantSaveReadOnlyRecordException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'key_value');
        $model->setRecordClassName(\LeanOrm\Model\ReadOnlyRecord::class);
        
        $model->updateSpecifiedRecord($model->createNewRecord()); // will throw exception
    }
    
    public function testThatUpdateSpecifiedRecordThrowsExceptionWithRecordBelongingToDifferentTable() {
        
        // acceptable insert values are
        // *bool, *null, *number, *string, *object with __toString
        // Any value outside of these is considered invalid for insert
        $this->expectException(\GDAO\ModelInvalidUpdateValueSuppliedException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'key_value');
        $model->setRecordClassName(\LeanOrm\Model\ReadOnlyRecord::class);
        
        $model->updateSpecifiedRecord(
            $this->testModelObjects['authors_with_specialized_collection_and_record']->createNewRecord()
        ); // will throw exception
    }
    
    protected function runScalarWhereParamValsTestsForUpdateMatchingDbTableRows(
        LeanOrm\Model $model, $scalarVal  
    ): void {
        
        // Set all values under the blankable_value col 
        // to a random value that is not $scalarVal
        $model->updateMatchingDbTableRows(
            ['blankable_value' => date('Y-m-d H:i:s') . random_int(1, 999) ]
        );
        
        // " WHERE Col = ? "  and " WHERE Col = ? " when ? is bool true
        // At this point the column contains a string value, where clause would 
        // return 0 records affected
        self::assertSame(
            $model,
            $model->updateMatchingDbTableRows(
                ['blankable_value' => 'A Val'],
                ['blankable_value' => $scalarVal]
            ) // " where blankable_value = ? " ? === true
        );
        self::assertSame(
            $model,
            $model->updateMatchingDbTableRows(
                ['blankable_value' => 'A Val'],
                ['blankable_value' => [$scalarVal, $scalarVal]]
            )  // " where blankable_value IN (?, ?) " ? === $scalarVal
        );

        // Now set all values under the blankable_value col 
        // to the desired value of $scalarVal
        $model->updateMatchingDbTableRows(
            ['blankable_value' => $scalarVal]
        );
        
        // At this point the column contains the value $scalarVal, 
        // where clause would return $numRecsInTable records affected
        self::assertSame(
            $model,
            $model->updateMatchingDbTableRows(
                ['blankable_value' => 'A Val'],
                ['blankable_value' => $scalarVal]
            ) // " where blankable_value = ? " ? === $scalarVal
        );
        
        foreach ($model->fetchRowsIntoArray() as $dbRecord) {
            
            self::assertEquals('A Val', $dbRecord['blankable_value']);
        } // foreach ($model->fetchRowsIntoArray() as $dbRecord)
        
        // Now set all values under the blankable_value col 
        // to the desired value of $scalarVal again
        $model->updateMatchingDbTableRows(['blankable_value' => $scalarVal]);
        
        self::assertSame(
            $model,
            $model->updateMatchingDbTableRows(
                ['blankable_value' => 'A Val'],
                ['blankable_value' => [$scalarVal, $scalarVal]]
            )  // " where blankable_value IN (?, ?) "        ? === $scalarVal
        );

        foreach ($model->fetchRowsIntoArray() as $dbRecord) {
            
            self::assertEquals('A Val', $dbRecord['blankable_value']);
        } // foreach ($model->fetchRowsIntoArray() as $dbRecord)
    }
    
    public function testThatInsertManyWorksAsExpected() {
        
        $model = new $this->modelClass(
            static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'key_value'
        );

        $isRunningOnPostgresql = strtolower($model->getPdoDriverName()) === 'pgsql';

        // Empty arrays should return false
        self::assertFalse($model->insertMany());
        self::assertFalse($model->insertMany([]));
        self::assertFalse($model->insertMany([[],[],[]]));
        
        // Non-existent columns should return false
        self::assertFalse(
            $model->insertMany(
                [
                    ['non_existent_col1'  => 'some value1', 'non_existent_col2'  => 'some value1'],
                    ['non_existent_col1'  => 'some value2', 'non_existent_col2'  => 'some value2'],
                    ['non_existent_col1'  => 'some value3', 'non_existent_col2'  => 'some value3']
                ]
            )
        );
        
        $this->clearDbTable($model);
        
        ///////////////////////////////////////////////////////
        // do some inserts with valid data types, ie:
        // bool, null, number, string, object with __toString
        $dateTime = date('Y-m-d H:i:s', strtotime("+4 minutes"));
        $dataPrototype = [
            'key_name' => 'Test Key', 
            'value' => 'Test Value', 
            'blankable_value' => 'Test Blankable Value',
            'm_timestamp' => $dateTime,
            'date_created' => $dateTime,
            'non_existent_col' => 'some value'
        ];
        $stringableObj = new StringableObject();
        $stringValOfObj = $stringableObj->__toString();
        
        $dataWithBoolTrue   = $dataPrototype;
        $dataWithBoolFalse  = $dataPrototype;
        $dataWithNull       = $dataPrototype;
        $dataWithInteger    = $dataPrototype;
        $dataWithFloat      = $dataPrototype;
        $dataWithStringable = $dataPrototype;
        
        $dataWithBoolTrue['blankable_value']    = true;
        $dataWithBoolFalse['blankable_value']   = false;
        $dataWithNull['blankable_value']        = null;
        $dataWithInteger['blankable_value']     = 777;
        $dataWithFloat['blankable_value']       = 777.888;
        $dataWithStringable['blankable_value']  = $stringableObj;
        
        $i = 0;
        $dataToInsert = [
            $dataWithBoolTrue, $dataWithBoolFalse, $dataWithNull, 
            $dataWithInteger, $dataWithFloat, $dataWithStringable
        ];
        
        foreach (array_keys($dataToInsert) as $key) {
            
            $dataToInsert[$key]['key_name'] .= ''.$i;
            $dataToInsert[$key]['value'] .= ''.$i;
            $i++;
        }
        
        $result1 = $model->insertMany($dataToInsert);
        
        // insertMany returned true which is what we want
        self::assertTrue($result1);
        
        // let's verify each row from the DB
        foreach ($dataToInsert as $potentiallySavedData) {
            
            $dbRecord = 
                $model->fetchOneRecord(
                            $model->getSelect()
                                  ->where(
                                        ' key_name = ? ', 
                                        $potentiallySavedData['key_name']
                                    ) 
                        )->getData();
            
            self::assertEquals($dbRecord['value'], $potentiallySavedData['value']);
            self::assertEquals($dbRecord['m_timestamp'], $dateTime);
            self::assertEquals($dbRecord['date_created'], $dateTime);

            if(is_bool($potentiallySavedData['blankable_value']) ) {
                
                if($potentiallySavedData['blankable_value']) {
                    
                    // true value
                    self::assertEquals(
                        $dbRecord['blankable_value'].'', 
                        $isRunningOnPostgresql ? 't' : '1'
                    );

                } else {
                    
                    // false value
                    self::assertEquals(
                        $dbRecord['blankable_value'].'', 
                        $isRunningOnPostgresql ? 'f' : '0'
                    );
                }
                
            } elseif(is_object($potentiallySavedData['blankable_value'])){
                
                self::assertEquals(
                    $dbRecord['blankable_value'], 
                    $stringValOfObj
                );
                
            } else {
                
                self::assertEquals(
                    $dbRecord['blankable_value'], 
                    $potentiallySavedData['blankable_value']
                );
            } // if(is_bool($potentiallySavedData['blankable_value']) )
        } // foreach ($dataToInsert as $potentiallySavedData)
        
        $this->clearDbTable($model); //empty the table again
        
        ///////////////////////////////////////////////////////////////////////
        // Going to test the created_timestamp_column_name
        // & updated_timestamp_column_name auto-populating
        // feature. Also test that when null values are set 
        // for the primary key column in a table that has
        // an auto-incrementing primary key column, the data
        // gets saved correctly.
        ///////////////////////////////////////////////////////////////////////
        $model->setCreatedTimestampColumnName('date_created');
        $model->setUpdatedTimestampColumnName('m_timestamp');
        
        $dataWithTimestampsSpecified                = $dataPrototype; // already set in $dataPrototype
        $dataWithTimestampsSpecifiedWithNull        = $dataPrototype;
        $dataWithTimestampsSpecifiedWithEmptyString = $dataPrototype;
        $dataWithoutTimestampFields                 = $dataPrototype;
        
        $i = 0;
        $dataWithTimestampsSpecified;
        $dataWithTimestampsSpecified['id'] = null; // testing null pk val
        $dataWithTimestampsSpecified['key_name'] .= ''.$i;
        $dataWithTimestampsSpecified['value'] .= ''.$i++;

        $dataWithTimestampsSpecifiedWithNull['id']                  = null; // testing null pk val
        $dataWithTimestampsSpecifiedWithNull['m_timestamp']         = null;
        $dataWithTimestampsSpecifiedWithNull['date_created']        = null;
        $dataWithTimestampsSpecifiedWithNull['key_name'] .= ''.$i;
        $dataWithTimestampsSpecifiedWithNull['value'] .= ''.$i++;
        
        $dataWithTimestampsSpecifiedWithEmptyString['id']           = null; // testing null pk val
        $dataWithTimestampsSpecifiedWithEmptyString['m_timestamp']  = '';
        $dataWithTimestampsSpecifiedWithEmptyString['date_created'] = '';
        $dataWithTimestampsSpecifiedWithEmptyString['key_name'] .= ''.$i;
        $dataWithTimestampsSpecifiedWithEmptyString['value'] .= ''.$i++;
        
        unset($dataWithoutTimestampFields['m_timestamp']);
        unset($dataWithoutTimestampFields['date_created']);
        $dataWithoutTimestampFields['id']           = null; // testing null pk val
        $dataWithoutTimestampFields['key_name']    .= ''.$i;
        $dataWithoutTimestampFields['value']       .= ''.$i++;
        
        $dataToInsert = [
            $dataWithTimestampsSpecified, $dataWithTimestampsSpecifiedWithNull, 
            $dataWithTimestampsSpecifiedWithEmptyString, $dataWithoutTimestampFields
        ];
        
        $result2 = $model->insertMany($dataToInsert);
        
        // insertMany returned true which is what we want
        self::assertTrue($result2);
        
        
        // let's verify each row from the DB
        foreach ($dataToInsert as $potentiallySavedData) {
            
            $dbRecord = 
                $model->fetchOneRecord(
                            $model->getSelect()
                                  ->where(
                                        ' key_name = ? ', 
                                        $potentiallySavedData['key_name']
                                    ) 
                        )->getData();
            
            if($potentiallySavedData === $dataWithTimestampsSpecified) {
                
                // this is the only record that had its timestamp fields
                // set properly. Verify that the set values are present
                // in the record just fetched from the db
                self::assertEquals($dateTime, $dbRecord['date_created']);
                self::assertEquals($dateTime, $dbRecord['m_timestamp']);
                
            } else {
                
                // every other record either did not have timestamp values
                // set or they were set with nulls or empty strings.
                self::assertNotEquals($dateTime, $dbRecord['date_created']);
                self::assertNotEquals($dateTime, $dbRecord['m_timestamp']);
            }
        }
        
        ///////////////////////////////////////////////////////////////////////
        // We are now going to save data into a table without a 
        // non-auto-incrementing primary key column. This table requires
        // that primary key values be specified in each row to insert.
        ///////////////////////////////////////////////////////////////////////
        $model2 = new $this->modelClass(
            static::$dsn, static::$username ?? "", static::$password ?? "", 
            [], 'id', 'key_value_no_auto_inc_pk'
        );
        
        $this->clearDbTable($model2); //empty the table
        
        $data2Insert = [
            [
                'id'                => 777,
                'key_name'          => 'Test Key 1', 
                'value'             => 'Test Value 1', 
                'blankable_value'   => 'Test Blankable Value 1',
                'm_timestamp'       => $dateTime,
                'date_created'      => $dateTime,
                'non_existent_col'  => 'some value'
            ],
            [
                'id'                => 778,
                'key_name'          => 'Test Key 2', 
                'value'             => 'Test Value 2', 
                'blankable_value'   => 'Test Blankable Value 2',
                'm_timestamp'       => $dateTime,
                'date_created'      => $dateTime,
                'non_existent_col'  => 'some value'
            ],
            
        ];
        self::assertTrue($model2->insertMany($data2Insert));
        $idsFromDB = $model2->fetchCol($model2->getSelect()->cols(['id']));
        
        // check that the primary key values we set in the data we just inserted
        // were saved to the DB
        self::assertEquals(
            $isRunningOnPostgresql ? [777, 778] : ['777', '778'], 
            $idsFromDB
        );
    }
    
    public function testThatInsertThrowsExceptionWithUnacceptableInsertValue() {
        
        // acceptable insert values are
        // *bool, *null, *number, *string, *object with __toString
        // Any value outside of these is considered invalid for insert
        $this->expectException(\GDAO\ModelInvalidInsertValueSuppliedException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'key_value');
        
        ///////////////////////////////////////////////////////////////////////////
        // Some of the keys in the data array are actual table cols are table cols
        $dateTime = date('Y-m-d H:i:s');
        $data2Save = [
            'key_name'          => 'Test Key',
            'value'             => 'Test Value',
            'blankable_value'   => ['Test Blankable Value'], // invalid value
            'm_timestamp'       => $dateTime,
            'date_created'      => $dateTime,
            'non_existent_col'  => 'some value'
        ];
        $model->insert($data2Save); // will throw exception
    }
    
    public function testThatInsertManyThrowsExceptionWithUnacceptableInsertValueForAColumnInARow() {
        
        // acceptable insert values are
        // *bool, *null, *number, *string, *object with __toString
        // Any value outside of these is considered invalid for insert
        $this->expectException(\GDAO\ModelInvalidInsertValueSuppliedException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'key_value');
        
        ///////////////////////////////////////////////////////////////////////////
        // Some of the keys in the data array are actual table cols are table cols
        $dateTime = date('Y-m-d H:i:s');
        $data2Save = [
            [
                'key_name'          => 'Test Key',
                'value'             => 'Test Value',
                'blankable_value'   => ['Test Blankable Value'], // invalid value
                'm_timestamp'       => $dateTime,
                'date_created'      => $dateTime,
                'non_existent_col'  => 'some value'
            ]
        ];
        $model->insertMany($data2Save); // will throw exception
    }
    
    public function testThatInsertManyThrowsExceptionWithNonArrayInsideArrayParameter() {
        
        // acceptable insert values are
        // *bool, *null, *number, *string, *object with __toString
        // Any value outside of these is considered invalid for insert
        $this->expectException(\GDAO\ModelInvalidInsertValueSuppliedException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'key_value');
        
        ///////////////////////////////////////////////////////////////////////////
        // Some of the keys in the data array are actual table cols are table cols
        $dateTime = date('Y-m-d H:i:s');
        $data2Save = [
            "non array value", // this should trigger an exception
            [
                'key_name'          => 'Test Key 5',
                'value'             => 'Test Value 5',
                'blankable_value'   => 'Test Blankable Value 5', 
                'm_timestamp'       => $dateTime,
                'date_created'      => $dateTime,
                'non_existent_col'  => 'some value'
            ]
        ];
        $model->insertMany($data2Save); // will throw exception
    }
    
    
    public function testThatUpdateMatchingDbTableRowsThrowsExceptionWithUnacceptableUpdateWhereParam() {
        
        // acceptable insert values are
        // *bool, *null, *number, *string, *object with __toString
        // Any value outside of these is considered invalid for insert
        $this->expectException(\LeanOrm\InvalidArgumentException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'key_value');
        $update_vals = ['key_name' => 'Test Key New',];
        $update_query = [
            'key_name'          => 'Test Key',
            'value'             => 'Test Value',
            'blankable_value'   => [
                                        'Test Blankable Value', 
                                        [], // invalid sub array value
                                   ], 
        ];
        $model->updateMatchingDbTableRows($update_vals, $update_query); // will throw exception
    }
    
    public function testThatUpdateMatchingDbTableRowsThrowsExceptionWithUnacceptableUpdateWhereParam2() {
        
        // acceptable insert values are
        // *bool, *null, *number, *string, *object with __toString
        // Any value outside of these is considered invalid for insert
        $this->expectException(\LeanOrm\InvalidArgumentException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'key_value');
        $update_vals = ['key_name' => 'Test Key New',];
        $update_query = [
            'key_name'          => 'Test Key',
            'value'             => 'Test Value',
            'blankable_value'   => function(){}, // invalid value 
        ];
        $model->updateMatchingDbTableRows($update_vals, $update_query); // will throw exception
    }
    
    public function testThatUpdateMatchingDbTableRowsThrowsExceptionWithUnacceptableUpdateParam() {
        
        // acceptable insert values are
        // *bool, *null, *number, *string, *object with __toString
        // Any value outside of these is considered invalid for insert
        $this->expectException(\GDAO\ModelInvalidUpdateValueSuppliedException::class);
        
        $model = new $this->modelClass(static::$dsn, static::$username ?? "", static::$password ?? "", [], 'id', 'key_value');
        $update_vals = [
            'key_name' => 'Test Key New',
            'blankable_value'   => function(){}, // invalid value 
        ];
        $update_query = [
            'key_name'          => 'Test Key',
            'value'             => 'Test Value',
        ];
        $model->updateMatchingDbTableRows($update_vals, $update_query); // will throw exception
    }
    
    public function testThatLoggerGetsTransferredFromParentRecordToRelatedRecords() {
        
        $postsModel = new LeanOrm\TestObjects\PostsModel(static::$dsn, static::$username ?? "", static::$password ?? "");
        $postsModel->setLogger(static::$psrLogger);

        $compareLoggers = 
            function(
                \LeanOrm\TestObjects\PostRecord $post, 
                GDAO\Model\CollectionInterface $relatedRecords, 
                bool $loggerMustBeSameInstance=true
            ): void {
                
                foreach($relatedRecords as $relatedRecord) {
                    
                    if($loggerMustBeSameInstance) {
                        
                        static::assertSame(
                            $post->getModel()->getLogger(), 
                            $relatedRecord->getModel()->getLogger()
                        );
                        
                    } else {
                      
                        static::assertNotSame(
                            $post->getModel()->getLogger(), 
                            $relatedRecord->getModel()->getLogger()
                        );
                    }
                    
                    static::assertSame(
                        $post->getModel()->canLogQueries(), 
                        $relatedRecord->getModel()->canLogQueries()
                    );
                } // foreach($relatedRecords as $relatedRecord)
            };
            
        $doLoop = function(GDAO\Model\CollectionInterface $allPostsWithAllRelateds) use ($compareLoggers): void {
            
            /** @var LeanOrm\TestObjects\PostRecord $postRecord */
            foreach($allPostsWithAllRelateds as $postRecord) {

                // author of the post
                static::assertSame(
                    $postRecord->getModel()->getLogger(), 
                    $postRecord->author->getModel()->getLogger()
                );
                static::assertSame(
                    $postRecord->getModel()->canLogQueries(), 
                    $postRecord->author->getModel()->canLogQueries()
                );

                // post's comments
                $compareLoggers($postRecord, $postRecord->comments);

                // summary of the post
                static::assertSame(
                    $postRecord->getModel()->getLogger(), 
                    $postRecord->summary->getModel()->getLogger()
                );
                static::assertSame(
                    $postRecord->getModel()->canLogQueries(), 
                    $postRecord->summary->getModel()->canLogQueries()
                );

                // post's posts_tags
                $compareLoggers($postRecord, $postRecord->posts_tags);

                // post's tags, \LeanOrm\TestObjects\TagsModel has its
                // own logger set in its constructor, its model will
                // not have the same logger as $postsModel even though
                // it's fetched as related data
                $compareLoggers($postRecord, $postRecord->tags, false);

            } // foreach($allPostsWithAllRelateds as $postRecord)  
        }; // $doLoop = function(.....
        
        ////////////////////////////////////////////////////////////////////////
        // Fetch with all relateds
        ////////////////////////////////////////////////////////////////////////
        $postsModel->disableQueryLogging();
        $allPostsWithAllRelateds = 
            $postsModel->fetchRecordsIntoCollection(
                null, [ 'author', 'comments', 'summary', 'posts_tags', 'tags', ]
            );
        $doLoop($allPostsWithAllRelateds);
        
        $postsModel->enableQueryLogging();
        // Because Query logging with a logger that prints to the console / terminal
        // will lead to Queries being outputed to the terminal phpunit is being run
        // in, let's buffer the output from the logging & prevent it from being 
        // displayed on the terminal
        \ob_start();
        $allPostsWithAllRelateds2 = 
            $postsModel->fetchRecordsIntoCollection(
                null, [ 'author', 'comments', 'summary', 'posts_tags', 'tags', ]
            );
        \ob_end_clean();
        $doLoop($allPostsWithAllRelateds2);
        
        $postsModel->disableQueryLogging();
    }
}
