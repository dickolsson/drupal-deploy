<?php

namespace Drupal\Tests\deploy\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workspace\Functional\WorkspaceTestUtilities;

/**
 * Tests conflicts reporting.
 *
 * @group workspace
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class WorkspaceConflictReportingTest extends BrowserTestBase {
  use WorkspaceTestUtilities;

  public static $modules = [
    'node',
    'user',
    'block',
    'menu_link_content',
    'taxonomy',
    'workspace',
    'multiversion',
    'deploy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_menu_block:account');
    $this->drupalPlaceBlock('system_messages_block');
    $this->setupWorkspaceSwitcherBlock();
  }

  /**
   * {@inheritdoc}
   */
  public function testConflictErrorMessages() {
    $permissions = [
      'create_workspace',
      'edit_own_workspace',
      'view_own_workspace',
      'view_any_workspace',
      'edit_any_workspace',
      'update any workspace from upstream',
      'bypass_entity_access_own_workspace',
      'create test content',
      'access administration pages',
      'administer taxonomy',
      'administer menu',
      'access content overview',
      'administer content types',
      'administer workspaces',
      'administer deployments',
    ];

    $node_type = NodeType::create(['type' => 'test', 'label' => 'Test']);
    $node_type->save();
    node_add_body_field($node_type);
    $test_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($test_user);

    $live = $this->getOneEntityByLabel('workspace', 'Live');
    $stage = $this->getOneEntityByLabel('workspace', 'Stage');
    $live->set('upstream', $stage->id());
    $live->save();

    $vocabulary = Vocabulary::create(['name' => 'Tags', 'vid' => 'tags', 'hierarchy' => 0]);
    $vocabulary->save();

    $node = Node::create(['type' => 'test', 'title' => 'Node title']);
    $node->save();
    $term = Term::create(['vid' => 'tags', 'name' => 'Term title']);
    $term->save();
    $menu_link_content = MenuLinkContent::create([
      'title' => 'Menu item title',
      'menu_name' => 'main',
      'link' => ['uri' => 'route:<front>'],
    ]);
    $menu_link_content->save();

    $this->drupalGet('admin/structure/deployment/add');
    $this->assertSession()->pageTextContains('There are no conflicts.');
    $deployment = [
      'name[0][value]' => 'Deploy Live to Stage'
    ];
    $this->drupalPostForm('admin/structure/deployment/add', $deployment, t('Deploy to Stage'));

    $this->drupalGet('admin/structure/deployment');
    $this->assertSession()->pageTextContains($deployment['name[0][value]']);

    $node->body->value = $this->randomString(100);
    $node->save();
    $term->description->value = $this->randomString(100);
    $term->save();
    $menu_link_content->description->value = $this->randomString(100);
    $menu_link_content->save();

    $this->switchToWorkspace($stage);

    $node1 = $this->getOneEntityByLabel('node', 'Node title');
    $term1 = $this->getOneEntityByLabel('taxonomy_term', 'Term title');
    $menu_link_content1 = $this->getOneEntityByLabel('menu_link_content', 'Menu item title');

    $node1->body->value = $this->randomString(100);
    $node1->save();
    $term1->description->value = $this->randomString(100);
    $term1->save();
    $menu_link_content1->description->value = $this->randomString(100);
    $menu_link_content1->save();

    $this->drupalGet('admin/structure/deployment/add');
    $this->assertSession()->pageTextContains('There are no conflicts.');
    $deployment = [
      'name[0][value]' => 'Deploy Stage to Live'
    ];
    $this->drupalPostForm('admin/structure/deployment/add', $deployment, t('Deploy to Live'));

    $this->drupalGet('admin/structure/deployment/add');
    $this->assertSession()->pageTextNotContains('There are no conflicts.');

    $this->drupalGet('admin/structure/deployment/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('There are no conflicts.');
    $this->drupalGet('admin/structure/deployment/2');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('There are 3 conflict(s) with the Live workspace. Pushing changes to Live may result in unexpected behavior or data loss, and cannot be undone. Please proceed with caution.');

    $this->drupalGet("/admin/structure/workspace/{$live->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('There are no conflicts.');
    $this->drupalGet("/admin/structure/workspace/{$live->id()}/conflicts");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('There are no conflicts.');
    $this->drupalGet("/admin/structure/workspace/{$stage->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('There are 3 conflict(s) with the Live workspace. Pushing changes to Live may result in unexpected behavior or data loss, and cannot be undone. Please proceed with caution.');
    $this->drupalGet("/admin/structure/workspace/{$stage->id()}/conflicts");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('There are no conflicts.');
    $this->assertSession()->pageTextContains('Node title');
    $this->assertSession()->pageTextContains('Term title');
    $this->assertSession()->pageTextContains('Menu item title');
  }

}
