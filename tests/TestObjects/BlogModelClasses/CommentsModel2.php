<?php
namespace LeanOrm\TestObjects;

/**
 * Description of CommentsModel
 *
 * @author rotimi
 */
class CommentsModel2 extends \LeanOrm\Model {
    
    protected string $primary_col = 'comment_id';
    
    protected string $table_name = 'comments';
    
    protected ?string $collection_class_name = \LeanOrm\TestObjects\CommentsCollection::class;
    
    protected ?string $record_class_name = \LeanOrm\TestObjects\CommentRecord::class;
    
    public function __construct(string $dsn = '', string $username = '', string $passwd = '', array $pdo_driver_opts = [], string $primary_col_name = '', string $table_name = '') {
        
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
    }
}
