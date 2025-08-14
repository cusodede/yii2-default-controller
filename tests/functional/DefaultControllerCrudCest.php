<?php
declare(strict_types = 1);

use app\models\Users;
use Codeception\Exception\ModuleException;
use yii\base\InvalidConfigException;

/**
 * Comprehensive functional tests for DefaultController CRUD operations
 * 
 * This test class covers complete Create, Read, Update, Delete workflows
 * from the user perspective, testing web interactions, form submissions,
 * redirects, and data validation across different scenarios.
 */
class DefaultControllerCrudCest {

	/**
	 * Set up test environment before each test
	 * @param FunctionalTester $I
	 */
	public function _before(FunctionalTester $I): void {
		// Clean up any existing test data
		Users::deleteAll(['like', 'username', 'test_']);
		Users::deleteAll(['like', 'username', 'crud_']);
		Users::deleteAll(['like', 'username', 'functional_']);
	}

	/**
	 * Clean up after each test
	 * @param FunctionalTester $I
	 */
	public function _after(FunctionalTester $I): void {
		// Additional cleanup if needed
		Users::deleteAll(['like', 'username', 'test_']);
		Users::deleteAll(['like', 'username', 'crud_']);
		Users::deleteAll(['like', 'username', 'functional_']);
	}

	// =========================================================================================
	// INDEX (LIST) FUNCTIONALITY TESTS
	// =========================================================================================

