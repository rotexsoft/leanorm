<?php
namespace LeanOrm\TestObjects;

/**
 * Description of PostsModel
 *
 * @author rotimi
 */
class PostsModel extends \LeanOrm\Model{
    
    protected string $primary_col = 'post_id';
    
    protected string $table_name = 'posts';
    
    public function __construct(
        string $dsn = '', 
        string $username = '', 
        string $passwd = '', 
        array $pdo_driver_opts = [], 
        string $primary_col_name = '', 
        string $table_name = ''
    ) {        
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
        $this->belongsTo(
                relation_name: 'author', 
                relationship_col_in_my_table: 'author_id', 
                foreign_table_name: 'authors', 
                foreign_key_col_in_foreign_table: 'author_id', 
                primary_key_col_in_foreign_table: 'author_id',
                foreign_models_class_name: AuthorsModel::class,
                foreign_models_record_class_name: AuthorRecord::class,
                foreign_models_collection_class_name: AuthorsCollection::class
            )
            ->belongsTo(
                relation_name: 'author_with_callback', 
                relationship_col_in_my_table: 'author_id', 
                foreign_table_name: 'authors', 
                foreign_key_col_in_foreign_table: 'author_id', 
                primary_key_col_in_foreign_table: 'author_id',
                foreign_models_class_name: AuthorsModel::class,
                foreign_models_record_class_name: \RecordForTestingPublicAndProtectedMethods::class,
                foreign_models_collection_class_name: \CollectionForTestingPublicAndProtectedMethods::class,
                sql_query_modifier: function(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select {
                    
                    $selectObj->orderBy(['author_id']); // just for testing that the query object gets manipulated

                    return $selectObj;
                }
            )
            ->hasOne(
                relation_name: 'summary',
                relationship_col_in_my_table: 'post_id', 
                foreign_table_name: 'summaries', 
                foreign_key_col_in_foreign_table: 'post_id', 
                primary_key_col_in_foreign_table: 'summary_id',
                foreign_models_class_name: SummariesModel::class,
                foreign_models_record_class_name: SummaryRecord::class,
                foreign_models_collection_class_name: SummariesCollection::class
            )
            ->hasOne(
                relation_name: 'summary_with_callback', 
                relationship_col_in_my_table: 'post_id', 
                foreign_table_name: 'summaries', 
                foreign_key_col_in_foreign_table: 'post_id', 
                primary_key_col_in_foreign_table: 'summary_id',
                foreign_models_class_name: SummariesModel::class,
                foreign_models_record_class_name: \RecordForTestingPublicAndProtectedMethods::class,
                foreign_models_collection_class_name: \CollectionForTestingPublicAndProtectedMethods::class,
                sql_query_modifier: function(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select {
                    
                    $selectObj->orderBy(['summary_id']); // just for testing that the query object gets manipulated

                    return $selectObj;
                }
            )
            ->hasMany(
                relation_name: 'comments', 
                relationship_col_in_my_table: 'post_id', 
                foreign_table_name: 'comments', 
                foreign_key_col_in_foreign_table: 'post_id', 
                primary_key_col_in_foreign_table: 'comment_id',
                foreign_models_class_name: CommentsModel::class,
                foreign_models_record_class_name: CommentRecord::class,
                foreign_models_collection_class_name: CommentsCollection::class
            )
            ->hasMany(
                relation_name: 'comments_with_callback', 
                relationship_col_in_my_table: 'post_id', 
                foreign_table_name: 'comments', 
                foreign_key_col_in_foreign_table: 'post_id', 
                primary_key_col_in_foreign_table: 'comment_id',
                foreign_models_class_name: CommentsModel::class,
                foreign_models_record_class_name: \RecordForTestingPublicAndProtectedMethods::class,
                foreign_models_collection_class_name: \CollectionForTestingPublicAndProtectedMethods::class,
                sql_query_modifier: function(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select {
                    
                    $selectObj->orderBy(['comment_id']); // just for testing that the query object gets manipulated

                    return $selectObj;
                }
            )
            ->hasMany(
                relation_name: 'posts_tags',
                relationship_col_in_my_table: 'post_id',
                foreign_table_name: 'posts_tags',
                foreign_key_col_in_foreign_table: 'post_id',
                primary_key_col_in_foreign_table: 'posts_tags_id',
                foreign_models_class_name: PostsTagsModel::class,
                foreign_models_record_class_name: PostTagRecord::class,
                foreign_models_collection_class_name: PostsTagsCollection::class
            )
            ->hasManyThrough(
                relation_name: 'tags',
                col_in_my_table_linked_to_join_table: 'post_id',
                join_table: 'posts_tags',
                col_in_join_table_linked_to_my_table: 'post_id',
                col_in_join_table_linked_to_foreign_table: 'tag_id',
                foreign_table_name: 'tags',
                col_in_foreign_table_linked_to_join_table: 'tag_id',
                primary_key_col_in_foreign_table: 'tag_id',
                foreign_models_class_name: TagsModel::class,
                foreign_models_record_class_name: TagRecord::class,
                foreign_models_collection_class_name: TagsCollection::class
            )
            ->hasManyThrough(
                relation_name: 'tags_with_callback',
                col_in_my_table_linked_to_join_table: 'post_id',
                join_table: 'posts_tags',
                col_in_join_table_linked_to_my_table: 'post_id',
                col_in_join_table_linked_to_foreign_table: 'tag_id',
                foreign_table_name: 'tags',
                col_in_foreign_table_linked_to_join_table: 'tag_id',
                primary_key_col_in_foreign_table: 'tag_id',
                foreign_models_class_name: TagsModel::class,
                foreign_models_record_class_name: \RecordForTestingPublicAndProtectedMethods::class,
                foreign_models_collection_class_name: \CollectionForTestingPublicAndProtectedMethods::class,
                sql_query_modifier: function(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select {

                    $selectObj->orderBy(['tags.tag_id']); // just for testing that the query object gets manipulated

                    return $selectObj;
                }
            )
            ->setCollectionClassName(PostsCollection::class)
            ->setRecordClassName(PostRecord::class);
    }
}
