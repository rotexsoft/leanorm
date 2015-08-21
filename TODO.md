* Add License

* Strive for 100% Unit Test Coverage

* Add a property to the model class called driver name eg. 'mysql', 'pgsql' or 'sqlite' which can be set by consumers of the model and can be used to 
implement functionality specific to each DBMS.

* Lower testing requirement to allow for PHP 5.3 (the only downside is the loss of the convenient use of the short array syntax)
> This is to make sure this package really works for PHP 5.3, since the short array syntax is only used in test files and not in the actual source (src) files

* In the documentation state that the **'cols'** parameter in the $params array for fetchPairs should only contain column names without the table name and dot as prefix

* Find a way using git hooks or something to update the year in the license during a commit, push or something.

* Add examples of how to implement validation using a 3rd party library like AuraFilter. Not adding filtering and validation in order to give consumers flexibility on how this should happen.

* Test that data for Insert and Update get saved as the correct data type in the DB.

* Add in the documentation that Records can be added to a collection via the $collection[] = $record; syntax. No need for a $collection->addRecord(record); method in the API

* Figure out how to use __call(..) in the Model, Collection and Record Classes. For example __call() could be used to map method calls for each record in a collection and an array containing the return values for each call applied to each record would be returned. See what Solar_Sql and other libraries are doing.

* Figure out how to improve data retrieval based on http://www.dragonbe.com/2015/07/speeding-up-database-calls-with-pdo-and.html and http://evertpot.com/switching-to-generators/.

* Implement save relations at the Collection and Record Levels.

* Test that the package works with views.

* Look into the possibility of refactoring the Record class to have connected and disconnected records
> Connected records will contain a reference to the Model object that created them while disconnected records will have no reference to the model that created them 
> (they can be used separately (by supplying an array of data to their constructor), no need for a Model or Collection class). 
> Fast Read-Only Records are another possibility (they don't need to keep track of initial data).

* Write an alternative implementation of \GDAO\Model\Collection using SplFixedArray instead of a plain old php array (SplFixedArray seems to be more memory efficient than php arrays). 
> in loadData(..) and __construct(..) add this line   
> $this->_data = \SplFixedArray::fromArray( $data->toArray() );   
> where $data is an instance of \GDAO\Model\GDAORecordsList expected as the first parameter to loadData(..) and __construct(..) 

