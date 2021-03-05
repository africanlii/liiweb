<?php

namespace Drupal\Tests\liiweb_api\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Class LiiWebApiTest
 *
 * @package Drupal\Tests\liiweb_api\Functional
 * @group liiweb
 */
class LiiWebApiTest extends LiiWebApiTestBase {

  /**
   * Test GET calls for the API.
   */
  public function testApiGet() {
    $this->createTestNode('/akn/za/act/1993/31');
    $this->checkApiGetOnUri('/akn/za/act/1993/31');

    $this->createTestNode('/akn/za/act/by-law/1993/test-bylaw');
    $this->checkApiGetOnUri('/akn/za/act/by-law/1993/test-bylaw');

  }

  /**
   * Helper function used to check if GET works properly.
   *
   * @param $uri
   *   The base of the AKN URI of a node created with the createTestNode function.
   *   Example: /akn/za/act/by-law/1993/test-bylaw
   */
  protected function checkApiGetOnUri($uri) {
    $getUrl = ltrim($uri, '/');

    // Check that GET to every revision is working
    $this->drupalGet("$getUrl/eng@1994-01-31");
    $this->assertText('Legislation new');

    $this->drupalGet("$getUrl/eng@1993-01-31");
    $this->assertText('Legislation old');

    $this->drupalGet("$getUrl/fra@1994-01-31");
    $this->assertText('Legislation new fr');

    $this->drupalGet("$getUrl/fra@1993-01-31");
    $this->assertText('Legislation old fr');

    // Check that we get a JSON as a response when setting the Accept: application/json header.
    $response = $this->getJsonFromUri("$uri/eng@1994-01-31");
    $this->assertTrue(strpos($response, '"title":"Legislation new"') !== FALSE);

    $response = $this->getJsonFromUri("$uri/eng@1993-01-31");
    $this->assertTrue(strpos($response, '"title":"Legislation old"') !== FALSE);

    $response = $this->getJsonFromUri("$uri/fra@1994-01-31");
    $this->assertTrue(strpos($response, '"title":"Legislation new fr"') !== FALSE);

    $response = $this->getJsonFromUri("$uri/fra@1993-01-31");
    $this->assertTrue(strpos($response, '"title":"Legislation old fr"') !== FALSE);
  }

  /**
   * Test delete calls for API.
   */
  public function testApiDelete() {
    $node = $this->createTestNode();
    $nid = $node->id();

    $this->drupalGet('akn/za/act/1993/31/fra@1994-01-31');
    $this->assertText('Legislation new fr');

    // Access denied for anonymous users on DELETE.
    $response = $this->apiRequest('/akn/za/act/1993/31/fra@1994-01-31', 'DELETE');
    $this->assertEqual($response->getStatusCode(), 401);

    // Try to delete a translation.
    $response = $this->apiRequest('/akn/za/act/1993/31/fra@1994-01-31', 'DELETE', TRUE);
    $this->assertEqual($response->getStatusCode(), 204);
    $this->drupalGet('akn/za/act/1993/31/fra@1994-01-31');
    $this->assertResponse(404);

    // Delete the default revision.
    $response = $this->apiRequest('/akn/za/act/1993/31/eng@1994-01-31', 'DELETE', TRUE);
    $this->assertEqual($response->getStatusCode(), 204);

    // Check that the default revision is now the older one.
    $node = Node::load($nid);
    $this->assertEqual($node->getTitle(), 'Legislation old');

    // Try to delete the only revision - the node gets deleted.
    $response = $this->apiRequest('/akn/za/act/1993/31/eng@1993-01-31', 'DELETE', TRUE);
    $this->assertEqual($response->getStatusCode(), 204);

    drupal_flush_all_caches();
    $node = Node::load($nid);
    $this->assertTrue(!$node instanceof NodeInterface);
  }

  /**
   * Test PATCH calls to the API.
   */
  public function testApiPatch() {
    $this->createTestNode();

    $this->drupalGet('akn/za/act/1993/31/fra@1994-01-31');
    $this->assertText('Legislation new fr');

    $data = [
      'data' => [
        'type' => 'node--legislation',
        'attributes' => [
          'title' => 'Title v2 FR',
        ],
      ],
    ];

    // Access denied for anonymous users on PATCH.
    $response = $this->apiRequest('/akn/za/act/1993/31/fra@1994-01-31', 'PATCH', FALSE, $data);
    $this->assertEqual($response->getStatusCode(), 401);

    $response = $this->apiRequest('/akn/za/act/1993/31/fra@1994-01-31', 'PATCH', TRUE, $data);
    $this->assertEqual($response->getStatusCode(), 200);
    $this->drupalGet('akn/za/act/1993/31/fra@1994-01-31');
    $this->assertText('Title v2 FR');

    $this->drupalGet('akn/za/act/1993/31/eng@1994-01-31');
    $this->assertText('Legislation new');
    $data['data']['attributes']['title'] = 'Title v2 EN';
    $response = $this->apiRequest('/akn/za/act/1993/31/eng@1994-01-31', 'PATCH', TRUE, $data);
    $this->assertEqual($response->getStatusCode(), 200);
    $this->drupalGet('akn/za/act/1993/31/eng@1994-01-31');
    $this->assertText('Title v2 EN');

    $this->drupalGet('akn/za/act/1993/31/eng@1993-01-31');
    $this->assertText('Legislation old');
    $data['data']['attributes']['title'] = 'Title old v2 EN';
    $response = $this->apiRequest('/akn/za/act/1993/31/eng@1993-01-31', 'PATCH', TRUE, $data);
    $this->assertEqual($response->getStatusCode(), 200);
    $this->drupalGet('akn/za/act/1993/31/eng@1993-01-31');
    $this->assertText('Title old v2 EN');
  }

