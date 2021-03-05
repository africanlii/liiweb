<?php

namespace Drupal\Tests\liiweb_api\Functional;

use Drupal\node\Entity\Node;

/**
 * Class LiiWebApiAccessTest
 *
 * @package Drupal\Tests\liiweb_api\Functional
 * @group liiweb
 */
class LiiWebApiAccessTest extends LiiWebApiTestBase {

  public function testNodeAccess() {
    $api_user = $this->createUser([
      'create legislation content',
      'delete own legislation content', 'edit own legislation content',
      'manipulate legislation api',
    ]);
    $editor = $this->createUser([
      'create legislation content',
      'edit any legislation content',
      'delete any legislation content',
    ]);

    // 1. Arbitrary users are not able to edit API-imported legislation
    /** @var \Drupal\node\Entity\Node $node */
    $node = Node::create([
      'type' => 'legislation',
      'title' => 'Legislation #1 created by API user',
      'field_created_by_api' => TRUE,
      'uid' => $api_user->id(),
      'status' => 1,
      'field_frbr_uri' => '/frbr1',
      'field_publication_name' => 'name',
      'field_expression_date' => '2000-01-01',
      'field_date' => '2000-01-01',
    ]);
    /** @var \Drupal\Core\Entity\EntityConstraintViolationListInterface $v */
    $v = $node->validate();
    if($v->count() > 0) {
      $this->fail('Failed to create Node #1');
    }
    $node->save();
    $this->drupalLogin($editor);
    $session = $this->getSession();
    $this->prepareRequest();
    $session->visit("/node/{$node->id()}/edit");
    $this->refreshVariables();
    $this->assertEquals(403, $session->getStatusCode());
    $this->drupalLogout();

    // 2. API users are able to edit their own legislation nodes
    /** @var \Drupal\node\Entity\Node $node */
    $node = Node::create([
      'type' => 'legislation',
      'title' => 'Legislation #2 created by API user',
      'field_created_by_api' => TRUE,
      'uid' => $api_user->id(),
      'status' => 1,
      'field_frbr_uri' => '/frbr2',
      'field_publication_name' => 'name',
      'field_expression_date' => '2000-01-01',
      'field_date' => '2000-01-01',
    ]);
    /** @var \Drupal\Core\Entity\EntityConstraintViolationListInterface $v */
    $v = $node->validate();
    if ($v->count() > 0) {
      $this->fail('Failed to create Node #2');
    }
    $node->save();

    $this->drupalLogin($api_user);
    $session = $this->getSession();
    $this->prepareRequest();
    $this->refreshVariables();
    $session->visit("/node/{$node->id()}/edit");
    $this->assertEquals(200, $session->getStatusCode());
  }

}
