[![Run PHP Tests and Code Quality Tools](https://github.com/rotexsoft/leanorm/actions/workflows/php.yml/badge.svg)](https://github.com/rotexsoft/leanorm/actions/workflows/php.yml) &nbsp; 
![GitHub release (latest SemVer)](https://img.shields.io/github/v/release/rotexsoft/leanorm) &nbsp; 
![GitHub](https://img.shields.io/github/license/rotexsoft/leanorm) &nbsp; 
[![Coverage Status](https://coveralls.io/repos/github/rotexsoft/leanorm/badge.svg)](https://coveralls.io/github/rotexsoft/leanorm) &nbsp; 
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/rotexsoft/leanorm) &nbsp; 
![Packagist Downloads](https://img.shields.io/packagist/dt/rotexsoft/leanorm) &nbsp; 
![GitHub top language](https://img.shields.io/github/languages/top/rotexsoft/leanorm) &nbsp; 
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/rotexsoft/leanorm) &nbsp; 
![GitHub commits since latest release (by date)](https://img.shields.io/github/commits-since/rotexsoft/leanorm/latest) &nbsp; 
![GitHub last commit](https://img.shields.io/github/last-commit/rotexsoft/leanorm) &nbsp; 
![GitHub Release Date](https://img.shields.io/github/release-date/rotexsoft/leanorm) &nbsp; 
<a href="https://libraries.io/github/rotexsoft/leanorm">
    <img alt="Libraries.io dependency status for GitHub repo" src="https://img.shields.io/librariesio/github/rotexsoft/leanorm">
</a>

# LeanOrm

#### A light-weight, highly performant PHP data access library. Good alternative to Doctrine & Eloquent without all the bells & whistles that are not needed in most applications.

## Installation Requirements

PHP 8.1+ for version 4.x.

PHP 7.4+ for versions 2.x & 3.x

[Composer](https://getcomposer.org/)

Version 2.x & 3.x of this package have been rigorously tested against sqlite 3.7.11+, MySQL 8.0.29+ & Postgresql 15.1+.

Version 4.x has been rigorously tested against:
- MariaDB 10.4.33+, 10.5.24+, 10.6.17+, 10.11.7+, 11.0.5+, 11.1.4+, 11.2.3+ & 11.3.2+
- MySql 5.6.51, 5.7.44, 8.0.36+, 8.1.0, 8.2.0 & 8.3.0+
- PostgreSQL 12.18+, 13.14+, 14.11+, 15.6+ & 16.2+
- See [run-tests-against-multiple-db-versions.php](./run-tests-against-multiple-db-versions.php)

MS SQL Server, is theoretically supported but hasn't been tested. 
Will provide more updates on MS SQL Server support once testing 
on that DB engine has been done.

If you are using Sqlite, version sqlite 3.7.11 or higher is required.

Version 1.x of this package never got a stable release. 

Version 2.x+ of this package is stable & uses [**aura/sqlquery**](https://github.com/auraphp/Aura.SqlQuery/tree/2.8.1#select) 2.8.0+ . 

Versions 3.x+ & 4.x+ of this package are also stable & use [**aura/sqlquery**](https://github.com/auraphp/Aura.SqlQuery/blob/3.x/docs/select.md) 3.0.0+.

Versions 2.x & 3.x mainly differ in the versions of **aura/sqlquery** their **Model::getSelect(): \Aura\SqlQuery\Common\Select** returns and 
3.x has a few newer features like **Model::fetchOneByPkey($id, array $relations_to_include = []): ?\GDAO\Model\RecordInterface**.

> Deprecated **Utils::search2D(...)** in 2.x has been removed in 3.x

Version 4.x is not backwards compatible with 3.x.

Versions 2.x & 3.x are feature complete as of May 2023, only bug fixes will be applied to those versions.


## Installation

>`composer require rotexsoft/leanorm`

There's an accompanying [command-line tool](https://github.com/rotexsoft/leanorm-cli) that can be used to automatically generate Model, Record & Collection classes for the tables and views in a database. To install this tool, just run the command below. Read the [documentation](https://github.com/rotexsoft/leanorm-cli/blob/main/README.md) for the tool for more information.

> `composer require --dev rotexsoft/leanorm-cli`

## Running Tests

>`./vendor/bin/phpunit --coverage-text`

> You can set the environment variable **LEANORM_PDO_DSN** with a valid $dsn string for pdo e.g. LEANORM_PDO_DSN=sqlite::memory:
    > For Postgres, the dsn must include **dbname=blog** and you should make sure a blog database exists in the Postgres instance. You don't need this for Sqlite, MariaDB or MySql, the database will be programmatically created.

> You can set the environment variable **LEANORM_PDO_USERNAME** with a valid $username string for pdo if needed e.g. LEANORM_PDO_USERNAME=jblow

> You can set the environment variable **LEANORM_PDO_PASSWORD** with a valid $password string for pdo if needed e.g. LEANORM_PDO_PASSWORD=some_password

> For example:

>   `LEANORM_PDO_DSN=sqlite::memory: LEANORM_PDO_USERNAME=jblow LEANORM_PDO_PASSWORD=some_password ./vendor/bin/phpunit --coverage-text`

>   `LEANORM_PDO_DSN="mysql:host=hostname_or_ip_address" LEANORM_PDO_USERNAME="jblow" LEANORM_PDO_PASSWORD="some_password" ./vendor/bin/phpunit --coverage-text`

### GDAO Classes & Interfaces

> Take a look at the code for the most up to date listing of methods

![GDAO Classes & Interfaces](https://raw.githubusercontent.com/rotexsoft/gdao/master/class-diagram.svg)

### LeanORM Classes

> Take a look at the code for the most up to date listing of methods

* **\LeanOrm\Model** extends the abstract **\GDAO\Model** class
* **\LeanOrm\Model\Record** & **\LeanOrm\Model\ReadOnlyRecord** both implement **\GDAO\Model\RecordInterface**
* **\LeanOrm\Model\Collection** implements **\GDAO\Model\CollectionInterface**

![LeanORM Classes](class-diagram.svg)

## Documentation
Documentation for the non-stable 1.x version of this package is located at http://rotexsoft.github.io/leanorm/

Documentation for version 2.x version can be found [here](https://github.com/rotexsoft/leanorm/blob/2.2.x/docs/index.md).

Documentation for version 3.x+ can be found [here](https://github.com/rotexsoft/leanorm/blob/3.x/docs/index.md).

Documentation for version 4.x+ can be found [here](https://github.com/rotexsoft/leanorm/blob/master/docs/index.md).

Please submit an issue (preferably with a pull request) to address mistakes or omissions in the documentation or to propose improvements to the documentation. 

## Contributing

PHPUnit Tests are set-up to run in a specific order in **phpunit.xml.dist**. 

Yes, the best practice is for tests to run independently of each other, 
but because there are fair amount of static methods in the DBConnector class, 
its tests need to be run first before other Test Classes. 

New Test files must be manually added to the phpunit.xml.dist file in order for those new tests to run.

### Branching

These are the branches in this repository:

- **master:** contains code for the latest major version of this package.
- **3.x:** contains code for the 3.x versions of this package. Only bug fixes should be added to this branch. This branch is feature complete.
- **2.2.x:** contains code for the 2.2.x versions of this package. Only bug fixes should be added to this branch. This branch is feature complete.
- **1.X:** contains code for the **1.X** versions of this package. This branch is abandoned.
- **gh-pages:** contains documentation for the 1.X versions of this package. This branch is abandoned.
