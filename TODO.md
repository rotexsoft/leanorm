* Add License
* Add a property to the model class called driver name eg. 'mysql', 'pgsql' or 'sqlite' which can be set by consumers of the model and can be used to 
implement functionality specific to each DBMS.

* Lower testing requirement to allow for PHP 5.3 (the only downside is the loss of the convenient use of the short array syntax)
> This is to make sure this package really works for PHP 5.3, since the short array syntax is only used in test files and not in the actual source (src) files

* In the documentation state that the **'cols'** parameter in the $params array for fetchPairs should only contain column names without the table name and dot as prefix
