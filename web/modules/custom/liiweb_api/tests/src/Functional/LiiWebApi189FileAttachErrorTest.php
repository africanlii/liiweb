<?php

namespace Drupal\Tests\liiweb_api\Functional;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use GuzzleHttp\RequestOptions;

/**
 * Class LiiWebApiTest
 *
 * @package Drupal\Tests\liiweb_api\Functional
 * @group liiweb
 */
class LiiWebApi189FileAttachErrorTest extends LiiWebApiTestBase {

  /**
   * Test responses are not cached f5b39da976756cbf7a05584f513a20bfd08a4fe3
   */
  public function testAttachFileToRevision() {
    $request_options = [];
    // Upload the file
    $headers = [
      'Content-Type' => 'application/octet-stream',
      'Content-Disposition' => 'file; filename="legislation-rev1.txt"',
      'Accept' => 'application/vnd.api+json',
    ];
    $request_options[RequestOptions::AUTH] = [static::API_USER, 'password'];
    $request_options[RequestOptions::HEADERS] = $headers;
    $request_options[RequestOptions::BODY] = 'This is the file content of the legislation TXT file';
    $self_link = Url::fromUri("base:/jsonapi/node/legislation/field_files")->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    $response = $this->getHttpClient()->request('POST', $self_link, $request_options);
    $this->assertEquals(201, $response->getStatusCode(), 'Failed to upload file');
    $content = $response->getBody()->getContents();
    $decoded = json_decode($content, TRUE);
    $fuuid = $decoded['data']['id'];

    // Create the work
    $payload = <<<EOT
    {
      "data": {
        "type": "node--legislation",
        "attributes": {
          "title": "Work /akn/za/act/1900/00/eng@1900-01-01",
          "langcode": "en",
          "field_date": "1900-01-01",
          "field_expression_date": "1900-01-01",
          "field_publication_name": "Publication name",
          "field_frbr_uri": "/akn/za/act/1900/00/eng@1900-01-01",
          "field_content": {
            "value": "This is a work"
          }
        }
      }
    }
    EOT;
    $payload = json_decode($payload, TRUE);

    # curl --fail -H "Content-Type: application/vnd.api+json; Accept: application/vnd.api+json" -X POST -u $HTTP_USER:$HTTP_PASSWORD --data @01-create-work.json
    # http://liiweb.test/api/node/legislation
    $request_options = [];
    // Upload the file
    $headers = [
      'Content-Type' => 'application/vnd.api+json',
      'Accept' => 'application/vnd.api+json',
    ];
    $request_options[RequestOptions::AUTH] = [static::API_USER, 'password'];
    $request_options[RequestOptions::HEADERS] = $headers;
    $request_options[RequestOptions::JSON] = $payload;
    $self_link = Url::fromUri("base:/api/node/legislation")->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    $response = $this->getHttpClient()->request('POST', $self_link, $request_options);
    $this->assertEquals(201, $response->getStatusCode(), 'Failed to create record');

    # Attach
    # curl --fail -H "Accept: application/json" -u $HTTP_USER:$HTTP_PASSWORD http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01
    # http://liiweb.test/akn/za/act/1900/00/eng@1900-01-01
    $payload = <<<EOT
      {"data": {
          "type": "node--legislation",
          "relationships": {
              "field_files": {
                  "data": [{
                      "type": "file--file",
                      "id": "{$fuuid}"
                  }]
              }
          }
      }}
    EOT;
    $payload = json_decode($payload, TRUE);
    $request_options = [];
    // Upload the file
    $headers = [
      'Accept' => 'application/vnd.api+json',
    ];
    $request_options[RequestOptions::AUTH] = [static::API_USER, 'password'];
    $request_options[RequestOptions::HEADERS] = $headers;
    $request_options[RequestOptions::JSON] = $payload;
    $self_link = Url::fromUri("base:")->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    $response = $this->getHttpClient()->request('PATCH', $self_link . 'akn/za/act/1900/00/eng@1900-01-01', $request_options);
    $this->assertEquals(200, $response->getStatusCode(), 'Failed to attach the file');
  }
}
