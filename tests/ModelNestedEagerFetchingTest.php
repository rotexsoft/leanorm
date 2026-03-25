<?php
use \LeanOrm\TestObjects\{
    AuthorsModel, AuthorRecord, AuthorsCollection,
    CommentsModel, CommentRecord, CommentsCollection,
    PostsModel, PostRecord, PostsCollection,
    PostsTagsModel, PostTagRecord, PostsTagsCollection,
    SummariesModel, SummaryRecord, SummariesCollection,
    TagsModel, TagRecord, TagsCollection
};
use \LeanOrm\Model as LeanOrmModel;
use \LeanOrm\Model\Record as LeanOrmRecord;
use \GDAO\Model\{CollectionInterface, RecordInterface};

/**
 * Description of ModelNestedEagerFetchingTest
 *
 * @author rotimi
 */
class ModelNestedEagerFetchingTest extends \PHPUnit\Framework\TestCase {
    
    use CommonPropertiesAndMethodsTrait;

    protected function getLeanOrmModel(string $modelClassName, string $tableName='', string $primaryColName=''): \LeanOrm\Model {

        static $models; if(!$models) { $models = []; }

        if(array_key_exists($modelClassName, $models)) { return $models[$modelClassName]; }

        if(!is_a($modelClassName, \LeanOrm\Model::class, true)) {

            throw new \Exception(
                "ERROR: The class name `{$modelClassName}` supplied for creating a new model is not "
               . "`" . \LeanOrm\Model::class . "` or any of its sub-classes!"
            );
        }

        /** @var \LeanOrm\Model $model */
        $model = new $modelClassName (
            static::$dsn, static::$username ?? "", static::$password ?? "",
            [], $primaryColName, $tableName
        );
        $model->setCreatedTimestampColumnName('date_created')
              ->setUpdatedTimestampColumnName('m_timestamp');
        $models[$modelClassName] = $model; // Cache

        return $model;
    }
    
    protected function setUpNestedEagerFetchData(): array {
        
        $authorsModel = $this->getLeanOrmModel(AuthorsModel::class);
        $postsModel = $this->getLeanOrmModel(\LeanOrm\TestObjects\PostsModel::class);
        $commentsModel = $this->getLeanOrmModel(\LeanOrm\TestObjects\CommentsModel::class);
        $postsTagsModel = $this->getLeanOrmModel(\LeanOrm\TestObjects\PostsTagsModel::class);
        $tagsModel = $this->getLeanOrmModel(\LeanOrm\TestObjects\TagsModel::class);
        $summariesModel = $this->getLeanOrmModel(\LeanOrm\TestObjects\SummariesModel::class);

        $dataToReturn = [];
        
        $authorJackBauer = $authorsModel->createNewRecord(['name'=>'Jack Bauer']);
        $authorJackBauer->save();
        $dataToReturn['author1'] = $authorJackBauer;

        $authorJaneDoeNoRelatedData = $authorsModel->createNewRecord(['name'=>'Jane Doe']);
        $authorJaneDoeNoRelatedData->save();
        $dataToReturn['author2'] = $authorJaneDoeNoRelatedData;

        /////////////
        // posts
        $jackBauerPost1 = $postsModel->createNewRecord([
            'author_id' => $authorJackBauer->getPrimaryVal(),
            'datetime'  => \date('Y-m-d H:i:s'),
            'title'     => 'Jack Bauer Post 1 Title',
            'body'      => 'Jack Bauer Post 1 Body',
        ]);
        $jackBauerPost1->save();
        $dataToReturn['author1post1'] = $jackBauerPost1;

        $jackBauerPost2NoRelatedData = $postsModel->createNewRecord([
            'author_id' => $authorJackBauer->getPrimaryVal(),
            'datetime'  => \date('Y-m-d H:i:s'),
            'title'     => 'Jack Bauer Post 2 Title',
            'body'      => 'Jack Bauer Post 2 Body',
        ]);
        $jackBauerPost2NoRelatedData->save();
        $dataToReturn['author1post2'] = $jackBauerPost2NoRelatedData;

        /////////////
        // comments
        $jackBauerPost1Comment1 = $commentsModel->createNewRecord([
            'post_id'   => $jackBauerPost1->getPrimaryVal(),
            'datetime'  =>  \date('Y-m-d H:i:s'),
            'name'      => 'Commenter 1',
            'email'     => 'a@b.com',
            'website'   => 'b.com',
            'body'      => 'Jack Bauer Post 1 Comment 1',
        ]);
        $jackBauerPost1Comment1->save();
        $dataToReturn['author1post1comment1'] = $jackBauerPost1Comment1;

        $jackBauerPost1Comment2 = $commentsModel->createNewRecord([
            'post_id'   => $jackBauerPost1->getPrimaryVal(),
            'datetime'  =>  \date('Y-m-d H:i:s'),
            'name'      => 'Commenter 2',
            'email'     => 'a@b.com',
            'website'   => 'b.com',
            'body'      => 'Jack Bauer Post 1 Comment 2',
        ]);
        $jackBauerPost1Comment2->save();
        $dataToReturn['author1post1comment2'] = $jackBauerPost1Comment2;

        /////////////
        // tags
        $tag1 = $tagsModel->createNewRecord([
            'name' => 'tag_5',
        ]);
        $tag1->save();
        $dataToReturn['tag1'] = $tag1;

        $tag2 = $tagsModel->createNewRecord([
            'name' => 'tag_6',
        ]);
        $tag2->save();
        $dataToReturn['tag2'] = $tag2;

        ///////////////
        // posts_tags
        $postTag1 = $postsTagsModel->createNewRecord([
            'tag_id'    => $tag1->getPrimaryVal(),
            'post_id'   => $jackBauerPost1->getPrimaryVal(),
        ]);
        $postTag1->save();
        $dataToReturn['author1post1tag1'] = $postTag1;

        $postTag2 = $postsTagsModel->createNewRecord([
            'tag_id'    => $tag2->getPrimaryVal(),
            'post_id'   => $jackBauerPost1->getPrimaryVal(),
        ]);
        $postTag2->save();
        $dataToReturn['author1post1tag2'] = $postTag2;

        ///////////////
        // summaries
        $jackBauerPost1Summary = $summariesModel->createNewRecord([
            'post_id'       => $jackBauerPost1->getPrimaryVal(),
            'view_count'    => 1,
            'comment_count' => 2,
        ]);
        $jackBauerPost1Summary->save();
        $dataToReturn['author1post1summary'] = $jackBauerPost1Summary;
        
        return $dataToReturn;
    }
    