	/**
	 * Test index page displays correctly with no data
	 *
	 * Verifies that the index page renders properly when no records exist,
	 * showing appropriate empty state and navigation elements.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testIndexPageWithNoData(FunctionalTester $I): void {
		// Arrange: Create a user to authenticate with
		$authUser = Users::CreateUser()->saveAndReturn();
		
		// Act: Navigate to index page
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/index');
		
		// Assert: Page loads successfully
		$I->seeResponseCodeIs(200);
		$I->seeInTitle('Сотрудники');
		
		// Should show grid structure even with no data
		$I->seeElement('.grid-view');
	}

	/**
	 * Test index page displays data correctly
	 *
	 * Verifies that when records exist, they are properly displayed
	 * in the grid with correct formatting and action buttons.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testIndexPageWithData(FunctionalTester $I): void {
		// Arrange: Create test users
		$authUser = Users::CreateUser()->saveAndReturn();
		
		$testUsers = [];
		for ($i = 1; $i <= 3; $i++) {
			$user = Users::CreateUser();
			$user->username = "test_user_{$i}";
			$user->login = "test_login_{$i}";
			$user->password = 'test_password';
			$user->saveAndReturn();
			$testUsers[] = $user;
		}
		
		// Act: Navigate to index page
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/index');
		
		// Assert: Page loads and displays data
		$I->seeResponseCodeIs(200);
		
		// Should see all test users (only username displayed in index table)
		foreach ($testUsers as $user) {
			$I->see($user->username);
			// Note: login not displayed in index table, only Actions, ID, Name columns
		}
		
		// Should have action buttons for each row (Russian tooltips)
		$I->seeElement('a[data-original-title="Просмотр"]'); // View
		$I->seeElement('a[data-original-title="Редактирование"]'); // Edit  
		$I->seeElement('a[data-original-title="Удалить"]'); // Delete
	}

	// =========================================================================================
	// CREATE FUNCTIONALITY TESTS
	// =========================================================================================

	/**
	 * Test create page loads correctly
	 *
	 * Verifies that the create form is displayed with all necessary
	 * fields and proper form structure.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testCreatePageLoadsCorrectly(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		
		// Act: Navigate to create page
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/create');
		
		// Assert: Page structure is correct
		$I->seeResponseCodeIs(200);
		$I->seeInTitle('Создание');
		
		// Form should be present with required fields
		$I->seeElement('form');
		$I->seeElement('input[name="Users[username]"]');
		$I->seeElement('input[name="Users[login]"]');
		$I->seeElement('input[name="Users[password]"]');
		$I->seeElement('button[type="submit"]');
	}

	/**
	 * Test successful user creation
	 *
	 * Tests the complete create workflow with valid data,
	 * verifying form submission, redirect, and data persistence.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testCreateUserSuccessfully(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testData = [
			'username' => 'crud_test_user',
			'login' => 'crud_test_login',
			'password' => 'crud_test_password'
		];
		
		// Act: Submit create form
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/create');
		$I->submitForm('form', [
			'Users' => $testData
		]);
		
		// Assert: Successful creation and redirect
		$I->seeResponseCodeIs(200);
		$I->seeInCurrentUrl('users/index');
		
		// Verify data was saved correctly
		$createdUser = Users::findOne(['username' => $testData['username']]);
		$I->assertNotNull($createdUser, 'User should be created in database');
		$I->assertEquals($testData['username'], $createdUser->username);
		$I->assertEquals($testData['login'], $createdUser->login);
		$I->assertEquals($testData['password'], $createdUser->password);
		
		// Should see the new user in the index (only username displayed)
		$I->see($testData['username']);
		// Note: login not displayed in index table, only Actions, ID, Name columns
	}

	/**
	 * Test create with validation errors
	 *
	 * Verifies that validation errors are properly displayed
	 * when invalid data is submitted.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testCreateWithValidationErrors(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$initialUserCount = Users::find()->count();
		
		// Act: Submit form with invalid data (empty required fields)
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/create');
		$I->submitForm('form', [
			'Users' => [
				'username' => '',  // Required field empty
				'login' => '',     // Required field empty
				'password' => ''   // Required field empty
			]
		]);
		
		// Assert: Should stay on create page with errors
		$I->seeResponseCodeIs(200);
		$I->seeInCurrentUrl('users/create');
		
		// Should display validation error messages
		$I->see('cannot be blank');
		$I->see('cannot be blank');
		$I->see('cannot be blank');
		
		// Should not create any new records
		$I->assertEquals($initialUserCount, Users::find()->count());
	}

	/**
	 * Test create with duplicate login
	 *
	 * Tests behavior when attempting to create a user with
	 * a login that already exists (if uniqueness is enforced).
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testCreateWithSpecialCharacters(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testData = [
			'username' => 'Test User (Special: @#$%)',
			'login' => 'test.user@domain.com',
			'password' => 'P@ssw0rd!123'
		];
		
		// Act: Submit form with special characters
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/create');
		$I->submitForm('form', [
			'Users' => $testData
		]);
		
		// Assert: Should handle special characters correctly
		$I->seeResponseCodeIs(200);
		$I->seeInCurrentUrl('users/index');
		
		// Verify special characters are preserved
		$createdUser = Users::findOne(['username' => $testData['username']]);
		$I->assertNotNull($createdUser);
		$I->assertEquals($testData['username'], $createdUser->username);
		$I->assertEquals($testData['login'], $createdUser->login);
	}

	// =========================================================================================
	// VIEW FUNCTIONALITY TESTS
	// =========================================================================================

	/**
	 * Test view page displays user data correctly
	 *
	 * Verifies that the view page shows all user information
	 * with proper formatting and navigation elements.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testViewUserDetails(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testUser = Users::CreateUser();
		$testUser->username = 'crud_view_test_user';
		$testUser->login = 'crud_view_test_login';
		$testUser->password = 'view_test_password';
		$testUser->saveAndReturn();
		
		// Act: Navigate to view page
		$I->amLoggedInAs($authUser);
		$I->amOnRoute("users/view?id={$testUser->id}");
		
		// Assert: Page displays correctly
		$I->seeResponseCodeIs(200);
		$I->seeInTitle("Просмотр {$testUser->username}");
		$I->see("Просмотр {$testUser->username}");
		
		// Should display user data
		$I->see($testUser->username);
		$I->see($testUser->login);
		// Password should not be visible in view (security)
		// $I->dontSee($testUser->password);
	}

	/**
	 * Test view page with non-existent ID
	 *
	 * Verifies that appropriate error handling occurs
	 * when attempting to view a non-existent record.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testViewNonExistentUser(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$nonExistentId = 99999;
		
		// Act: Attempt to view non-existent user
		$I->amLoggedInAs($authUser);
		$I->amOnRoute("users/view?id={$nonExistentId}");
		
		// Assert: Should receive 404 error
		$I->seeResponseCodeIs(404);
	}

	/**
	 * Test view page without ID parameter
	 *
	 * Verifies error handling when no ID is provided.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testViewWithoutIdParameter(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		
		// Act: Attempt to view without ID
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/view');
		
		// Assert: Should receive 400 bad request
		$I->seeResponseCodeIs(400);
	}

	// =========================================================================================
	// UPDATE/EDIT FUNCTIONALITY TESTS
	// =========================================================================================

	/**
	 * Test edit page loads with existing data
	 *
	 * Verifies that the edit form is pre-populated
	 * with existing user data.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testEditPageLoadsWithData(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testUser = Users::CreateUser();
		$testUser->username = 'crud_edit_test_user';
		$testUser->login = 'crud_edit_test_login';
		$testUser->password = 'edit_test_password';
		$testUser->saveAndReturn();
		
		// Act: Navigate to edit page
		$I->amLoggedInAs($authUser);
		$I->amOnRoute("users/edit?id={$testUser->id}");
		
		// Assert: Page loads with data
		$I->seeResponseCodeIs(200);
		$I->seeInTitle("Редактирование {$testUser->username}");
		
		// Form should be pre-filled with existing data
		$I->seeInField('Users[username]', $testUser->username);
		$I->seeInField('Users[login]', $testUser->login);
		$I->seeInField('Users[password]', $testUser->password);
	}

	/**
	 * Test successful user update
	 *
	 * Tests the complete update workflow with valid data,
	 * verifying form submission, redirect, and data persistence.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testUpdateUserSuccessfully(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testUser = Users::CreateUser();
		$testUser->username = 'crud_original_user';
		$testUser->login = 'crud_original_login';
		$testUser->password = 'original_password';
		$testUser->saveAndReturn();
		
		$updatedData = [
			'username' => 'crud_updated_user',
			'login' => 'crud_updated_login',
			'password' => 'updated_password'
		];
		
		// Act: Submit update form
		$I->amLoggedInAs($authUser);
		$I->amOnRoute("users/edit?id={$testUser->id}");
		$I->submitForm('form', [
			'Users' => $updatedData
		]);
		
		// Assert: Successful update and redirect
		$I->seeResponseCodeIs(200);
		$I->seeInCurrentUrl('users/index');
		
		// Verify data was updated correctly
		$testUser->refresh();
		$I->assertEquals($updatedData['username'], $testUser->username);
		$I->assertEquals($updatedData['login'], $testUser->login);
		$I->assertEquals($updatedData['password'], $testUser->password);
		
		// Should see updated data in index (only username displayed)
		$I->see($updatedData['username']);
		// Note: login not displayed in index table, only Actions, ID, Name columns
	}

	/**
	 * Test update with validation errors
	 *
	 * Verifies that validation errors are properly displayed
	 * during update operations.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testUpdateWithValidationErrors(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testUser = Users::CreateUser();
		$testUser->username = 'crud_validation_test';
		$testUser->login = 'crud_validation_login';
		$testUser->password = 'validation_password';
		$testUser->saveAndReturn();
		
		$originalData = [
			'username' => $testUser->username,
			'login' => $testUser->login,
			'password' => $testUser->password
		];
		
		// Act: Submit form with invalid data
		$I->amLoggedInAs($authUser);
		$I->amOnRoute("users/edit?id={$testUser->id}");
		$I->submitForm('form', [
			'Users' => [
				'username' => '',  // Empty required field
				'login' => '',     // Empty required field
				'password' => ''   // Empty required field
			]
		]);
		
		// Assert: Should stay on edit page with errors
		$I->seeResponseCodeIs(200);
		$I->seeInCurrentUrl("users/edit?id={$testUser->id}");
		
		// Should display validation errors
		$I->see('cannot be blank');
		$I->see('cannot be blank');
		$I->see('cannot be blank');
		
		// Original data should be unchanged
		$testUser->refresh();
		$I->assertEquals($originalData['username'], $testUser->username);
		$I->assertEquals($originalData['login'], $testUser->login);
		$I->assertEquals($originalData['password'], $testUser->password);
	}

	/**
	 * Test edit with non-existent ID
	 *
	 * Verifies error handling when attempting to edit
	 * a non-existent record.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testEditNonExistentUser(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$nonExistentId = 99999;
		
		// Act: Attempt to edit non-existent user
		$I->amLoggedInAs($authUser);
		$I->amOnRoute("users/edit?id={$nonExistentId}");
		
		// Assert: Should receive 404 error
		$I->seeResponseCodeIs(404);
	}

	// =========================================================================================
	// DELETE FUNCTIONALITY TESTS
	// =========================================================================================

	/**
	 * Test successful user deletion
	 *
	 * Verifies that users can be deleted and are properly
	 * removed from the database.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testDeleteUserSuccessfully(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testUser = Users::CreateUser();
		$testUser->username = 'crud_delete_test_user';
		$testUser->login = 'crud_delete_login';
		$testUser->password = 'delete_password';
		$testUser->saveAndReturn();
		$userId = $testUser->id;
		
		// Act: Delete the user
		$I->amLoggedInAs($authUser);
		$I->amOnRoute("users/delete?id={$userId}");
		
		// Assert: Should redirect to index
		$I->seeResponseCodeIs(200);
		$I->seeInCurrentUrl('users/index');
		
		// User should be deleted from database
		$deletedUser = Users::findOne($userId);
		$I->assertNull($deletedUser, 'User should be deleted from database');
		
		// Should not see deleted user in index
		$I->dontSee($testUser->username);
		$I->dontSee($testUser->login);
	}

	/**
	 * Test delete with non-existent ID
	 *
	 * Verifies error handling when attempting to delete
	 * a non-existent record.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testDeleteNonExistentUser(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$nonExistentId = 99999;
		
		// Act: Attempt to delete non-existent user
		$I->amLoggedInAs($authUser);
		$I->amOnRoute("users/delete?id={$nonExistentId}");
		
		// Assert: Should receive 404 error
		$I->seeResponseCodeIs(404);
	}

	/**
	 * Test delete without ID parameter
	 *
	 * Verifies error handling when no ID is provided for deletion.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testDeleteWithoutIdParameter(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		
		// Act: Attempt to delete without ID
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/delete');
		
		// Assert: Should receive 400 bad request
		$I->seeResponseCodeIs(400);
	}

	// =========================================================================================
	// DATA INTEGRITY AND PERSISTENCE TESTS
	// =========================================================================================

	/**
	 * Test data persistence across operations
	 *
	 * Comprehensive test that creates, reads, updates, and deletes
	 * a record to verify complete data integrity.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testCompleteDataLifecycle(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testData = [
			'username' => 'crud_lifecycle_user',
			'login' => 'crud_lifecycle_login',
			'password' => 'lifecycle_password'
		];
		
		// Act & Assert: Complete CRUD lifecycle
		$I->amLoggedInAs($authUser);
		
		// 1. CREATE
		$I->amOnRoute('users/create');
		$I->submitForm('form', ['Users' => $testData]);
		$I->seeInCurrentUrl('users/index');
		
		$createdUser = Users::findOne(['username' => $testData['username']]);
		$I->assertNotNull($createdUser);
		
		// 2. READ (View)
		$I->amOnRoute("users/view?id={$createdUser->id}");
		$I->seeResponseCodeIs(200);
		$I->see($testData['username']);
		
		// 3. UPDATE
		$updatedData = [
			'username' => 'crud_lifecycle_updated',
			'login' => 'crud_lifecycle_updated_login',
			'password' => 'updated_lifecycle_password'
		];
		
		$I->amOnRoute("users/edit?id={$createdUser->id}");
		$I->submitForm('form', ['Users' => $updatedData]);
		$I->seeInCurrentUrl('users/index');
		
		$createdUser->refresh();
		$I->assertEquals($updatedData['username'], $createdUser->username);
		
		// 4. DELETE
		$I->amOnRoute("users/delete?id={$createdUser->id}");
		$I->seeInCurrentUrl('users/index');
		
		$deletedUser = Users::findOne($createdUser->id);
		$I->assertNull($deletedUser);
	}

	/**
	 * Test Unicode and special character handling
	 *
	 * Verifies that the system properly handles international
	 * characters and special symbols.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testUnicodeAndSpecialCharacterHandling(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$unicodeData = [
			'username' => 'Тест 测试 テスト 🚀',
			'login' => 'unicode.test@домен.рф',
			'password' => 'Пароль123!@#'
		];
		
		// Act: Create user with Unicode data
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/create');
		$I->submitForm('form', ['Users' => $unicodeData]);
		
		// Assert: Unicode data should be handled correctly
		$I->seeResponseCodeIs(200);
		$I->seeInCurrentUrl('users/index');
		
		$createdUser = Users::findOne(['username' => $unicodeData['username']]);
		$I->assertNotNull($createdUser);
		$I->assertEquals($unicodeData['username'], $createdUser->username);
		
		// Should display Unicode characters correctly in views
		$I->amOnRoute("users/view?id={$createdUser->id}");
		$I->see($unicodeData['username']);
	}
}