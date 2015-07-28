<?php
/**
 * Description of PostsModel
 *
 * @author aadegbam
 */
class PostsModel extends \LeanOrm\Model
{
    protected $_collection_class_name = 'PostsCollection';
    protected $_record_class_name = 'PostRecord';

    protected $_created_timestamp_column_name = 'date_created';
    protected $_updated_timestamp_column_name = 'm_timestamp'; 

    protected $_primary_col = 'post_id';
    protected $_table_name = 'posts';

    protected $_relations = [
        'author' => [
                    'relation_type' => \LeanOrm\Model::RELATION_TYPE_BELONGS_TO,

                    'foreign_key_col_in_my_table' => 'author_id',

                    'foreign_table' => 'authors',
                    'foreign_key_col_in_foreign_table' => 'author_id',

                    'primary_key_col_in_foreign_table' => 'author_id',
                    'foreign_models_class_name' => 'AuthorsModel',
                    'foreign_models_collection_class_name' => 'AuthorsCollection',
                    'foreign_models_record_class_name' => 'AuthorRecord',
                ],
        'comments' => [
                    'relation_type' => \LeanOrm\Model::RELATION_TYPE_HAS_MANY,

                    'foreign_key_col_in_my_table' => 'post_id',

                    'foreign_table' => 'comments',
                    'foreign_key_col_in_foreign_table' => 'post_id',

                    'primary_key_col_in_foreign_table' => 'comment_id',
                    'foreign_models_class_name' => 'CommentsModel',
                    'foreign_models_collection_class_name' => 'CommentsCollection',
                    'foreign_models_record_class_name' => 'CommentRecord',
                ],
        'summaries' => [
                    'relation_type' => \LeanOrm\Model::RELATION_TYPE_HAS_ONE,

                    'foreign_key_col_in_my_table' => 'post_id',

                    'foreign_table' => 'summaries',
                    'foreign_key_col_in_foreign_table' => 'post_id',

                    'primary_key_col_in_foreign_table' => 'summary_id',
                    'foreign_models_class_name' => 'SummariesModel',
                    'foreign_models_collection_class_name' => 'SummariesCollection',
                    'foreign_models_record_class_name' => 'SummaryRecord',
                ],
        'posts_tags' => [
                    'relation_type' => \GDAO\Model::RELATION_TYPE_HAS_MANY,

                    'foreign_key_col_in_my_table' => 'post_id',

                    'foreign_table' => 'posts_tags',
                    'foreign_key_col_in_foreign_table' => 'post_id',

                    'primary_key_col_in_foreign_table' => 'posts_tags_id',
                    'foreign_models_class_name' => 'PostsTagsModel',
                    'foreign_models_collection_class_name' => 'PostsTagsCollection',
                    'foreign_models_record_class_name' => 'PostsTagRecord',
                ],
        'tags' => [
                    'relation_type' => \GDAO\Model::RELATION_TYPE_HAS_MANY_THROUGH,
                    'col_in_my_table_linked_to_join_table' => 'post_id',

                    'join_table' => 'posts_tags',
                    'col_in_join_table_linked_to_my_table' => 'post_id',
                    'col_in_join_table_linked_to_foreign_table' => 'tag_id',

                    'foreign_table' => 'tags',
                    'col_in_foreign_table_linked_to_join_table' => 'tag_id',

                    'primary_key_col_in_foreign_models_table' => 'tag_id',
                    'foreign_models_class_name' => 'TagsModel',
                    'foreign_models_collection_class_name' => 'TagsCollection',
                    'foreign_models_record_class_name' => 'TagRecord',
                ],
    ];
}