    protected function tearDownNestedEagerFetchData(
        AuthorRecord $authorWithRelatedData,
        AuthorRecord $authorWithoutRelatedData
    ): void {
        
        $authorWithoutRelatedData->delete();

        foreach ($authorWithRelatedData->posts as $post) {

            foreach($post->comments as $comment) {

                $comment->delete();
            }

            foreach($post->posts_tags as $pt) {

                $pt->tag->delete();
                $pt->delete();
            }

            ($post->summary instanceof \LeanOrm\TestObjects\SummaryRecord) && $post->summary->delete();
            $post->delete();
        }
        $authorWithRelatedData->delete();
    }
    
    protected function recordsMatch(array $fieldNames, $record1, $record2): bool {
        
        foreach($fieldNames as $fieldName) {
            
            if(
                (
                    \is_scalar($record1->$fieldName)
                    && \is_scalar($record2->$fieldName)
                    && $record1->$fieldName .'' !== $record2->$fieldName . ''
                )
                || $record1->$fieldName !== $record2->$fieldName
            ){
                return false;
            }
        }
        
        return \count($fieldNames) > 0 ? true : false;
    }
    
    protected function collectionOrArrayContainsRecord(
        CollectionInterface|array $records,
        RecordInterface $record,
        array $fieldNames
    ): bool {

        return null !== $this->getMatchingRecordInCollectionOrArray(
            $records, $record, $fieldNames
        );
    }
    
    protected function getMatchingRecordInCollectionOrArray(
        CollectionInterface|array $records,
        RecordInterface $record,
        array $fieldNames
    ): ?RecordInterface {

        foreach($records as $currentRecord) {
            
            if($this->recordsMatch($fieldNames, $currentRecord, $record)) {
                
                return $currentRecord;
            }
        }

        return null; // if we got here, means we didn't find a matching record
    }

    public function testThatFetchOneByPkeyWithNestedEagerLoadingWorksAsExpected() {
        
        // Since fetchOneByPkey calls fetchOneRecord under the hood, this test
        // tests for both methods
        $testData = $this->setUpNestedEagerFetchData();
        $authorsModel = $this->getLeanOrmModel(AuthorsModel::class);

        $relationsToLoad = [
            'posts' => [
                'comments',
                'posts_tags' => ['tag'], 
                'summary',
                'tags', // has many through posts_tags
            ]
        ];
        
        $authorsModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($authorsModel->getDbConnector()->getConnectionName());
        $authorsModel->enableQueryLogging();
        /** @var \LeanOrm\TestObjects\AuthorRecord $authorWithRelatedData */
        $authorWithRelatedData = $authorsModel->fetchOneByPkey(
            $testData['author1']->getPrimaryVal(), $relationsToLoad
        );
        $authorsModel->disableQueryLogging();

        /** @var \LeanOrm\TestObjects\AuthorRecord $authorWithoutRelatedData */
        $authorWithoutRelatedData = $authorsModel->fetchOneByPkey(
            $testData['author2']->getPrimaryVal(), $relationsToLoad
        );

        $this->doEagerLoadTestForTwoAuthors(
            $authorWithRelatedData, $authorWithoutRelatedData, $testData
        );
        
        $authorsModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($authorsModel->getDbConnector()->getConnectionName());
        $this->tearDownNestedEagerFetchData(
            $testData['author1'], $testData['author2']
        );
    }

