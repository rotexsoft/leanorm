<?php
namespace LeanOrm\TestObjects;

/**
 * Description of TagsModel
 *
 * @author rotimi
 */
class TagsModel extends \LeanOrm\Model {

    public function __construct(string $dsn = '', string $username = '', string $passwd = '', array $pdo_driver_opts = [], string $primary_col_name = '', string $table_name = '') {
        
        $this->setTableName('tags')->setPrimaryCol('tag_id');
        
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
        $this->hasMany(
            'posts_tags',
            'tag_id',
            'posts_tags',
            'tag_id',
            'posts_tags_id',
            PostsTagsModel::class,
            PostTagRecord::class,
            PostsTagsCollection::class
        )
        ->hasManyThrough(
            'posts',
            'tag_id',
            'posts_tags',
            'tag_id',
            'post_id',
            'posts',
            'post_id',
            'post_id',
            PostsModel::class,
            PostRecord::class,
            PostsCollection::class
        )        
        ->setCollectionClassName(TagsCollection::class)
        ->setRecordClassName(TagRecord::class);
    }
}
