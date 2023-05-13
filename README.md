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

PHP 7.4+.

[Composer](https://getcomposer.org/)

Version 2.X & 3.X of this package have been rigorously tested against sqlite 3.7.11+, 
MySQL 8.0.29+ & Postgresql 15.1+. 

MS SQL Server, is theoretically supported but hasn't been tested. 
Will provide more updates on MS SQL Server support once testing 
on that DB engine has been done.

If you are using Sqlite, version sqlite 3.7.11 or higher is required.

Version 1.X of this package never got a stable release. 

Version 2.X+ of this package is stable & uses [**aura/sqlquery**](https://github.com/auraphp/Aura.SqlQuery/tree/2.8.1#select) 2.8.0+ . 

Please use version 3.X+ of this package which uses [**aura/sqlquery**](https://github.com/auraphp/Aura.SqlQuery/blob/3.x/docs/select.md) 3.0.0+.

Versions 2.X & 3.X will contain the same features, but only differ in the versions of **aura/sqlquery** their **Model::getSelect(): \Aura\SqlQuery\Common\Select** returns.

> Deprecated **Utils::search2D(...)** in 2.X has been removed in 3.X

A future version 4 will require php 8.1 as the minimum PHP version.

Versions 2.x & 3.x are feature complete as of May 2023, only bug fixes will be applied to those versions.
 
New features will all be going to version 4 on the 4.x branch. Until the first stable release of version 4
happens, version 3 bug fixes will be added to the 3.x branch which will continue to be synced to the master 
branch until the first stable release of version 4 is published (at that point, the 4.x branch will be merged
into master and start being synced with master moving forward). Bug fixes for version 2.2.x will be applied to
to the 2.2.x branch. I will continue to accept bug-fix pull requests for versions 2.x & 3.x even after version
4 is released. New feature pull requests for versions 2.x & 3.x will be rejected, new features will only be added to the 4.x branch.

## Installation

>`composer require rotexsoft/leanorm`

There's an accompanying [command-line tool](https://github.com/rotexsoft/leanorm-cli) that can be used to automatically generate Model, Record & Collection classes for the tables and views in a database. To install this tool, just run the command below. Read the [documentation](https://github.com/rotexsoft/leanorm-cli/blob/main/README.md) for the tool for more information.

> `composer require --dev rotexsoft/leanorm-cli`

## Running Tests

>`./vendor/bin/phpunit --coverage-text`

> You can set the environment variable **LEANORM_PDO_DSN** with a valid $dsn string for pdo e.g. LEANORM_PDO_DSN=sqlite::memory:

> You can set the environment variable **LEANORM_PDO_USERNAME** with a valid $username string for pdo if needed e.g. LEANORM_PDO_USERNAME=jblow

> You can set the environment variable **LEANORM_PDO_PASSWORD** with a valid $password string for pdo if needed e.g. LEANORM_PDO_PASSWORD=some_password

> For example:

>   `LEANORM_PDO_DSN=sqlite::memory: LEANORM_PDO_USERNAME=jblow LEANORM_PDO_PASSWORD=some_password ./vendor/bin/phpunit --coverage-text`

>   `LEANORM_PDO_DSN="mysql:host=hostname_or_ip_address;dbname=blog" LEANORM_PDO_USERNAME="jblow" LEANORM_PDO_PASSWORD="some_password" ./vendor/bin/phpunit --coverage-text`

### GDAO Classes & Interfaces

![GDAO Classes & Interfaces](https://raw.githubusercontent.com/rotexsoft/gdao/master/class-diagram.svg)

### LeanORM Classes

* **\LeanOrm\Model** extends the abstract **\GDAO\Model** class
* **\LeanOrm\Model\Record** & **\LeanOrm\Model\ReadOnlyRecord** both implement **\GDAO\Model\RecordInterface**
* **\LeanOrm\Model\Collection** implements **\GDAO\Model\CollectionInterface**

![LeanORM Classes](class-diagram.svg)

## Documentation
Documentation for the non-stable 1.X version of this package is located at http://rotexsoft.github.io/leanorm/

Documentation for version 2.X version can be found [here](https://github.com/rotexsoft/leanorm/blob/2.2.x/docs/index.md).

Documentation for the most recent 3.X+ version can be found [here](docs/index.md).

Please submit an issue (preferably with a pull request) to address mistakes or omissions in the documentation or to propose improvements to the documentation. 

## Contributing

PHPUnit Tests are set-up to run in a specific order in **phpunit.xml.dist**. 

Yes, the best practice is for tests to run independently of each other, 
but because there are fair amount of static methods in the DBConnector class, 
its tests need to be run first before other Test Classes. 

New Test files must be manually added to the phpunit.xml.dist file in order for those new tests to run.