    public function testThatFetchStartingWithASummaryRecordWithNestedEagerLoadingWorksAsExpected() {

        $testData = $this->setUpNestedEagerFetchData();
        $summariesModel = $this->getLeanOrmModel(SummariesModel::class);

        $relationsToLoad = [
            'post' => [
                'comments',
                'posts_tags' => ['tag'], 
                'author',
                'tags', // has many through posts_tags
            ]
        ];
        
        $summariesModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($summariesModel->getDbConnector()->getConnectionName());
        $summariesModel->enableQueryLogging();
        /** @var \LeanOrm\TestObjects\SummaryRecord $summaryWithRelatedData */
        $summaryWithRelatedData = $summariesModel->fetch(
            [$testData['author1post1summary']->getPrimaryVal()], 
            null, $relationsToLoad, true, true
        );
        $summariesModel->disableQueryLogging();
        
        // We are just checking that the right number of queries were issued based
        // on the relationship names specified in $relationsToLoad
        // 
        // 1. in the SummariesModel class
        //      1. To select * from summaries where ... to get the summary record
        //      2. To select * from posts where post belongs to summary record above
        // 
        // 2. in the PostsModel class
        //      1. To select comments belonging to the post
        //      2. To select posts_tags associated with the post
        //      3. To select author associated with the post
        //      4. To select tags associated with the post via posts_tags
        // 
        // 3. in the PostsTagsModel class
        //      1. To select tag belonging to the each post_tag
        self::assertCount(3, LeanOrmModel::getQueryLogForAllInstances($summariesModel->getDbConnector()->getConnectionName()));

        foreach(LeanOrmModel::getQueryLogForAllInstances($summariesModel->getDbConnector()->getConnectionName()) as $logKey => $logEntries) {
            
            if(\str_ends_with($logKey, SummariesModel::class)) {
                
                // There should be two queries in $logEntries
                // One for the query to select from the authors table
                // One to eager load posts for the selected authors
                self::assertCount(2, $logEntries);
                self::assertStringContainsString(
                    'SELECT\n     summaries.* \nFROM\n',
                    \json_encode($logEntries)
                );
                self::assertStringContainsString(
                    'SELECT\n    posts.*\nFROM\n',
                    \json_encode($logEntries)
                );
            }
            
            if(\str_ends_with($logKey, PostsModel::class)) {
                
                // There should be four queries in $logEntries for fetching 
                // related data for posts
                // One for the query to select from the comments table
                // One for the query to select from the posts_tags table
                // One for the query to select from the summaries table
                // One for the query to select from the tags table inner join posts_tags table
                self::assertCount(4, $logEntries);
                self::assertStringContainsString(
                    'SELECT\n    comments.*\nFROM\n',
                    \json_encode($logEntries)
                );
                self::assertStringContainsString(
                    'SELECT\n    posts_tags.*\nFROM\n',
                    \json_encode($logEntries)
                );
                self::assertStringContainsString(
                    'SELECT\n    authors.*\nFROM\n',
                    \json_encode($logEntries)
                );
                
                self::assertStringContainsString(
                    ',\n     tags.* \nFROM\n',
                    \json_encode($logEntries)
                );
                self::assertStringContainsString(
                    '\nINNER JOIN ',
                    \json_encode($logEntries)
                );
            }
            
            if(\str_ends_with($logKey, PostsTagsModel::class)) {
                
                // There should be one query in $logEntries
                // One to eager load tags for the postTags
                self::assertCount(1, $logEntries);
                self::assertStringContainsString(
                    'SELECT\n    tags.*\nFROM\n',
                    \json_encode($logEntries)
                );
            }
        }
        
        $summariesModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($summariesModel->getDbConnector()->getConnectionName());
        $this->tearDownNestedEagerFetchData(
            $testData['author1'], $testData['author2']
        );
    }

    public function testThatFetchRowsIntoArrayDoesNotDoNestedEagerLoadingWhenAnArrayOfNestedRelationsIsSupplied() {

        $testData = $this->setUpNestedEagerFetchData();
        $postsModel = $this->getLeanOrmModel(PostsModel::class);
        $summariesModel = $this->getLeanOrmModel(SummariesModel::class);

        $relationsToLoad = [
            'post' => [
                'comments',
                'posts_tags' => ['tag'], 
                'author',
                'tags', // has many through posts_tags
            ],
            'nonexistent_relation'=>[],
        ];
        
        $summariesModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($summariesModel->getDbConnector()->getConnectionName());
        $summariesModel->enableQueryLogging();
        $summariesWithRelatedData = $summariesModel->fetchRowsIntoArray(
            null, $relationsToLoad
        );
        $summariesModel->disableQueryLogging();

        // check the data returned
        foreach ($summariesWithRelatedData as $summary) {
            
            self::assertArrayHasKey('post', $summary);
            self::assertArrayHasAllKeys($summary, $summariesModel->getTableColNames());
            self::assertArrayHasAllKeys($summary['post'], $postsModel->getTableColNames());
            
            self::assertArrayNotHasKey('nonexistent_relation', $summary);
            self::assertArrayNotHasKey('comments', $summary['post']);
            self::assertArrayNotHasKey('posts_tags', $summary['post']);
            self::assertArrayNotHasKey('author', $summary['post']);
            self::assertArrayNotHasKey('tags', $summary['post']);
        }

        // We are just checking that the right number of queries were issued based
        // on the relationship names specified in $relationsToLoad
        // 
        // 1. in the SummariesModel class
        //      1. To select * from summaries where ... to get the summary record
        //      2. To select * from posts where post belongs to summary record above
        //      
        // Data for the nested relationships in the sub-array whose key is `post`
        // should not be loaded.
        self::assertCount(1, LeanOrmModel::getQueryLogForAllInstances($summariesModel->getDbConnector()->getConnectionName()));

        foreach(LeanOrmModel::getQueryLogForAllInstances($summariesModel->getDbConnector()->getConnectionName()) as $logKey => $logEntries) {
            
            if(\str_ends_with($logKey, SummariesModel::class)) {
                
                // There should be two queries in $logEntries
                // One for the query to select from the authors table
                // One to eager load posts for the selected authors
                self::assertCount(2, $logEntries);
                self::assertStringContainsString(
                    'SELECT\n     summaries.* \nFROM\n',
                    \json_encode($logEntries)
                );
                self::assertStringContainsString(
                    'SELECT\n    posts.*\nFROM\n',
                    \json_encode($logEntries)
                );
            }
        }
        
        $summariesModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($summariesModel->getDbConnector()->getConnectionName());
        $this->tearDownNestedEagerFetchData(
            $testData['author1'], $testData['author2']
        );
    }

