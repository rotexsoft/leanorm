# More about Models

- [Introduction](#introduction)
- [Methods for Fetching data from the Database](#methods-for-fetching-data-from-the-database)
    - [Fetching data from the Database via fetchCol](#fetching-data-from-the-database-via-fetchcol)
    - [Fetching data from the Database via fetchOneRecord](#fetching-data-from-the-database-via-fetchonerecord)
    - [Fetching data from the Database via fetchOneByPkey](#fetching-data-from-the-database-via-fetchonebypkey)
    - [Fetching data from the Database via fetchPairs](#fetching-data-from-the-database-via-fetchpairs)
    - [Fetching data from the Database via fetchRecordsIntoArray](#fetching-data-from-the-database-via-fetchrecordsintoarray)
    - [Fetching data from the Database via fetchRecordsIntoArrayKeyedOnPkVal](#fetching-data-from-the-database-via-fetchrecordsintoarraykeyedonpkval)
    - [Fetching data from the Database via fetchRecordsIntoCollection](#fetching-data-from-the-database-via-fetchrecordsintocollection)
    - [Fetching data from the Database via fetchRecordsIntoCollectionKeyedOnPkVal](#fetching-data-from-the-database-via-fetchrecordsintocollectionkeyedonpkval)
    - [Fetching data from the Database via fetchRowsIntoArray](#fetching-data-from-the-database-via-fetchrowsintoarray)
    - [Fetching data from the Database via fetchRowsIntoArrayKeyedOnPkVal](#fetching-data-from-the-database-via-fetchrowsintoarraykeyedonpkval)
    - [Fetching data from the Database via fetchValue](#fetching-data-from-the-database-via-fetchvalue)
    - [Fetching data from the Database via fetch](#fetching-data-from-the-database-via-fetch)
- [Defining Relationships between Models and Working with Related Data](#defining-relationships-between-models-and-working-with-related-data)
    - [Belongs To](#belongs-to)
    - [Has One](#has-one)
    - [Has Many](#has-many)
    - [Has Many Through](#has-many-through-aka-many-to-many)
    - [Relationship Definition Code Samples](#relationship-definition-code-samples)
        - [Option 1: define the relationships for each instance after creating each instance](#option-1-define-the-relationships-for-each-instance-after-creating-each-instance)
        - [Option 2: define the relationships inside the constructor of each Model Class](#option-2-define-the-relationships-inside-the-constructor-of-each-model-class)
    - [Accessing Related Data Code Samples](#accessing-related-data-code-samples)
- [Query Logging](#query-logging)

## Introduction

The Model class generates all the SQL for accessing and manipulating data in the database; it uses the DBConnector class to execute the SQL statements. The Model class together with the DBConnector class act as a Table Data Gateway.

The Model class also acts as a Data Mapper by being able to map:

- a row of data in a database table to a Record object
- rows of data in a database table into a Collection object containing one or more Record objects
- foreign key relationship(s) between database tables into attribute(s) of a record object. Four relationship types are supported:
    1. Belongs-To 
    2. Has-One
    3. Has-Many
    4. Has-Many-Through a.k.a Many to Many)

There is also **\LeanOrm\CachingModel** class that is meant to cache method return values across
all instances of **\LeanOrm\CachingModel** and its sub-classes (on each invocation of a php script
via command line or a webserver) where possible to improve performance. You can use it instead of 
**\LeanOrm\Model** in your applications. The cached results do not persist between different
execution of your php script(s).

As at of the writing of this documentation, there are only two protected methods whose results
are being cached:
* **fetchTableListFromDB(): array**
* **fetchTableColsFromDB(string $table_name): array**

Other methods that could gain performance improvements will be added and documented as time goes on.

## Methods for Fetching data from the Database

> **WARNING:** When fetching data & trying to eager load related data, make sure the column on which the relationship is defined is amongst the columns you have specified to be selected in the fetch query (if you have chosen to explicitly specify columns to be returned by the fetch* method) because values from that column would be needed to fetch related data. If you don't specify any columns, then all columns (including the column on which the relationship is defined) in the table/view are returned.

The following methods for fetching data from the database are defined in **\GDAO\Model** which is extended by **\LeanOrm\Model**:

- [__**fetchCol(?object $query = null): array**__](#fetching-data-from-the-database-via-fetchcol)
> selects data from a single database table's / view's column and returns an array of the column values. By default, it selects data from the first column in a database table / view.

- [__**fetchOneRecord(?object $query = null, array $relations_to_include = []): ?\GDAO\Model\RecordInterface**__](#fetching-data-from-the-database-via-fetchonerecord)
> selects a single row of data from a database table / view and returns it as an instance of **\LeanOrm\Model\Record** (or any of its subclasses). By default, it fetches the first row of data in a database table / view into a Record object. This method returns null if the table or view is empty or the query doesn't match any record.

- [__**fetchOneByPkey($id, array $relations_to_include = []): ?\GDAO\Model\RecordInterface**__](#fetching-data-from-the-database-via-fetchonebypkey)
> selects a single row of data from a database table / view whose primary key value matches the specified primary key value in the **$id** parameter. For views, the primary key field will be whatever value is set in the Model class' **primary_col** property. This method returns an instance of **\LeanOrm\Model\Record** (or any of its subclasses). This method returns null if the table or view is empty or the specified primary key value doesn't match any record in the database table / view.

- [__**fetchPairs(?object $query = null): array**__](#fetching-data-from-the-database-via-fetchpairs)
> selects data from two database table / view columns and returns an array whose keys are values from the first column and whose values are the values from the second column. By default, it selects data from the first two columns in a database table / view.

- [__**fetchRecordsIntoArray(?object $query = null, array $relations_to_include = []): array**__](#fetching-data-from-the-database-via-fetchrecordsintoarray)
> selects one or more rows of data from a database table / view and returns them as instances of **\LeanOrm\Model\Record** (or any of its subclasses) inside an array. By default, it selects all rows of data in a database table / view and returns them as an array of record objects.

- [__**fetchRecordsIntoCollection(?object $query = null, array $relations_to_include = []): \GDAO\Model\CollectionInterface**__](#fetching-data-from-the-database-via-fetchrecordsintocollection)
> selects one or more rows of data from a database table / view and returns them as instances of **\LeanOrm\Model\Record** (or any of its subclasses) inside an instance of **\LeanOrm\Model\Collection** (or any of its subclasses). By default, it selects all rows of data in a database table / view and returns them as a collection of record objects.

- [__**function fetchRowsIntoArray(?object $query = null, array $relations_to_include = []): array**__](#fetching-data-from-the-database-via-fetchrowsintoarray)
> selects one or more rows of data from a database table / view and returns them as associative arrays inside an array. By default, it selects all rows of data in a database table / view and returns them as associative arrays inside an array.

- [__**fetchValue(?object $query = null): mixed**__](#fetching-data-from-the-database-via-fetchvalue)
> selects a single value from a single column of a single row of data from a database table / view and returns the value (eg. as a string, or an appropriate data type). By default, it selects the value of the first column of the first row of data from a database table / view.

All these fetch methods accept a first argument which is a query object. LeanOrm uses [Aura\SqlQuery](https://github.com/auraphp/Aura.SqlQuery/blob/3.x/docs/select.md) as its query object. You can create a query object to inject into each fetch method using the **getSelect(): \Aura\SqlQuery\Common\Select** method in **\LeanOrm\Model**. Read the documentation for [Aura\SqlQuery](https://github.com/auraphp/Aura.SqlQuery/blob/3.x/docs/select.md) to figure out how to customize the sql queries executed by each fetch method. Some examples will be shown later on below.

> NOTE: Please ALWAYS use named parameters / place-holders in all your queries, [Aura\SqlQuery](https://github.com/auraphp/Aura.SqlQuery/blob/3.x/docs/select.md) version 3.x was designed to work with named parameters / place-holders. DO NOT use question mark parameters / place-holders in your queries.

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
> Selects one or more rows of data from a database table / view whose primary key values in the database table / view matches the primary key values specified in the **$ids** and returns them as instances of **\LeanOrm\Model\Record** (or any of its subclasses) inside an array or an instance of **\LeanOrm\Model\Collection** (or any of its subclasses) or returns them as an array of arrays.


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

### Fetching data from the Database via fetchCol

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
                             ->where(' author_id <= :author_id_val ', ['author_id_val' => 5])
            );
```

### Fetching data from the Database via fetchOneRecord

If you want to fetch just one row of data from a database table into a record object, use the fetchOneRecord method. This method returns null if the table or view is empty or the query doesn't match any record. Below are a few examples of how to use this method:

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
                         ->where(' author_id = :author_id_val ', ['author_id_val' => 5])
        );

// $record will contain the first row of data returned by
//   select authors.author_id, authors.name from authors where author_id = 5;
//      
// It will also contain a collection of posts records returned by
//   select posts.* from posts where author_id = 5;
$record = $authorsModel->fetchOneRecord(
            $authorsModel->getSelect()
                         ->cols(['author_id', 'name'])
                         ->where(' author_id = :author_id_val ', ['author_id_val' => 5]),
            ['posts'] // eager fetch posts for the author
        );
```

### Fetching data from the Database via fetchOneByPkey

If you want to fetch just one row of data from a database table into a record object and you know the primary key value of the row of data you want to fetch, use the fetchOneByPkey method. This method returns null if the table or view is empty or the specified primary key value doesn't match any record. Below are a few examples of how to use this method:

```php
<?php
$authorsModel = new AuthorsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');

// $record will contain the first row of data returned by
// select authors.* from authors where author_id = 5;
$record = $authorsModel->fetchOneByPkey(5);

// $record will contain the first row of data returned by
//   select authors.* from authors where author_id = 5;
//      
// It will also contain a collection of posts records returned by
//   select posts.* from posts where author_id = 5;
$record = $authorsModel->fetchOneByPkey(
            5,
            ['posts'] // eager fetch posts for the author
        );
```

### Fetching data from the Database via fetchPairs

If you want to fetch key value pairs from two columns in a database table, use the fetchPairs method. A good example of when to use this method is when you want to generate a drop-down list of authors in your application where the author_id will be the value of each select option item and the author's name will be the display text for each select option item. Below are a few examples of how to use this method:

```php
<?php
$authorsModel = new AuthorsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');

// $keyValPairs will be an array whose keys have values from the first column (i.e. author_id)
// of the authors table and whose corresponding values have values from the second column (i.e. name)
// of the authors table.
$keyValPairs = $authorsModel->fetchPairs();

// $keyValPairs will be an array whose keys have values from the first specified column (i.e. author_id)
// of the authors table and whose corresponding values have values from the second specified column 
// (i.e. date_created) of the authors table where the author_id <= 5
$keyValPairs = $authorsModel->fetchPairs(
                $authorsModel->getSelect()
                             ->cols(['author_id', 'date_created'])
                             ->where(' author_id <= :author_id_val ', ['author_id_val' => 5])
            );

// Similar to example above, except that the second specified column is an expression
// (i.e. `concat(author_id, '-', 'name')` ). When using expressions in your fetch 
// method calls, try to use expressions supported by mysql, postgres, sqlite &
// sqlsrvr so that when you change your dsn to use any of the database engines
// your code will still work, if not, you would have to manually update your code
// to make it work when you change your dsn to a different database engine.
$keyValPairs = $authorsModel->fetchPairs(
                $authorsModel->getSelect()
                             ->cols(['author_id', " concat(author_id, '-', 'name') "])
                             ->where(' author_id <= :author_id_val ', ['author_id_val' => 5])
            );
```

### Fetching data from the Database via fetchRecordsIntoArray

If you want to fetch rows of data from a database table as record objects stored in an array whose keys start from 0 and end at N-1, then use the fetchRecordsIntoArray method. 

This method is slightly more memory efficient than fetchRecordsIntoCollection because the returned records & the related data (if any) are not injected into collection objects. You however lose the ability to call collection class methods on the returned result, but would have to individually call the record class methods on each record in the returned array to do things like save or delete each record after modification. Below are a few examples of how to use this method:

```php
<?php
$authorsModel = new AuthorsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');

// $records is an array containing the all rows of data as record objects returned by
// select authors.* from authors;
$records = $authorsModel->fetchRecordsIntoArray();

// $records is an array containing the all rows of data as record objects returned by
// select authors.author_id, authors.name from authors where author_id <= 5;
$records = $authorsModel->fetchRecordsIntoArray(
            $authorsModel->getSelect()
                         ->cols(['author_id', 'name'])
                         ->where(' author_id <= :author_id_val ', ['author_id_val' => 5])
        );

// $records is an array containing the all rows of data as record objects returned by
//   select authors.author_id, authors.name from authors where author_id <= 5;
//      
// Each record will also contain an array of associated posts records returned by
//   select posts.* from posts where author_id in 
//      (select authors.author_id from authors where author_id <= 5);
$records = $authorsModel->fetchRecordsIntoArray(
            $authorsModel->getSelect()
                         ->cols(['author_id', 'name'])
                         ->where(' author_id <= :author_id_val ', ['author_id_val' => 5]),
            ['posts'] // eager fetch posts for all the matching authors
        );
```

### Fetching data from the Database via fetchRecordsIntoArrayKeyedOnPkVal

If you want to fetch rows of data from a database table as record objects stored in an array whose keys are the primary key values of the matching rows of data in the database table, then use the fetchRecordsIntoArrayKeyedOnPkVal method.

This method works exactly like [fetchRecordsIntoArray](#fetching-data-from-the-database-via-fetchrecordsintoarray), except that the key values in the returned array of records are different.

> **NOTE:** This method is implemented in **\LeanOrm\Model** & not a part of **\GDAO\Model**. Sub-classes of **\GDAO\Model** that are not also sub-classes of **\LeanOrm\Model** are not guaranteed to implement it.

### Fetching data from the Database via fetchRecordsIntoCollection

If you want to fetch rows of data from a database table as record objects stored in a collection whose keys start from 0 and end at N-1, then use the fetchRecordsIntoCollection method. 

This method uses slightly more memory than other fetch methods that return multiple rows of data because every row of data (including related data) in the result returned by this method is stored in a record object and the record objects are stored in applicable collection objects. Using this method, however, allows you to be able call collection class methods on the collection returned by this method to do things like save all the records in the collection after performing some operations on the the records or delete all the records from the database if they are no longer needed, and so on, this also applies to the related data stored in collection objects for each record. Below are a few examples of how to use this method:

```php
<?php
$authorsModel = new AuthorsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');

// $records is a collection object containing the all rows of data as record objects returned by
// select authors.* from authors;
$records = $authorsModel->fetchRecordsIntoCollection();

// $records is a collection object containing the all rows of data as record objects returned by
// select authors.author_id, authors.name from authors where author_id <= 5;
$records = $authorsModel->fetchRecordsIntoCollection(
            $authorsModel->getSelect()
                         ->cols(['author_id', 'name'])
                         ->where(' author_id <= :author_id_val ', ['author_id_val' => 5])
        );

// $records is a collection object containing the all rows of data as record objects returned by
//   select authors.author_id, authors.name from authors where author_id <= 5;
//      
// Each record will also contain a collection object of associated posts records returned by
//   select posts.* from posts where author_id in 
//      (select authors.author_id from authors where author_id <= 5);
$records = $authorsModel->fetchRecordsIntoCollection(
            $authorsModel->getSelect()
                         ->cols(['author_id', 'name'])
                         ->where(' author_id <= :author_id_val ', ['author_id_val' => 5]),
            ['posts'] // eager fetch posts for all the matching authors
        );
```

### Fetching data from the Database via fetchRecordsIntoCollectionKeyedOnPkVal

If you want to fetch rows of data from a database table as record objects stored in a collection object whose keys are the primary key values of the matching rows of data in the database table, then use the fetchRecordsIntoCollectionKeyedOnPkVal method. 

Using this method allows you to be able call collection class methods on the collection returned by this method to do things like save all the records in the collection after performing some operations on the the records or delete all the records from the database if they are no longer needed, and so on.

This method works exactly like [fetchRecordsIntoCollection](#fetching-data-from-the-database-via-fetchrecordsintocollection), except that the key values in the returned collection of records are different.

> **NOTE:** This method is implemented in **\LeanOrm\Model** & not a part of **\GDAO\Model**. Sub-classes of **\GDAO\Model** that are not also sub-classes of **\LeanOrm\Model** are not guaranteed to implement it.

### Fetching data from the Database via fetchRowsIntoArray

If you want to fetch rows of data from a database table as associative arrays stored in an array whose keys start from 0 and end at N-1, then use the fetchRowsIntoArray method. 

This method is the most memory efficient method to fetch multiple rows of data from a database table because all the data are returned as native php arrays as opposed to other fetch methods that return each row of data as record objects and puts all those records into a collection or an array. Use this method if you just want to display the fetched data & don't need to update & save or delete the fetched data after processing. Below are a few examples of how to use this method:

```php
<?php
$authorsModel = new AuthorsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');

// $records is an array containing the all rows of data as associative arrays returned by
// select authors.* from authors;
$records = $authorsModel->fetchRowsIntoArray();

// $records is an array containing the all rows of data as associative arrays returned by
// select authors.author_id, authors.name from authors where author_id <= 5;
$records = $authorsModel->fetchRowsIntoArray(
            $authorsModel->getSelect()
                         ->cols(['author_id', 'name'])
                         ->where(' author_id <= :author_id_val ', ['author_id_val' => 5])
        );

// $records is an array containing the all rows of data as record objects returned by
//   select authors.author_id, authors.name from authors where author_id <= 5;
//      
// Each record will also contain an array of associated posts records returned by
//   select posts.* from posts where author_id in 
//      (select authors.author_id from authors where author_id <= 5);
$records = $authorsModel->fetchRowsIntoArray(
            $authorsModel->getSelect()
                         ->cols(['author_id', 'name'])
                         ->where(' author_id <= :author_id_val ', ['author_id_val' => 5]),
            ['posts'] // eager fetch posts for all the matching authors
        );
```

### Fetching data from the Database via fetchRowsIntoArrayKeyedOnPkVal

If you want to fetch rows of data from a database table as associative arrays stored in an array whose keys are the primary key values of the matching rows of data in the database table, then use the fetchRowsIntoArrayKeyedOnPkVal method. 

This method works exactly like [fetchRowsIntoArray](#fetching-data-from-the-database-via-fetchrowsintoarray), except that the key values in the returned array of associative arrays are different.

> **NOTE:** This method is implemented in **\LeanOrm\Model** & not a part of **\GDAO\Model**. Sub-classes of **\GDAO\Model** that are not also sub-classes of **\LeanOrm\Model** are not guaranteed to implement it.

### Fetching data from the Database via fetchValue

If you want to fetch a single value from a single column of a single row of data from a database table or a computed value from a database table, then use the fetchValue method. Below are a few examples of how to use this method:

```php
<?php
$authorsModel = new AuthorsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');

// $value is the value in the first column (i.e. author_id) and first row returned by
// select authors.* from authors;
$value = $authorsModel->fetchValue();

// $value is the value in the first column (i.e. name) and first row returned by
// select authors.name from authors;
$value = $authorsModel->fetchValue(
            $authorsModel->getSelect()
                         ->cols(['name'])
        );

// $value is the computed value returned by
// select max(author_id) from authors;
$value = $authorsModel->fetchValue(
            $authorsModel->getSelect()
                         ->cols([' max(author_id) '])
        );

// $value is the computed value returned by
// select max(author_id) from authors where author_id <= 5;
// Obviously, this value will always be <= 5
$value = $authorsModel->fetchValue(
            $authorsModel->getSelect()
                         ->cols(['max(author_id)'])
                         ->where(' author_id <= :author_id_val ', ['author_id_val' => 5])
        );

// NOTE: if the database table is empty or the select query returns no row(s) of 
//       data, then fetchValue will return NULL
```

### Fetching data from the Database via fetch

The fetch method is a convenience method that you can use when you know the primary key values of the records you want to fetch from a database table. You just supply the primary key values (in an array) of the records you want to fetch, as its first argument. You can also inject a query object to further customize the query that's used to fetch the desired data under the hood. It calls one of the following methods below depending on the other arguments supplied to it when it's called:
- [**fetchRecordsIntoCollection**](#fetching-data-from-the-database-via-fetchrecordsintocollection)
- [**fetchRecordsIntoCollectionKeyedOnPkVal**](#fetching-data-from-the-database-via-fetchrecordsintocollectionkeyedonpkval)
- [**fetchRecordsIntoArray**](#fetching-data-from-the-database-via-fetchrecordsintoarray)
- [**fetchRecordsIntoArrayKeyedOnPkVal**](#fetching-data-from-the-database-via-fetchrecordsintoarraykeyedonpkval)
- [**fetchRowsIntoArray**](#fetching-data-from-the-database-via-fetchrowsintoarray)
- [**fetchRowsIntoArrayKeyedOnPkVal**](#fetching-data-from-the-database-via-fetchrowsintoarraykeyedonpkval)

See source code documentation for **\LeanOrm\Model::fetch** to understand how to use this method. The query object that you can inject as a second argument to this method works exactly like all the query objects in the prior code samples above.

> **NOTE:** This method is implemented in **\LeanOrm\Model** & not a part of **\GDAO\Model**. Sub-classes of **\GDAO\Model** that are not also sub-classes of **\LeanOrm\Model** are not guaranteed to implement it.


## Defining Relationships between Models and Working with Related Data

LeanOrm allows you to define relationships between Model classes. These relationships usually mirror the foreign key relationships between the underlying database tables associated with the models. You can also define relationships between Model classes that represent database views, even though views don't have foreign key definitions at the database levels.

> Just as LeanOrm does not work with tables with composite primary keys, it likewise does not support composite keys for defining relationship between tables, the relationships are defined on a single column in each table.

> When calling Model methods for defining relationships, it is strongly recommended that you PHP 8's named argument syntax for passing arguments to these methods, because these methods have a fair amount of arguments that can be passed to them and using named arguments will make your relationship definition code clearer and easier to understand.

The schema below will be used in the examples to demonstrate how to define relationships.

![Blog Schema](../demo/blog-db.png)

Four types of relationships are supported:

### Belongs-To
Each row of data in a database table / view belongs to only one row of data in another database table / view. For example, if you have two tables, authors and posts, an author record would belong to a post if there is a post_id field in the authors table. If the authors table doesn't have a post_id field (which is the case in the schema diagram above) and instead the posts table has an author_id field (which is also the case in the schema diagram above), then a post record would belong to an author. Where the foreign key column is located is what determines which entity belongs to the other.


### Has-One 
Each row of data in a database table / view has zero or only one row of data in another database table / view. For example, if you have two tables, posts and summaries, a summary record has one post if there is a summary_id field in the posts table. If the posts table doesn't have a summary_id field (which is the case in the schema diagram above) and instead the summaries table has a post_id field (which is also the case in the schema diagram above), this means that a post record has zero or one summary. Where the foreign key column is located is what determines which entity owns the other. This type of relationship is also a variant of **Has-Many**, in which the many is just one and only one record.

### Has-Many
Each row in a Table A, is related to zero or more rows in another Table B. Each row in table B is related to only one row in Table A.
- Each row in Table A is related to zero or many (has many) rows in Table B 
- Each row in Table B, belongs to exactly one row in Table A
- In the sample schema above, an author can have zero or many posts, while each post always only belongs to an author

### Has-Many-Through a.k.a Many to Many) 
This type of relationship requires at least three tables. Basically many records in Table A can be associated with many records in another Table C. Similarly many records in Table C can be associated with many records in Table A. The associations are defined in an intermediary Table B. 
- In the sample schema above, a **post** record can have many **tags** and a **tag** record can have many **posts** and these relationships are defined in the **posts_tags** table (also known as a join table).

> For the purpose of this documentation, we will call the Model class that we are trying to define a relationship on as the native Model and the other Model (whose row(s) / record(s) are to be returned when the relationship is executed) as the foreign Model.

**\LeanOrm\Model** has four instance methods for defining relationships between Models:

- **belongsTo**
- **hasOne**
- **hasMany**
- **hasManyThrough**

There a two recommended ways of defining relationships between Models.

1. After creating an instance of **\LeanOrm\Model** or any of its sub-classes, you should then go on to define relationships for that instance by calling the appropriate relationship defining methods (belongsTo, hasOne, hasMany or hasManyThrough) on that instance as needed. If you architect your application to only create a single Model instance for each table / view in your database, this approach would work well for you. It is recommended that you call the relationship definition methods immediately after creating each Model object. For example, if you manage objects in your application using a dependency injection container, then you should put the relationship definition method calls wherever each Model object is being created in your container setup code.
    > If you create more than one Model instance for each table / view in your database, then that means you will have to call the relationship definition methods on each Model instance for each database table / view, which will lead to lots of duplicate code scattered in your code-base. You should instead use the second method method of defining relationships described below if you have created individual Model classes (and optionally, Collection & Record Classes) for each database table  / view you intend to access in your application

2. Define relationships inside the constructor of each Model class (which should each be a sub-class of **\LeanOrm\Model**). If you intend to use direct instances of **\LeanOrm\Model** for each table / view in your database, this technique will not work for you, you will only be able to use option 1 above in that scenario. You only need to have created a unique Model class for each database table / view to use this option. You don't really need to have defined corresponding Collection  & Record classes to pair with each Model class, it is perfectly fine for those Model classes to use 
**\LeanOrm\Model\Record** & **\LeanOrm\Model\Collection** to store data from the database. 
    > With this option, you only need to define the relationships once in each Model class's constructor & every instance of each Model class will already have those relationships defined immediately after instantiation.


### Relationship Definition Code Samples


We will demonstrate via code samples the various ways to define Model relationships using the schema shown earlier in this section for concrete examples.

#### Option 1: define the relationships for each instance after creating each instance.

An Author can have many Posts. Here's how to model that relationship on a Model instance associated with the **authors** table:


```php

$authorsModel = new \LeanOrm\Model(
    "mysql:host=localhost;dbname=blog", // dsn string
    "username", // username
    "password", // password
    [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'], //pdo options
    'author_id', // primary key column name
    'authors' // table name
);

$authorsModel->hasMany(
    relation_name: 'posts', 
    relationship_col_in_my_table: 'author_id', 
    relationship_col_in_foreign_table: 'author_id',
    foreign_table_name: 'posts',
    primary_key_col_in_foreign_table: 'post_id'
);

// When you fetch all authors,
// you can eagerly load all their associated posts like so
$authorsWithPosts = $authorsModel->fetchRowsIntoArray(null, ['posts']);

//////////////////////////////////////////////////////
//////////////////////////////////////////////////////

class PostsCollection extends \LeanOrm\Model\Collection { }

class PostRecord extends \LeanOrm\Model\Record { }

class PostsModel extends \LeanOrm\Model {
    
    protected ?string $collection_class_name = PostsCollection::class;
    protected ?string $record_class_name = PostRecord::class;
    protected string $primary_col = 'post_id';
    protected string $table_name = 'posts';

    public function __construct(
        string $dsn = '', 
        string $username = '', 
        string $passwd = '', 
        array $pdo_driver_opts = [], 
        string $primary_col_name = '', 
        string $table_name = ''
    ) { 
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
    }
}

// If we had the  Model Class above already created, we could further 
// shorten our relationship definition by omitting the foreign_table_name &
// primary_key_col_in_foreign_table parameters in the call to hasMany and just
// add the foreign_models_class_name parameter like so:

$authorsModel->hasMany(
    relation_name: 'posts', 
    relationship_col_in_my_table: 'author_id', 
    relationship_col_in_foreign_table: 'author_id',
    foreign_models_class_name: PostsModel::class
); // does the same thing as the previous call to hasMany above

```

A Post can belong to a single Author. Here's how to model that relationship on a Model instance associated with the **posts** table:

```php
$postsModel = new \LeanOrm\Model(
    "mysql:host=localhost;dbname=blog", // dsn string
    "username", // username
    "password", // password
    [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'], //pdo options
    'post_id', // primary key column name
    'posts' // table name
);

$postsModel->belongsTo(
    relation_name: 'author', 
    relationship_col_in_my_table: 'author_id', 
    relationship_col_in_foreign_table: 'author_id',
    foreign_table_name: 'authors',
    primary_key_col_in_foreign_table: 'author_id'
);

// When you fetch all posts,
// you can eagerly load each author each post belongs to like so
$postsWithAssociatedAuthor = 
    $postsModel->fetchRowsIntoArray(null, ['author']);

//////////////////////////////////////////////////////
//////////////////////////////////////////////////////

class AuthorsCollection extends \LeanOrm\Model\Collection { }

class AuthorRecord extends \LeanOrm\Model\Record { }

class AuthorsModel extends \LeanOrm\Model {
    
    protected ?string $collection_class_name = AuthorsCollection::class;
    protected ?string $record_class_name = AuthorRecord::class;
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
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
    }
}

// If we had the  Model Class above already created, we could further 
// shorten our relationship definition by omitting the foreign_table_name &
// primary_key_col_in_foreign_table parameters in the call to belongsTo 
// and just add the foreign_models_class_name parameter like so:

$postsModel->belongsTo(
    relation_name: 'author', 
    relationship_col_in_my_table: 'author_id', 
    relationship_col_in_foreign_table: 'author_id',
    foreign_models_class_name: AuthorsModel::class
);
```

A Post can have many tags via associations in a **posts_tags** join table. Here's how to model that relationship on a Model instance associated with the **posts** table:

```php
$postsModel = new \LeanOrm\Model(
    "mysql:host=localhost;dbname=blog", // dsn string
    "username", // username
    "password", // password
    [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'], //pdo options
    'post_id', // primary key column name
    'posts' // table name
);

$postsModel->hasManyThrough(
    relation_name: 'tags',
    col_in_my_table_linked_to_join_table: 'post_id',
    join_table: 'posts_tags',
    col_in_join_table_linked_to_my_table: 'post_id',
    col_in_join_table_linked_to_foreign_table: 'tag_id',
    col_in_foreign_table_linked_to_join_table: 'tag_id',
    foreign_table_name: 'tags',            
    primary_key_col_in_foreign_table: 'tag_id'
);

// When you fetch all posts,
// you can eagerly load their associated tags like so
$postsWithTags = 
    $postsModel->fetchRowsIntoArray(null, ['tags']);

//////////////////////////////////////////////////////
//////////////////////////////////////////////////////

class TagsCollection extends \LeanOrm\Model\Collection { }

class TagRecord extends \LeanOrm\Model\Record { }

class TagsModel extends \LeanOrm\Model {
    
    protected ?string $collection_class_name = TagsCollection::class;
    protected ?string $record_class_name = TagRecord::class;
    protected string $primary_col = 'tag_id';
    protected string $table_name = 'tags';

    public function __construct(
        string $dsn = '', 
        string $username = '', 
        string $passwd = '', 
        array $pdo_driver_opts = [], 
        string $primary_col_name = '', 
        string $table_name = ''
    ) { 
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
    }
}

// If we had the  Model Class above already created, we could further 
// shorten our relationship definition by omitting the foreign_table_name &
// primary_key_col_in_foreign_table parameters in the call to hasManyThrough 
// and just add the foreign_models_class_name parameter like so:

$postsModel->hasManyThrough(
    relation_name: 'tags',
    col_in_my_table_linked_to_join_table: 'post_id',
    join_table: 'posts_tags',
    col_in_join_table_linked_to_my_table: 'post_id',
    col_in_join_table_linked_to_foreign_table: 'tag_id',
    col_in_foreign_table_linked_to_join_table: 'tag_id',
    foreign_models_class_name: TagsModel::class
);
```

A Post can have one and only one summary. Here's how to model that relationship on a Model instance associated with the **posts** table:

```php
$postsModel = new \LeanOrm\Model (
    "mysql:host=localhost;dbname=blog", // dsn string
    "username", // username
    "password", // password
    [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'], //pdo options
    'post_id', // primary key column name
    'posts' // table name
);

$postsModel->hasOne(
    relation_name: 'summary', 
    relationship_col_in_my_table: 'post_id', 
    relationship_col_in_foreign_table: 'post_id',
    foreign_table_name: 'summaries',
    primary_key_col_in_foreign_table: 'summary_id'
); // Post has one Summary

// When you fetch all posts,
// you can eagerly load each associated summary like so
$postsWithEachSummary = 
    $postsModel->fetchRowsIntoArray(null, ['summary']);

//////////////////////////////////////////////////////
//////////////////////////////////////////////////////

class SummariesCollection extends \LeanOrm\Model\Collection { }

class SummaryRecord extends \LeanOrm\Model\Record { }

class SummariesModel extends \LeanOrm\Model {
    
    protected ?string $collection_class_name = SummariesCollection::class;
    protected ?string $record_class_name = SummaryRecord::class;
    protected string $primary_col = 'summary_id';
    protected string $table_name = 'summaries';

    public function __construct(
        string $dsn = '', 
        string $username = '', 
        string $passwd = '', 
        array $pdo_driver_opts = [], 
        string $primary_col_name = '', 
        string $table_name = ''
    ) { 
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
    }
}

// If we had the Model Class above already created, we could further 
// shorten our relationship definition by omitting the foreign_table_name &
// primary_key_col_in_foreign_table parameters in the call to hasOne 
// and just add the foreign_models_class_name parameter like so:

$postsModel->hasOne(
    relation_name: 'summary', 
    relationship_col_in_my_table: 'post_id', 
    relationship_col_in_foreign_table: 'post_id',
    foreign_models_class_name: SummariesModel::class
); // Post has one Summary
```


#### Option 2: define the relationships inside the constructor of each Model Class. 


It is assumed that you have generated a Model class for each database table  / view you want your application to access.

We will define:

1. An author has many posts relationship inside the AuthorsModel class
2. A post belongs to an author relationship inside the PostsModel class
3. A post has many tags through the posts_tags join table relationship inside the PostsModel class
4. A post has one summary relationship inside the PostsModel class

```php
class AuthorsCollection extends \LeanOrm\Model\Collection { }

class AuthorRecord extends \LeanOrm\Model\Record { }

class AuthorsModel extends \LeanOrm\Model {
    
    protected ?string $collection_class_name = AuthorsCollection::class;
    protected ?string $record_class_name = AuthorRecord::class;
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
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
        
        $this->hasMany(
            relation_name: 'posts', 
            relationship_col_in_my_table: 'author_id', 
            relationship_col_in_foreign_table: 'author_id',
            foreign_models_class_name: PostsModel::class
        ); // Author has Many Posts
    }
}

class PostsCollection extends \LeanOrm\Model\Collection { }

class PostRecord extends \LeanOrm\Model\Record { }

class PostsModel extends \LeanOrm\Model {
    
    protected ?string $collection_class_name = PostsCollection::class;
    protected ?string $record_class_name = PostRecord::class;
    protected string $primary_col = 'post_id';
    protected string $table_name = 'posts';

    public function __construct(
        string $dsn = '', 
        string $username = '', 
        string $passwd = '', 
        array $pdo_driver_opts = [], 
        string $primary_col_name = '', 
        string $table_name = ''
    ) { 
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
        
        $this->belongsTo(
            relation_name: 'author', 
            relationship_col_in_my_table: 'author_id', 
            relationship_col_in_foreign_table: 'author_id',
            foreign_models_class_name: AuthorsModel::class
        ); // Post belongs to an Author
        
        $this->hasOne(
            relation_name: 'summary', 
            relationship_col_in_my_table: 'post_id', 
            relationship_col_in_foreign_table: 'post_id',
            foreign_models_class_name: SummariesModel::class
        ); // Post has one Summary
        
        $this->hasManyThrough(
            relation_name: 'tags',
            col_in_my_table_linked_to_join_table: 'post_id',
            join_table: 'posts_tags',
            col_in_join_table_linked_to_my_table: 'post_id',
            col_in_join_table_linked_to_foreign_table: 'tag_id',
            col_in_foreign_table_linked_to_join_table: 'tag_id',
            foreign_models_class_name: TagsModel::class
        ); // Post has many Tags through the posts_tags join table
    }
}

class SummariesCollection extends \LeanOrm\Model\Collection { }

class SummaryRecord extends \LeanOrm\Model\Record { }

class SummariesModel extends \LeanOrm\Model {
    
    protected ?string $collection_class_name = SummariesCollection::class;
    protected ?string $record_class_name = SummaryRecord::class;
    protected string $primary_col = 'summary_id';
    protected string $table_name = 'summaries';

    public function __construct(
        string $dsn = '', 
        string $username = '', 
        string $passwd = '', 
        array $pdo_driver_opts = [], 
        string $primary_col_name = '', 
        string $table_name = ''
    ) { 
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
    }
}

class TagsCollection extends \LeanOrm\Model\Collection { }

class TagRecord extends \LeanOrm\Model\Record { }

class TagsModel extends \LeanOrm\Model {
    
    protected ?string $collection_class_name = TagsCollection::class;
    protected ?string $record_class_name = TagRecord::class;
    protected string $primary_col = 'tag_id';
    protected string $table_name = 'tags';

    public function __construct(
        string $dsn = '', 
        string $username = '', 
        string $passwd = '', 
        array $pdo_driver_opts = [], 
        string $primary_col_name = '', 
        string $table_name = ''
    ) { 
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
    }
}

// Later on in your application when you create Model instances
// You can fetch the related data like so:

$authorsModel = new AuthorsModel(
    "mysql:host=localhost;dbname=blog", // dsn string
    "username", // username
    "password", // password
    [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'], //pdo options
    'author_id', // primary key column name
    'authors' // table name
);
$authorsWithPosts = 
    $authorsModel->fetchRowsIntoArray(null, ['posts']);


$postsModel = new PostsModel(
    "mysql:host=localhost;dbname=blog", // dsn string
    "username", // username
    "password", // password
    [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'], //pdo options
    'post_id', // primary key column name
    'posts' // table name
);
$postsWithAuthorSummaryAndTags = 
    $postsModel->fetchRowsIntoArray(null, ['author', 'summary', 'tags']);

```


All the relationship definition methods (belongsTo, hasOne, hasMany & hasManyThrough) have an optional argument named **sql_query_modifier** which they can all accept. This argument is supposed to be a callback function that can be used to modify the query object used for fetching related data. The callable should have the syntax below:

```php
function(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select {
    
    // call some methods on $selectObj

    return $selectObj; // and finally return $selectObj
} // Optional callback to manipulate query object used to fetch related data
```

Using one of the relationship definition examples above, we can further ensure in the call to **hasMany** defining the Author has many Posts relationship   that the Posts returned when fetching authors and their associated posts will have the posts returned in reverse chronological order using the code below:

```php
$authorsModel = new \LeanOrm\Model(
    "mysql:host=localhost;dbname=blog", // dsn string
    "username", // username
    "password", // password
    [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'], //pdo options
    'author_id', // primary key column name
    'authors' // table name
);

$authorsModel->hasMany(
    relation_name: 'posts', 
    relationship_col_in_my_table: 'author_id', 
    relationship_col_in_foreign_table: 'author_id',
    foreign_models_class_name: PostsModel::class,
    sql_query_modifier: function(\Aura\SqlQuery\Common\Select $selectObj): \Aura\SqlQuery\Common\Select {

        // This will ensure that the posts associated
        // with each author are sorted in reverse
        // chronological order
        $selectObj->orderBy(['date_created DESC']);

        return $selectObj; // and finally return $selectObj
    } // Optional callback to manipulate query object used to fetch related data
);
```

> Take a look at the relationship definition methods (belongsTo, hasOne, hasMany & hasManyThrough) in **\LeanOrm\Model** to see all the arguments each method accepts to get ideas on how to further configure your Model relationship definitions in your applications.

### Accessing Related Data Code Samples

The code samples in this section build on the code samples in the [Relationship Definition Code Samples](#relationship-definition-code-samples) section above.

In order to access related data, you must call one of the **fetch*** methods that return any one of these:
- a single record ([**fetchOneByPkey**](#fetching-data-from-the-database-via-fetchonebypkey)),
- a single record ([**fetchOneRecord**](#fetching-data-from-the-database-via-fetchonerecord)), 
- array of records ([**fetchRecordsIntoArray**](#fetching-data-from-the-database-via-fetchrecordsintoarray)), 
- collection of records ([**fetchRecordsIntoCollection**](#fetching-data-from-the-database-via-fetchrecordsintocollection))
- or an array of arrays ([**fetchRowsIntoArray**](#fetching-data-from-the-database-via-fetchrowsintoarray). Each sub-array represents a db table row of data) 

You can either 
1. eager-load the related data when any of the earlier mentioned **fetch*** methods is called on an instance of any model class, which is the most efficient way of loading related data as it leads to only one additional query per relationship that you have specified to be eager loaded. For fetch methods that return arrays of arrays, you must eager-load the related data you want when the fetch method is called. This is the only way to make related data available in the array of arrays returned by fetch methods that return an array of arrays. 
    * For example, using the relationships defined in the **PostsModel** class in the [Relationship Definition Code Samples](#relationship-definition-code-samples) section above, if you specify that you want the following (**author**, **summary**, **comments** & **tags**) related data to be eager-loaded when fetching post records or array of arrays containing posts table data, then
        - one query would be issued to fetch the desired posts records
        - one query would be issued to fetch the related **author** data for all the desired post records
         one query would be issued to fetch the related **summary** data for all the desired post records
        - one query would be issued to fetch the related **comments** data for all the desired post records
        - one query would be issued to fetch the related **tags** data for all the desired post records
        - finally leading to a total of 5 queries issued to fetch the desired post records and all the associated **author**, **summary**, **comments** & **tags** data. Note that the fetch method stitches the associated related data to each returned record or array representing a row of data from the posts db table.
         
2. load the related data for each record returned when any of the earlier mentioned **fetch*** methods is called and there were no relationship names specified for eager-loading when the fetch method was called on an instance of any model class. This option only applies to fetch methods that return a single record object, an array of record objects or a collection of record objects. This is an inefficient way of loading related data because an additional query is issued for each desired related data for each record.
    - For example, if you fetched 5 records from the posts table without eager-loading any of their related data when the fetch method was called, when you loop through the 5 records, for each record,
        - one extra query will be issued to get the related **author** data for  each post record if you access the **author** property on each post record object while looping
        - one extra query will be issued to get the related **summary** data for  each post record if you access the **summary** property on each post record object while looping
        - one extra query will be issued to get the related **comments** data for  each post record if you access the **comments** property on each post record object while looping
        - one extra query will be issued to get the related **tags** data for  each post record if you access the **tags** property on each post record object while looping
        - this means that 4 additional queries are issued to get the related data for each of the 5 post records which leads to a total of 1 + (5 x 4) = 21 queries to access the 5 post records and all their related **author**, **summary**, **comments** & **tags** data. The 1 is for the query issued by the fetch method to retrieve the 5 post records. This is clearly less efficient than eager-loading as eager loading all the four relationships (**author**, **summary**, **comments** & **tags**)  would have led to only 5 queries being issued to fetch the 5 post records and all the related data.

Eager-loading code samples are shown below. If you remove the array of relationship names to eager-load (supplied to the various fetch method calls in the code samples below) from each call to fetch methods that return a single record, array of records or collection of records, then the less-efficient non-eager-loading behavior described in point 2 above will kick in when you try to access each related data property of each record returned by the fetch method. 

> **NOTE:** You cannot eager-load related data when **fetchCol**, **fetchPairs** or **fetchValue** is called on any instance of **\LeanOrm\Model** or its sub-classes.

> See [Fetching Nested Related Data](./more-about-collections.md#fetching-nested-related-data) for examples of how to load data related to fetched related data.

```php
<?php

$postsModel = new PostsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');


////////////////////////////////////////////////////////////////////////////////
// Fetching records & eager-loading related data via fetchRecordsIntoArray
////////////////////////////////////////////////////////////////////////////////

$allPostRecordsInAnArray = 
    $postsModel->fetchRecordsIntoArray(
        null, // we are not injecting a query obj, default
              //    select * from posts 
              // query will be issued
        ['author', 'summary', 'comments', 'tags'] // related data to eager-load
                                                  // 4 additional queries
    );

foreach ($allPostRecordsInAnArray as $postRecord) {
    
    echo 'Post: ' . $postRecord->title . PHP_EOL;
    
    ////////////////////////////////////////////////////////////////////////////
    // BelongsTo: a post belongs to an author, there can never be a post without 
    // an author
    ////////////////////////////////////////////////////////////////////////////
    
    echo 'Author: ' . $postRecord->author->name  . PHP_EOL;
    
    ////////////////////////////////////////////////////////////////////////////
    // HasOne: a post can have zero or one summary, there can be a post without 
    // a summary. Check if a related summary record was found for the current 
    // post record. If the post doesn't have a summary, $postRecord->summary 
    // will be NULL.
    ////////////////////////////////////////////////////////////////////////////
    
    if($postRecord->summary instanceof SummaryRecord) {
        
        echo 'Summary ID: ' . $postRecord->summary->summary_id  . PHP_EOL;
        
    } else {
        
        echo 'No Summary'  . PHP_EOL;
    }
    
    ////////////////////////////////////////////////////////////////////////////
    // HasMany: a post can have zero, one or more comments. Because we called 
    // fetchRecordsIntoArray to fetch the post records, the hasMany related 
    // data for each post record will also be records stored in an array and if
    // in this case, a record does not have any comments, $postRecord->comments 
    // will have a value of []
    ////////////////////////////////////////////////////////////////////////////
    
    echo 'Comments: '  . PHP_EOL;
    
    /** @var CommentRecord $comment */
    foreach($postRecord->comments as $comment) {
        
        echo "\tComment # {$comment->comment_id}: {$comment->name} "  . PHP_EOL;
    }
    
    ////////////////////////////////////////////////////////////////////////////
    // hasManyThrough: a post can have zero, one or more tags through the 
    // associations defined in the posts_tags table. Because we called 
    // fetchRecordsIntoArray to fetch the post records, the hasManyThrough 
    // related data for each post record will also be records stored in an 
    // array and if in this case, a record does not have any tags,
    // $postRecord->tags will have a value of []
    ////////////////////////////////////////////////////////////////////////////
    
    echo 'Tags: '  . PHP_EOL;
    
    /** @var TagRecord $tag */
    foreach($postRecord->tags as $tag) {
        
        echo "\tTag # {$tag->tag_id}: {$tag->name} "  . PHP_EOL;
    }
    
    echo PHP_EOL;
    
} // foreach ($allPostRecordsInAnArray as $postRecord)


////////////////////////////////////////////////////////////////////////////////
// Fetching records & eager-loading related data via fetchRecordsIntoCollection
////////////////////////////////////////////////////////////////////////////////

echo '//////////////////////////////////////////////////////////////' . PHP_EOL;

$allPostRecordsInACollection = 
    $postsModel->fetchRecordsIntoCollection(
        null, // we are not injecting a query obj, default
              //    select * from posts 
              // query will be issued
        ['author', 'summary', 'comments', 'tags'] // related data to eager-load
                                                  // 4 additional queries
    );

foreach ($allPostRecordsInACollection as $postRecord) {
    
    echo 'Post: ' . $postRecord->title . PHP_EOL;
    
    ////////////////////////////////////////////////////////////////////////////
    // BelongsTo: a post belongs to an author, there can never be a post without 
    // an author
    ////////////////////////////////////////////////////////////////////////////
    
    echo 'Author: ' . $postRecord->author->name  . PHP_EOL;
    
    ////////////////////////////////////////////////////////////////////////////
    // HasOne: a post can have zero or one summary, there can be a post without 
    // a summary. Check if a related summary record was found for the current 
    // post record. If the post doesn't have a summary, $postRecord->summary 
    // will be NULL.
    ////////////////////////////////////////////////////////////////////////////
    
    if($postRecord->summary instanceof SummaryRecord) {
        
        echo 'Summary ID: ' . $postRecord->summary->summary_id  . PHP_EOL;
        
    } else {
        
        echo 'No Summary'  . PHP_EOL;
    }
    
    ////////////////////////////////////////////////////////////////////////////
    // HasMany: a post can have zero, one or more comments. Because we called 
    // fetchRecordsIntoCollection to fetch the post records, the hasMany related 
    // data for each post record will also be records stored in a collection 
    // (an instance of CommentsCollection in this case) and if in this case, a 
    // record does not have any comments, $postRecord->comments will still be a 
    // collection (still an instance of CommentsCollection in this case) that 
    // has no records. You can call collection methods on $postRecord->comments
    ////////////////////////////////////////////////////////////////////////////
    
    echo 'Comments: '  . PHP_EOL;
    
    /** @var CommentRecord $comment */
    foreach($postRecord->comments as $comment) {
        
        echo "\tComment # {$comment->comment_id}: {$comment->name} "  . PHP_EOL;
    }
    
    ////////////////////////////////////////////////////////////////////////////
    // hasManyThrough: a post can have zero, one or more tags through the 
    // associations defined in the posts_tags table. Because we called 
    // fetchRecordsIntoCollection to fetch the post records, the hasManyThrough 
    // related data for each post record will also be records stored in a 
    // collection (an instance of TagsCollection in this case) and if in this 
    // case, a record does not have any tags, $postRecord->tags will still be a
    // collection (still an instance of TagsCollection in this case) that
    // has no records. You can call collection methods on $postRecord->tags
    ////////////////////////////////////////////////////////////////////////////
    
    echo 'Tags: '  . PHP_EOL;
    
    /** @var TagRecord $tag */
    foreach($postRecord->tags as $tag) {
        
        echo "\tTag # {$tag->tag_id}: {$tag->name} "  . PHP_EOL;
    }
    
    echo PHP_EOL;
    
} // foreach ($allPostRecordsInACollection as $postRecord)


//////////////////////////////////////////////////////////////////////////////////
// Fetching rows of post data & eager-loading related data via fetchRowsIntoArray
//////////////////////////////////////////////////////////////////////////////////

echo '//////////////////////////////////////////////////////////////' . PHP_EOL;

$allPostRowsInAnArray = 
    $postsModel->fetchRowsIntoArray(
        null, // we are not injecting a query obj, default
              //    select * from posts 
              // query will be issued
        ['author', 'summary', 'comments', 'tags'] // related data to eager-load
                                                  // 4 additional queries
    );

foreach ($allPostRowsInAnArray as $postRow) {
    
    echo 'Post: ' . $postRow['title'] . PHP_EOL;
    
    ////////////////////////////////////////////////////////////////////////////
    // BelongsTo: a post belongs to an author, there can never be a post without
    // an author
    ////////////////////////////////////////////////////////////////////////////
    
    echo 'Author: ' . $postRow['author']['name']  . PHP_EOL;
    
    ////////////////////////////////////////////////////////////////////////////
    // HasOne: a post can have zero or one summary, there can be a post without 
    // a summary. Check if a related summary row of data was found for the current 
    // post row of data. If the post doesn't have a summary, 
    // array_key_exists('summary', $postRow) will be false
    ////////////////////////////////////////////////////////////////////////////
    
    if(array_key_exists('summary', $postRow)) {
        
        echo 'Summary ID: ' . $postRow['summary']['summary_id']  . PHP_EOL;
        
    } else {
        
        echo 'No Summary'  . PHP_EOL;
    }
    
    ////////////////////////////////////////////////////////////////////////////
    // HasMany: a post can have zero, one or more comments. Because we called 
    // fetchRowsIntoArray to fetch the post rows of data, the hasMany related 
    // data for each post record will also be rows of data stored in an array 
    // and if in this case, a post does not have any comments, 
    // $postRow['comments'] will be equal to []
    ////////////////////////////////////////////////////////////////////////////
    
    echo 'Comments: '  . PHP_EOL;

    foreach($postRow['comments'] as $comment) {
        
        echo "\tComment # {$comment['comment_id']}: {$comment['name']} "  . PHP_EOL;
    }
    
    ////////////////////////////////////////////////////////////////////////////
    // hasManyThrough: a post can have zero, one or more tags through the 
    // associations defined in the posts_tags table. Because we called 
    // fetchRowsIntoArray to fetch the post rows of data, the hasManyThrough 
    // related data for each post rows of data will also be rows of data stored 
    // in an array and if in this case, a record does not have any tags, 
    // $postRow['tags'] will be equal to []
    ////////////////////////////////////////////////////////////////////////////
    
    echo 'Tags: '  . PHP_EOL;
    
    foreach($postRow['tags'] as $tag) {
        
        echo "\tTag # {$tag['tag_id']}: {$tag['name']} "  . PHP_EOL;
    }
    
    echo PHP_EOL;
    
} // foreach ($allPostRowsInAnArray as $postRow)


////////////////////////////////////////////////////////////////////////////////
// Fetching a single row of post data as a record & eager-loading related data 
// via fetchOneRecord
////////////////////////////////////////////////////////////////////////////////

echo '//////////////////////////////////////////////////////////////' . PHP_EOL;

$postRecord = 
    $postsModel->fetchOneRecord(
        null, // we are not injecting a query obj, default
              //    select * from posts 
              // query will be issued
        ['author', 'summary', 'comments', 'tags'] // related data to eager-load
                                                  // 4 additional queries
    );

echo 'Post: ' . $postRecord->title . PHP_EOL;

////////////////////////////////////////////////////////////////////////////
// BelongsTo: a post belongs to an author, there can never be a post without 
// an author
////////////////////////////////////////////////////////////////////////////

echo 'Author: ' . $postRecord->author->name  . PHP_EOL;

////////////////////////////////////////////////////////////////////////////
// HasOne: a post can have zero or one summary, there can be a post without 
// a summary. Check if a related summary record was found for the current 
// post record. If the post doesn't have a summary, $postRecord->summary 
// will be NULL.
////////////////////////////////////////////////////////////////////////////

if($postRecord->summary instanceof SummaryRecord) {

    echo 'Summary ID: ' . $postRecord->summary->summary_id  . PHP_EOL;

} else {

    echo 'No Summary'  . PHP_EOL;
}

////////////////////////////////////////////////////////////////////////////
// HasMany: a post can have zero, one or more comments. Because we called 
// fetchOneRecord to fetch the post records, the hasMany related 
// data for each post record will also be records stored in a collection 
// (an instance of CommentsCollection in this case) and if in this case, a 
// record does not have any comments, $postRecord->comments will still be a 
// collection (still an instance of CommentsCollection in this case) that 
// has no records. You can call collection methods on $postRecord->comments
////////////////////////////////////////////////////////////////////////////

echo 'Comments: '  . PHP_EOL;

/** @var CommentRecord $comment */
foreach($postRecord->comments as $comment) {

    echo "\tComment # {$comment->comment_id}: {$comment->name} "  . PHP_EOL;
}

////////////////////////////////////////////////////////////////////////////
// hasManyThrough: a post can have zero, one or more tags through the 
// associations defined in the posts_tags table. Because we called 
// fetchOneRecord to fetch the post records, the hasManyThrough 
// related data for each post record will also be records stored in a 
// collection (an instance of TagsCollection in this case) and if in this 
// case, a record does not have any tags, $postRecord->tags will still be a
// collection (still an instance of TagsCollection in this case) that
// has no records. You can call collection methods on $postRecord->tags
////////////////////////////////////////////////////////////////////////////

echo 'Tags: '  . PHP_EOL;

/** @var TagRecord $tag */
foreach($postRecord->tags as $tag) {

    echo "\tTag # {$tag->tag_id}: {$tag->name} "  . PHP_EOL;
}

echo PHP_EOL;

```

## Query Logging

All the Query Logging functionality described in this section are only implemented in the **\LeanOrm\Model** class and not its parent class **\GDAO\Model**. Other non-**\LeanOrm\Model** sub-classes of **\GDAO\Model** are not required or guaranteed to implement the Query Logging functionality described in this section.

LeanOrm allows you to log the queries generated and executed when:
- delete methods are called on an instance of a model class
- fetch methods are called on an instance of a model class
- related data is fetched (either via eager-loading or non-eager-loading / lazy-loading)
- insert methods are called on an instance of a model class
- update methods are called on an instance of a model class

> Queries you execute directly via the **PDO** object returned when **getPDO** is called on an instance of a model class are NOT logged by LeanORM. 

> You may want to look into using a tool like [Debugbar](http://phpdebugbar.com/docs/base-collectors.html#pdo) to capture all queries in your application. Since LeanORM only ever creates a single PDO instance per unique dsn string for use across all model instances that are created with that dsn string, you only need to retrieve the PDO object for one of those models to bind it to Debugbar's PDO collector which would allow Debugbar to capture every single query executed via that PDO instance across all the model instances that share that PDO connection.

By default, LeanOrm logs queries into an internal array for each model class instance and another static internal array for all queries across all model instances. Each log entry is an array with the following keys:
- **sql**: its corresponding value is a string representing the sql query that was logged
- **bind_params**: its corresponding value is an array containing all the parameters (if any), that were bound to the sql query above
- **date_executed**: its corresponding value is a string representing the full date and time the sql query was executed in `y-m-d H:i:s` format
- **class_method**: its corresponding value is a string representing the name of the class and the method where the sql query was executed
- **line_of_execution**: its corresponding value is a string representing the line number in the class where the query was logged

In addition to logging queries into the arrays described above, LeanOrm allows injecting a [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md) compliant logger object into each instance of the model class by calling **setLogger**. This allows you to log the query and it's related data described above to various destinations like a log file, standard output, etc. This logger is OPTIONAL.

LeanOrm has the following instance methods on the **\LeanOrm\Model** class that are related to logging:
- **canLogQueries:** returns true if query logging is enabled on a particular instance of the Model class or any of its sub-classes or false if query logging is disabled on the model instance. It returns false by default.
- **clearQueryLog:** resets the internal array containing all the logged query entries for a particular instance of the model class.
- **disableQueryLogging:** turns query logging off on the instance of the model class that it's called on. After calling this method, the instance of the model class which it is called on will stop logging queries.
- **enableQueryLogging:** turns query logging on on the instance of the model class that it's called on. After calling this method, the instance of the model class which it is called on will start logging queries.
- **getQueryLog:** returns an array containing all the logged query entries (each of which is also a sub-array described in the log entry section earlier above) for a particular instance of the model class.
- **getLogger:** returns the [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md) compliant logger (if any was set) injected into the instance of the model class this method is being called on or it returns null if no logger was set.
- **setLogger:** it is used to inject a [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md) compliant logger into the instance of the model class this method is being called on. It can also set the current logger to null if you want to stop using the currently set logger.

LeanOrm has the following static methods on the **\LeanOrm\Model** class that are related to logging:
- **clearQueryLogForAllInstances:** it clears the static internal array containing all query log entries across all instances of the model class that have logging turned on. This does not clear the query log for each individual instance of the Model class, you would have to call the **clearQueryLog** method on each instance to do that.
- **getQueryLogForAllInstances:** it returns a copy of the static internal array containing all query log entries across all instances of the model class that have logging turned on. It can also return all the query log entries (if any) for a particular instance of model, if that instance is passed as an argument (it's equivalent to calling **getQueryLog** directly on the instance of the model class).

> If you only want to debug queries executed by a single instance of the model class, you should just call the **getQueryLog** method on that instance. If you also have a logger set for that model instance, you will be able to see in real-time the queries executed on that instance in the destination where your logger is logging to.

> If you only want to debug queries executed by more than one  instance of the model class, you could either call the **getQueryLog** method on each of those instances or call **\LeanOrm\Model::getQueryLogForAllInstances** to get all the queries executed across all instances of the model class that have logging turned on. If you also have a logger set for those model instances, you will be able to see in real-time the queries executed on those instances in the destination where your logger is logging to.

Below is a little code snippet of how to enable query logging and display the logged query entries for a single instance of the model class.

```php
<?php
$postsModel = new PostsModel('mysql:host=hostname;dbname=blog', 'user', 'pwd');

if(!$postsModel->canLogQueries()) {
    
    $postsModel->enableQueryLogging();
    // Applicable queries generated and executed by $postsModel
    // will start being logged from here downwards
}

/////////////////////////////////////////
// Execute queries on the model object
////////////////////////////////////////

$postRecord = 
    $postsModel->fetchOneRecord(
        null, // we are not injecting a query obj, default
              //    select * from posts 
              // query will be issued
        ['author', 'summary', 'comments', 'tags'] // related data to eager-load
                                                  // 4 additional queries
    );

$postsModel->disableQueryLogging();
// Applicable queries generated and executed by $postsModel
// will stop being logged from here downwards

///////////////////////////////////////////////////
// Dump the query log for the model instance above
///////////////////////////////////////////////////

var_export($postsModel->getQueryLog());
```

The code above should output something like this below:

```php
[
    0 => [
        'sql' => 'SELECT
                        posts.* 
                   FROM
                       `posts`
                   LIMIT 1',
        'bind_params' => [],
        'date_executed' => '2023-01-18 22:31:59',
        'class_method' => 'LeanOrm\\Model::fetchOneRecord',
        'line_of_execution' => '1530',
    ],
    1 => [
        'sql' => 'SELECT
                        authors.*
                    FROM
                        `authors`
                    WHERE
                         `authors`.`author_id` = :_1_ 
                    ORDER BY
                        author_id',
        'bind_params' => [ '_1_' => '1', ],
        'date_executed' => '2023-01-18 22:31:59',
        'class_method' => 'LeanOrm\\Model::getBelongsToOrHasOneOrHasManyData',
        'line_of_execution' => '931',
    ],
    
    2 => [
        'sql' => 'SELECT
                        summaries.*
                    FROM
                        `summaries`
                    WHERE
                         `summaries`.`post_id` = :_1_ 
                    ORDER BY
                        summary_id',
        'bind_params' => ['_1_' => '1',],
        'date_executed' => '2023-01-18 22:31:59',
        'class_method' => 'LeanOrm\\Model::getBelongsToOrHasOneOrHasManyData',
        'line_of_execution' => '931',
    ],
    
    3 => [
        'sql' => 'SELECT
                        comments.*
                    FROM
                        `comments`
                    WHERE
                         `comments`.`post_id` = :_1_ 
                    ORDER BY
                        comment_id',
        'bind_params' => ['_1_' => '1',],
        'date_executed' => '2023-01-18 22:31:59',
        'class_method' => 'LeanOrm\\Model::getBelongsToOrHasOneOrHasManyData',
        'line_of_execution' => '931',
    ],
    
    4 => [
        'sql' => 'SELECT
                        `posts_tags`.`post_id` ,
                        tags.* 
                   FROM
                       `tags`
                   INNER JOIN `posts_tags` ON  `posts_tags`.`tag_id` = `tags`.`tag_id`
                   WHERE
                        `posts_tags`.`post_id` = :_1_ 
                   ORDER BY
                       `tags`.`tag_id`',
        'bind_params' => ['_1_' => '1',],
        'date_executed' => '2023-01-18 22:31:59',
        'class_method' => 'LeanOrm\\Model::loadHasManyThrough',
        'line_of_execution' => '689',
    ],
]
```

That's all for query logging. To learn more take a look at the source code for the query logging related methods in **\LeanOrm\Model**.


[<<< Previous](./getting-started.md) | [Next >>>](./more-about-records.md)