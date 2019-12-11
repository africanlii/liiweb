<?php

namespace Drupal\Tests\liiweb_api\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Class LiiWebApiTest
 *
 * @package Drupal\Tests\liiweb_api\Functional
 * @group liiweb
 */
class LiiWebApiTest extends LiiWebApiTestBase {

  public static $modules = ['node', 'liiweb_api', 'liiweb_features', 'liiweb', 'path'];


  /**
   * Test GET calls for the API.
   */
  public function testApiGet() {
    $this->createTestNode();

    $this->drupalGet('akn/za/1993/31/eng@1994-01-31');
    $this->assertText('Legislation new');

    $this->drupalGet('akn/za/1993/31/eng@1993-01-31');
    $this->assertText('Legislation old');

    $this->drupalGet('akn/za/1993/31/fra@1994-01-31');
    $this->assertText('Legislation new fr');

    $this->drupalGet('akn/za/1993/31/fra@1993-01-31');
    $this->assertText('Legislation old fr');

    $this->drupalGet('akn/za/1993/31');
    $this->assertText('Legislation new');

    $response = $this->getJsonFromUri('/akn/za/1993/31/eng@1994-01-31');
    $this->assertTrue(strpos($response, '"title":"Legislation new"') !== FALSE);

    $response = $this->getJsonFromUri('/akn/za/1993/31/eng@1993-01-31');
    $this->assertTrue(strpos($response, '"title":"Legislation old"') !== FALSE);

    $response = $this->getJsonFromUri('/akn/za/1993/31/fra@1994-01-31');
    $this->assertTrue(strpos($response, '"title":"Legislation new fr"') !== FALSE);

    $response = $this->getJsonFromUri('/akn/za/1993/31/fra@1993-01-31');
    $this->assertTrue(strpos($response, '"title":"Legislation old fr"') !== FALSE);

    $response = $this->getJsonFromUri('/akn/za/1993/31');
    $this->assertTrue(strpos($response, '"title":"Legislation new"') !== FALSE);
  }

  /**
   * Test delete calls for API.
   */
  public function testApiDelete() {
    $this->createTestNode();

    $this->drupalGet('akn/za/1993/31/fra@1994-01-31');
    $this->assertText('Legislation new fr');

    $response = $this->apiRequest('/akn/za/1993/31/fra@1994-01-31', 'DELETE');
    $this->assertEqual($response->getStatusCode(), 403);

    $response = $this->apiRequest('/akn/za/1993/31/fra@1994-01-31', 'DELETE', TRUE);
    $this->assertEqual($response->getStatusCode(), 204);
    $this->drupalGet('akn/za/1993/31/fra@1994-01-31');
    $this->assertResponse(404);

    $response = $this->apiRequest('/akn/za/1993/31/eng@1994-01-31', 'DELETE', TRUE);
    $this->assertEqual($response->getStatusCode(), 204);
    $response = $this->apiRequest('/akn/za/1993/31/eng@1994-01-31', 'DELETE', TRUE);
    $this->assertEqual($response->getStatusCode(), 204);

    $this->drupalGet('akn/za/1993/31');
    $this->assertText('Legislation old');

    $response = $this->apiRequest('/akn/za/1993/31/eng@1993-01-31', 'DELETE', TRUE);
    $this->assertEqual($response->getStatusCode(), 400);

    $urlAliases = \Drupal::database()->select('url_alias', 'u');
    $urlAliases->addField('u', 'alias');
    $urlAliases->orderBy('langcode', 'asc');
    $result = $urlAliases->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEqual(count($result), 2);
    $this->assertEqual($result[0]['alias'], '/akn/za/1993/31/eng@1993-01-31');
    $this->assertEqual($result[1]['alias'], '/akn/za/1993/31/fra@1993-01-31');

    $response = $this->apiRequest('/akn/za/1993/31', 'DELETE', TRUE);
    $this->assertEqual($response->getStatusCode(), 204);

    $this->drupalGet('akn/za/1993/31');
    $this->assertResponse(404);
  }

}
