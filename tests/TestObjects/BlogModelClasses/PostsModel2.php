<?php
namespace LeanOrm\TestObjects;

/**
 * Description of PostsModel
 *
 * @author rotimi
 */
class PostsModel2 extends \LeanOrm\Model {
    
    public function __construct(
        string $dsn = '', 
        string $username = '', 
        string $passwd = '', 
        array $pdo_driver_opts = [], 
        string $primary_col_name = '', 
        string $table_name = ''
    ) {        
        $this->setTableName('posts')->setPrimaryCol('post_id');
        
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
        $this->setCollectionClassName(PostsCollection::class)
            ->setRecordClassName(PostRecord::class);
    }
}
