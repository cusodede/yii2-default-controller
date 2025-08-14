<?php
declare(strict_types = 1);

use app\models\Users;
use app\models\VanillaUsers;
use Codeception\Exception\ModuleException;
use yii\base\InvalidConfigException;

/**
 * Comprehensive functional tests for DefaultController user workflows
 * 
 * This test class covers complete user journeys, integration scenarios,
 * multi-step workflows, and real-world usage patterns that users
 * would encounter when interacting with the application.
 */
class DefaultControllerWorkflowCest {

	/**
	 * Set up test environment before each test
	 * @param FunctionalTester $I
	 */
	public function _before(FunctionalTester $I): void {
		// Clean up any existing test data
		Users::deleteAll(['like', 'username', 'workflow_']);
		VanillaUsers::deleteAll(['like', 'username', 'workflow_']);
	}

	/**
	 * Clean up after each test
	 * @param FunctionalTester $I
	 */
	public function _after(FunctionalTester $I): void {
		Users::deleteAll(['like', 'username', 'workflow_']);
		VanillaUsers::deleteAll(['like', 'username', 'workflow_']);
	}

	// =========================================================================================
	// COMPLETE USER JOURNEY TESTS
	// =========================================================================================

	/**
	 * Test complete user management workflow
	 * 
	 * Simulates a complete user management scenario from initial
	 * navigation to final record management including all CRUD operations.
	 * 
	 * @param FunctionalTester $I
	 * @throws Exception|ModuleException|InvalidConfigException
	 */
	public function testCompleteUserManagementWorkflow(FunctionalTester $I): void {
		// Arrange: Set up authentication
		$adminUser = Users::CreateUser()->saveAndReturn();
		$I->amLoggedInAs($adminUser);
		
		// STEP 1: Navigate to user management
		$I->amOnRoute('users/index');
		$I->seeResponseCodeIs(200);
		$I->see('Сотрудники');
		
		// STEP 2: Create a new user - navigate directly since button placeholders aren't rendered
		$I->amOnRoute('users/create');
		$I->seeResponseCodeIs(200);
		$I->seeElement('#users-create'); // Check create form exists
		
		$newUserData = [
			'username' => 'workflow_new_employee',
			'login' => 'new.employee@company.com',
			'password' => 'secure_password123'
		];
		
		$I->submitForm('form', [
			'Users' => $newUserData
		]);
		
		// Should redirect to index after creation
		$I->seeInCurrentUrl('users/index');
		$I->see($newUserData['username']);
		
		// Verify user was created in database
		$createdUser = Users::findOne(['username' => $newUserData['username']]);
		$I->assertNotNull($createdUser);
		
		// STEP 3: View the created user
		$I->click("//a[contains(@href, 'users/view?id={$createdUser->id}')]");
		$I->seeResponseCodeIs(200);
		$I->see($newUserData['username']);
		$I->see($newUserData['login']);
		
		// STEP 4: Edit the user - navigate directly since buttons aren't visible
		$I->amOnRoute('users/edit', ['id' => $createdUser->id]);
		$I->seeResponseCodeIs(200);
		$I->seeInField('Users[username]', $newUserData['username']); // Form should be populated
		
		// Modify user data
		$updatedData = [
			'username' => 'workflow_updated_employee',
			'login' => 'updated.employee@company.com',
			'password' => 'new_secure_password456'
		];
		
		$I->submitForm('form', [
			'Users' => $updatedData
		]);
		
		// Should redirect to index after update
		$I->seeInCurrentUrl('users/index');
		$I->see($updatedData['username']);
		$I->dontSee($newUserData['username']); // Old name shouldn't appear
		
		// Verify update in database
		$createdUser->refresh();
		$I->assertEquals($updatedData['username'], $createdUser->username);
		$I->assertEquals($updatedData['login'], $createdUser->login);
		
		// STEP 5: Search for the user
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'updated_employee'
		]);
		$I->see($updatedData['username']);
		$I->dontSee($adminUser->username); // Should be filtered out
		
		// STEP 6: Delete the user
		$I->click("//a[contains(@href, 'users/delete?id={$createdUser->id}')]");
		$I->seeInCurrentUrl('users/index');
		$I->dontSee($updatedData['username']); // Should be removed from list
		
		// Verify deletion in database
		$deletedUser = Users::findOne($createdUser->id);
		$I->assertNull($deletedUser);
	}

	/**
	 * Test bulk user management workflow
	 * 
	 * Simulates managing multiple users in sequence,
	 * testing efficiency and consistency of operations.
	 * 
	 * @param FunctionalTester $I
	 * @throws Exception|ModuleException|InvalidConfigException
	 */
	public function testBulkUserManagementWorkflow(FunctionalTester $I): void {
		// Arrange
		$adminUser = Users::CreateUser()->saveAndReturn();
		$I->amLoggedInAs($adminUser);
		
		// STEP 1: Create multiple users in sequence
		$usersToCreate = [
			['username' => 'workflow_employee_01', 'login' => 'emp01@company.com'],
			['username' => 'workflow_employee_02', 'login' => 'emp02@company.com'],
			['username' => 'workflow_employee_03', 'login' => 'emp03@company.com'],
		];
		
		$createdUserIds = [];
		
		foreach ($usersToCreate as $userData) {
			$I->amOnRoute('users/create');
			$I->submitForm('form', [
				'Users' => array_merge($userData, ['password' => 'bulk_password'])
			]);
			
			$I->seeInCurrentUrl('users/index');
			$I->see($userData['username']);
			
			// Track created user
			$createdUser = Users::findOne(['username' => $userData['username']]);
			$createdUserIds[] = $createdUser->id;
		}
		
		// STEP 2: Verify all users appear in index
		$I->amOnRoute('users/index');
		foreach ($usersToCreate as $userData) {
			$I->see($userData['username']);
		}
		
		// STEP 3: Search and filter operations
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'workflow_employee'
		]);
		
		// Should see all workflow employees
		foreach ($usersToCreate as $userData) {
			$I->see($userData['username']);
		}
		$I->dontSee($adminUser->username); // Admin should be filtered out
		
		// STEP 4: Update users in batch workflow
		foreach ($createdUserIds as $index => $userId) {
			$I->amOnRoute("users/edit?id={$userId}");
			$I->submitForm('form', [
				'Users' => [
					'username' => "workflow_updated_employee_{$index}",
					'login' => "updated_emp{$index}@company.com",
					'password' => 'updated_password'
				]
			]);
			$I->seeInCurrentUrl('users/index');
		}
		
		// STEP 5: Verify batch updates
		$I->amOnRoute('users/index');
		for ($i = 0; $i < count($createdUserIds); $i++) {
			$I->see("workflow_updated_employee_{$i}");
		}
		
		// Original names should not appear
		foreach ($usersToCreate as $userData) {
			$I->dontSee($userData['username']);
		}
	}

	/**
	 * Test user workflow with validation errors
	 * 
	 * Simulates real user scenarios where validation errors occur
	 * and tests the recovery and correction workflow.
	 * 
	 * @param FunctionalTester $I
	 * @throws Exception|ModuleException|InvalidConfigException
	 */
	public function testUserWorkflowWithValidationErrors(FunctionalTester $I): void {
		// Arrange
		$adminUser = Users::CreateUser()->saveAndReturn();
		$I->amLoggedInAs($adminUser);
		
		// STEP 1: Attempt to create user with invalid data
		$I->amOnRoute('users/create');
		$I->submitForm('form', [
			'Users' => [
				'username' => '',  // Invalid: empty required field
				'login' => '',     // Invalid: empty required field
				'password' => ''   // Invalid: empty required field
			]
		]);
		
		// Should stay on create page with errors
		$I->seeInCurrentUrl('users/create');
		$I->see('cannot be blank');
		
		// STEP 2: Correct errors and resubmit
		$validData = [
			'username' => 'workflow_corrected_user',
			'login' => 'corrected@company.com',
			'password' => 'valid_password'
		];
		
		$I->submitForm('form', [
			'Users' => $validData
		]);
		
		// Should now succeed and redirect
		$I->seeInCurrentUrl('users/index');
		$I->see($validData['username']);
		
		// STEP 3: Test update with validation errors
		$createdUser = Users::findOne(['username' => $validData['username']]);
		$I->amOnRoute("users/edit?id={$createdUser->id}");
		
		// Submit with invalid data
		$I->submitForm('form', [
			'Users' => [
				'username' => '',  // Clear required field
				'login' => $createdUser->login,
				'password' => $createdUser->password
			]
		]);
		
		// Should stay on edit page with errors
		$I->seeInCurrentUrl("users/edit?id={$createdUser->id}");
		$I->see('cannot be blank');
		
		// STEP 4: Correct and resubmit edit
		$I->submitForm('form', [
			'Users' => [
				'username' => 'workflow_finally_corrected',
				'login' => $createdUser->login,
				'password' => $createdUser->password
			]
		]);
		
		// Should succeed
		$I->seeInCurrentUrl('users/index');
		$I->see('workflow_finally_corrected');
		$I->dontSee($validData['username']); // Old name gone
	}

	// =========================================================================================
	// INTEGRATION WORKFLOW TESTS
	// =========================================================================================

	/**
	 * Test workflow across different controller types
	 * 
	 * Tests integration between different DefaultController implementations
	 * (e.g., Users vs VanillaUsers) to ensure consistency.
	 * 
	 * @param FunctionalTester $I
	 * @throws Exception|ModuleException|InvalidConfigException
	 */
	public function testWorkflowAcrossDifferentControllerTypes(FunctionalTester $I): void {
		// Arrange
		$adminUser = Users::CreateUser()->saveAndReturn();
		$I->amLoggedInAs($adminUser);
		
		// STEP 1: Create regular user
		$I->amOnRoute('users/create');
		$regularUserData = [
			'username' => 'workflow_regular_user',
			'login' => 'regular@company.com',
			'password' => 'regular_password'
		];
		$I->submitForm('form', ['Users' => $regularUserData]);
		$I->seeInCurrentUrl('users/index');
		
		// STEP 2: Create vanilla user (different controller)
		$I->amOnRoute('vanilla-users/create');
		$vanillaUserData = [
			'username' => 'workflow_vanilla_user',
			'login' => 'vanilla@company.com',
			'password' => 'vanilla_password'
		];
		$I->submitForm('form', ['VanillaUsers' => $vanillaUserData]);
		$I->seeInCurrentUrl('vanilla-users/index');
		
		// STEP 3: Verify both users exist independently
		$regularUser = Users::findOne(['username' => $regularUserData['username']]);
		$vanillaUser = VanillaUsers::findOne(['username' => $vanillaUserData['username']]);
		
		$I->assertNotNull($regularUser);
		$I->assertNotNull($vanillaUser);
		
		// STEP 4: Test operations on both types
		// View regular user
		$I->amOnRoute("users/view?id={$regularUser->id}");
		$I->see($regularUserData['username']);
		
		// View vanilla user
		$I->amOnRoute("vanilla-users/view?id={$vanillaUser->id}");
		$I->see($vanillaUserData['username']);
		
		// STEP 5: Test search on both controllers
		$I->amOnRoute('users/index', ['UsersSearch[username]' => 'regular']);
		$I->see($regularUserData['username']);
		$I->dontSee($vanillaUserData['username']);
		
		$I->amOnRoute('vanilla-users/index', ['VanillaUsersSearch[username]' => 'vanilla']);
		$I->see($vanillaUserData['username']);
		$I->dontSee($regularUserData['username']);
	}

	/**
	 * Test workflow with session persistence
	 * 
	 * Verifies that user sessions and state are maintained
	 * across different operations and page navigations.
	 * 
	 * @param FunctionalTester $I
	 * @throws Exception|ModuleException|InvalidConfigException
	 */
	public function testWorkflowWithSessionPersistence(FunctionalTester $I): void {
		// Arrange
		$adminUser = Users::CreateUser()->saveAndReturn();
		$I->amLoggedInAs($adminUser);
		
		// STEP 1: Set up initial state with search
		$testUser = Users::CreateUser();
		$testUser->username = 'workflow_session_test';
		$testUser->login = 'session@test.com';
		$testUser->password = 'session_password';
		$testUser->saveAndReturn();
		
		$I->amOnRoute('users/index', ['UsersSearch[username]' => 'session']);
		$I->see($testUser->username);
		
		// STEP 2: Navigate to different action and back
		$I->amOnRoute("users/view?id={$testUser->id}");
		$I->see($testUser->username);
		
		// Return to index
		$I->amOnRoute('users/index');
		// Note: Search filter persistence would depend on implementation
		
		// STEP 3: Perform update operation
		$I->amOnRoute("users/edit?id={$testUser->id}");
		$I->submitForm('form', [
			'Users' => [
				'username' => 'workflow_session_updated',
				'login' => $testUser->login,
				'password' => $testUser->password
			]
		]);
		
		// STEP 4: Verify session maintained through operations
		$I->seeInCurrentUrl('users/index');
		$I->see('workflow_session_updated');
		
		// User should still be logged in
		$I->amOnRoute('users/create');
		$I->seeResponseCodeIs(200); // Should not be redirected to login
	}

	// =========================================================================================
	// ERROR RECOVERY WORKFLOW TESTS
	// =========================================================================================

	/**
	 * Test workflow recovery from various error conditions
	 * 
	 * Simulates error scenarios and tests how well users
	 * can recover and continue their workflow.
	 * 
	 * @param FunctionalTester $I
	 * @throws Exception|ModuleException|InvalidConfigException
	 */
	public function testWorkflowErrorRecoveryScenarios(FunctionalTester $I): void {
		// Arrange
		$adminUser = Users::CreateUser()->saveAndReturn();
		$I->amLoggedInAs($adminUser);
		
		// SCENARIO 1: 404 Error Recovery
		$I->amOnRoute('users/view?id=99999');
		$I->seeResponseCodeIs(404);
		
		// Should be able to navigate back to working area
		$I->amOnRoute('users/index');
		$I->seeResponseCodeIs(200);
		
		// SCENARIO 2: Bad Request Recovery
		$I->amOnRoute('users/view'); // Missing required ID
		$I->seeResponseCodeIs(400);
		
		// Should be able to continue working
		$I->amOnRoute('users/create');
		$I->seeResponseCodeIs(200);
		
		// SCENARIO 3: Create user after error to verify system still works
		$recoveryData = [
			'username' => 'workflow_error_recovery',
			'login' => 'recovery@test.com',
			'password' => 'recovery_password'
		];
		
		$I->submitForm('form', ['Users' => $recoveryData]);
		$I->seeInCurrentUrl('users/index');
		$I->see($recoveryData['username']);
		
		// Verify user was actually created despite previous errors
		$recoveredUser = Users::findOne(['username' => $recoveryData['username']]);
		$I->assertNotNull($recoveredUser);
	}

	/**
	 * Test workflow with concurrent user operations
	 * 
	 * Simulates scenarios where multiple operations might
	 * conflict or interfere with each other.
	 * 
	 * @param FunctionalTester $I
	 * @throws Exception|ModuleException|InvalidConfigException
	 */
	public function testWorkflowWithConcurrentOperations(FunctionalTester $I): void {
		// Arrange
		$adminUser = Users::CreateUser()->saveAndReturn();
		$I->amLoggedInAs($adminUser);
		
		// STEP 1: Create base user
		$baseUser = Users::CreateUser();
		$baseUser->username = 'workflow_concurrent_base';
		$baseUser->login = 'concurrent@base.com';
		$baseUser->password = 'base_password';
		$baseUser->saveAndReturn();
		
		// STEP 2: Simulate concurrent operations
		// First operation: View user
		$I->amOnRoute("users/view?id={$baseUser->id}");
		$I->see($baseUser->username);
		
		// Second operation: Edit same user (as if from different tab/session)
		$I->amOnRoute("users/edit?id={$baseUser->id}");
		$I->submitForm('form', [
			'Users' => [
				'username' => 'workflow_concurrent_updated',
				'login' => $baseUser->login,
				'password' => $baseUser->password
			]
		]);
		
		// Should succeed
		$I->seeInCurrentUrl('users/index');
		$I->see('workflow_concurrent_updated');
		
		// STEP 3: Verify data integrity
		$baseUser->refresh();
		$I->assertEquals('workflow_concurrent_updated', $baseUser->username);
		
		// STEP 4: Third operation: Delete (simulating another concurrent action)
		$I->amOnRoute("users/delete?id={$baseUser->id}");
		$I->seeInCurrentUrl('users/index');
		$I->dontSee('workflow_concurrent_updated');
		
		// Verify deletion
		$deletedUser = Users::findOne($baseUser->id);
		$I->assertNull($deletedUser);
	}

	// =========================================================================================
	// PERFORMANCE WORKFLOW TESTS
	// =========================================================================================

	/**
	 * Test workflow performance with realistic data volumes
	 * 
	 * Verifies that common user workflows complete within
	 * acceptable time limits under realistic load conditions.
	 * 
	 * @param FunctionalTester $I
	 * @throws Exception|ModuleException|InvalidConfigException
	 */
	public function testWorkflowPerformanceWithRealisticData(FunctionalTester $I): void {
		// Arrange: Create realistic dataset
		$adminUser = Users::CreateUser()->saveAndReturn();
		$I->amLoggedInAs($adminUser);
		
		$startTime = microtime(true);
		
		// Create moderate dataset to simulate realistic conditions
		for ($i = 1; $i <= 50; $i++) {
			$user = Users::CreateUser();
			$user->username = sprintf("workflow_perf_user_%03d", $i);
			$user->login = sprintf("perf_user_%03d@company.com", $i);
			$user->password = 'perf_password';
			$user->saveAndReturn();
		}
		
		$setupTime = microtime(true) - $startTime;
		
		// STEP 1: Test index page load time
		$indexStartTime = microtime(true);
		$I->amOnRoute('users/index');
		$I->seeResponseCodeIs(200);
		$indexLoadTime = microtime(true) - $indexStartTime;
		
		// STEP 2: Test search performance
		$searchStartTime = microtime(true);
		$I->amOnRoute('users/index', ['UsersSearch[username]' => 'perf_user']);
		$I->seeResponseCodeIs(200);
		$searchTime = microtime(true) - $searchStartTime;
		
		// STEP 3: Test CRUD operations on existing data
		$crudStartTime = microtime(true);
		
		$testUser = Users::findOne(['username' => 'workflow_perf_user_025']);
		
		// View
		$I->amOnRoute("users/view?id={$testUser->id}");
		$I->seeResponseCodeIs(200);
		
		// Edit
		$I->amOnRoute("users/edit?id={$testUser->id}");
		$I->submitForm('form', [
			'Users' => [
				'username' => 'workflow_perf_updated_025',
				'login' => $testUser->login,
				'password' => $testUser->password
			]
		]);
		$I->seeInCurrentUrl('users/index');
		
		$crudTime = microtime(true) - $crudStartTime;
		
		// Assert: Performance benchmarks
		$I->assertLessThan(5.0, $setupTime, 'Data setup should complete within 5 seconds');
		$I->assertLessThan(3.0, $indexLoadTime, 'Index page should load within 3 seconds');
		$I->assertLessThan(2.0, $searchTime, 'Search should complete within 2 seconds');
		$I->assertLessThan(3.0, $crudTime, 'CRUD operations should complete within 3 seconds');
		
		// Verify functionality still works correctly
		// Check database directly since user might not be visible on current page due to pagination
		$updatedUser = Users::findOne(['username' => 'workflow_perf_updated_025']);
		$I->assertNotNull($updatedUser, 'User should be updated in database');
		$I->assertEquals('workflow_perf_updated_025', $updatedUser->username);
		
		// Verify old username no longer exists
		$oldUser = Users::findOne(['username' => 'workflow_perf_user_025']);
		$I->assertNull($oldUser, 'Old username should no longer exist');
	}
}