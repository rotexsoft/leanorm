<?php
/**
 * Description of TagsModel
 *
 * @author aadegbam
 */
class TagsModel extends \LeanOrm\Model
{
    protected $_collection_class_name = 'TagsCollection';
    protected $_record_class_name = 'TagRecord';

    protected $_created_timestamp_column_name = 'm_timestamp';
    protected $_updated_timestamp_column_name = 'date_created'; 

    protected $_primary_col = 'tag_id';
    protected $_table_name = 'tags';

    protected $_relations = [
        'posts_tags' => [
                    'relation_type' => \GDAO\Model::RELATION_TYPE_HAS_MANY,

                    'foreign_key_col_in_my_table' => 'tag_id',

                    'foreign_table' => 'posts_tags',
                    'foreign_key_col_in_foreign_table' => 'tag_id',

                    'primary_key_col_in_foreign_table' => 'posts_tags_id',
                    'foreign_models_class_name' => 'PostsTagsModel',
                    'foreign_models_collection_class_name' => 'PostsTagsCollection',
                    'foreign_models_record_class_name' => 'PostsTagRecord',
                ],
        'posts' => [
                    'relation_type' => \GDAO\Model::RELATION_TYPE_HAS_MANY_THROUGH,
                    'col_in_my_table_linked_to_join_table' => 'tag_id',

                    'join_table' => 'posts_tags',
                    'col_in_join_table_linked_to_my_table' => 'tag_id',
                    'col_in_join_table_linked_to_foreign_table' => 'post_id',

                    'foreign_table' => 'posts',
                    'col_in_foreign_table_linked_to_join_table' => 'post_id',

                    'primary_key_col_in_foreign_models_table' => 'post_id',
                    'foreign_models_class_name' => 'PostsModel',
                    'foreign_models_collection_class_name' => 'PostsCollection',
                    'foreign_models_record_class_name' => 'PostRecord',
                ],
    ];
}