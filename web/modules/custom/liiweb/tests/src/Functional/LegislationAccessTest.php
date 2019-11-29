<?php
/**
 * @file NodeAccessTest.php
 */

namespace Drupal\Tests\liiweb\Functional;

use Drupal\node\Entity\Node;

/**
 * Class LegislationAccessTest
 *
 * @package Drupal\Tests\liiweb_api\Functional
 * @group liiweb
 */
class LegislationAccessTest extends LiiBrowserTestBase {

  public static $modules = ['node', 'liiweb_api'];

  /**
   * {@inheritDoc}
   */
  public function setUp() {
    parent::setUp();
    $this->createLegislationContentType();
  }

  /**
   * Test liiweb_api_node_access
   */
  public function testNodeAccess() {
    $api_user = $this->createUser(self::$permissions_api_legislation);
    $editor = $this->createUser(['create legislation content', 'edit any legislation content', 'delete any legislation content']);

    // 1. Arbitrary users are not able to edit API-imported legislation
    /** @var \Drupal\node\Entity\Node $node */
    $node = Node::create([
      'type' => 'legislation',
      'title' => 'Legislation #1 created by API user',
      'field_created_by_api' => true,
      'uid' => $api_user->id(),
      'status' => 1,
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
      'field_created_by_api' => true,
      'uid' => $api_user->id(),
      'status' => 1,
    ]);
    /** @var \Drupal\Core\Entity\EntityConstraintViolationListInterface $v */
    $v = $node->validate();
    if($v->count() > 0) {
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
