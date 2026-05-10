# The \LeanOrm\DBConnector Class

This class serves as both a [PDO](https://www.php.net/manual/en/class.pdo.php) connection manager and a query executor (powered by PDO). It is used by **\LeanOrm\Model** to interact with the database. You can also use it to directly interact with the database in your applications without going through an instance of the **\LeanOrm\Model** (or its sub-classes). 

If you already have an instance of **\LeanOrm\Model** (or any of its sub-classes), you can get the instance of **\LeanOrm\DBConnector** associated with that Model class instance by calling the **getDbConnector()** method on the Model class instance and use the **\LeanOrm\DBConnector** instance returned to directly interact with the database.

## Configuration and Creation

You can 

1. inject an existing **PDO** instance via the **\LeanOrm\DBConnector::setPdo** method 

2. or use the **\LeanOrm\DBConnector::configure** method to define the settings for each **PDO** instance you want to create. 

> Each **PDO** instance and configuration setting must be tagged with a unique connection name (just a unique string value). That connection name can be used to access the associated **PDO** object later on via the **\LeanOrm\DBConnector::getPdo** method. If you don't specify a connection name, the default connection name (**\LeanOrm\DBConnector::DEFAULT_CONNECTION**) will be used.

After injecting an existing **PDO** instance or configuring the settings for the **PDO** object(s) to be created, you must call the **\LeanOrm\DBConnector::create** method with the connection name you specified in either step 1 or 2 above to create an instance of **\LeanOrm\DBConnector** which will have the desired instance of **PDO** associated with it.

### Method 1 (Injecting an existing PDO instance & creating an instance of \LeanOrm\DBConnector)

```php
<?php

$dsn = 'sqlite::memory:';
$pdo = new \PDO($dsn);

///////////////////////////////////////////////////
// Using the default connection name
///////////////////////////////////////////////////

// Since the connection name is not passed as a
// second argument below, the default connection
// name \LeanOrm\DBConnector::DEFAULT_CONNECTION
// will be associated with this pdo instance
\LeanOrm\DBConnector::setPdo($pdo);

// Since the connection name is not passed as an argument
// below, the instance of DBConnector that will be created
// and returned will be associated with the PDO object 
// associated with the default connection name 
// \LeanOrm\DBConnector::DEFAULT_CONNECTION above
$dbConnector = \LeanOrm\DBConnector::create();

///////////////////////////////////////////////////
// Specifying a non-default connection name
///////////////////////////////////////////////////
$connectionName = 'connection-to-primary-server';

// Since the connection name is passed as a second
// argument below, the specified connection name
// will be associated with this pdo instance
\LeanOrm\DBConnector::setPdo($pdo, $connectionName);

// Since the connection name is passed as an argument
// below, the instance of DBConnector that will be created
// and returned will be associated with the PDO object 
// associated with the specified connection name above
$dbConnector = \LeanOrm\DBConnector::create($connectionName);
```

### Method 2 (Configuring settings needed to setup a PDO instance & creating an instance of \LeanOrm\DBConnector)

Below are the following setting keys for settings configurable via the **\LeanOrm\DBConnector::configure** method:

- **\LeanOrm\DBConnector::CONFIG_KEY_CONNECTION_STR:** must be a valid dsn string value acceptable as the first argument to [PDO::__construct](https://www.php.net/manual/en/pdo.construct.php). The default value is **'sqlite::memory:'**
- **\LeanOrm\DBConnector::CONFIG_KEY_ERR_MODE:** must be a valid value acceptable for the [PDO::ATTR_ERRMODE](https://www.php.net/manual/en/pdo.error-handling.php) setting. The default value is **\PDO::ERRMODE_EXCEPTION**. This setting can be ommitted if you are ok with using the default **\PDO::ERRMODE_EXCEPTION**
- **\LeanOrm\DBConnector::CONFIG_KEY_USERNAME:** if you plan to connect to a database like **MySQL** which requires a username to connect to it, then that username should be supplied as the value of this setting. You don't need this setting for sqlite databases that don't require a username. This value will be supplied as a second argument to [PDO::__construct](https://www.php.net/manual/en/pdo.construct.php)
- **\LeanOrm\DBConnector::CONFIG_KEY_PASSWORD:** if you plan to connect to a database like **MySQL** which requires a password to connect to it, then that password should be supplied as the value of this setting. You don't need this setting for sqlite databases that don't require a password. This value will be supplied as a third argument to [PDO::__construct](https://www.php.net/manual/en/pdo.construct.php)
- **\LeanOrm\DBConnector::CONFIG_KEY_DRIVER_OPTS:** must be a valid array acceptable as the the fourth argument to [PDO::__construct](https://www.php.net/manual/en/pdo.construct.php). It is an optional setting.

```php
<?php
//////////////////////////////////////////////////////////
// Technique 1
//
// Calling the create below without previously calling
// configure will create an instance of PDO with the 
// dsn value of 'sqlite::memory:' and a PDO::ATTR_ERRMODE
// value of \PDO::ERRMODE_EXCEPTION and that PDO instance
// will be associated with the default connection name
// value of \LeanOrm\DBConnector::DEFAULT_CONNECTION
// and the DBConnector instance returned will also
// have \LeanOrm\DBConnector::DEFAULT_CONNECTION as
// its connection name value.
//
// This is the simplest way to create an instance of 
// DBConnector. But you will only be interacting with
// an in-memory sqlite database which is of no real use
// in real-world applications.
$dbConnector = \LeanOrm\DBConnector::create();
//////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////
// Technique 2
//
$connectionName = 'connection-to-primary-server';
$settings = [
    \LeanOrm\DBConnector::CONFIG_KEY_CONNECTION_STR => 'mysql:host=localhost;dbname=some_db',
    \LeanOrm\DBConnector::CONFIG_KEY_DRIVER_OPTS => [
        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
    ],
    \LeanOrm\DBConnector::CONFIG_KEY_ERR_MODE => \PDO::ERRMODE_EXCEPTION,
    \LeanOrm\DBConnector::CONFIG_KEY_USERNAME => 'cool-user',
    \LeanOrm\DBConnector::CONFIG_KEY_PASSWORD => 'cool-password',
];

// Configure all settings in one call by passing a settings array 
// to the configure method. The settings are associated with the
// specified connection name
\LeanOrm\DBConnector::configure($settings, null, $connectionName);

// Since the connection name is also passed as an argument
// below, the instance of DBConnector that will be created
// and returned will be associated with the PDO object 
// that will be created using the settings above which
// are also associated with the specified connection name
$dbConnector = \LeanOrm\DBConnector::create($connectionName);
//////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////
// Technique 3
//
$connectionName = 'connection-to-primary-server';

// Configure each setting by a separate call to the configure method.
// Each setting is associated with the specified connection name
\LeanOrm\DBConnector::configure(
    \LeanOrm\DBConnector::CONFIG_KEY_CONNECTION_STR, 
    'mysql:host=localhost;dbname=some_db', 
    $connectionName
);

\LeanOrm\DBConnector::configure(
    \LeanOrm\DBConnector::CONFIG_KEY_DRIVER_OPTS,
    [
        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
    ],
    $connectionName
);

\LeanOrm\DBConnector::configure(
    \LeanOrm\DBConnector::CONFIG_KEY_ERR_MODE,
    \PDO::ERRMODE_EXCEPTION,
    $connectionName
);

\LeanOrm\DBConnector::configure(
    \LeanOrm\DBConnector::CONFIG_KEY_USERNAME,
    'cool-user',
    $connectionName
);

\LeanOrm\DBConnector::configure(
    \LeanOrm\DBConnector::CONFIG_KEY_PASSWORD,
    'cool-password',
    $connectionName
);

// Since the connection name is also passed as an argument
// below, the instance of DBConnector that will be created
// and returned will be associated with the PDO object 
// that will be created using the settings above which
// are also associated with the specified connection name
$dbConnector = \LeanOrm\DBConnector::create($connectionName);
```

> **NOTE:** Each instance of **\LeanOrm\DBConnector** created via the **create** method that has the same connection name share the same PDO object associated with that connection name.

> If you only want one instance of **\LeanOrm\DBConnector** to be associated with a specific connection name do not call the **create** method. Instead call **\LeanOrm\DBConnector::getInstance**. This will guarantee that only one instance of **\LeanOrm\DBConnector** is associated with a specific connection name and obviously that one instance will always only use the **PDO** object associated with that connection name.

You can get the connection name associated with an instance of **\LeanOrm\DBConnector** by calling the **getConnectionName()** method on that instance.


## Accessing PDO instance(s)

You can call **\LeanOrm\DBConnector::getPdo**, with a connection name as argument, to get the **PDO** instance associated with the specified connection name. 

Calling the method with no argument will return the **PDO** instance associated with the default connection name **\LeanOrm\DBConnector::DEFAULT_CONNECTION**.

If you have an instance of **\LeanOrm\DBConnector** already, just call the **getMyPdo()** method on that instance to get the **PDO** object associated with that instance of **\LeanOrm\DBConnector**.


## Executing Queries

The methods below are convenience methods for executing SQL SELECT queries and getting the result back in the expected data type:

> **NOTE:** The **$calling_object** optional argument to all the querying methods below (in this section) is only used for query logging purposes. It is set to the instance of **\LeanOrm\DBConnector** each query method is called on if it's left as null default value. It can be used to filter out query log entries associated with an instance of **\LeanOrm\DBConnector** or an instance of another object passed as the argument to the querying methods below. 

> This argument has NO IMPACT on your query. You can ignore it if you are not using the query logging feature which is explained later below.

```php
    /////////////////////////////////////////////////////////////
    // Use this to fetch the first row returned by the select 
    // query as an associative array where the keys are the 
    // column names
    //
    // - If the query returns no rows, false is returned
    /////////////////////////////////////////////////////////////
    public function dbFetchOne(
        string $select_query,
        array $parameters = [],
        ?object $calling_object=null
    ): mixed

    /////////////////////////////////////////////////////////////
    // Use this to fetch a sequential array of all rows returned
    // by the select query ( where each row is an associative 
    // array where the keys are the column names)
    //
    // - An empty array is returned if no row is returned by 
    //   the query
    /////////////////////////////////////////////////////////////
    public function dbFetchAll(
        string $select_query,
        array $parameters = [],
        ?object $calling_object=null
    ): array

    /////////////////////////////////////////////////////////////
    // Use this to fetch a sequential array of the first column
    // from all rows returned by the select query
    //
    // - An empty array is returned if no row is returned by 
    //   the query
    /////////////////////////////////////////////////////////////
    public function dbFetchCol(
        string $select_query,
        array $parameters = [],
        ?object $calling_object=null
    ): array 

    /////////////////////////////////////////////////////////////
    // Use this to fetch an associative array where keys are 
    // made up of values from the first specified database
    // table column and the values are from the second
    // specified database column in the select query.
    //
    // - An empty array is returned if no row is returned by 
    //   the query
    /////////////////////////////////////////////////////////////
    public function dbFetchPairs(
        string $select_query,
        array $parameters = [],
        ?object $calling_object=null
    ): array

    /////////////////////////////////////////////////////////////
    // Use this to fetch the value of the first row in the 
    // first column returned by the select query.
    //
    // - Null is returned if the query returns no rows
    /////////////////////////////////////////////////////////////
    public function dbFetchValue(
        string $select_query,
        array $parameters = [],
        ?object $calling_object=null
    ): mixed
```

Here are some actual examples of how to use the methods above:

```php

///////////////////////////////////////////////////
// get the first row returned from the query below
$firstPost = $dbConnector->dbFetchOne('select * from posts');

// get the first row returned from the query below
$firstPostWithIdGt1 = $dbConnector->dbFetchOne(
    'select * from posts where post_id > :id', 
    ['id'=>1]
);

///////////////////////////////////////////////////
// get all rows returned from the query below
$allPosts = $dbConnector->dbFetchAll('select * from posts');

// get all rows returned from the query below
$allPostsWithIdGt1 = $dbConnector->dbFetchAll(
    'select * from posts where post_id > :id',
    ['id'=>1]
);

///////////////////////////////////////////////////
// get an array of values for the first column (post_id) 
// from the rows returned from the query below
$allPostIds = $dbConnector->dbFetchCol(
    'select post_id, title from posts'
);

// get an array of values for the first column (post_id) 
// from the rows returned from the query below
$allPostIdsWithIdGt1 = $dbConnector->dbFetchCol(
    'select post_id, title from posts where post_id > :id',
    ['id'=>1]
);

///////////////////////////////////////////////////
// get an array whose keys are the values for the 
// first column (post_id) and whose values are
// from the second column (title) from the rows
// returned from the query below
$allPostIdAndTitlePairs = $dbConnector->dbFetchPairs(
    'select post_id, title from posts'
);

// get an array whose keys are the values for the 
// first column (post_id) and whose values are
// from the second column (title) from the rows
// returned from the query below
$allPostIdAndTitlePairsWithIdGt1 = $dbConnector->dbFetchPairs(
    'select post_id, title from posts where post_id > :id',
    ['id'=>1]
);

///////////////////////////////////////////////////
// get the value returned by the query below
$maxViewCount = $dbConnector->dbFetchValue(
    'select MAX(view_count) from summaries'
);

// get the value returned by the query below
$maxViewCountOfCountsLt4 = $dbConnector->dbFetchValue(
    'select MAX(view_count) from summaries where view_count < :count',
    ['count'=>4]
);
```


For queries that the **dbFetch\*** methods above can't handle, you can use the **runQuery** method. For example, delete, insert, complex select queries, update and other types of queries.

```php
    /////////////////////////////////////////////////////////////
    // Runs a query and returns the result as an instance of 
    // \LeanOrm\DBExceuteQueryResult
    /////////////////////////////////////////////////////////////
    public function runQuery(
        string $query, 
        array $parameters=[], 
        ?object $calling_object=null
    ): \LeanOrm\DBExceuteQueryResult
```
Here are some actual examples of how to use **runQuery**:

```php
$queryResult1 = $dbConnector->runQuery(
    "update posts set m_timestamp = '2026-05-11 14:53:35' where post_id = :da_id",
    ['da_id'=>4]
);

// $queryResult1->pdo_statement contains the instance of
// \PDOStatement used to run the query or null if the query
// could not be prepared successfully

// $queryResult1->pdo_statement_execute_result contains the result
// returned when execute was called on $queryResult1->pdo_statement
// under the hood to execute the query

// $queryResult1->query_execution_time_in_seconds is the time in
// seconds it took to run the query
```


## Query logging

    public function canLogQueries(): bool

    public static function clearQueryLog(
        null|string $connection_name = null,
        null|object $object_to_match = null
    ): void

    public function disableQueryLogging(): static

    public function enableQueryLogging(): static

    public function getLogger(): ?LoggerInterface

    public static function getQueryLog(
        null|string $connection_name = null,
        null|object $object_to_match = null
    ): array

    public function setLogger(?LoggerInterface $logger): static








[<<< Previous](./more-about-collections.md)