    public function testThatFetchRowsIntoArrayKeyedOnPkValDoesNotDoNestedEagerLoadingWhenAnArrayOfNestedRelationsIsSupplied() {

        $testData = $this->setUpNestedEagerFetchData();
        $postsModel = $this->getLeanOrmModel(PostsModel::class);
        $summariesModel = $this->getLeanOrmModel(SummariesModel::class);

        $relationsToLoad = [
            'post' => [
                'comments',
                'posts_tags' => ['tag'], 
                'author',
                'tags', // has many through posts_tags
            ],
            'nonexistent_relation'=>[],
        ];
        
        $summariesModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($summariesModel->getDbConnector()->getConnectionName());
        $summariesModel->enableQueryLogging();
        $summariesWithRelatedData = $summariesModel->fetchRowsIntoArrayKeyedOnPkVal(
            null, $relationsToLoad
        );
        $summariesModel->disableQueryLogging();

        // check the data returned
        foreach ($summariesWithRelatedData as $summary) {
            
            self::assertArrayHasKey('post', $summary);
            self::assertArrayHasAllKeys($summary, $summariesModel->getTableColNames());
            self::assertArrayHasAllKeys($summary['post'], $postsModel->getTableColNames());
            
            self::assertArrayNotHasKey('nonexistent_relation', $summary);
            self::assertArrayNotHasKey('comments', $summary['post']);
            self::assertArrayNotHasKey('posts_tags', $summary['post']);
            self::assertArrayNotHasKey('author', $summary['post']);
            self::assertArrayNotHasKey('tags', $summary['post']);
        }

        // We are just checking that the right number of queries were issued based
        // on the relationship names specified in $relationsToLoad
        // 
        // 1. in the SummariesModel class
        //      1. To select * from summaries where ... to get the summary record
        //      2. To select * from posts where post belongs to summary record above
        //      
        // Data for the nested relationships in the sub-array whose key is `post`
        // should not be loaded.
        self::assertCount(1, LeanOrmModel::getQueryLogForAllInstances($summariesModel->getDbConnector()->getConnectionName()));

        foreach(LeanOrmModel::getQueryLogForAllInstances($summariesModel->getDbConnector()->getConnectionName()) as $logKey => $logEntries) {
            
            if(\str_ends_with($logKey, SummariesModel::class)) {
                
                // There should be two queries in $logEntries
                // One for the query to select from the authors table
                // One to eager load posts for the selected authors
                self::assertCount(2, $logEntries);
                self::assertStringContainsString(
                    'SELECT\n     summaries.* \nFROM\n',
                    \json_encode($logEntries)
                );
                self::assertStringContainsString(
                    'SELECT\n    posts.*\nFROM\n',
                    \json_encode($logEntries)
                );
            }
        }
        
        $summariesModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($summariesModel->getDbConnector()->getConnectionName());
        $this->tearDownNestedEagerFetchData(
            $testData['author1'], $testData['author2']
        );
    }

    public function testThatFetchRecordsIntoArrayWithNestedEagerLoadingWorksAsExpected() {

        $testData = $this->setUpNestedEagerFetchData();
        $authorsModel = $this->getLeanOrmModel(AuthorsModel::class);

        $relationsToLoad = [
            'posts' => [
                'comments',
                'posts_tags' => ['tag'], 
                'summary',
                'tags', // has many through posts_tags
            ]
        ];
        
        $authorsModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($authorsModel->getDbConnector()->getConnectionName());
        $authorsModel->enableQueryLogging();
        $authors = $authorsModel->fetchRecordsIntoArray(
            null, $relationsToLoad
        );
        $authorsModel->disableQueryLogging();

        $this->doEagerLoadTestForTwoAuthors(
            $this->getMatchingRecordInCollectionOrArray(
                records: $authors,
                record: $testData['author1'],
                fieldNames:  [$authorsModel->getPrimaryCol()]
            ),
            $this->getMatchingRecordInCollectionOrArray(
                records: $authors,
                record: $testData['author2'],
                fieldNames:  [$authorsModel->getPrimaryCol()]
            ),
            $testData,
            false
        );
        
        $authorsModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($authorsModel->getDbConnector()->getConnectionName());
        $this->tearDownNestedEagerFetchData(
            $testData['author1'], $testData['author2']
        );
    }

