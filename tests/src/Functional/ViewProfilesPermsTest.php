<?php

namespace Drupal\Tests\view_profiles_perms\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;
use  Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;

/**
 * Tests the permissions provided by view_profile_perms module.
 *
 * @package Drupal\Tests\view_profiles_perms\Functional
 *
 * @group view_profiles_perms
 */
class ViewProfilesPermsTest extends BrowserTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'view_profiles_perms_test',
  ];

  /**
   * A user role.
   *
   * @var \Drupal\user\Entity\Role
   */
  protected $role;

  /**
   * Tests view profiles permissions.
   */
  public function testViewProfilePerms() {
    $assert = $this->assertSession();
    // Assert the right permissions appear in the UI.
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);
    $this->drupalGet('admin/people/permissions');
    $this->assertText('View profiles permissions');
    $this->assertText("Access Manager profiles");
    $this->assertText("Access Developer profiles");
    $assert->checkboxChecked('developer[access manager profiles]');
    $assert->checkboxNotChecked('anonymous[access user profiles]');
    $assert->checkboxNotChecked('authenticated[access user profiles]');

    // Create a user with each role.
    $developer = $this->drupalCreateUser();
    $developer->addRole('developer');
    $developer->save();
    $manager = $this->drupalCreateUser();
    $manager->addRole('manager');
    $manager->save();

    // Assert Developers can access Managers profiles.
    $this->drupalLogin($developer);
    $this->drupalGet('user/' . $manager->id());
    $this->assertResponse(200, "Developers can access Manager's profiles");
    // Assert Managers can't access Develoepers profiles.
    $this->drupalLogin($manager);
    $this->drupalGet('user/' . $developer->id());
    $this->assertResponse(403, "Managers can't access Developers's profiles");

    // Assert users with more than one role, and only one with access.
    $user = $this->drupalCreateUser();
    $user->addRole('developer');
    $user->addRole('manager');
    $user->save();
    $this->drupalLogin($developer);
    $this->drupalGet('user/' . $user->id());
    $this->assertResponse(200, "Developer can acces another user with both roles");

    // Assert that the global 'access user profiles' permission overrides our
    // permissions.
    $this->drupalLogin($admin);
    $this->drupalPostForm('admin/people/permissions', ['authenticated[access user profiles]' => TRUE], 'Save permissions');
    $assert->checkboxChecked('authenticated[access user profiles]');
    // Managers shoudl now be able to access Develoepers profiles.
    $this->drupalLogin($manager);
    $this->drupalGet('user/' . $developer->id());
    $this->assertResponse(200, "Managers can access Developers's profiles");
  }
}
