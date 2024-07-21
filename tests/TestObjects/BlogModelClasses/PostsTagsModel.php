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
                        relation_name: 'post',
                        foreign_key_col_in_this_models_table: 'post_id',
                        foreign_table_name: 'posts',
                        foreign_key_col_in_foreign_table: 'post_id',
                        primary_key_col_in_foreign_table: 'post_id',
                        foreign_models_class_name: PostsModel::class,
                        foreign_models_record_class_name: PostRecord::class,
                        foreign_models_collection_class_name: PostsCollection::class
                )
                ->belongsTo(
                        relation_name: 'tag',
                        foreign_key_col_in_this_models_table: 'tag_id',
                        foreign_table_name: 'tags',
                        foreign_key_col_in_foreign_table: 'tag_id',
                        primary_key_col_in_foreign_table: 'tag_id',
                        foreign_models_class_name: TagsModel::class,
                        foreign_models_record_class_name: TagRecord::class,
                        foreign_models_collection_class_name: TagsCollection::class
                )
                ->setCollectionClassName(PostsTagsCollection::class)
                ->setRecordClassName(PostTagRecord::class);
    }

}
