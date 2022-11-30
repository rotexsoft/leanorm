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
            'post', 
            'post_id', 
            'posts', 
            'post_id', 
            'post_id',
            PostsModel::class,
            PostRecord::class,
            PostsCollection::class
        )
        ->setCollectionClassName(CommentsCollection::class)
        ->setRecordClassName(CommentRecord::class);
    }
}
