# AfricanLII JsonAPI

This is a list of valid CURL requests for the AfricanLII JsonAPI endpoint.

Please note that these examples will only work if the CURL command is run from this folder (or from a folder where the json files with data exist)

## Creating a new legislation

curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u admin:password --data @new_legislation.json \
http://africanlii.local/api/node/legislation

## Edit a revision

curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X PATCH -u admin:password --data @update_legislation.json \
http://africanlii.local/akn/za/1993/31/eng@2017-03-11

## Create a new revision for a node

curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u admin:password --data @new_revision.json \
http://africanlii.local/akn/za/1993/31/eng@2019-03-11

## Create a new translation for a revision

curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u admin:password --data @new_translation.json \
http://africanlii.local/akn/za/1993/31/fra@2019-03-11
