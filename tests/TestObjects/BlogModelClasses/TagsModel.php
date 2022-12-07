<?php
namespace LeanOrm\TestObjects;

use Psr\Log\LogLevel;

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

            public function log($level, $message, array $context = array())
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
            protected function format($level, $message, $context, $timestamp = null)
            {
                if ($timestamp === null) $timestamp = date('Y-m-d H:i:s');
                return PHP_EOL . '[' . $timestamp . '] ' . strtoupper($level) . ': ' . $this->interpolate($message, $context) . PHP_EOL;
            }
        };
        
        $this->setLogger($psrLogger);
    }
}
