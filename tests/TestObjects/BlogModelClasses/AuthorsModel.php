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
            'posts', 
            'author_id', 
            'posts', 
            'author_id', 
            'post_id', 
            PostsModel::class, 
            PostRecord::class, 
            PostsCollection::class, 
            null
        )
        ->hasMany(
            'one_post', 
            'author_id', 
            'posts', 
            'author_id', 
            'post_id', 
            PostsModel::class, 
            PostRecord::class, 
            PostsCollection::class, 
            function(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select {
                    
                $selectObj->limit(1);
            
                return $selectObj;
            }
        )
        ->setCollectionClassName(AuthorsCollection::class)
        ->setRecordClassName(AuthorRecord::class);
    }
}
