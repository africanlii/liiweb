<?php

namespace Drupal\Tests\liiweb_api\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\views\Tests\ViewTestData;
use GuzzleHttp\Exception\RequestException;

abstract class LiiWebApiTestBase extends BrowserTestBase {

  const API_USER = 'api@api.test';

  public static $modules = ['node', 'liiweb_api', 'liiweb_features', 'liiweb', 'path'];

  /**
   * {@inheritDoc}
   */
  public function setUp() {
    parent::setUp();

    \Drupal::configFactory()->getEditable('jsonapi.settings')->set('read_only', FALSE)->save();
    \Drupal::configFactory()->getEditable('language.types')
      ->set('negotiation.language_content.enabled', [
        'language-frbr-uri' => -9,
        'language-interface' => 9,
      ])
      ->set('negotiation.language_url.enabled', [
        'language-frbr-uri' => 0,
        'language-url-fallback' => 1,
      ])
      ->set('negotiation.language_interface.enabled', [
        'language-frbr-uri' => -20,
        'language-user' => -16,
        'language-browser' => -15,
        'language-selected' => -14,
      ])
      ->save();

    $role = Role::load('anonymous');
    $role->grantPermission('view legislation revisions');
    $role->save();

    /** @var \Drupal\user\Entity\User $apiUser */
    $apiUser = User::create([
      'name' => static::API_USER,
      'mail' => static::API_USER,
    ]);
    $apiUser->setPassword('password');
    $apiUser->set('status', 1);
    $apiUser->addRole('api_legislation');
    $apiUser->save();

    ViewTestData::createTestViews(self::class, static::$modules);
  }

  /**
   * Create a node with 2 revisions, both translated.
   */
  protected function createTestNode() {
    $node = Node::create([
      'type' => 'legislation',
      'title' => 'Legislation old',
      'field_frbr_uri' =>'/akn/za/1993/31/eng@1993-01-31',
      'field_publication_date' => '1993-01-31',
      'uid' => user_load_by_mail(static::API_USER),
    ]);
    $node->save();

    $node->addTranslation('fr', [
      'field_frbr_uri' =>'/akn/za/1993/31/fra@1993-01-31',
      'title' => 'Legislation old fr',
      'uid' => user_load_by_mail(static::API_USER),
      'field_publication_date' => '1993-01-31',
    ]);
    $node->setNewRevision(FALSE);
    $node->save();

    $node->get('field_frbr_uri')->setValue('/akn/za/1993/31/eng@1994-01-31');
    $node->get('field_publication_date')->setValue('1994-01-31');
    $node->setTitle('Legislation new');
    $node->setNewRevision();
    $node->save();

    $node = Node::load($node->id());
    $node = $node->getTranslation('fr');
    $node->setTitle('Legislation new fr');
    $node->get('field_frbr_uri')->setValue('/akn/za/1993/31/fra@1994-01-31');
    $node->get('field_publication_date')->setValue('1994-01-31');
    $node->save();

    return $node;
  }

  /**
   * Get the body of a GET request with the Accept: application/json header.
   */
  protected function getJsonFromUri($uri) {
    $response = $this->apiRequest($uri, 'GET', FALSE, NULL, ['Accept' => 'application/json']);
    return $response->getBody();
  }

  /**
   * @param $uri
   * @param $method
   * @param null $data
   * @param array $headers
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  protected function apiRequest($uri, $method, $loginApiUser = FALSE, $data = NULL, $headers = ['Content-Type' => 'application/vnd.api+json', 'Accept: application/vnd.api+json']) {
    $client = $this->getHttpClient();
    $settings = [
      'headers' => $headers,
      'json' => $data,
    ];
    if ($loginApiUser) {
      $settings += ['auth'  => [static::API_USER, 'password']];
    }
    try {
      $response = $client->request($method, $this->getAbsoluteUrl($uri), $settings);
      return $response;
    }
    catch (RequestException $e) {
      return $e->getResponse();
    }
  }

  /**
   * Helper function to log in as an user.
   *
   * @param string $mail
   *   The user mail.
   */
  protected function userLogIn($mail) {
    $user = user_load_by_mail($mail);
    $user->passRaw = 'password';
    $this->drupalLogin($user);
  }

  /**
   * Helper function to login the API user.
   */
  protected function loginApiUser() {
    $this->userLogIn(static::API_USER);
  }

}
