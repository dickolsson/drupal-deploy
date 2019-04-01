<?php

namespace Drupal\Tests\deploy\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workspace\Functional\WorkspaceTestUtilities;

/**
 * Tests workspace archive functionality.
 *
 * @group workspace
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class WorkspaceArchiveTest extends BrowserTestBase {
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

    $node_type = NodeType::create(['type' => 'test', 'label' => 'Test']);
    $node_type->save();
    node_add_body_field($node_type);

    $permissions = [
      'create_workspace',
      'edit_own_workspace',
      'view_own_workspace',
      'bypass_entity_access_own_workspace',
      'create test content',
      'access administration pages',
      'access content overview',
      'administer content types',
      'administer workspaces',
      'administer deployments',
      'Deploy to Live',
      'Deploy to Stage',
    ];
    $test_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($test_user);
  }

  /**
   * Tests Workspace archive as part of deploy.
   */
  public function testWorkspaceArchive() {
    $live = $this->getOneEntityByLabel('workspace', 'Live');
    $stage = $this->getOneEntityByLabel('workspace', 'Stage');
    $live->set('upstream', $stage->id());
    $live->save();
    $this->switchToWorkspace($stage);

    $node = Node::create(['type' => 'test', 'title' => 'Node title']);
    $node->save();

    $this->deployThroughUi($stage, $live, ['archive' => TRUE]);

    // Make sure archived workspace is not listed.
    $this->drupalGet('admin/structure/workspace');
    $this->assertSession()->pageTextNotContains('Stage');
  }

  /**
   * Tests archive of a target workspace.
   *
   * Deployment 1: SalesPromo => Stage (Archived) => Live.
   *   - Both deployments are successful.
   * Deployment 2: SalesPromo => Stage (Archived)
   *   - Should allow to activate Stage instead of failed deploy.
   */
  public function testTargetWorkspaceArchive() {
    $stage = $this->getOneEntityByLabel('workspace', 'Stage');

    $live = $this->getOneEntityByLabel('workspace', 'Live');
    $live->set('upstream', $stage->id());
    $live->save();

    $sales_promo = $this->createWorkspaceThroughUI('Sales promo', 'sales_promo');
    $sales_promo->set('upstream', $stage->id());
    $sales_promo->save();
    $this->switchToWorkspace($sales_promo);

    $node = Node::create(['type' => 'test', 'title' => 'March sales!']);
    $node->save();

    // 1. Deploy  Sales promo to Stage.
    $this->deployThroughUi($sales_promo, $stage);

    // 2. Deploy Stage to Live.
    $this->deployThroughUi($stage, $live, ['archive' => TRUE]);

    // 3. Switch to 'Sales promo' and deploy new content to Stage.
    $this->switchToWorkspace($sales_promo);
    $node = Node::create(['type' => 'test', 'title' => 'April sales!']);
    $node->save();

    // 4. Deploy  Sales promo to archived Stage.
    $this->drupalGet('admin/structure/deployment/add');
    $this->assertSession()->pageTextNotContains('Creating new deployments is not allowed at the moment. Contact somebody who has access to Status report page to unblock creating new content deployments.');
    $this->assertSession()->pageTextContains('Source and target must be set, make sure your current workspace has an upstream.');
  }

  /**
   * Deploy changes from source to target workspace via UI.
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $source
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $target
   * @param array $edit
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function deployThroughUi(WorkspaceInterface $source, WorkspaceInterface $target, array $edit = []) {
    $this->switchToWorkspace($source);
    $this->drupalGet('admin/structure/deployment/add');
    $this->assertSession()->pageTextContains('There are no conflicts.');
    $deployment = $edit + [
      'name[0][value]' => new FormattableMarkup('Deploy :source to :target', [':source' => $source->label(), ':target' => $target->label()]),
    ];
    $this->drupalPostForm('admin/structure/deployment/add', $deployment, new FormattableMarkup('Deploy to :target', [':target' => $target->label()]));
    \Drupal::service('cron')->run();

    $this->drupalGet('admin/structure/deployment');
    $this->assertSession()->pageTextContains($deployment['name[0][value]']);
  }

}
