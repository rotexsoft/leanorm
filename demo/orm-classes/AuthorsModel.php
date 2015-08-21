<?php
/**
 * Description of AuthorsModel
 *
 * @author aadegbam
 */
class AuthorsModel extends \LeanOrm\Model
{
    protected $_collection_class_name = 'AuthorsCollection';
    protected $_record_class_name = 'AuthorRecord';

    protected $_created_timestamp_column_name = 'date_created';
    protected $_updated_timestamp_column_name = 'm_timestamp'; 

    protected $_primary_col = 'author_id';
    protected $_table_name = 'authors';

    protected $_relations = [
        
        //Entry below specifies that an author can have one or more posts (ie. a has-many relationship).
        'posts' => [
                    'relation_type' => \LeanOrm\Model::RELATION_TYPE_HAS_MANY,

                    'foreign_key_col_in_my_table' => 'author_id',

                    'foreign_table' => 'posts',
                    'foreign_key_col_in_foreign_table' => 'author_id',

                    'primary_key_col_in_foreign_table' => 'post_id',
                    'foreign_models_class_name' => 'PostsModel',
                    'foreign_models_collection_class_name' => 'PostsCollection',
                    'foreign_models_record_class_name' => 'PostRecord',
                ]
    ];
    
}