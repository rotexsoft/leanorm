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


##### A Generic Data Objects ( https://github.com/rotexsoft/gdao ) implementation based on a stripped down version of idiorm (\\LeanOrm\\DBConnector). A light-weight, highly performant PHP data access library. 

See http://rotexsoft.github.io/leanorm/ for documentation.

## Dev Notes

 * Old versions have branches corresponding to their version numbers (e.g. 1.X) 
while the most current / actively being developed version is on the master branch


## Concepts

Courtesy of https://www.semicolonandsons.com/code_diary/databases/difference-between-has-one-belongs-to-and-has-many

### Difference between has one belongs to and has many

This is part of the Semicolon&Sons [Code Diary](https://www.semicolonandsons.com/code_diary) - consisting of lessons learned on the job. You're in the [databases](https://www.semicolonandsons.com/code_diary/databases) category.


The only difference between `hasOne` and `belongsTo` is where the foreign key column is located.

Let's say you have two entities: User and an Account.

-   If the users table has the `account_id` column then a User `belongsTo` Account. (And the Account either `hasOne` or `hasMany` Users)
    
-   But if the users table does _not have_ the `account_id` column, and instead the accounts table has the `user_id` column, then User `hasOne` or `hasMany` Accounts
    

In short `hasOne` and `belongsTo` are inverses of one another - if one record `belongTo` the other, the other `hasOne` of the first. Or, more accurately, eiterh `hasOne` or `hasMany` - depending on how many times its id appears.
