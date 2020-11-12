#!/bin/bash

USER="admin"
PASSWORD="password"

echo "Creating new work: /akn/za/act/1900/00/eng@1900-01-01"
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $USER:$PASSWORD --data @01-create-work.json http://liiweb.test/api/node/legislation
curl -sD - -H "Accept: application/vnd.api+json" -u $USER:$PASSWORD http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01 -o /dev/null

echo ""
echo "Creating new work (/akn/za/act/2000/00/eng@2000-01-01) with previous work as parent (/akn/za/act/1900/00/eng@1900-01-01)"
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $USER:$PASSWORD --data @02-create-work-with-parent.json http://liiweb.test/api/node/legislation
curl -H "Accept: application/vnd.api+json" -u $USER:$PASSWORD http://liiweb.test/akn/za/act/2000/00/eng@2000-01-01

echo ""
echo "Create new work (/akn/za/act/1800/00/eng@1800-01-01) repealed by the previously work (/akn/za/act/2000/00/eng@2000-01-01)"
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $USER:$PASSWORD --data @03-create-work-with-repeal.json http://liiweb.test/api/node/legislation
curl -H "Accept: application/vnd.api+json" -u $USER:$PASSWORD http://liiweb.test/akn/za/act/1800/00/eng@1800-01-01

echo ""
echo "Update existing expression of work (/akn/za/act/1900/00/eng@1900-01-01)"
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X PATCH -u $USER:$PASSWORD --data @04-update-expression.json http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01
curl -H "Accept: application/vnd.api+json" -u $USER:$PASSWORD http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01

echo ""
echo "Create a new expression /akn/za/act/1900/00/eng@1900-02-02"
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $USER:$PASSWORD --data @06-create-expression.json http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01
curl -H "Accept: application/vnd.api+json" -u $USER:$PASSWORD http://liiweb.test/akn/za/act/1900/00/eng@1900-02-02
curl -H "Accept: application/vnd.api+json" -u $USER:$PASSWORD http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01

echo ""
echo "Translate expression /akn/za/act/1900/00/eng@1900-02-02"
curl -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $USER:$PASSWORD --data @09-translate-expression.json http://liiweb.test/akn/za/act/2000/00/eng@2000-01-01
curl -H "Accept: application/vnd.api+json" -u $USER:$PASSWORD http://liiweb.test/akn/za/act/1900/00/fre@2000-01-01
curl -H "Accept: application/vnd.api+json" -u $USER:$PASSWORD http://liiweb.test/akn/za/act/1900/00/eng@1900-02-02
curl -H "Accept: application/vnd.api+json" -u $USER:$PASSWORD http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01

echo ""
echo "Delete expression /akn/za/act/1900/00/eng@1900-02-02"
curl -X DELETE -u $USER:$PASSWORD http://liiweb.test/akn/za/act/1900/00/eng@1900-02-02
curl -H "Accept: application/vnd.api+json" -u $USER:$PASSWORD http://liiweb.test/akn/za/act/1900/00/fre@2000-01-01
curl -H "Accept: application/vnd.api+json" -u $USER:$PASSWORD http://liiweb.test/akn/za/act/1900/00/eng@1900-02-02
curl -H "Accept: application/vnd.api+json" -u $USER:$PASSWORD http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01