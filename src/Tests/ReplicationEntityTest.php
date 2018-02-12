<?php

namespace Drupal\deploy\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the replication entity.
 *
 * @group deploy
 */
class ReplicationEntityTest extends WebTestBase {

  protected $strictConfigSchema = FALSE;

  public static $modules = ['deploy'];

  /**
   * Functional test for Replication.
   */
  public function testReplication() {
    $this->webUser = $this->drupalCreateUser([
      'administer workspaces',
      'administer deployments',
      'Deploy to Live',
    ]);

    $this->drupalLogin($this->webUser);

    $this->drupalPostForm('admin/structure/workspace/2/activate', [], t('Activate'));

    $this->drupalGet('admin/structure/deployment/add');
    $deployment = [
      'name[0][value]' => 'Test Deployment',
    ];
    $this->drupalPostForm('admin/structure/deployment/add', $deployment, t('Deploy to Live'));

    $this->drupalGet('admin/structure/deployment');
    $this->assertText($deployment['name[0][value]'], 'Deployment found in list of deployments');
  }

}
