# Getting Started

- [Design Considerations](#design-considerations)
- [How it works](#how-it-works)
    - [Defining and Creating Model Objects](#defining-and-creating-model-objects)
    - [Creating Records & Inserting Data into the Database](#creating-records--inserting-data-into-the-database)
    - [Methods for Fetching data from the Database](#methods-for-fetching-data-from-the-database)
        - [Fetching data from the Database via fetchCol](#fetching-data-from-the-database-via-fetchcol)
        - [Fetching data from the Database via fetchOneRecord](#fetching-data-from-the-database-via-fetchonerecord)
        - [Fetching data from the Database via fetchPairs](#fetching-data-from-the-database-via-fetchpairs)

## Design Considerations

Applications using this package are:

- Expected to have each database table with a single preferably auto-incrementing numeric primary key column (composite primary keys are not supported; however a single primary key column that is non-numeric should still work)

## How it works

LeanOrm will create only one PDO connection to a specific database (ie. one PDO connection per unique dsn string). Creating one or more Model objects for one or more tables with the same dsn string will lead to the creation of only one PDO connection to the database; that one PDO connection will be shared amongst all model instances created with that same dsn string. If you use two different dsn strings, two PDO connections to the database(s) will be created by LeanOrm. Consequently, using three different dsn strings will lead to the creation of three different PDO database connections, and so on for four or more different dsn strings.

All examples are based on the schema below:

![Blog Schema](../demo/blog-db.png)

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
        'author_id' // primary key column name
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
 * @property mixed $author_id int unsigned NOT NULL
 * @property mixed $name varchar(255)
 * @property mixed $m_timestamp datetime NOT NULL
 * @property mixed $date_created datetime NOT NULL
 */
class AuthorRecord extends \LeanOrm\Model\Record {
    
    //put your code here
}
```

### Creating Records & Inserting Data into the Database

```php
<?php
$authorsModel = new AuthorsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');

//Method 1:
$newRecord = $authorsModel->createNewRecord(); // create a blank new record
$newRecord->name = 'Joe Blow'; // set a value for a column
$newRecord['name'] = 'Joe Blow'; // also sets a value for a column
$newRecord->save();

//Method 2:
$newRecord = $authorsModel->createNewRecord(); // create a blank new record
$newRecord->save([ 'name' => 'Joe Blow']); // inject data to the save method

//Method 3:
$newRecord = $authorsModel->createNewRecord([ 'name' => 'Joe Blow']);
$newRecord->save();

//Method 4:
$insertedData = $authorsModel->insert([ 'name' => 'Joe Blow']); // save to the DB
// $insertedData is an associative array of the data just inserted into the
// database. This data will include an auto-generated primary key value.
$existingRecord = $authorsModel->createNewRecord($insertedData)
                               ->markAsNotNew();

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

////////////////////////////////////////////////////////////////////////////////
//NOTE: if you have a collection (an instance of \LeanOrm\Model\Collection) 
//      containing 2 or more new (not existing) records you can also efficiently 
//      save the new records by calling \LeanOrm\Model\Collection::saveAll(true). 
//      See the documentation for Collections for more details.
////////////////////////////////////////////////////////////////////////////////
```

### Methods for Fetching data from the Database

The following methods for fetching data from the database are defined in **\GDAO\Model** which is extended by **\LeanOrm\Model**:

- __**fetchCol(?object $query = null): array**__
> selects data from a single database table's column and returns an array of the column values. By default, it selects data from the first column in a database table.

- __**fetchOneRecord(?object $query = null, array $relations_to_include = []): ?\GDAO\Model\RecordInterface**__
> selects a single row of data from a database table and returns it as an instance of **\LeanOrm\Model\Record** (or any of its subclasses). By default, it fetches the first row of data in a database table into a Record object.

- __**fetchPairs(?object $query = null): array**__
> selects data from two database table columns and returns an array whose keys are values from the first column and whose values are the values from the second column. By default, it selects data from the first two columns in a database table.

- __**fetchRecordsIntoArray(?object $query = null, array $relations_to_include = []): array**__
> selects one or more rows of data from a database table and returns them as instances of **\LeanOrm\Model\Record** (or any of its subclasses) inside an array. By default, it selects all rows of data in a database table and returns them as an array of record objects.

- __**fetchRecordsIntoCollection(?object $query = null, array $relations_to_include = []): \GDAO\Model\CollectionInterface**__
> selects one or more rows of data from a database table and returns them as instances of **\LeanOrm\Model\Record** (or any of its subclasses) inside an instance of **\LeanOrm\Model\Collection** (or any of its subclasses). By default, it selects all rows of data in a database table and returns them as a collection of record objects.

- __**function fetchRowsIntoArray(?object $query = null, array $relations_to_include = []): array**__
> selects one or more rows of data from a database table and returns them as associative arrays inside an array.

- __**fetchValue(?object $query = null): mixed**__
> selects a single value from a single column of a single row of data from a database table and returns the value (eg. as a string, or an appropriate data type). 

All these fetch methods accept a first argument which is a query object. LeanOrm uses [Aura\SqlQuery](https://github.com/auraphp/Aura.SqlQuery/blob/2.8.0/README.md) as its query object. You can create a query object to inject into each fetch method using the **getSelect(): \Aura\SqlQuery\Common\Select** method in **\LeanOrm\Model**. Read the documentation for [Aura\SqlQuery](https://github.com/auraphp/Aura.SqlQuery/blob/2.8.0/README.md) to figure out how to customize the sql queries executed by each fetch method. Some examples will be shown later on below.

Some of these fetch methods also accept a second argument called **$relations_to_include**. It is basically an array of relationship names for related data defined in the Model class. When you specify these relationship names in a fetch method, the fetch method will eager load the related data which would eliminate the need to issues N queries to fetch the related data for a specified defined relation for each fetched record which leads to the N+1 problem. For example, when fetching records from the authors table via the AuthorsModel, each author record / row can have one or more posts associated with it. If you do not specify that the posts for the author records be eager fetched during a fetch, then when you loop through the returned author records, additional queries will be issued to fetch the posts for each author. If we have 3 authors in the database, then doing a fetch without eager loading posts will lead to the following queries being issued when you loop through the authors and try to access the posts associated with each of them:

```sql
select * from authors
select * from posts where author_id = 1
select * from posts where author_id = 2
select * from posts where author_id = 3
```

If we eager load the posts during the call to the fetch method, then only the two queries below will be issued regardless of how many author records exist in the authors table. 

```sql
select * from authors
select * from posts where author_id IN ( ids of all the authors we are fetching)
```

The second query above is pseudo-code. It is used to grab all the posts needed and the fetch method will stitch the associated posts to the matching author records. This is the better and efficient way to load related data.

The following methods for fetching data from the database are NOT defined in **\GDAO\Model** but are only defined in **\LeanOrm\Model** (other Model classes directly extending **\GDAO\Model** may not implement them):

```php
public function fetch(
    array $ids, 
    ?\Aura\SqlQuery\Common\Select $select_obj=null, 
    array $relations_to_include=[], 
    bool $use_records=false, 
    bool $use_collections=false, 
    bool $use_p_k_val_as_key=false
): array|\LeanOrm\Model\Collection
```
> Selects one or more rows of data from a database table whose primary key values in the database table matches the primary key values specified in the **$ids** and returns them as instances of **\LeanOrm\Model\Record** (or any of its subclasses) inside an array or an instance of **\LeanOrm\Model\Collection** (or any of its subclasses) or returns them as an array of arrays.


```php
public function fetchRecordsIntoArrayKeyedOnPkVal(
    ?\Aura\SqlQuery\Common\Select $select_obj=null, 
    array $relations_to_include=[]
): array
```
> Works exactly like **fetchRecordsIntoArray**, except that each record in the returned array has a key whose value is the value of the record's primary key field, as opposed to the sequential 0 to N-1 keys which are present in the array returned by **fetchRecordsIntoArray**

```php
public function fetchRecordsIntoCollectionKeyedOnPkVal(
    ?\Aura\SqlQuery\Common\Select $select_obj=null, 
    array $relations_to_include=[]
): \GDAO\Model\CollectionInterface
```
> Works exactly like **fetchRecordsIntoCollection**, except that each record in the returned collection has a key whose value is the value of the record's primary key field, as opposed to the sequential 0 to N-1 keys which are present in the collection returned by **fetchRecordsIntoCollection**

```php
public function fetchRowsIntoArrayKeyedOnPkVal(
    ?\Aura\SqlQuery\Common\Select $select_obj=null, 
    array $relations_to_include=[]
): array
```
> Works exactly like **fetchRowsIntoArray**, except that each row in the returned array has a key whose value is the value of the row's primary key field, as opposed to the sequential 0 to N-1 keys which are present in the array returned by **fetchRowsIntoArray**

#### Fetching data from the Database via fetchCol

If you want to grab all the values for a specific database column in a database table, use the fetchCol method. Below are a few examples of how to use this method:

```php
<?php
$authorsModel = new AuthorsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');


// $colVals will contain all the values in the first column (i.e. author_id)
// of the authors table
$colVals = $authorsModel->fetchCol();

// $colVals will contain all the values in the specified column (i.e. name)
// of the authors table where the author_id <= 5
$colVals = $authorsModel->fetchCol(
                $authorsModel->getSelect()
                             ->cols(['name'])
                             ->where(' author_id <= ? ', 5)
            );
```

#### Fetching data from the Database via fetchOneRecord

If you want to fetch just one row of data from a database table into a record object, use the fetchOneRecord method. Below are a few examples of how to use this method:

```php
<?php
$authorsModel = new AuthorsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');

// $record will contain the first row of data returned by
// select authors.* from authors Limit 1;
$record = $authorsModel->fetchOneRecord();

// $record will contain the first row of data returned by
// select authors.author_id, authors.name from authors where author_id = 5;
$record = $authorsModel->fetchOneRecord(
            $authorsModel->getSelect()
                         ->cols(['author_id', 'name'])
                         ->where(' author_id = ? ', 5)
        );

// $record will contain the first row of data returned by
//   select authors.author_id, authors.name from authors where author_id = 5;
//      
// It will also contain a collection of posts records returned by
//   select posts.* from posts where author_id = 5;
$record = $authorsModel->fetchOneRecord(
            $authorsModel->getSelect()
                         ->cols(['author_id', 'name'])
                         ->where(' author_id = ? ', 5),
            ['posts'] // eager fetch posts for the author
        );
```

#### Fetching data from the Database via fetchPairs

If you want to fetch key value pair from two columns in a database table, use the fetchPairs method. A good example of when to use this method is when you want to generate a drop-down list of authors in your application where the author_id will be the value of each select option item and the author's name will be the display text for each select option item. Below are a few examples of how to use this method:

```php
<?php
$authorsModel = new AuthorsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');


```