    public function testThatFetchRecordsIntoArrayKeyedOnPkValWithNestedEagerLoadingWorksAsExpected() {

        $testData = $this->setUpNestedEagerFetchData();
        $authorsModel = $this->getLeanOrmModel(AuthorsModel::class);

        $relationsToLoad = [
            'posts' => [
                'comments',
                'posts_tags' => ['tag'], 
                'summary',
                'tags', // has many through posts_tags
            ]
        ];
        
        $authorsModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($authorsModel->getDbConnector()->getConnectionName());
        $authorsModel->enableQueryLogging();
        $authors = $authorsModel->fetchRecordsIntoArrayKeyedOnPkVal(
            null, $relationsToLoad
        );
        $authorsModel->disableQueryLogging();

        $this->doEagerLoadTestForTwoAuthors(
            $this->getMatchingRecordInCollectionOrArray(
                records: $authors,
                record: $testData['author1'],
                fieldNames:  [$authorsModel->getPrimaryCol()]
            ),
            $this->getMatchingRecordInCollectionOrArray(
                records: $authors,
                record: $testData['author2'],
                fieldNames:  [$authorsModel->getPrimaryCol()]
            ),
            $testData,
            false
        );
        
        $authorsModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($authorsModel->getDbConnector()->getConnectionName());
        $this->tearDownNestedEagerFetchData(
            $testData['author1'], $testData['author2']
        );
    }

    public function testThatFetchRecordsIntoCollectionWithNestedEagerLoadingWorksAsExpected() {

        $testData = $this->setUpNestedEagerFetchData();
        $authorsModel = $this->getLeanOrmModel(AuthorsModel::class);

        $relationsToLoad = [
            'posts' => [
                'comments',
                'posts_tags' => ['tag'], 
                'summary',
                'tags', // has many through posts_tags
            ]
        ];
        
        $authorsModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($authorsModel->getDbConnector()->getConnectionName());
        $authorsModel->enableQueryLogging();
        $authors = $authorsModel->fetchRecordsIntoCollection(
            null, $relationsToLoad
        );
        $authorsModel->disableQueryLogging();

        $this->doEagerLoadTestForTwoAuthors(
            $this->getMatchingRecordInCollectionOrArray(
                records: $authors,
                record: $testData['author1'],
                fieldNames:  [$authorsModel->getPrimaryCol()]
            ),
            $this->getMatchingRecordInCollectionOrArray(
                records: $authors,
                record: $testData['author2'],
                fieldNames:  [$authorsModel->getPrimaryCol()]
            ),
            $testData,
            true
        );
        
        $authorsModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($authorsModel->getDbConnector()->getConnectionName());
        $this->tearDownNestedEagerFetchData(
            $testData['author1'], $testData['author2']
        );
    }

    public function testThatFetchRecordsIntoCollectionKeyedOnPkValWithNestedEagerLoadingWorksAsExpected() {

        $testData = $this->setUpNestedEagerFetchData();
        $authorsModel = $this->getLeanOrmModel(AuthorsModel::class);

        $relationsToLoad = [
            'posts' => [
                'comments',
                'posts_tags' => ['tag'], 
                'summary',
                'tags', // has many through posts_tags
            ]
        ];
        
        $authorsModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($authorsModel->getDbConnector()->getConnectionName());
        $authorsModel->enableQueryLogging();
        $authors = $authorsModel->fetchRecordsIntoCollectionKeyedOnPkVal(
            null, $relationsToLoad
        );
        $authorsModel->disableQueryLogging();

        $this->doEagerLoadTestForTwoAuthors(
            $this->getMatchingRecordInCollectionOrArray(
                records: $authors,
                record: $testData['author1'],
                fieldNames:  [$authorsModel->getPrimaryCol()]
            ),
            $this->getMatchingRecordInCollectionOrArray(
                records: $authors,
                record: $testData['author2'],
                fieldNames:  [$authorsModel->getPrimaryCol()]
            ),
            $testData,
            true
        );
        
        $authorsModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($authorsModel->getDbConnector()->getConnectionName());
        $this->tearDownNestedEagerFetchData(
            $testData['author1'], $testData['author2']
        );
    }
    