  /**
   * Test node creation/translation through the API.
   */
  public function testApiPost() {
    $data = [
      "data" => [
        "type" => "node--legislation",
        "attributes" => [
          "title" => "Work: /akn/za/act/1993/31/eng@1993-01-31 - Simple work (first expression)",
          "langcode" => "en",
          "field_expression_date" => "1993-01-31",
          "field_date" => "1993-01-31",
          "field_publication_name" => "Work: Original publication name",
          "field_frbr_uri" => "/akn/za/act/1993/31/eng@1993-01-31"
        ],
        "relationships" => [
          "field_tags" => [
            "data" => [
              [
                "type" => "taxonomy_term--legislation_tags",
                "tid" => "tid",
                "id" => "virtual",
                "attributes" => [
                  "name" => "Tag #1"
                ]
              ],
              [
                "type" => "taxonomy_term--legislation_tags",
                "tid" => "tid",
                "id" => "virtual",
                "attributes" => [
                  "name" => "Tag #2"
                ]
              ]
            ]
          ]
        ]
      ]
    ];
    $response = $this->apiRequest('/api/node/legislation', 'POST', FALSE, $data);
    $this->assertEqual($response->getStatusCode(), 401);

    $response = $this->apiRequest('/api/node/legislation', 'POST', TRUE, $data);
    $this->assertEqual($response->getStatusCode(), 201);

    $this->drupalGet('akn/za/act/1993/31/eng@1993-01-31');
    $this->assertResponse(200);

    $body = json_decode($response->getBody(), TRUE);
    $nid = $body['data']['attributes']['drupal_internal__nid'];
    $node = Node::load($nid);
    $this->assertEqual($node->getTitle(), "Work: /akn/za/act/1993/31/eng@1993-01-31 - Simple work (first expression)");
    $this->assertEqual($node->get('field_tags')->get(0)->entity->getName(), 'Tag #1');
    $this->assertEqual($node->get('field_tags')->get(1)->entity->getName(), 'Tag #2');

    $data['data']['attributes']['langcode'] = 'fr';
    $data['data']['attributes']['title'] = 'Title FR';
    // We forgot to change the FRBR URI - cannot have 2 revisions with the same URI.
    $response = $this->apiRequest('/akn/za/act/1993/31/eng@1993-01-31', 'POST', TRUE, $data);
    $this->assertEqual($response->getStatusCode(), 422);

    $data['data']['attributes']['field_frbr_uri'] = '/akn/za/act/1993/31/fra@1993-01-31';

    // Cannot create translations for revisions that don't exist.
    $response = $this->apiRequest('/akn/za/act/1993/31/eng@1994xxx-01-31', 'POST', TRUE, $data);
    $this->assertEqual($response->getStatusCode(), 404);

    // Create french translation.
    $response = $this->apiRequest('/akn/za/act/1993/31/eng@1993-01-31', 'POST', TRUE, $data);
    $this->assertEqual($response->getStatusCode(), 201);

    $this->drupalGet('akn/za/act/1993/31/fra@1993-01-31');
    $this->assertResponse(200);
    $this->assertText('Title FR');

    $this->drupalGet('akn/za/act/1993/31/eng@1993-01-31');
    $this->assertResponse(200);
    $this->assertText("Work: /akn/za/act/1993/31/eng@1993-01-31 - Simple work (first expression)");

    $data['data']['attributes']['title'] = 'Title v2';
    $data['data']['attributes']['langcode'] = 'en';

    $response = $this->apiRequest('/akn/za/act/1993/31/eng@1993-01-31', 'POST', TRUE, $data);
    // We forgot to change the FRBR URI - cannot have 2 revisions with the same URI.
    $this->assertEqual($response->getStatusCode(), 422);

    $data['data']['attributes']['field_frbr_uri'] = '/akn/za/act/1993/31/eng@1994-01-31';
    $response = $this->apiRequest('/akn/za/act/1993/31/eng@1993-01-31', 'POST', TRUE, $data);
    $this->assertEqual($response->getStatusCode(), 201);
    $this->drupalGet('akn/za/act/1993/31/eng@1994-01-31');
    $this->assertText('Title v2');

    $response = $this->apiRequest('/api/node/legislation', 'POST', TRUE, $data);
    // Cannot have 2 revisions with the same FRBR URI.
    $this->assertEqual($response->getStatusCode(), 422);
  }

  /**
   * Check that language negotiation works properly.
   */
  public function testLanguageNegotiation() {
    $this->createTestNode();

    $this->drupalGet('node');
    $this->assertRaw('/akn/za/act/1993/31/eng@1994-01-31');

    $this->drupalGet('fr/node');
    $this->assertRaw('/akn/za/act/1993/31/fra@1994-01-31');
  }

}
