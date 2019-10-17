<?php

namespace Drupal\Tests\search_api_attachments\Unit;

use Drupal\search_api_attachments\Plugin\search_api_attachments\SolrExtractor;
use Drupal\Tests\UnitTestCase;

/**
 * Tests extracting body text from xml.
 *
 * @covers \Drupal\search_api_attachments\Plugin\search_api_attachments\SolrExtractor::extractBody
 *
 * @group search_api_attachments
 */
class ExtractBody extends UnitTestCase {

  /**
   * Tests setting the Values.
   *
   * @dataProvider xmlstringProvider
   */
  public function testSetValues($xml, $expected) {
    $text = SolrExtractor::extractBody($xml);
    $this->assertEquals($text, $expected);
  }

  /**
   * XML and extracted body.
   *
   * @see testSetValue()
   */
  public function xmlstringProvider() {
    return [
      // Simple.
      ["<?xml?><html><head><title>ABC</title></head><body>No Tags</body></html>", "No Tags"],
      // UTF-8.
      ["<?xml?><html><head><title>ÄΒℂ</title></head><body>body ⊂ xml</body></html>", "body ⊂ xml"],
      // Tags.
      ["<?xml?><html><head><title>ABC</title></head><body><p>Text <em>with</em> Tags</p></body></html>", "<p>Text <em>with</em> Tags</p>"],
      // Body with attributes, and newlines.
      ["<?xml?><html><head><title>ABC</title></head><body attribute=\"value\"><p>Text\n <em>with</em>\n Tags</p></body></html>", "<p>Text\n <em>with</em>\n Tags</p>"],
      // Fall-back.
      ["<?xml?><custom><tag>Just strip everything and leave text</tag></custom>", "Just strip everything and leave text"],
      // Actual example.
      [
        '<?xml version="1.0" encoding="UTF-8"?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta
name="stream_size" content="22362"/>
<meta name="pdf:PDFVersion"
content="1.4"/>
<meta name="X-Parsed-By"
            content="org.apache.tika.parser.DefaultParser"/>
<meta
name="X-Parsed-By"
            content="org.apache.tika.parser.pdf.PDFParser"/>
<meta
name="xmp:CreatorTool" content="Writer"/>
<meta
name="stream_content_type" content="application/octet-stream"/>
<meta
name="meta:creation-date" content="2015-09-10T17:53:51Z"/>
<meta
name="stream_source_info" content="content"/>
<meta name="created"
            content="Thu Sep 10 17:53:51 UTC 2015"/>
<meta
name="xmpTPg:NPages" content="1"/>
<meta name="Creation-Date"
content="2015-09-10T17:53:51Z"/>
<meta name="resourceName"
            content="search_api_attachments_test_extraction.pdf"/>
<meta
name="dcterms:created" content="2015-09-10T17:53:51Z"/>
<meta
name="dc:format" content="application/pdf; version=1.4"/>
<meta
name="stream_name"
            content="/var/www/d8/fpp/fpp/web/sites/default/files/search_api_attachments_test_extraction.pdf"/>
<meta
name="pdf:encrypted" content="false"/>
<meta name="producer"
content="LibreOffice 4.3"/>
<meta name="Content-Type" content="application/pdf"/>
<title></title>
</head>
<body>
        <div class="page">
<p/>
<p>Congratulations!
The extraction seems working!
Yay!</p>
<p/>
</div>
</body>
</html>', '
        <div class="page">
<p/>
<p>Congratulations!
The extraction seems working!
Yay!</p>
<p/>
</div>
',
      ],
    ];
  }

}
