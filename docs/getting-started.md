# Quick Start Guide

- [Design Considerations](#design-considerations)
- [How it works](#how-it-works)
    - [Defining and Creating Model Objects](#defining-and-creating-model-objects)
        - [Using a Factory Function to Instantiate & Retrieve Models](#using-a-factory-function-to-instantiate-and-retrieve-models)
    - [Creating Records & Inserting Data into the Database](#creating-records--inserting-data-into-the-database)
    - [Deleting Data](#deleting-data)
    - [Updating Data](#updating-data)


## Design Considerations

Applications using this package are:

- Expected to have each database table with a single preferably auto-incrementing numeric primary key column (composite primary keys are not supported; however a single primary key column that is non-numeric should still work)

## How it works

LeanOrm will create only one PDO connection to a specific database (ie. one PDO connection per unique dsn string). Creating one or more Model objects for one or more tables with the same dsn string will lead to the creation of only one PDO connection to the database; that one PDO connection will be shared amongst all model instances created with that same dsn string. If you use two different dsn strings, two PDO connections to the database(s) will be created by LeanOrm. Consequently, using three different dsn strings will lead to the creation of three different PDO database connections, and so on for four or more different dsn strings.

All examples are based on the schema below:

![Blog Schema](../demo/blog-db.png)

Below are the Sql statements for setting up this database in Mysql 8.0+

```sql
/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`blog` /*!40100 DEFAULT CHARACTER SET latin1 */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `blog`;

/*Table structure for table `authors` */

DROP TABLE IF EXISTS `authors`;

CREATE TABLE `authors` (
  `author_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) DEFAULT NULL,
  `m_timestamp` DATETIME NOT NULL,
  `date_created` DATETIME NOT NULL,
  PRIMARY KEY (`author_id`)
) ENGINE=INNODB;

/*Table structure for table `comments` */

DROP TABLE IF EXISTS `comments`;

CREATE TABLE `comments` (
  `comment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` INT UNSIGNED NOT NULL,
  `datetime` DATETIME DEFAULT NULL,
  `name` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `body` TEXT,
  `m_timestamp` DATETIME NOT NULL,
  `date_created` DATETIME NOT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `fk_comments_belong_to_post` (`post_id`),
  CONSTRAINT `fk_comments_belong_to_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=INNODB;

/*Table structure for table `posts` */

DROP TABLE IF EXISTS `posts`;

CREATE TABLE `posts` (
  `post_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `author_id` INT UNSIGNED NOT NULL,
  `datetime` DATETIME DEFAULT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `body` TEXT,
  `m_timestamp` DATETIME NOT NULL,
  `date_created` DATETIME NOT NULL,
  PRIMARY KEY (`post_id`),
  KEY `fk_posts_belong_to_an_author` (`author_id`),
  CONSTRAINT `fk_posts_belong_to_an_author` FOREIGN KEY (`author_id`) REFERENCES `authors` (`author_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=INNODB;

/*Table structure for table `posts_tags` */

DROP TABLE IF EXISTS `posts_tags`;

CREATE TABLE `posts_tags` (
  `posts_tags_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` INT UNSIGNED NOT NULL,
  `tag_id` INT UNSIGNED NOT NULL,
  `m_timestamp` DATETIME NOT NULL,
  `date_created` DATETIME NOT NULL,
  PRIMARY KEY (`posts_tags_id`),
  KEY `fk_post_tags_belong_to_a_post` (`post_id`),
  KEY `fk_post_tags_belongs_to_a_tag` (`tag_id`),
  CONSTRAINT `fk_post_tags_belong_to_a_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_post_tags_belongs_to_a_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`tag_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=INNODB;

/*Table structure for table `summaries` */

DROP TABLE IF EXISTS `summaries`;

CREATE TABLE `summaries` (
  `summary_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` INT UNSIGNED NOT NULL,
  `view_count` INT DEFAULT NULL,
  `comment_count` INT DEFAULT NULL,
  `m_timestamp` DATETIME NOT NULL,
  `date_created` DATETIME NOT NULL,
  PRIMARY KEY (`summary_id`),
  UNIQUE KEY `post_id` (`post_id`),
  CONSTRAINT `fk_a_post_has_one_summary` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=INNODB;

/*Table structure for table `tags` */

DROP TABLE IF EXISTS `tags`;

CREATE TABLE `tags` (
  `tag_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) DEFAULT NULL,
  `m_timestamp` DATETIME NOT NULL,
  `date_created` DATETIME NOT NULL,
  PRIMARY KEY (`tag_id`)
) ENGINE=INNODB;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
```

### Defining and Creating Model Objects

There are two basic ways to use this package:

1. Create instances of **\LeanOrm\Model** (one for each database table in your application's database) and then use each instance to fetch, insert, update & delete the database table data. This approach is good for small to medium sized projects. Such instances use **\LeanOrm\Model\Collection** & **\LeanOrm\Model\Record** by default. Below is an example of creating an instance of **\LeanOrm\Model** associated with an **authors** table in the database:

```php
<?php
$authorsModel = 
    new \LeanOrm\Model(
        "mysql:some-host-name;dbname=blog", // dsn string
        "u", // username
        "p", // password
        [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'], //pdo options
        'author_id', // primary key column name
        'authors' // table name
    );

//////////////////////////////////////////
// You can go on to define relationships, 
// change the Record & Collection classes
// used by this instance, etc:
//////////////////////////////////////////

// $authorsModel->belongsTo(...)
// $authorsModel->hasMany(...);
// $authorsModel->hasManyThrough(...);
// $authorsModel->hasOne(...);

// Set collection class to use for this instance. 
// Instances of this collection class will be  
// returned by Model methods that return a collection
// $authorsModel->setCollectionClassName(...);

// Set record class to use for this instance. 
// Instances of this record class will be  
// returned by Model methods that return a record
// $authorsModel->setRecordClassName(...);
```

2. Or you could create Model classes for each database table in your application's database. Each of these classes must extend **\LeanOrm\Model**. This is the recommended approach for large applications. There is a [tool](https://github.com/rotexsoft/leanorm-cli) you can use to automatically generate Model, Record & Collection classes for each of the tables & views in your database. Below are examples of a Model, Collection & Record classes associated with an **authors** table in the database:

**AuthorsModel.php**

```php
<?php
declare(strict_types=1);

class AuthorsModel extends \LeanOrm\Model {
    
    // you can change this during runtime by calling 
    // setCollectionClassName(...) on an instance of this class
    protected ?string $collection_class_name = AuthorsCollection::class;
    
    // you can change this during runtime by calling 
    // setRecordClassName(...) on an instance of this class
    protected ?string $record_class_name = AuthorRecord::class;

    protected ?string $created_timestamp_column_name = 'date_created';
    protected ?string $updated_timestamp_column_name = 'm_timestamp';
    protected string $primary_col = 'author_id';
    protected string $table_name = 'authors';
    
    public function __construct(
        string $dsn = '', 
        string $username = '', 
        string $passwd = '', 
         array $pdo_driver_opts = [], 
        string $primary_col_name = '', 
        string $table_name = ''
    ) {
        parent::__construct(
            $dsn, $username, $passwd, 
            $pdo_driver_opts, $primary_col_name, $table_name
        );
        
        // Define relationships below here
        
        //$this->belongsTo(...)
        //$this->hasMany(...);
        //$this->hasManyThrough(...);
        //$this->hasOne(...)
    }
}

```

**AuthorsCollection.php**

```php
<?php
declare(strict_types=1);

class AuthorsCollection extends \LeanOrm\Model\Collection {
    
    //put your code here
}
```

**AuthorRecord.php**

```php
<?php
declare(strict_types=1);

/**
 * NOTE: the property attributes below are for IDE autocompletion and 
 * are not needed / used by LeanOrm under the hood. They don't need to
 * be in your record classes.
 * 
 * @property mixed $author_id int unsigned NOT NULL
 * @property mixed $name varchar(255)
 * @property mixed $m_timestamp datetime NOT NULL
 * @property mixed $date_created datetime NOT NULL
 */
class AuthorRecord extends \LeanOrm\Model\Record {
    
    //put your code here
}
```

#### Using a Factory Function to Instantiate and Retrieve Models

If you have many tables and views in your database, it may become tedious to have to keep manually adding instances of each Model class needed by your application to a dependencies file or a container. You could simply write a function to create new Models based on the specified class name. 

Below is a simple implementation of a function that creates a single instance of a model per Model class name and always returns that instance when called. You can write something similar to manage the creation of Model objects in your application.

```php
<?php

// This function creates a single instance of a model class for each specified 
// model class and returns that instance every time this function is called.
$createOrGetModel = function(
    string $modelName, 
    string $tableName='', 
    string $primaryColName=''
): \LeanOrm\Model {
    
    static $models;
    
    if(!$models) {
        
        $models = [];
    }
    
    if(array_key_exists($modelName, $models)) {
        
        return $models[$modelName];
    }

    if(!is_a($modelName, \LeanOrm\Model::class, true)) {
        
        throw new \Exception(
            "ERROR: The class name `{$modelName}` supplied for creating a new model is not "
           . "`" . \LeanOrm\Model::class . "` or any of its sub-classes!"
        );
    }
    
    // NOTE: You should use a function like getenv() or similar 
    // to inject the dsn, username and password values into the 
    // constructor call below so you don't commit live credentials
    // into the repository for your project.
    $models[$modelName] = new $modelName(
        'mysql:host=hostname;dbname=blog', 'user', 'passwd',
        [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'],
        $primaryColName, $tableName
    );
    
    return $models[$modelName];
};

// You just call the function to create & retrieve an 
// instance of a model class like so:
$authorsModel = $createOrGetModel(AuthorsModel::class);
```


### Creating Records & Inserting Data into the Database

```php
<?php
$authorsModel = new AuthorsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');

//Method 1:
$newRecord = $authorsModel->createNewRecord(); // create a blank new record
$newRecord->name = 'Joe Blow'; // set a value for a column
$newRecord['name'] = 'Joe Blow'; // also sets a value for a column
$newRecord->save(); // saves the record to the authors table in the database

//Method 2:
$newRecord = $authorsModel->createNewRecord(); // create a blank new record
$newRecord->save([ 'name' => 'Joe Blow']); // you can inject data into the save method

//Method 3:
$newRecord = $authorsModel->createNewRecord([ 'name' => 'Joe Blow']);
$newRecord->save(); // saves the record to the authors table in the database

// NOTE: the save method for Record objects returns 
// - true: successful save, 
// - false: failed save, 
// - null: no changed data to save

//Method 4:
$insertedData = $authorsModel->insert([ 'name' => 'Joe Blow']); // save to the DB
// $insertedData is an associative array of the data just inserted into the
// database. This data will include an auto-generated primary key value, if
// the database table we just inserted the record into has an 
// auto-incrementing primary key column.
$existingRecord = $authorsModel->createNewRecord($insertedData)
                               ->markAsNotNew(); // mark record as not new

// A new record is a record that has never been saved to the database

//Multiple Inserts:
//Below is the most efficient way insert multiple rows to the database.
//$allSuccessfullyInserted will be === true if all the inserts were
//successful, otherwise it will be  === false which means the multiple
//insert was unsuccessful (nothing is saved to the database in this case).
$allSuccessfullyInserted = $authorsModel->insertMany(
                                [
                                    ['name' => 'Joe Blow'],
                                    ['name' => 'Jane Doe']
                                ]
                            );

// For Model classes that have their $created_timestamp_column_name 
// & $updated_timestamp_column_name properties set to valid database
// column names whose data type is a datetime data type, LeanORM will
// automatically populate those fields with the current timestamp
// computed via date('Y-m-d H:i:s') when each new record for that
// Model is inserted into the database.
//
// After successfully inserting a new record into the database via method 1,
// 2 or 3, the record object which save was invoked will be updated with the 
// primary key value of the new record if the record has an 
// auto-incrementing primary key column.
//
// The timestamp values are also added to the record object if those fields
// exist.
//
// When existing records are saved via the save methods on a record object, 
// or via the saveAll method on a collection object or via the update* 
// methods on a Model object, the $updated_timestamp_column_name column in 
// the database automatically gets updated with the current timestamp.

////////////////////////////////////////////////////////////////////////////////
//NOTE: if you have a collection (an instance of \LeanOrm\Model\Collection) 
//      containing 2 or more new (not existing) records you can also efficiently 
//      save the new records by calling \LeanOrm\Model\Collection::saveAll(true). 
//      See the documentation for Collections for more details.
////////////////////////////////////////////////////////////////////////////////
```

## Deleting Data

There are four ways of deleting data from the database:

1. By fetching one or more existing records from the database into record objects and then calling the **delete** method on each Record object (NOTE: the data is deleted from the database but the Record object still contains the data and is automatically marked as new. To make sure the data is both deleted from the database and cleared out of the Record object the **delete** method on the Record object must be called with a boolean value of **true** as its first parameter). 

2. By fetching one or more existing records from the database into record objects stored in a Collection object, and then calling the **deleteAll** method on the Collection object. This will cause all the records in the collection to be deleted from the database, but the Record objects will still be in the Collection object with their data intact. **removeAll** should additionally be called on the Collection object to remove the Record objects from the Collection object.

3. By calling the **deleteMatchingDbTableRows** method on a Model object. This method does not involve the retrieval of Record objects, rather only the conditions for matching the rows of data in the database table to be deleted needs to be supplied to **deleteMatchingDbTableRows**. It accepts an associative array whose keys should be the names of the database table column names & whose values are the values to use in an equality test to match against the corresponding database column names. If you need more fine grained criteria other than an equality test to match records that need to be deleted, you should consider the fourth option below.

    >Note: there is also a **deleteSpecifiedRecord** method in the Model class which accepts a Record object as parameter and deletes the database row associated with the Record object, sets the primary key value of the Record object to null if the primary key field is auto-incrementing and also sets the **is_new** property of the Record object to the boolean value of true (NOTE: it does not clear all the other data in the Record object). The **deleteSpecifiedRecord** method does not really need to be called, since the delete method in the Record class calls it internally when **delete** is called on a Record object that needs to be deleted.

4. By using the **PDO** object returned by the **getPDO** method of the Model class to execute a **DELETE** SQL query directly on the database or using the **runQuery** method of the instance of **\LeanOrm\DBConnector** returned by the **getDbConnector** method of the Model class. You need to be very careful to make sure your deletion query targets the exact records you want to delete so that you don't accidentally delete data that should not be deleted.

> **NOTE:** You can use the Record class' **delete** method & the Collection class' **deleteAll** & **removeAll** methods to also delete fetched related data. 

Below are some code samples demonstrating how to delete data:

```php
<?php
$authorsModel = new AuthorsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');

// first insert 6 records into the authors table
 $authorsModel->insertMany(
    [
        ['name' => 'Joe Blow'],
        ['name' => 'Jill Blow'],
        ['name' => 'Jack Doe'],
        ['name' => 'Jane Doe'],
        ['name' => 'Jack Bauer'],
        ['name' => 'Jane Bauer'],
    ]
);
 
///////////////////////////////////////////////////////////////////
$joeBlowRecord = $authorsModel->fetchOneRecord(
                    $authorsModel->getSelect()
                                 ->where(' name = :name_val ', [ 'name_val' => 'Joe Blow'])
                );
// - Deletes record from the database table 
// - Flags the record object as new
// - Clears related data associated with the record object 
//      - (does not delete them from the database)
// - Removes the primary key field from the record object, 
//      - if it's an auto-incrementing field in the database table
// - Other remaining data in the record remains 
$joeBlowRecord->delete(false);

///////////////////////////////////////////////////////////////////
$jillBlowRecord = $authorsModel->fetchOneRecord(
                    $authorsModel->getSelect()
                                 ->where(' name = :name_val ', [ 'name_val' => 'Jill Blow'])
                );
// - Deletes record from the database table 
// - Flags the record object as new
// - Clears all data associated with the record object
$jillBlowRecord->delete(true);

///////////////////////////////////////////////////////////////////
$jackAndJaneDoe = $authorsModel->fetchRecordsIntoCollection(
                    $authorsModel->getSelect()
                                 ->where(
                                        ' name IN (:bar) ', // named paceholder for WHERE IN
                                        [ 'bar' => [ 'Jack Doe', 'Jane Doe' ]]
                                    )
                );

// - Delete records from the database 
// - Flags each record object as new
// - Clears related data associated with each record object 
//      - (does not delete them from the database)
// - Removes the primary key field from each record object, 
//      - if it's an auto-incrementing field in the database table
// - Other remaining data in each record remains 
// - Record objects remain in the collection
$jackAndJaneDoe->deleteAll();

// Removes all the record objects from the collection object
// If those record objects are not referenced via any other variable,
// they will be garbage collected when next PHP's garbage collection
// mechanism kicks in.
$jackAndJaneDoe->removeAll();

///////////////////////////////////////////////////////////////////

// Generates and executes the sql query below:
//  DELETE from authors where name in ('Jack Bauer', 'Jane Bauer');
$authorsModel->deleteMatchingDbTableRows(
                [
                    'name' => ['Jack Bauer', 'Jane Bauer']
                ]
            );

///////////////////////////////////////////////////////////////////

// For more complicated DELETE queries, use the PDO object
$pdo = $authorsModel->getPDO();
$data = ['start'=> '2022-12-31 21:10:20', 'end' => '2022-12-31 21:08:20'];
$sql = "DELETE FROM authors WHERE date_created < :start AND m_timestamp < :end";
$pdo->prepare($sql)->execute($data);

///////////////////////////////////////////////////////////////////
// The code below does the exact same thing as the PDO code above
$dbConnector = $authorsModel->getDbConnector();
// passing $authorsModel as the last argument is optional and only
// useful if you have query logging enabled and you want this query
// to be added to the query log entries for $authorsModel
$dbConnector->runQuery($sql, $data, $authorsModel);
```
## Updating Data

These are the ways of updating data in the database:

1. By fetching one or more existing records, modifying them & calling either the **save** or **saveInTransaction** method on each record.

2. By fetching one or more records into a Collection object, modifying those record objects contained in the collection & then calling the **saveAll** method on the collection object.

3. By fetching one or more records & calling the Model class' **updateSpecifiedRecord** method on each record. The Record class' **save** methods actually use **updateSpecifiedRecord** under the hood to save existing records, so you really should not need to be using the Model class' **updateSpecifiedRecord** method to update existing records.

4. By calling the Model class' **updateMatchingDbTableRows** method on any instance of the Model class. This method does not retrieve existing records from the database, it only generates & executes a SQL UPDATE statement with some equality criteria (e.g. WHERE colname = someval or colname in (val1,...,valN) or colname IS NULL) based on the arguments supplied to the method.

5. By using the **PDO** object returned by the **getPDO** method of the Model class to execute an **UPDATE** SQL query directly on the database or using the **runQuery** method of the instance of **\LeanOrm\DBConnector** returned by the **getDbConnector** method of the Model class. You need to be very careful to make sure your **UPDATE** query targets the exact records you want to update so that you don't accidentally update data that should not be updated.

Below are some code samples demonstrating how to update data:

```php
<?php
$authorsModel = new AuthorsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');


// first insert 6 records into the authors table
 $authorsModel->insertMany(
    [
        ['name' => 'Joe Blow'],
        ['name' => 'Jill Blow'],
        ['name' => 'Jack Doe'],
        ['name' => 'Jane Doe'],
        ['name' => 'Jack Bauer'],
        ['name' => 'Jane Bauer'],
    ]
);
 
///////////////////////////////////////////////////////////////////
$joeBlowRecord = $authorsModel->fetchOneRecord(
                    $authorsModel->getSelect()
                                 ->where(' name = :name_val ', ['name_val' => 'Joe Blow'])
                );

// Prepend a title to Joe Blow's name
$joeBlowRecord->name = 'Mr. ' . $joeBlowRecord->name;
$joeBlowRecord->save(); // update the record

///////////////////////////////////////////////////////////////////
$jackAndJaneDoe = $authorsModel->fetchRecordsIntoCollection(
                    $authorsModel->getSelect()
                                 ->where(
                                        ' name IN (:bar) ', 
                                        [ 'bar' => ['Jack Doe', 'Jane Doe'] ]
                                    )
                );

foreach ($jackAndJaneDoe as $record){

    // reverse the name of each record
    $record->name = strrev($record->name); 
}

// update all the modified records in the collection
$jackAndJaneDoe->saveAll();

///////////////////////////////////////////////////////////////////
$jillBlowRecord = $authorsModel->fetchOneRecord(
                    $authorsModel->getSelect()
                                 ->where(' name = :name_val ', ['name_val' => 'Jill Blow'])
                );

// reverse the name for this record
$jillBlowRecord->name = strrev($jillBlowRecord->name);

// update the record
$authorsModel->updateSpecifiedRecord($jillBlowRecord);

///////////////////////////////////////////////////////////////////

// Generates and executes the sql query below:
//  UPDATE authors set date_created = '20 minutes before now' where name in ('Jack Bauer', 'Jane Bauer');
$authorsModel->updateMatchingDbTableRows(
                [ 
                    'date_created' => date('Y-m-d H:i:s', strtotime("-20 minutes")) 
                ],
                [
                    'name' => ['Jack Bauer', 'Jane Bauer']
                ]
            );

///////////////////////////////////////////////////////////////////

// For more complicated UPDATE queries, use the PDO object
$pdo = $authorsModel->getPDO();
$data = ['start'=> '2022-12-31 21:10:20', 'end' => '2022-12-31 21:08:20'];
$sql = "UPDATE authors SET name = CONCAT(author_id, '-', name) WHERE date_created < :start AND m_timestamp < :end";
$pdo->prepare($sql)->execute($data);

///////////////////////////////////////////////////////////////////
// The code below does the exact same thing as the PDO code above
$dbConnector = $authorsModel->getDbConnector();
// passing $authorsModel as the last argument is optional and only
// useful if you have query logging enabled and you want this query
// to be added to the query log entries for $authorsModel
$dbConnector->runQuery($sql, $data, $authorsModel);
```

 ### The various methods for fetching data from the database can be found [here](#methods-for-fetching-data-from-the-database)


[<<< Previous](./indtroduction.md) | [Next >>>](./more-about-models.md)
