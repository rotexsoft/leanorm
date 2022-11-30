<?php
use Atlas\Pdo\Connection;

/**
 * Description of SqliteSchemaCreatorAndSeeder
 *
 * @author rotimi
 */
class SqliteSchemaCreatorAndSeeder implements SchemaCreatorAndSeederInterface {
    
    protected Connection $connection;

    public function __construct(Connection $conn) {
        
        $this->connection = $conn;
    }

    public function createTables(): bool {
        
        try {
            $this->createAuthorsTable();
            $this->createEmptyDataTable();
            $this->createPostsTable();
            $this->createCommentsTable();
            $this->createSummariesTable();
            $this->createTagsTable();
            $this->createPostsTagsTable();
            return true;
        } catch (\Exception $exc) { return false; }
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
        } catch (\Exception $exc) { return false; }
    }
    
    
    protected function createEmptyDataTable(): void {
        
        $this->connection->query("
            CREATE TABLE empty_data (
                id INTEGER PRIMARY KEY,
                name TEXT,
                m_timestamp TEXT NOT NULL,
                date_created TEXT NOT NULL
            )
        ");
    }
    
    protected function createAuthorsTable(): void {
        
        $this->connection->query("
            CREATE TABLE authors (
                author_id INTEGER PRIMARY KEY,
                name TEXT,
                m_timestamp TEXT NOT NULL,
                date_created TEXT NOT NULL
            )
        ");
    }
    
    protected function createCommentsTable(): void {
        
        $this->connection->query("
            CREATE TABLE comments (
              comment_id INTEGER PRIMARY KEY,
              post_id INTEGER NOT NULL,
              name TEXT,
              email TEXT,
              website TEXT,
              body TEXT,
              m_timestamp TEXT NOT NULL,
              date_created TEXT NOT NULL,
              FOREIGN KEY(post_id) REFERENCES posts(post_id)
            )
        ");
    }
    
    protected function createPostsTable(): void {
        
        $this->connection->query("
            CREATE TABLE posts (
              post_id INTEGER PRIMARY KEY,
              author_id INTEGER NOT NULL,
              datetime TEXT,
              title TEXT,
              body TEXT,
              m_timestamp TEXT NOT NULL,
              date_created TEXT NOT NULL,
              FOREIGN KEY(author_id) REFERENCES authors(author_id)
            )
        ");
    }
    
    protected function createSummariesTable(): void {
        
        $this->connection->query("
            CREATE TABLE summaries (
              summary_id INTEGER PRIMARY KEY,
              post_id INTEGER NOT NULL,
              view_count INTEGER,
              comment_count INTEGER,
              m_timestamp TEXT NOT NULL,
              date_created TEXT NOT NULL,
              UNIQUE(post_id),
              FOREIGN KEY(post_id) REFERENCES posts(post_id)
            )
        ");
    }
    
    protected function createTagsTable(): void {
        
        $this->connection->query("
            CREATE TABLE tags (
              tag_id INTEGER PRIMARY KEY,
              name TEXT NOT NULL,
              m_timestamp TEXT NOT NULL,
              date_created TEXT NOT NULL
            )
        ");
    }
    
    protected function createPostsTagsTable(): void {
        
        $this->connection->query("
            CREATE TABLE posts_tags (
              posts_tags_id INTEGER PRIMARY KEY,
              post_id INTEGER NOT NULL,
              tag_id INTEGER NOT NULL,
              m_timestamp TEXT NOT NULL,
              date_created TEXT NOT NULL,
              FOREIGN KEY(post_id) REFERENCES posts(post_id),
              FOREIGN KEY(tag_id) REFERENCES tags(tag_id)
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
            $datetime = $date_created;
            
            $minuteIncrement++;
            $m_timestamp = date('Y-m-d H:i:s',  strtotime("+{$minuteIncrement} minutes"));
            
            $this->connection->perform($stm, [$authorIds[$index-1], $datetime, $title, $body, $date_created, $m_timestamp]);
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
