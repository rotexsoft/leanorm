<?php
use Atlas\Pdo\Connection;

/**
 * Description of PostgreSQLSchemaCreatorAndSeeder
 *
 * @author rotimi
 */
class PostgreSQLSchemaCreatorAndSeeder implements SchemaCreatorAndSeederInterface {
    
    protected Connection $connection;

    public function __construct(Connection $conn) {
        
        $this->connection = $conn;
    }

    public function createTables(): bool {
        
        try {
            
            //$this->connection->query("SET session_replication_role = replica;");
            $this->createAuthorsTableAndView();
            $this->createEmptyDataTable();
            $this->createKeyValueTable();
            $this->createPostsTable();
            $this->createCommentsTable();
            $this->createSummariesTable();
            $this->createTagsTable();
            $this->createPostsTagsTable();
            //$this->connection->query("SET foreign_key_checks = 1");
            return true;
            
        } catch (\Exception $exc) { 
            
            throw $exc;
        }
    }

    public function populateTables(): bool {
        
        try {
            $this->populateAuthorsTable();
            $this->populatePostsTable();
            $this->populateCommentsTable();
            $this->populateSummariesTable();
            $this->populateTagsTable();
            $this->populatePostsTagsTable();
            return true;
            
        } catch (\Exception $exc) { 
            
            throw $exc;
        }
    }
    