    public function testThatCollection_EagerLoadRelatedDataWorksAsExpected() {

        $testData = $this->setUpNestedEagerFetchData();
        $authorsModel = $this->getLeanOrmModel(AuthorsModel::class);

        $relationsToLoad = [
            'posts' => [
                'comments',
                'posts_tags' => ['tag'], 
                'summary',
                'tags', // has many through posts_tags
            ]
        ];
        $authorsModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($authorsModel->getDbConnector()->getConnectionName());
        
        $authorsModel->enableQueryLogging();
        /** @var \LeanOrmModel\Collection $authors */
        $authors = $authorsModel->fetch(
            [
                $testData['author1']->getPrimaryVal(),
                $testData['author2']->getPrimaryVal(),
            ], 
            null, [], true, true
        );
        $authorsAsArray = $authors->getDataAndRelatedData();

        self::assertCount(2, $authors);
        self::assertArrayHasKey('posts', $authorsAsArray[0]);
        self::assertArrayHasKey('posts', $authorsAsArray[1]);
        self::assertEquals(LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG, $authorsAsArray[0]['posts']);
        self::assertEquals(LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG, $authorsAsArray[1]['posts']);
        
        $authors->eagerLoadRelatedData($relationsToLoad);
        $authorsModel->disableQueryLogging();
        
        $authorsAsArray2 = $authors->getDataAndRelatedData();
        self::assertNotEquals(LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG, $authorsAsArray2[0]['posts']);
        self::assertNotEquals(LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG, $authorsAsArray2[1]['posts']);
        
        $this->doEagerLoadTestForTwoAuthors(
            $this->getMatchingRecordInCollectionOrArray(
                records: $authors,
                record: $testData['author1'],
                fieldNames:  [$authorsModel->getPrimaryCol()]
            ),
            $this->getMatchingRecordInCollectionOrArray(
                records: $authors,
                record: $testData['author2'],
                fieldNames:  [$authorsModel->getPrimaryCol()]
            ),
            $testData,
            true
        );
        
        $authorsModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($authorsModel->getDbConnector()->getConnectionName());
        $this->tearDownNestedEagerFetchData(
            $testData['author1'], $testData['author2']
        );
    }
    
    public function testThatCollectionAndRecord_GetDataAndRelatedDataWorksAsExpected() {

        $testData = $this->setUpNestedEagerFetchData();
        $authorsModel = $this->getLeanOrmModel(AuthorsModel::class);
        $postsModel = $this->getLeanOrmModel(PostsModel::class);
        $commentsModel = $this->getLeanOrmModel(CommentsModel::class);
        $postsTagsModel = $this->getLeanOrmModel(PostsTagsModel::class);
        $summariesModel = $this->getLeanOrmModel(SummariesModel::class);
        $tagsModel = $this->getLeanOrmModel(TagsModel::class);

        $relationsToLoad = [
            'posts' => [
                'comments',
                'posts_tags' /*=> ['tag']*/, 
                'summary',
                'tags', // has many through posts_tags
            ]
        ];

        /** @var \LeanOrmModel\Collection $authors */
        $authors = $authorsModel->fetch(
            [
                $testData['author1']->getPrimaryVal(),
                $testData['author2']->getPrimaryVal(),
            ], 
            null, [], true, true
        );
        $authorsAsArray = $authors->getDataAndRelatedData();

        self::assertCount(2, $authors);
        
        // compare the author table data
        foreach ($authorsModel->getTableColNames() as $colName) {
         
            self::assertEquals($testData['author1'][$colName], $authorsAsArray[0][$colName]);
            self::assertEquals($testData['author2'][$colName], $authorsAsArray[1][$colName]);
        }
        
        self::assertArrayHasKey('posts', $authorsAsArray[0]);
        self::assertArrayHasKey('posts', $authorsAsArray[1]);
        self::assertEquals(LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG, $authorsAsArray[0]['posts']);
        self::assertEquals(LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG, $authorsAsArray[1]['posts']);
        self::assertEquals(LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG, $authorsAsArray[0]['one_post']);
        self::assertEquals(LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG, $authorsAsArray[1]['one_post']);
        
        // Now eager-load data into the first author record we got back in 
        // $testData and the collection of authors we fetched above
        $authors->eagerLoadRelatedData($relationsToLoad);
        $author1 = $testData['author1'];
        $authorsModel->recursivelyStitchRelatedData($authorsModel, $relationsToLoad, $author1, true);
        
        ////////////////////////////////////////////////////////////////////////
        // Test that the related data got loaded
        $authorsAsArray2 = $authors->getDataAndRelatedData();
        
        // compare the author table data
        foreach ($authorsModel->getTableColNames() as $colName) {
         
            self::assertEquals($author1[$colName], $authorsAsArray[0][$colName]);
            self::assertEquals($testData['author2'][$colName], $authorsAsArray[1][$colName]);
        }
        
        // one_post was not specified in the $relationsToLoad array
        self::assertEquals(LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG, $authorsAsArray2[1]['one_post']);
        
        self::assertArrayHasKey('posts', $authorsAsArray2[0]);
        self::assertArrayHasKey('posts', $authorsAsArray2[1]);
        
        // author that has related posts data should have that data loaded now
        self::assertNotEquals(LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG, $authorsAsArray2[0]['posts']);
        
        // author that has no related posts data should have it set to an empty array
        self::assertEquals([], $authorsAsArray2[1]['posts']);
        
        // loop through the posts for the author with related posts to inspect
        // the related post data and other nested related data
        foreach($authorsAsArray2[0]['posts'] as $post) {
            
            $postToCompare = $author1->posts->findRecord([
                $postsModel->getPrimaryCol() => $post[$postsModel->getPrimaryCol()]
            ]);
            self::assertInstanceOf(PostRecord::class, $postToCompare);
            
            // compare posts table data
            foreach ($postsModel->getTableColNames() as $colName) {
                
                self::assertEquals($postToCompare[$colName], $post[$colName]);
            }
            
            // compare comments table data for each comment associated with the
            // current post
            foreach($post['comments'] as $comment) {
                
                $commentToCompare = $postToCompare->comments->findRecord([
                    $commentsModel->getPrimaryCol() =>
                        $comment[$commentsModel->getPrimaryCol()]
                ]);
                self::assertInstanceOf(CommentRecord::class, $commentToCompare);
                
                foreach ($commentsModel->getTableColNames() as $colName) {

                    self::assertEquals($commentToCompare[$colName], $comment[$colName]);
                }
                
                // the nested comment should not have the post data loaded
                // we didn't specify for it to be loaded in our fetch
                self::assertEquals(
                    LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG,
                    $comment['post']
                );
            }
            
            // compare summaries table data for the summary associated with the
            // current post
            foreach($summariesModel->getTableColNames() as $colName) {
                
                ($post['summary'] !== []) 
                    && self::assertEquals($postToCompare['summary'][$colName], $post['summary'][$colName]);
            }

            // These nested relationships were not specified to be loaded
            ($post['summary'] !== []) 
                && self::assertEquals(
                    LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG,
                    $post['summary']['post']
                );
            self::assertEquals(
                LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG,
                $post['summary_with_callback']
            );
            self::assertEquals(
                LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG,
                $post['comments_with_callback']
            );
            
            // compare posts_tags table data for each post_tag associated with the
            // current post
            foreach($post['posts_tags'] as $postTag) {
                
                $postTagToCompare = $postToCompare->posts_tags->findRecord([
                    $postsTagsModel->getPrimaryCol() =>
                        $postTag[$postsTagsModel->getPrimaryCol()]
                ]);
                self::assertInstanceOf(PostTagRecord::class, $postTagToCompare);
                
                foreach ($postsTagsModel->getTableColNames() as $colName) {

                    self::assertEquals($postTagToCompare[$colName], $postTag[$colName]);
                }
                
                // nested data below wasn't specified to be eager loaded
                self::assertEquals(
                    LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG,
                    $postTag['post']
                );
                self::assertEquals(
                    LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG,
                    $postTag['tag']
                );
            }
            
            // compare tags table data for each tag associated with the
            // current post via the posts_tags join table
            foreach($post['tags'] as $tag) {
                
                $tagToCompare = $postToCompare->tags->findRecord([
                    $tagsModel->getPrimaryCol() =>
                        $tag[$tagsModel->getPrimaryCol()]
                ]);
                self::assertInstanceOf(TagRecord::class, $tagToCompare);
                
                foreach ($tagsModel->getTableColNames() as $colName) {

                    self::assertEquals($tagToCompare[$colName], $tag[$colName]);
                }
                
                // nested data below wasn't specified to be fetched
                self::assertEquals(
                    LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG,
                    $tag['posts_tags']
                );
                self::assertEquals(
                    LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG,
                    $tag['posts']
                );
            }
            
            // nested data below wasn't specified to be loaded
            self::assertEquals(
                LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG,
                $post['author']
            );
            
            self::assertEquals(
                LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG,
                $post['author_with_callback']
            );
            
            self::assertEquals(
                LeanOrmRecord::RELATED_DATA_NOT_LOADED_OR_EXISTENT_MSG,
                $post['tags_with_callback']
            );
            
        } // foreach($authorsAsArray2[0]['posts'] as $post)

        $authorsModel->clearQueryLog();
        LeanOrmModel::clearQueryLogForAllInstances($authorsModel->getDbConnector()->getConnectionName());
        $this->tearDownNestedEagerFetchData(
            $testData['author1'], $testData['author2']
        );
    }
    
