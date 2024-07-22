<?php
namespace LeanOrm\TestObjects;

/**
 * Description of CommentsModel
 *
 * @author rotimi
 */
class CommentsModel extends \LeanOrm\Model {
    
    public function __construct(string $dsn = '', string $username = '', string $passwd = '', array $pdo_driver_opts = [], string $primary_col_name = '', string $table_name = '') {
        
        $this->setTableName('comments')->setPrimaryCol('comment_id');
        
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
        $this->belongsTo(
            relation_name: 'post', 
            relationship_col_in_my_table: 'post_id', 
            foreign_table_name: 'posts', 
            foreign_key_col_in_foreign_table: 'post_id', 
            primary_key_col_in_foreign_table: 'post_id',
            foreign_models_class_name: PostsModel::class,
            foreign_models_record_class_name: PostRecord::class,
            foreign_models_collection_class_name: PostsCollection::class
        )
        ->setCollectionClassName(CommentsCollection::class)
        ->setRecordClassName(CommentRecord::class);
    }
}
