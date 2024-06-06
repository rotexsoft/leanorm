# Introduction

## Why another PHP ORM (Object-Relational Mapping) Package?

LeanOrm is an implementation of the [Generic Data Objects (GDAO)](https://github.com/rotexsoft/gdao) package (a package containing an abstract Model class, two interfaces (CollectionInterface and RecordInterface) and a bunch of Exception classes).

This package aims to provide only the commonly used data access and manipulation features that most PHP applications need. ORM packages like [Propel](http://propelorm.org/), [Doctrine ORM](http://www.doctrine-project.org/projects/orm.html), [Eloquent](https://laravel.com/docs/master/eloquent), etc also provide these features and more features which may never be needed in many PHP applications. LeanOrm's code base is designed to be:

- easily comprehensible
- easily extensible (via inheritance or composition) and
- compact (with one Model class, two Record classes (Read-Only and Read-Write), a Collection class, a DBConnector class (that talks to the database via PDO) and a bunch of Exception classes).

For the most part, users of this package will only be interacting with the Model class. An instance of the Model class is associated with a single table or view in the database. 

Instances of the Model class can be used to:

- Fetch data from the database into 
    - one Record object or multiple Record objects wrapped in either an array or a Collection object
    - or one array (representing a row of data from the database) or multiple arrays (i.e. an array of arrays with each sub-array representing a row of data from the database)
- Insert new data into the database table associated with the Model (You cannot insert data into views)
- Update existing data in the database table associated with the Model (You cannot update data in views)
- Delete data from the database table associated with the Model (You cannot delete data from views)
- Define relationship(s) between its associated database table / view and other database tables / views
- Get database table / view metadata for the table / view the Model is associated with
- and some other database operations

LeanOrm implements the [Table Data Gateway](https://en.wikipedia.org/wiki/Table_data_gateway) and [Data Mapper](https://en.wikipedia.org/wiki/Data_mapper_pattern) patterns. This allows for very loose coupling. Business logic dealing with a single row of data in a database table can be put in the Record class for the table in question, business logic dealing with multiple rows of data in a database table can be put in the Collection class for the table in question while database access logic remains in the Model class for the table in question.

The Model class generates all the SQL for accessing and manipulating data in the database; it uses the DBConnector class to execute the SQL statements. The Model class together with the DBConnector class act as a Table Data Gateway.

The Model class also acts as a Data Mapper by being able to map:

- a row of data in a database table to a Record object
- rows of data in a database table in to a Collection object containing one or more Record objects
- foreign key relationship(s) between database tables into attribute(s) of a record object. Four relationship types are supported:
    1. Belongs-To 
    2. Has-One
    3. Has-Many
    4. Has-Many-Through a.k.a Many to Many)

## Deliberately Omitted Features

- virtual / calculated columns,
- soft deletion,
- single table inheritance,
- magic fetch methods (eg. fetchBySomeColName),
- sanitization & validation of data (packages like [Aura Filter](https://github.com/auraphp/Aura.Filter), [Respect Validate](https://github.com/Respect/Validation), [Valitron](https://github.com/vlucas/valitron), etc. can be used),
- automatic serialization and un-serialization of database columns,
- migration (packages like [Phinx](https://github.com/cakephp/phinx), etc. can be used)

Most of these deliberately omitted features can be easily implemented in your applications by extending LeanOrm classes and creating new methods or overriding existing methods and adding the necessary code there or by using other composer installable packages that provide the desired functionality.

[Next >>>](./getting-started.md)
