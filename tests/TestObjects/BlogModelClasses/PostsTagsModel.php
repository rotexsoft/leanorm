<?php
namespace LeanOrm\TestObjects;

/**
 * Description of PostsTagsModel
 *
 * @author rotimi
 */
class PostsTagsModel extends \LeanOrm\Model {
    
    public function __construct(string $dsn = '', string $username = '', string $passwd = '', array $pdo_driver_opts = [], string $primary_col_name = '', string $table_name = '') {
        
        $this->setTableName('posts_tags')->setPrimaryCol('posts_tags_id');
        
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
        ->belongsTo(
            'tag',
            'tag_id',
            'tags',
            'tag_id',
            'tag_id',
            TagsModel::class,
            TagRecord::class,
            TagsCollection::class   
        )
        ->setCollectionClassName(PostsTagsCollection::class)
        ->setRecordClassName(PostTagRecord::class);
    }
}
