<?php

namespace Drupal\Tests\deploy\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests access for creating deployments.
 *
 * @group workspace
 */
class DeploymentAccessTest extends BrowserTestBase {

  public static $modules = [
    'user',
    'deploy',
    'toolbar',
  ];

  /**
   * Test deployment access.
   */
  public function testDeploymentAccess() {
    $web_assert = $this->assertSession();

    // Create and login a user with limited permissions.
    $permissions = [
      'access administration pages',
      'administer workspaces',
      'access toolbar',
    ];
    $test_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($test_user);

    // Check the user can't access the deploy link or the deploy page for Live.
    $this->drupalGet('<front>');
    $web_assert->linkExists('Live');
    $web_assert->linkNotExists('Deploy');
    $this->drupalGet('/admin/structure/deployment/add');
    $web_assert->statusCodeEquals('403');

    // Switch to the stage workspace.
    $this->drupalPostForm('/admin/structure/workspace/2/activate', [], 'Activate');

    // Check ther use can't access the deploy link or the deploy page for Stage.
    $this->drupalGet('<front>');
    $web_assert->linkExists('Stage');
    $web_assert->linkNotExists('Deploy');
    $this->drupalGet('/admin/structure/deployment/add');
    $web_assert->statusCodeEquals('403');

    // Give the user access to deploy to live.
    $test_user_roles = $test_user->getRoles();
    $this->grantPermissions(Role::load(reset($test_user_roles)), ['Deploy to Live']);

    // Check the use can access the deploy link or the deploy page for Stage.
    $this->drupalGet('<front>');
    $web_assert->linkExists('Stage');
    $web_assert->linkExists('Deploy');
    $this->drupalGet('/admin/structure/deployment/add');
    $web_assert->statusCodeEquals('200');
  }

}
