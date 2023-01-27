# More about Models

There is **\LeanOrm\CachingModel** class that is meant to cache method return values across
all instances of **\LeanOrm\CachingModel** and its sub-classes (on each invocation of a php script
via command line or a webserver) where possible to improve performance. You can use it instead of 
**\LeanOrm\Model** in your applications. The cached results do not persist between different
execution of your php script(s).

As at of the writing of this documentation, there are only two protected methods whose results
are being cached:
* **fetchTableListFromDB(): array**
* **fetchTableColsFromDB(string $table_name): array**

Other methods that could gain performance improvements will be added and documented as time goes on.