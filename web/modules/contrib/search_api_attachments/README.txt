INTRODUCTION
------------
Search Api Attachments

This module will extract the content out of attached files using chosen method
among:
 - the Tika App JAR
 - the Tika Server JAR
 - the build in Solr extractor
 - the Pdftotext command line tool
 - the python Pdf2txt extractor
 - the docconv extractor
and index it.
Search API attachments will index many file formats.

REQUIREMENTS
------------
This module needs search_api module to be enabled on your site.
Depending on the extracting method you want to use, you may need java on your
server or python or ...

HOOKS
-----
This module provides hook_search_api_attachments_indexable.
See more details in search_api_attachments.api.php

MODULE INSTALLATION
-------------------
Copy search_api_attachments into your modules folder

Install the search_api_attachments module in your Drupal site

Go to the configuration: admin/config/search/search_api_attachments

Choose an extraction method and follow the instructions under the respective
heading below.

DEVELOPMENT
-----------
To generate a pareview.sh report, submit the form in https://bit.ly/2TmdFFz
To check the number of items in search_api_attachments queue: drush queue-list
Items are added to the queue table in the database
To run items in the queue : drush queue-run search_api_attachments

EXTRACTION CONFIGURATION: TIKA APP
----------------------------------
On Ubuntu 18.04

Install java
> sudo apt-get install openjdk-7-jdk

Download Apache Tika App JAR: http://tika.apache.org/download.html
> wget http://mir2.ovh.net/ftp.apache.org/dist/tika/tika-app-1.18.jar

Enter the full path on your server where you downloaded the jar
e.g. /var/apache-tika/tika-app-1.18.jar.

EXTRACTION CONFIGURATION: TIKA SERVER
-------------------------------------
On Ubuntu 18.04

Install java
> sudo apt-get install openjdk-7-jdk

Download Apache Tika Server JAR: http://tika.apache.org/download.html
> wget https://www-eu.apache.org/dist/tika/tika-server-1.20.jar
OR
> wget https://www-us.apache.org/dist/tika/tika-server-1.20.jar

Launch Tika server
> java -jar tika-server-1.20.jar

Configure search_api_attachments to use it at the following path:
/admin/config/search/search_api_attachments

More info:
- https://wiki.apache.org/tika/TikaJAXRS
- https://github.com/apache/tika/tree/master/tika-server

EXTRACTION CONFIGURATION: SOLR
------------------------------
Install and configure the search_api_solr module
https://www.drupal.org/project/search_api_solr
Make sure to configure it as explained in its README.txt
Create at least one solr server (/admin/config/search/search-api/add-server)
Now you can choose it from /admin/config/search/search_api_attachments


EXTRACTION CONFIGURATION: PDFTOTEXT
-----------------------------------
Pdftotext is a command line utility tool included by default on many linux
distributions. See the wikipedia page for more info:
https://en.wikipedia.org/wiki/Pdftotext

EXTRACTION CONFIGURATION: PYTHON PDF2TXT
----------------------------------------
On Debian 8

Install python or make sure you already have it
Get Pdf2txt (https://github.com/euske/pdfminer)
Install Pdf2txt as described in https://github.com/euske/pdfminer
or try
> sudo apt-get install python-pdfminer

EXTRACTION CONFIGURATION: GO DOCCONV
------------------------------------
Install golang or make sure you already have it
get docconv (https://github.com/sajari/docconv)
Install docconv as described in https://github.com/sajari/docconv


SIMPLE USAGE EXAMPLE 1: FILE FIELDS CONTENT: FILE ENTITIES
----------------------------------------------------------
0) This is tested with :
   drupal 8.8.x
   search_api 8.x-1.x
   search_api_attachments 8.x-1.x

1) Install drupal, search_api search_api_db and search_api_attachments.

2) Go to admin/structure/types/manage/article/fields/add-field and add a
   file field 'My pdfs' (field_my_pdfs).

3) Go to node/add/article and add an article node with a pdf.

4) Go to admin/config/search/search_api_attachments and configure the
   Tika extractor.

5) Go to admin/config/search/search-api/add-server and add server 'My server'
   (my_server) with the default Database Backend.

6) Go to admin/config/search/search-api/add-index and add a new index 'My index'
   (my_index) with 'Content' as Data source and 'My server' as Server.

7) Go to admin/config/search/search-api/index/my_index/processors and enable
   the File attachments processor.

8) Go to admin/config/search/search-api/index/my_index/fields/add/nojs and:
   - in the General section, add the "Search api attachments: My pdfs" field.
   - in the Content section, add the "Title".
   - in the Content section, add the "Body".

9) Go to /admin/config/search/search-api/index/my_index/fields to configure
   "Search api attachments: My pdfs" and "Title" to Fulltext.

10) Go to admin/structure/views/add and add a Page view:
    - View name: SAA
    - View settings:Show: Index My index
    - Page settings: Check Create a page with title and path 'saa' that
      displays "Rendered entity" format.
      ("Search results" format seems not working for now)

11) Add a filter to the view: the 'Fulltext search' with
    - Operator : Contains any of these words
    - Check the Expose checkbox

12) Go to admin/structure/views/view/saa and in the "Exposed Form" section (in
       the ADVANCED section), hit the 'Basic' link and choose 'Input required'
       so that the view doesn't display any default results.

13) Go to admin/config/search/search-api/index/my_index and Index items.

14) Go to /saa and search for any term in the title, body or in the pdf file :)



SIMPLE USAGE EXAMPLE 2: MEDIA FIELDS CONTENT : MEDIA ENTITIES OF TYPE FILE
--------------------------------------------------------------------------
0) This is tested with :
   drupal 8.8.x
   search_api 8.x-1.x
   search_api_attachments 8.x-1.x

1) Install drupal, media, search_api search_api_db and search_api_attachments.

2) Go to admin/structure/types/manage/article/fields/add-field and add a
   media field 'My medias' (field_my_medias).
   (choose File in the Media type settings)

3 ) Go to media/add/file and add a media with a pdf file

4) Go to node/add/article and add an article node that references the media
   entity created at step 3

5) Configure the extractor at admin/config/search/search_api_attachments and Go
   to admin/config/search/search-api/add-server and add server 'My server'
   (my_server) with the default Database Backend.

6) Go to admin/config/search/search-api/add-index and add a new index 'My index'
   (my_index) with 'Content' as Data source and 'My server' as Server.

7) Go to admin/config/search/search-api/index/my_index/processors and enable
   the File attachments processor.

8) Go to admin/config/search/search-api/index/my_index/fields/add/nojs and:
   - in the General section, add the "Search api attachments: My medias" field.
   - in the Content section, add the "Title".
   - in the Content section, add the "Body".

9) Go to /admin/config/search/search-api/index/my_index/fields to configure
   "Search api attachments: My medias" and "Title" to Fulltext.

10) Go to admin/structure/views/add and add a Page view:
    - View name: SAA
    - View settings:Show: Index My index
    - Page settings: Check Create a page with title and path 'saa' that
      displays "Rendered entity" format.
    ("Search results" format seems not working for now)

11) Add a filter to the view: the 'Fulltext search' with
    - Operator : Contains any of these words
    - Check the Expose checkbox

12) Go to admin/structure/views/view/saa and in the "Exposed Form" section (in
       the ADVANCED section), hit the 'Basic' link and choose 'Input required'
       so that the view doesn't display any default results.

13) Go to admin/config/search/search-api/index/my_index and Index items.

14) Go to /saa and search for any term in the title, body or in the pdf file :)
