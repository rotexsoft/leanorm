<?php
/**
 * Description of PostsTagsModel
 *
 * @author aadegbam
 */
class PostsTagsModel extends \LeanOrm\Model
{
    protected $_collection_class_name = 'PostsTagsCollection';
    protected $_record_class_name = 'PostsTagRecord';

    protected $_created_timestamp_column_name = 'date_created';
    protected $_updated_timestamp_column_name = 'm_timestamp'; 

    protected $_primary_col = 'posts_tags_id';
    protected $_table_name = 'posts_tags';

    protected $_relations = [
        'post' => [
                    'relation_type' => \LeanOrm\Model::RELATION_TYPE_BELONGS_TO,

                    'foreign_key_col_in_my_table' => 'post_id',

                    'foreign_table' => 'posts',
                    'foreign_key_col_in_foreign_table' => 'post_id',

                    'primary_key_col_in_foreign_table' => 'post_id',
                    'foreign_models_class_name' => 'PostsModel',
                    'foreign_models_collection_class_name' => 'PostsCollection',
                    'foreign_models_record_class_name' => 'PostRecord',
                ],
        'tag' => [
                    'relation_type' => \LeanOrm\Model::RELATION_TYPE_BELONGS_TO,

                    'foreign_key_col_in_my_table' => 'tag_id',

                    'foreign_table' => 'tags',
                    'foreign_key_col_in_foreign_table' => 'tag_id',

                    'primary_key_col_in_foreign_table' => 'tag_id',
                    'foreign_models_class_name' => 'TagsModel',
                    'foreign_models_collection_class_name' => 'TagsCollection',
                    'foreign_models_record_class_name' => 'TagRecord',
                ],
    ];
}