    protected function doEagerLoadTestForTwoAuthors(
        AuthorRecord $authorWithRelatedData, 
        AuthorRecord $authorWithoutRelatedData,
        array $testData,
        bool $doCollectionAssertions = true
    ): void {
        
        $postsModel = $this->getLeanOrmModel(PostsModel::class);
        $commentsModel = $this->getLeanOrmModel(CommentsModel::class);
        $postsTagsModel = $this->getLeanOrmModel(PostsTagsModel::class);
        $summariesModel = $this->getLeanOrmModel(SummariesModel::class);
        $tagsModel = $this->getLeanOrmModel(TagsModel::class);

        // verify eager loaded posts
        (!$doCollectionAssertions) && self::assertIsArray($authorWithRelatedData->posts);
        $doCollectionAssertions && self::assertInstanceOf(PostsCollection::class, $authorWithRelatedData->posts);
        self::assertCount(2, $authorWithRelatedData->posts);
        self::assertTrue(
            $this->collectionOrArrayContainsRecord(
                records: $authorWithRelatedData->posts, 
                record: $testData['author1post1'], 
                fieldNames: $postsModel->getTableColNames()
            )
        );
        self::assertTrue(
            $this->collectionOrArrayContainsRecord(
                records: $authorWithRelatedData->posts, 
                record: $testData['author1post2'], 
                fieldNames: $postsModel->getTableColNames()
            )
        );
        
        // verify eager loaded posts->comments & posts->posts_tags
        foreach($authorWithRelatedData->posts as $postRecord) {
            
            if(
                $this->recordsMatch(
                    $postsModel->getTableColNames(),
                    $testData['author1post1'], 
                    $postRecord
                )
            ) {
                // comments
                (!$doCollectionAssertions) && self::assertIsArray($postRecord->comments);
                $doCollectionAssertions && self::assertInstanceOf(CommentsCollection::class, $postRecord->comments);
                self::assertCount(2, $postRecord->comments);
                self::assertTrue(
                    $this->collectionOrArrayContainsRecord(
                        records: $postRecord->comments, 
                        record: $testData['author1post1comment1'], 
                        fieldNames: $commentsModel->getTableColNames()
                    )
                );
                self::assertTrue(
                    $this->collectionOrArrayContainsRecord(
                        records: $postRecord->comments, 
                        record: $testData['author1post1comment2'], 
                        fieldNames: $commentsModel->getTableColNames()
                    )
                );
                
                // post_tags
                (!$doCollectionAssertions) && self::assertIsArray($postRecord->posts_tags);
                $doCollectionAssertions && self::assertInstanceOf(PostsTagsCollection::class, $postRecord->posts_tags);
                self::assertCount(2, $postRecord->posts_tags);
                self::assertTrue(
                    $this->collectionOrArrayContainsRecord(
                        records: $postRecord->posts_tags, 
                        record: $testData['author1post1tag1'], 
                        fieldNames: $postsTagsModel->getTableColNames()
                    )
                );
                self::assertTrue(
                    $this->collectionOrArrayContainsRecord(
                        records: $postRecord->posts_tags, 
                        record: $testData['author1post1tag2'], 
                        fieldNames: $postsTagsModel->getTableColNames()
                    )
                );
                
                // tags
                (!$doCollectionAssertions) && self::assertIsArray($postRecord->tags);
                $doCollectionAssertions && self::assertInstanceOf(TagsCollection::class, $postRecord->tags);
                self::assertCount(2, $postRecord->tags);
                self::assertTrue(
                    $this->collectionOrArrayContainsRecord(
                        records: $postRecord->tags, 
                        record: $testData['tag1'], 
                        fieldNames: $tagsModel->getTableColNames()
                    )
                );
                self::assertTrue(
                    $this->collectionOrArrayContainsRecord(
                        records: $postRecord->tags, 
                        record: $testData['tag2'], 
                        fieldNames: $tagsModel->getTableColNames()
                    )
                );
                
                // summary
                self::assertInstanceOf(SummaryRecord::class, $postRecord->summary);
                self::assertTrue(
                    $this->recordsMatch(
                        record2: $postRecord->summary, 
                        record1: $testData['author1post1summary'], 
                        fieldNames: $summariesModel->getTableColNames()
                    )
                );
                
            } else {
                // the second post has no related data
                self::assertCount(0, $postRecord->comments);
                self::assertCount(0, $postRecord->posts_tags);
                self::assertCount(0, $postRecord->summary);
                self::assertCount(0, $postRecord->tags);
            }
        }
        
        // Finally check that the number of queries in the query log is
        // 1 + number of eager relation names in the array of relations
        foreach(LeanOrmModel::getQueryLogForAllInstances($summariesModel->getDbConnector()->getConnectionName()) as $logKey => $logEntries) {
            
            if(\str_ends_with($logKey, AuthorsModel::class)) {
                
                // There should be two queries in $logEntries
                // One for the query to select from the authors table
                // One to eager load posts for the selected authors
                self::assertCount(2, $logEntries);
                
                self::assertStringContainsString(
                    'SELECT\n     authors.* \nFROM\n',
                    \json_encode($logEntries)
                );
                self::assertStringContainsString(
                    'SELECT\n    posts.*\nFROM\n',
                    \json_encode($logEntries)
                );
            }
            
            if(\str_ends_with($logKey, PostsModel::class)) {
                
                // There should be four queries in $logEntries for fetching 
                // related data for posts
                // One for the query to select from the comments table
                // One for the query to select from the posts_tags table
                // One for the query to select from the summaries table
                // One for the query to select from the tags table inner join posts_tags table
                self::assertCount(4, $logEntries);
                self::assertStringContainsString(
                    'SELECT\n    comments.*\nFROM\n',
                    \json_encode($logEntries)
                );
                self::assertStringContainsString(
                    'SELECT\n    posts_tags.*\nFROM\n',
                    \json_encode($logEntries)
                );
                self::assertStringContainsString(
                    'SELECT\n    summaries.*\nFROM\n',
                    \json_encode($logEntries)
                );
                
                self::assertStringContainsString(
                    ',\n     tags.* \nFROM\n',
                    \json_encode($logEntries)
                );
                self::assertStringContainsString(
                    '\nINNER JOIN ',
                    \json_encode($logEntries)
                );
            }
            
            if(\str_ends_with($logKey, PostsTagsModel::class)) {
                
                // There should be one query in $logEntries
                // One to eager load tags for the postTags
                self::assertCount(1, $logEntries);
                self::assertStringContainsString(
                    'SELECT\n    tags.*\nFROM\n',
                    \json_encode($logEntries)
                );
            }
        }

        // Verify no eager loaded posts and since no eager loaded posts
        // no other nested related data related to posts
        self::assertCount(0, $authorWithoutRelatedData->posts);
        self::assertCount(0, $authorWithoutRelatedData->one_post);
    }
}
