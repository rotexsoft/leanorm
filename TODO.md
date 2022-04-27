Add them to https://github.com/rotexsoft/leanorm/issues moving forward. 

* Strive for 100% Unit Test Coverage

* Take a look at https://github.com/usmanhalalit/pixie for inspiration on how to implement a fluent interface for querying Models. The current fetch*() methods are not very user-friendly with regards to specifying 'where' params

* Add magic methods like fetchOneRecordByCol1AndCol2($col1_val, $col2_val) as code snippets in documentation

* Lower testing requirement to allow for PHP 5.3 (the only downside is the loss of the convenient use of the short array syntax)
> This is to make sure this package really works for PHP 5.3, since the short array syntax is only used in test files and not in the actual source (src) files

* In the documentation state that the **'cols'** parameter in the $params array for fetchPairs should only contain column names without the table name and dot as prefix

* Find a way of using git hooks or something to update the year in the license during a commit, push or something.

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

* Write an alternative implementation of \GDAO\Model\Collection using SplFixedArray instead of a plain old php array (SplFixedArray seems to be more memory efficient than php arrays). 
> in loadData(..) and __construct(..) add this line   
> $this->_data = \SplFixedArray::fromArray( $data->toArray() );   
> where $data is an instance of \GDAO\Model\RecordsList expected as the first parameter to loadData(..) and __construct(..) 

* Move Model::getCurrentConnectionInfo() to the GDAO\Model class and write unit test for it.

* Look into implement a yield-like feature for the fetch methods (similar to what exists in Aura.Sql https://github.com/auraphp/Aura.Sql/compare/2.4.3...2.5.0 )

* Update documentation for fetch method on the github page to reflect change that allows the use of scalars

* Aim to meet the coding standards by http://thephpleague.com/#quality and hopefully see if this project can become a league package http://thephpleague.com/#contribute see https://github.com/thephpleague/skeleton

* Add a $fillables property to the Model class to contain a list of column names that can be set on each Record belonging to a Model like in Laravel
    - Add a property called $enforce_fillables that will enforce the fillable logic when loading data into a record
    - Optionally add another property called $throw_fillable_violation_exception to allow throwing an exception when $enforce_fillables === true and the user tries to load data into a field not listed in the $fillables array

* Look into making sure Records and Collections can be serialized (the pdo object associated with the model connected to a record / collection may be problematic when (un)serializing)
    - look at creating disconnected records and collection classes
        - seems like a cleaner approach since stuff can be serialized and unserialized without caring about the existence of a pdo connection to re-create a collection or record object
    - or use __sleep() and __wakeup() to select what gets serialized and unserialized

* https://github.com/morris/lessql & https://github.com/paragonie/easydb see tests & try to get inspiration
