<?php

namespace LeanOrm\TestObjects;

/**
 * Description of PostsTagsModel
 *
 * @author rotimi
 */
class PostsTagsModel extends \LeanOrm\Model {

    protected array $table_cols = [
        'posts_tags_id' => [
            'name' => 'posts_tags_id',
            'type' => 'integer',
            'size' => NULL,
            'scale' => NULL,
            'notnull' => false,
            'default' => NULL,
            'autoinc' => false,
            'primary' => true,
        ],
        'post_id' => [
            'name' => 'post_id',
            'type' => 'integer',
            'size' => NULL,
            'scale' => NULL,
            'notnull' => true,
            'default' => NULL,
            'autoinc' => false,
            'primary' => false,
        ],
        'tag_id' => [
            'name' => 'tag_id',
            'type' => 'integer',
            'size' => NULL,
            'scale' => NULL,
            'notnull' => true,
            'default' => NULL,
            'autoinc' => false,
            'primary' => false,
        ],
        'm_timestamp' => [
            'name' => 'm_timestamp',
            'type' => 'text',
            'size' => NULL,
            'scale' => NULL,
            'notnull' => true,
            'default' => NULL,
            'autoinc' => false,
            'primary' => false,
        ],
        'date_created' => [
            'name' => 'date_created',
            'type' => 'text',
            'size' => NULL,
            'scale' => NULL,
            'notnull' => true,
            'default' => NULL,
            'autoinc' => false,
            'primary' => false,
        ],
    ];

    public function __construct(string $dsn = '', string $username = '', string $passwd = '', array $pdo_driver_opts = [], string $primary_col_name = '', string $table_name = '') {

        // Primary key will be auto-set using $this->table_cols data
        //$this->setPrimaryCol('posts_tags_id');
        $this->setTableName('posts_tags');

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
