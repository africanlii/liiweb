# AfricanLII JsonAPI

Tu successfully run this example, please do the following:

1. run the `curl` commands commands from this folder;
2. use the correct URL to the test instance;
3. Use credentials of a valid account with role Legislation API.

## Create a new work

This example creates a new work (assimilated as well with the first expression of a node).

```
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u api_user:password --data @create_work.json \
http://liiweb.test/api/node/legislation
```

After this call a new Drupal node is created in the system having a current revision.

This example creates a new work (assimilated as well with the first expression of a node) having completed fields: tags, primary work, repeal work:
```
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u api_user:password --data @create_complete_work.json \
http://liiweb.test/api/node/legislation
```
Notice: If the entity searched by "attributes" key is not found, then a new entity is created and linked to the new work


## Update an existing expression

This example updates the title and publication name of the legislation previously created. In this way any other field can be changed.

**Important**: If the date of this expression (field_publication_date) is the newest for this work, this expression is set as the 'latest' expression. Otherwise it's just another expression attached to the history of this work.

```
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X PATCH -u api_user:password --data @update_expression.json \
http://liiweb.test/akn/za/1993/31/eng@2017-03-11
```

After the call the Drupal revision will have its fields updated with the new values and can be visible by visiting the page for this legislation. Note the existing revision has been altered and the system had not created a new one.



This example will update a work, removing one of it's tags, the primary work and the repeal work
```
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X PATCH -u api_user:password --data @update_complex_expression.json \
http://liiweb.test/akn/za/1993/31/eng@2017-03-13
```

## Create a new expression

This example creates a new expression to the already existing work.

```
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u api_user:password --data @create_expression.json \
http://liiweb.test/akn/za/1993/31/eng@2019-03-11
```

After the call a new node revision is created and set as the current revision. If you visit the "Revisions" tab for this node you will see it now has two revisions, and this one is the current revision.


## Create an expression in another language

This example creates a french translation

```
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u api_user:password --data @create_expression_translation.json \
http://liiweb.test/akn/za/1993/31/fra@2019-03-11
```

After the call a new french translation was created and set as current for French. If you visit the "Translate" tab for this node a french translation will appear.


## Delete an expression

This example deletes an expression and all its translations


```
curl -X DELETE -u api_user:password http://liiweb.test/akn/za/1993/31/fra@2019-03-11
```

After the call the revision for this node will be deleted, both in English and French. 

**Note**: You cannot delete a single translation of an expression of a work (i.e. only French translation) due to the way Drupal handles default translation: If you have a node translated in French while English is the default language, then English cannot be removed to leave only French in place. Therefore, to have a consistent behavior, when removing an expression all its translations are removed.


## Delete an entire work

```
curl -X DELETE -u api_user:password http://liiweb.test/akn/za/1993/31
```

This will remove the whole node together with all its revisions.
