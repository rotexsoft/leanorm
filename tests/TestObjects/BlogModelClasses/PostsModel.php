<?php
namespace LeanOrm\TestObjects;

/**
 * Description of PostsModel
 *
 * @author rotimi
 */
class PostsModel extends \LeanOrm\Model{
    
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
        $this->belongsTo(
                'author', 
                'author_id', 
                'authors', 
                'author_id', 
                'author_id',
                AuthorsModel::class,
                AuthorRecord::class,
                AuthorsCollection::class
            )
            ->hasMany(
                'comments', 
                'post_id', 
                'comments', 
                'post_id', 
                'comment_id',
                CommentsModel::class,
                CommentRecord::class,
                CommentsCollection::class
            )
            ->hasOne(
                'summary', 
                'post_id', 
                'summaries', 
                'post_id', 
                'summary_id',
                SummariesModel::class,
                SummaryRecord::class,
                SummariesCollection::class
            )
            ->hasMany(
                'posts_tags',
                'post_id',
                'posts_tags',
                'post_id',
                'posts_tags_id',
                PostsTagsModel::class,
                PostTagRecord::class,
                PostsTagsCollection::class
            )
            ->hasManyThrough(
                'tags',
                'post_id',
                'posts_tags',
                'post_id',
                'tag_id',
                'tags',
                'tag_id',
                'tag_id',
                TagsModel::class,
                TagRecord::class,
                TagsCollection::class
            )
            ->setCollectionClassName(PostsCollection::class)
            ->setRecordClassName(PostRecord::class);
    }
}
