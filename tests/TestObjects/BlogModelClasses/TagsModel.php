<?php
namespace LeanOrm\TestObjects;

use Psr\Log\LogLevel;

/**
 * Description of TagsModel
 *
 * @author rotimi
 */
class TagsModel extends \LeanOrm\Model {
    
    protected string $primary_col = 'tag_id';
    
    protected string $table_name = 'tags';
    
    protected ?string $collection_class_name = \LeanOrm\TestObjects\TagsCollection::class;
    
    protected ?string $record_class_name = \LeanOrm\TestObjects\TagRecord::class;

    public function __construct(string $dsn = '', string $username = '', string $passwd = '', array $pdo_driver_opts = [], string $primary_col_name = '', string $table_name = '') {
                
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
        
        $this->hasMany(
            relation_name: 'posts_tags',
            relationship_col_in_my_table: 'tag_id',
            foreign_table_name: 'posts_tags',
            relationship_col_in_foreign_table: 'tag_id',
            primary_key_col_in_foreign_table: 'posts_tags_id',
            foreign_models_class_name: PostsTagsModel::class,
            foreign_models_record_class_name: PostTagRecord::class,
            foreign_models_collection_class_name: PostsTagsCollection::class
        )
        ->hasManyThrough(
            relation_name: 'posts',
            col_in_my_table_linked_to_join_table: 'tag_id',
            join_table: 'posts_tags',
            col_in_join_table_linked_to_my_table: 'tag_id',
            col_in_join_table_linked_to_foreign_table: 'post_id',
            foreign_table_name: 'posts',
            col_in_foreign_table_linked_to_join_table: 'post_id',
            primary_key_col_in_foreign_table: 'post_id',
            foreign_models_class_name: PostsModel::class,
            foreign_models_record_class_name: PostRecord::class,
            foreign_models_collection_class_name: PostsCollection::class
        );
        
        $psrLogger = new class extends \Psr\Log\AbstractLogger {
            
            protected $min_level = LogLevel::DEBUG;
            protected $levels = [
                LogLevel::DEBUG,
                LogLevel::INFO,
                LogLevel::NOTICE,
                LogLevel::WARNING,
                LogLevel::ERROR,
                LogLevel::CRITICAL,
                LogLevel::ALERT,
                LogLevel::EMERGENCY
            ];

            public function __construct($min_level = LogLevel::DEBUG)
            {
                $this->min_level = $min_level;
            }

            public function log($level, Stringable|string $message, array $context = array())
            {
                if (!$this->min_level_reached($level)) {
                    return;
                }
                echo $this->format($level, $message, $context);
            }

            /**
             * @param string $level
             * @return boolean
             */
            protected function min_level_reached($level)
            {
                return \array_search($level, $this->levels) >= \array_search($this->min_level, $this->levels);
            }

            /**
             * Interpolates context values into the message placeholders.
             *
             * @author PHP Framework Interoperability Group
             *
             * @param string $message
             * @param array $context
             * @return string
             */
            protected function interpolate($message, array $context)
            {
                if (false === strpos($message, '{')) {
                    return $message;
                }

                $replacements = array();
                foreach ($context as $key => $val) {
                    if (null === $val || is_scalar($val) || (\is_object($val) && method_exists($val, '__toString'))) {
                        $replacements["{{$key}}"] = $val;
                    } elseif ($val instanceof \DateTimeInterface) {
                        $replacements["{{$key}}"] = $val->format(\DateTime::RFC3339);
                    } elseif (\is_object($val)) {
                        $replacements["{{$key}}"] = '[object '.\get_class($val).']';
                    } else {
                        $replacements["{{$key}}"] = '['.\gettype($val).']';
                    }
                }

                return strtr($message, $replacements);
            }

            /**
             * @param string $level
             * @param string $message
             * @param array $context
             * @param string|null $timestamp A Timestamp string in format 'Y-m-d H:i:s', defaults to current time
             * @return string
             */
            protected function format(string $level, string $message, array $context, ?string $timestamp = null)
            {
                if ($timestamp === null) $timestamp = date('Y-m-d H:i:s');
                return PHP_EOL . '[' . $timestamp . '] ' . strtoupper($level) . ': ' . $this->interpolate($message, $context) . PHP_EOL;
            }
        };
        
        $this->setLogger($psrLogger);
    }
}
