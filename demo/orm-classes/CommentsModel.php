<?php
/**
 * Description of CommentsModel
 *
 * @author aadegbam
 */
class CommentsModel extends \LeanOrm\Model
{
    protected $_collection_class_name = 'CommentsCollection';
    protected $_record_class_name = 'CommentRecord';

    protected $_created_timestamp_column_name = 'date_created';
    protected $_updated_timestamp_column_name = 'm_timestamp'; 

    protected $_primary_col = 'comment_id';
    protected $_table_name = 'comments';

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
    ];
}