# More about Collections

## Fetching Nested Related Data

When you use any of the **fetch\*** methods to fetch a collection of records from the database, you can include the name(s) of related data (defined by calling any of the relationship definition methods (e.g. hasMany, belongsTo, etc.) on the Model associated with the Collection) to be eager fetched into the collection of records to be returned. There however is a limitation to this type of eager fetching as leanOrm does not support nested eager fetching through the **fetch\*** methods.

Using the blog database example below:

![Blog Schema](../demo/blog-db.png)

I can define that an **author** has Many **posts** in my Authors Model class and when I want to fetch a collection of author records from the database, I can specify that the posts for each author record should be eager loaded by doing something like this:

```php
// The posts relationship should have been defined by a call to hasMany in the Authors Model class
$anAuthorWithPosts = $authorModel->fetchOneRecord(null, ['posts']);
```

Looking at the schema diagram above we can also define that a **post** has many **tags** through the **posts_tags** table.

 What if I also want to get the tags associated with each Post when fetching the author like I did above? Well, we can't directly do that via the **fetchOneRecord** method or any of the other **fetch\*** methods in the **Model** class as they do not support nested relationships (maybe a future release would support that).

 The solution to this problem will be to call the **eagerLoadRelatedData(array $relationsToInclude):static** method in the collection class on the collection of related data (in this case the **posts** collection) and specify the relation name defined in the associated Model (in this case the Posts Model) that you want to eagerLoad. So to get an author with the author's related posts and the related tags for each post, you can use the code below:

 ```php
// The posts relationship should have been defined by a call to hasMany in the Authors Model class
$anAuthorWithPosts = $authorModel->fetchOneRecord(null, ['posts']);

// The tags relationship should have been defined by a call to hasManyThrough in the Posts Model class
$anAuthorWithPosts->posts->eagerLoadRelatedData(['tags']);
 ```

 > NOTE: If you dont call the **eagerLoadRelatedData** method on the **posts** collection like we did above and just loop through the **posts** collection and try to access the **tags** collection on each post record, a seperate query will be executed for each post record to get its related **tags**, as oppossed to just one query that gets excuted to fetch all tags associated with all posts when **eagerLoadRelatedData** is called.

> Some form of eager loading of nested related data will be implemented in the fetch methods that return a record, or a collection of records or an array of records in the future. It might look something like this: 

```php
 **$anAuthorWithPostsAndTags = $authorModel->fetchOneRecord(null, ['posts'=>['tags]]);**
```

[<<< Previous](./more-about-records.md)