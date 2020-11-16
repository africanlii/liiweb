#!/bin/bash

HTTP_USER="admin"
HTTP_PASSWORD="password"

echo "Creating new work: /akn/za/act/1900/00/eng@1900-01-01"
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $HTTP_USER:$HTTP_PASSWORD --data @01-create-work.json http://liiweb.test/api/node/legislation

echo ""
echo "Creating new work (/akn/za/act/2000/00/eng@2000-01-01) with previous work as parent (/akn/za/act/1900/00/eng@1900-01-01)"
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $HTTP_USER:$HTTP_PASSWORD --data @02-create-work-with-parent.json http://liiweb.test/api/node/legislation

echo ""
echo "Create new work (/akn/za/act/1800/00/eng@1800-01-01) repealed by the previously work (/akn/za/act/2000/00/eng@2000-01-01)"
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $HTTP_USER:$HTTP_PASSWORD --data @03-create-work-with-repeal.json http://liiweb.test/api/node/legislation

echo ""
echo "Update existing expression of work (/akn/za/act/1900/00/eng@1900-01-01)"
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X PATCH -u $HTTP_USER:$HTTP_PASSWORD --data @04-update-expression.json http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01

echo ""
echo "Create a new expression /akn/za/act/1900/00/eng@1900-02-02"
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $HTTP_USER:$HTTP_PASSWORD --data @06-create-expression.json http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01

echo ""
echo "Translate expression /akn/za/act/1900/00/eng@1900-02-02"
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $HTTP_USER:$HTTP_PASSWORD --data @09-translate-expression.json http://liiweb.test/akn/za/act/2000/00/eng@2000-01-01

echo ""
echo "Delete expression /akn/za/act/1900/00/eng@1900-02-02"
curl -X DELETE -u $HTTP_USER:$HTTP_PASSWORD http://liiweb.test/akn/za/act/1900/00/eng@1900-02-02