    protected function createEmptyDataTable(): void {
        
        $this->connection->query("
            DROP TABLE IF EXISTS empty_data;
        ");
        
        $this->connection->query("
            CREATE TABLE empty_data (
                id SERIAL PRIMARY KEY,
                name TEXT NOT NULL,
                m_timestamp TIMESTAMP NOT NULL,
                date_created TIMESTAMP NOT NULL
            )
        ");
    }
    
    protected function createKeyValueTable(): void {
        
        $this->connection->query("
            DROP TABLE IF EXISTS key_value;
        ");
        
        $this->connection->query("
            CREATE TABLE key_value (
                id SERIAL PRIMARY KEY,
                key_name TEXT NOT NULL,
                value TEXT NOT NULL,
                blankable_value TEXT DEFAULT NULL,
                m_timestamp TIMESTAMP NOT NULL,
                date_created TIMESTAMP NOT NULL
            )
        ");
    }
    
    protected function createAuthorsTableAndView(): void {
        
        $this->connection->query("
            DROP VIEW IF EXISTS v_authors;
        ");
        
        $this->connection->query("
            DROP TABLE IF EXISTS authors;
        ");
        
        $this->connection->query("
            CREATE TABLE authors (
              author_id SERIAL PRIMARY KEY,
              name varchar(255) DEFAULT NULL,
              m_timestamp TIMESTAMP NOT NULL,
              date_created TIMESTAMP NOT NULL
            )
        ");
        
        $this->connection->query("
            CREATE VIEW v_authors AS 
            SELECT
              authors.author_id    AS author_id,
              authors.name         AS name,
              authors.m_timestamp  AS m_timestamp,
              authors.date_created AS date_created
            FROM authors
        ");
    }
    
    protected function createCommentsTable(): void {
        
        $this->connection->query("
            DROP TABLE IF EXISTS comments;
        ");
        
        $this->connection->query("
            CREATE TABLE comments (
              comment_id SERIAL PRIMARY KEY,
              post_id int NOT NULL,
              datetime TIMESTAMP DEFAULT NULL,
              name varchar(255) DEFAULT NULL,
              email varchar(255) DEFAULT NULL,
              website varchar(255) DEFAULT NULL,
              body text,
              m_timestamp TIMESTAMP NOT NULL,
              date_created TIMESTAMP NOT NULL
            )
        ");
    }
    
    protected function createPostsTable(): void {
        
        $this->connection->query("
            DROP TABLE IF EXISTS posts;
        ");
        
        $this->connection->query("
            CREATE TABLE posts (
              post_id SERIAL PRIMARY KEY,
              author_id int NOT NULL,
              datetime TIMESTAMP DEFAULT NULL,
              title varchar(255) DEFAULT NULL,
              body text,
              m_timestamp TIMESTAMP NOT NULL,
              date_created TIMESTAMP NOT NULL
            )
        ");
    }
    
    protected function createSummariesTable(): void {
        
        $this->connection->query("
            DROP TABLE IF EXISTS summaries;
        ");
        
        $this->connection->query("
            CREATE TABLE summaries (
              summary_id SERIAL PRIMARY KEY,
              post_id int NOT NULL UNIQUE,
              view_count int DEFAULT 0,
              comment_count int DEFAULT 0,
              m_timestamp TIMESTAMP NOT NULL,
              date_created TIMESTAMP NOT NULL
            )
        ");
    }
    
    protected function createTagsTable(): void {
        
        $this->connection->query("
            DROP TABLE IF EXISTS tags;
        ");
        
        $this->connection->query("
            CREATE TABLE tags (
              tag_id SERIAL PRIMARY KEY,
              name varchar(255) DEFAULT NULL,
              m_timestamp TIMESTAMP NOT NULL,
              date_created TIMESTAMP NOT NULL
            )
        ");
    }
    
    protected function createPostsTagsTable(): void {
        
        $this->connection->query("
            DROP TABLE IF EXISTS posts_tags;
        ");
        
        $this->connection->query("
            CREATE TABLE posts_tags (
              posts_tags_id SERIAL PRIMARY KEY,
              post_id int NOT NULL,
              tag_id int NOT NULL,
              m_timestamp TIMESTAMP NOT NULL,
              date_created TIMESTAMP NOT NULL
            )
        ");
    }
    
    protected function populateAuthorsTable(): void {
        
        $stm = "INSERT INTO authors (name, m_timestamp, date_created) VALUES (?, ?, ?)";
        
        for ($index = 1; $index <= 10; $index++) {
            
            $name = "user_{$index}";
            $minuteIncrement = $index;
            $date_created = date('Y-m-d H:i:s',  strtotime("+{$minuteIncrement} minutes"));
            $minuteIncrement++;
            $m_timestamp = date('Y-m-d H:i:s',  strtotime("+{$minuteIncrement} minutes"));
            
            $this->connection->perform($stm, [$name, $m_timestamp, $date_created]);
        }
    }
    
    protected function populatePostsTable(): void {
        
        $stm = "INSERT INTO posts (author_id, datetime, title, body, m_timestamp, date_created) VALUES (?, ?, ?, ?, ?, ?)";
        $authorIds = [1,2,1,2];
        
        for ($index = 1; $index <= 4; $index++) {
            
            $title = "Post {$index}";
            $body = "Post Body {$index}";
            
            $minuteIncrement = $index;
            $date_created = date('Y-m-d H:i:s',  strtotime("+{$minuteIncrement} minutes"));
            $TIMESTAMP = $date_created;
            
            $minuteIncrement++;
            $m_timestamp = date('Y-m-d H:i:s',  strtotime("+{$minuteIncrement} minutes"));
            
            $this->connection->perform($stm, [$authorIds[$index-1], $TIMESTAMP, $title, $body, $date_created, $m_timestamp]);
        }
    }
    
    protected function populateCommentsTable(): void {
        
        $stm = "INSERT INTO comments (post_id, name, email, website, body, m_timestamp, date_created) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $postIds = [1,2,3,4];
        
        for ($index = 1; $index <= 4; $index++) {
            
            $name = "Name {$index}";
            $email = "Email {$index}";
            $website = "Website {$index}";
            $body = "Body {$index}";
            
            $minuteIncrement = $index;
            $date_created = date('Y-m-d H:i:s',  strtotime("+{$minuteIncrement} minutes"));
            
            $minuteIncrement++;
            $m_timestamp = date('Y-m-d H:i:s',  strtotime("+{$minuteIncrement} minutes"));
            
            $this->connection->perform($stm, [$postIds[$index-1], $name, $email, $website, $body, $m_timestamp, $date_created,]);
        }
    }
    
    protected function populateSummariesTable(): void {
        
        $stm = "INSERT INTO summaries (post_id, view_count, comment_count, m_timestamp, date_created) VALUES (?, ?, ?, ?, ?)";
        $postIds = [1,2,3,4];
        
        for ($index = 1; $index <= 4; $index++) {
            
            $name = "Name {$index}";
            $email = "Email {$index}";
            $website = "Website {$index}";
            $body = "Body {$index}";
            
            $minuteIncrement = $index;
            $date_created = date('Y-m-d H:i:s',  strtotime("+{$minuteIncrement} minutes"));
            
            $minuteIncrement++;
            $m_timestamp = date('Y-m-d H:i:s',  strtotime("+{$minuteIncrement} minutes"));
            
            $this->connection->perform($stm, [$postIds[$index-1], $index, $index, $m_timestamp, $date_created]);
        }
    }
    
    protected function populateTagsTable(): void {
        
        $stm = "INSERT INTO tags (name, m_timestamp, date_created) VALUES (?, ?, ?)";
        
        for ($index = 1; $index <= 4; $index++) {
            
            $name = "tag_{$index}";
            
            $minuteIncrement = $index;
            $date_created = date('Y-m-d H:i:s',  strtotime("+{$minuteIncrement} minutes"));
            $minuteIncrement++;
            $m_timestamp = date('Y-m-d H:i:s',  strtotime("+{$minuteIncrement} minutes"));
            
            $this->connection->perform($stm, [$name, $m_timestamp, $date_created]);
        }
    }
    
    protected function populatePostsTagsTable(): void {
        
        $stm = "INSERT INTO posts_tags(post_id, tag_id, m_timestamp, date_created) VALUES (?, ?, ?, ?)";
        $postAndTagIds = [1,2,3,4];
        
        for ($index = 1; $index <= 4; $index++) {
            
            $minuteIncrement = $index;
            $date_created = date('Y-m-d H:i:s',  strtotime("+{$minuteIncrement} minutes"));
            
            $minuteIncrement++;
            $m_timestamp = date('Y-m-d H:i:s',  strtotime("+{$minuteIncrement} minutes"));
            
            $this->connection->perform(
                $stm, 
                [
                    $postAndTagIds[$index-1], 
                    $postAndTagIds[$index-1], 
                    $m_timestamp, 
                    $date_created
                ]
            );
        }
    }

}
