# AfricanLII JSON API interaction

To successfully run this example, please do the following:

1. Run the following `curl` commands from project folder docs/jsonapi, in successive order;
2. Use the correct URL to Drupal instance (i.e. liiweb.test);
3. Use credentials of a valid account with role Legislation API (or System Administrator).

## 1. Create a new work

This example creates a new work (assimilated as well with the first expression of a node). The work is tagged with tags: Tag #1 and Tag #2 which are created if they do not already exist in the system

```
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $USER:$PASSWORD --data @01-create-work.json http://liiweb.test/api/node/legislation
```

*Drupal note*: After this call a new Drupal node is created in the system having a current revision.

## 2. Create a work with a parent work

This example creates another work with the primary work set to the previously created sample entity.

```
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $USER:$PASSWORD --data @02-create-work-with-parent.json http://liiweb.test/api/node/legislation
```

Notice: In this example the referenced entity `"attributes": { "field_frbr_uri": "/akn/za/act/1993/31/eng@1993-01-31" }` must exist in order to be successfully linked to this work.


## 3. Create a work with repeal information

This example creates another work which is repealed by the previously created sample entity.

```
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $USER:$PASSWORD --data @03-create-work-with-repeal.json http://liiweb.test/api/node/legislation
```

Notice: In this example the referenced entity `"attributes": { "field_frbr_uri": "/akn/za/act/2017/15/eng@2017-06-15" }` must exist in order to be successfully linked to this work.

## Update an existing expression

This example corrects the title and publication name of a work previously created. In this way any other fields could be changed.

**Important**: If the date of this expression (field_publication_date) is the newest for this work, this expression is set as the 'latest' expression. Otherwise it's just another expression attached to the history of this work.

```
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X PATCH -u $USER:$PASSWORD --data @04-update-expression.json http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01
```

After the call the Drupal revision will have its fields updated with the new values (the existing revision has been altered, and a new one was not created). This example removed one of the tags (Tag 1) and added a new tag (Tag #3)

To replace relationships, just pass empty data structures like this:

```json
{
    "relationships": {
      "field_tags": {
        "data": [
          {
            "type": "taxonomy_term--legislation_tags",
            "tid": "tid",
            "id": "virtual"
          }
        ]
      },
      "field_parent_work": {
        "data": [
          {
            "type": "node--legislation",
            "nid": "nid",
            "id": "virtual"
          }
        ]
      },
      "field_repeal": {
        "data": [
          {
            "type": "paragraph--work_repeal",
            "pid": "pid",
            "id": "virtual"
          }
        ]
      }
    }
}
```

## Attaching files to an expression

This process must be done in two steps:

Step 1. Upload the file in Drupal and obtain its ID

File:

```bash
curl http://liiweb.test/jsonapi/node/legislation/field_files \
   -u $USER:$PASSWORD \
   -H 'Accept: application/vnd.api+json' \
   -H 'Content-Type: application/octet-stream' \
   -H 'Content-Disposition: attachment; filename="test.pdf"' \
   --data-binary @docs/jsonapi/test.pdf
```

Image:

```
curl http://liiweb.test/jsonapi/node/legislation/field_images \
   -u $USER:$PASSWORD \
   -H 'Accept: application/vnd.api+json' \
   -H 'Content-Type: application/octet-stream' \
   -H 'Content-Disposition: attachment; filename="test.png"' \
   --data-binary @docs/jsonapi/test.jpg
```

Example output:

```json
{
  "data": {
    "type": "file--file",
    "id": "b4b921e1-bdd4-47d7-a380-b573cbae262a"
  }
}
```

This POST saves the file entity in Drupal and returns the file information. It is important to save the data.id field and use it to link to the actual work/expression.


2. Attach the file entity to the expression

Example:

WARNING: For the example below to work properly you must edit the IDs from the JSON file with those above.

```
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X PATCH -u $USER:$PASSWORD --data @05-attach-files-to-expression.json \
http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01
```

## Create a new expression

This example creates a new expression to the already existing work.

```
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $USER:$PASSWORD --data @06-create-expression.json http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01
```

Where `http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01` is the FRBR URI of an existing expression.

After the call a new node *revision* is created and set as the current revision if its field_publication_date is the latest. If you visit the "Revisions" tab for this node you will see it now has two revisions, and this one is the newest and the current revision. Visiting both revisions reveals the different field data.


## Translate an expression translation

This example creates a french translation for the expression above

```
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $USER:$PASSWORD --data @09-translate-expression.json http://liiweb.test/akn/za/act/2000/00/eng@2000-01-01
```

Where `http://liiweb.test/akn/za/act/2000/00/eng@2000-01-01` is the FRBR URI of the original translation for an expression.

After the call a new french translation exists for that expression. If you visit the "Translate" tab for this node a french translation will appear.


## Delete an expression

This example deletes an expression and all its translations

```
curl -X DELETE -u $USER:$PASSWORD http://liiweb.test/akn/za/act/1900/00/eng@1900-02-02
```

After the call the revision for this node will be deleted, both in English and French.

**Note**: You cannot delete a single translation of an expression of a work (i.e. only French translation) due to the way Drupal handles default translation: If you have a node translated in French while English is the default language, then English cannot be removed to leave only French in place. Therefore, to have a consistent behavior, when removing an expression all its translations are removed.


## Delete an entire work

1. Get the UUID of the node (`response.data.id`)
```
curl -X GET -H "Accept: application/json" -u $USER:$PASSWORD http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01
```

2. Delete the node
```
curl -X DELETE -u api_user:password http://liiweb.test/jsonapi/node/legislation/0a552e15-0fd1-43f7-b619-3b39b333c0ce
```
Where `0a552e15-0fd1-43f7-b619-3b39b333c0ce` is the node UUID

This will remove the whole node together with all its revisions.
