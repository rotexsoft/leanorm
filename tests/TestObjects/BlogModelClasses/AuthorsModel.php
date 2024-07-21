<?php
namespace LeanOrm\TestObjects;

/**
 * Description of AuthorsModel
 *
 * @author rotimi
 */
class AuthorsModel extends \LeanOrm\Model {
    
    public function __construct(string $dsn = '', string $username = '', string $passwd = '', array $pdo_driver_opts = [], string $primary_col_name = '', string $table_name = '') {
        
        $this->setTableName('authors')->setPrimaryCol('author_id');
        
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
        
        $this->hasMany(
            relation_name: 'posts', 
            foreign_key_col_in_this_models_table: 'author_id', 
            foreign_table_name: 'posts', 
            foreign_key_col_in_foreign_table: 'author_id', 
            primary_key_col_in_foreign_table: 'post_id', 
            foreign_models_class_name: PostsModel::class, 
            foreign_models_record_class_name: PostRecord::class, 
            foreign_models_collection_class_name: PostsCollection::class, 
            sql_query_modifier: null
        )
        ->hasMany(
            relation_name: 'one_post', 
            foreign_key_col_in_this_models_table: 'author_id', 
            foreign_table_name: 'posts', 
            foreign_key_col_in_foreign_table: 'author_id', 
            primary_key_col_in_foreign_table: 'post_id', 
            foreign_models_class_name: PostsModel::class, 
            foreign_models_record_class_name: PostRecord::class, 
            foreign_models_collection_class_name: PostsCollection::class, 
            sql_query_modifier: function(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select {
                    
                $selectObj->limit(1);
            
                return $selectObj;
            }
        )
        ->setCollectionClassName(AuthorsCollection::class)
        ->setRecordClassName(AuthorRecord::class);
    }
}
