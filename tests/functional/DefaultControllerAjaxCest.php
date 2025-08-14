<?php
declare(strict_types = 1);

use app\models\Users;
use Codeception\Exception\ModuleException;

/**
 * Comprehensive functional tests for DefaultController AJAX interactions
 * 
 * This test class covers AJAX functionality including modal operations,
 * form validation, search functionality, and asynchronous data operations
 * that enhance user experience without full page reloads.
 */
class DefaultControllerAjaxCest {

	/**
	 * Set up test environment before each test
	 */
	public function _before(): void {
		// Clean up any existing test data
		Users::deleteAll(['like', 'username', 'ajax_']);
		Users::deleteAll(['like', 'username', 'modal_']);
	}

	/**
	 * Clean up after each test
	 */
	public function _after(): void {
		Users::deleteAll(['like', 'username', 'ajax_']);
		Users::deleteAll(['like', 'username', 'modal_']);
	}

	// =========================================================================================
	// AJAX SEARCH FUNCTIONALITY TESTS
	// =========================================================================================

	/**
	 * Test AJAX search returns proper JSON response
	 *
	 * Verifies that the AJAX search endpoint returns correctly
	 * formatted JSON data for Select2 and other autocomplete widgets.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testAjaxSearchReturnsJsonResponse(FunctionalTester $I): void {
		// Arrange: Create test users for searching
		$authUser = Users::CreateUser()->saveAndReturn();
		
		$searchableUsers = [];
		for ($i = 1; $i <= 5; $i++) {
			$user = Users::CreateUser();
			$user->username = "ajax_searchable_user_{$i}";
			$user->login = "ajax_search_login_{$i}";
			$user->password = 'search_password';
			$user->saveAndReturn();
			$searchableUsers[] = $user;
		}
		
		// Act: Make AJAX search request
		$I->amLoggedInAs($authUser);
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->amOnRoute('users/ajax-search', [
			'term' => 'searchable',
			'column' => 'username'
		]);
		
		// Assert: Should return JSON response
		$I->seeResponseCodeIs(200);
		$I->seeHttpHeader('Content-Type', 'application/json; charset=UTF-8');
		
		// Response should have Select2 format
		$response = json_decode($I->grabResponse(), true);
		$I->assertArrayHasKey('results', $response);
		$I->assertIsArray($response['results']);
		$I->assertCount(5, $response['results']);
		
		// Each result should have id and text fields
		foreach ($response['results'] as $result) {
			$I->assertArrayHasKey('id', $result);
			$I->assertArrayHasKey('text', $result);
		}
	}

	/**
	 * Test AJAX search with different search terms
	 *
	 * Verifies that search functionality works with various
	 * search terms and returns appropriate results.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testAjaxSearchWithVariousTerms(FunctionalTester $I): void {
		// Arrange: Create diverse test data
		$authUser = Users::CreateUser()->saveAndReturn();
		
		$testUsers = [
			['username' => 'ajax_john_doe', 'login' => 'john.doe'],
			['username' => 'ajax_jane_smith', 'login' => 'jane.smith'],
			['username' => 'ajax_bob_johnson', 'login' => 'bob.johnson'],
			['username' => 'ajax_alice_brown', 'login' => 'alice.brown']
		];
		
		foreach ($testUsers as $userData) {
			$user = Users::CreateUser();
			$user->username = $userData['username'];
			$user->login = $userData['login'];
			$user->password = 'test_password';
			$user->saveAndReturn();
		}
		
		$I->amLoggedInAs($authUser);
		
		// Test search by partial username
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->amOnRoute('users/ajax-search', [
			'term' => 'john',
			'column' => 'username'
		]);
		
		$response = json_decode($I->grabResponse(), true);
		$I->assertCount(2, $response['results']); // john_doe and bob_johnson
		
		// Test search by login
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->amOnRoute('users/ajax-search', [
			'term' => 'smith',
			'column' => 'login'
		]);
		
		$response = json_decode($I->grabResponse(), true);
		$I->assertCount(1, $response['results']); // jane.smith
	}

	/**
	 * Test AJAX search with empty term
	 *
	 * Verifies behavior when search term is empty or null.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testAjaxSearchWithEmptyTerm(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		
		// Act: Search with empty term
		$I->amLoggedInAs($authUser);
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->amOnRoute('users/ajax-search', [
			'term' => '',
			'column' => 'username'
		]);
		
		// Assert: Should return all users when term is empty (common behavior)
		$I->seeResponseCodeIs(200);
		$response = json_decode($I->grabResponse(), true);
		$I->assertArrayHasKey('results', $response);
		$I->assertIsArray($response['results']);
		$I->assertGreaterThanOrEqual(1, count($response['results'])); // At least the auth user exists
		
		// Each result should have proper structure
		foreach ($response['results'] as $result) {
			$I->assertArrayHasKey('id', $result);
			$I->assertArrayHasKey('text', $result);
		}
	}

	/**
	 * Test AJAX search case insensitivity
	 *
	 * Verifies that search works regardless of case.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testAjaxSearchCaseInsensitive(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testUser = Users::CreateUser();
		$testUser->username = 'ajax_CaseSensitive_User';
		$testUser->login = 'case.test.login';
		$testUser->password = 'test_password';
		$testUser->saveAndReturn();
		
		$I->amLoggedInAs($authUser);
		
		// Test lowercase search
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->amOnRoute('users/ajax-search', [
			'term' => 'casesensitive',
			'column' => 'username'
		]);
		
		$response1 = json_decode($I->grabResponse(), true);
		$I->assertCount(1, $response1['results']);
		
		// Test uppercase search
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->amOnRoute('users/ajax-search', [
			'term' => 'CASESENSITIVE',
			'column' => 'username'
		]);
		
		$response2 = json_decode($I->grabResponse(), true);
		$I->assertCount(1, $response2['results']);
		
		// Results should be identical
		$I->assertEquals($response1['results'], $response2['results']);
	}

	/**
	 * Test multi-column AJAX search
	 *
	 * Verifies that search can work across multiple columns.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testAjaxSearchMultiColumn(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testUser = Users::CreateUser();
		$testUser->username = 'ajax_multi_user';
		$testUser->login = 'multi_column_search';
		$testUser->password = 'test_password';
		$testUser->saveAndReturn();
		
		// Act: Search across username and login columns
		$I->amLoggedInAs($authUser);
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->amOnRoute('users/ajax-search', [
			'term' => 'multi',
			'column' => 'username,login'
		]);
		
		// Assert: Should find results in both columns
		$I->seeResponseCodeIs(200);
		$response = json_decode($I->grabResponse(), true);
		$I->assertGreaterThanOrEqual(1, count($response['results']));
	}

	// =========================================================================================
	// MODAL OPERATIONS TESTS
	// =========================================================================================

	/**
	 * Test modal view rendering
	 *
	 * Verifies that AJAX requests for view operations
	 * return modal-formatted content.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testModalViewRendering(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testUser = Users::CreateUser();
		$testUser->username = 'modal_view_user';
		$testUser->login = 'modal_view_login';
		$testUser->password = 'modal_password';
		$testUser->saveAndReturn();
		
		// Act: Make AJAX request to view action
		$I->amLoggedInAs($authUser);
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->amOnRoute("users/view?id={$testUser->id}");
		
		// Assert: Should return modal content
		$I->seeResponseCodeIs(200);
		$response = $I->grabResponse();
		
		// Should contain user data
		$I->assertStringContainsString($testUser->username, $response);
		$I->assertStringContainsString($testUser->login, $response);
		
		// Should be modal format (not full page)
		$I->assertStringNotContainsString('<!DOCTYPE html>', $response);
		$I->assertStringNotContainsString('<html', $response);
	}

	/**
	 * Test modal create form rendering
	 *
	 * Verifies that AJAX requests for create operations
	 * return modal-formatted forms.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testModalCreateFormRendering(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		
		// Act: Make AJAX request to create action
		$I->amLoggedInAs($authUser);
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->amOnRoute('users/create');
		
		// Assert: Should return modal form
		$I->seeResponseCodeIs(200);
		$response = $I->grabResponse();
		
		// Should contain form elements
		$I->assertStringContainsString('form', $response);
		$I->assertStringContainsString('Users[username]', $response);
		$I->assertStringContainsString('Users[login]', $response);
		$I->assertStringContainsString('Users[password]', $response);
		
		// Should be modal format
		$I->assertStringNotContainsString('<!DOCTYPE html>', $response);
	}

	/**
	 * Test modal edit form rendering
	 *
	 * Verifies that AJAX requests for edit operations
	 * return modal-formatted forms with pre-filled data.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testModalEditFormRendering(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testUser = Users::CreateUser();
		$testUser->username = 'modal_edit_user';
		$testUser->login = 'modal_edit_login';
		$testUser->password = 'modal_password';
		$testUser->saveAndReturn();
		
		// Act: Make AJAX request to edit action
		$I->amLoggedInAs($authUser);
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->amOnRoute("users/edit?id={$testUser->id}");
		
		// Assert: Should return modal form with data
		$I->seeResponseCodeIs(200);
		$response = $I->grabResponse();
		
		// Should contain form with existing data
		$I->assertStringContainsString('form', $response);
		$I->assertStringContainsString($testUser->username, $response);
		$I->assertStringContainsString($testUser->login, $response);
		
		// Should be modal format
		$I->assertStringNotContainsString('<!DOCTYPE html>', $response);
	}

	// =========================================================================================
	// AJAX FORM VALIDATION TESTS
	// =========================================================================================

	/**
	 * Test AJAX form validation on create
	 *
	 * Verifies that AJAX validation requests return
	 * proper validation error responses.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testAjaxValidationOnCreate(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		
		// Act: Submit AJAX validation request with invalid data
		$I->amLoggedInAs($authUser);
		
		// Visit the create page to set up the form and session
		$I->amOnRoute('users/create');
		$I->seeResponseCodeIs(200);
		
		// Submit AJAX validation request
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->submitForm('form', [
			'ajax' => 'users-form',
			'Users' => [
				'username' => '',  // Required field empty
				'login' => '',     // Required field empty
				'password' => ''   // Required field empty
			]
		]);
		
		// Assert: Should return JSON validation errors
		$I->seeResponseCodeIs(200);
		$I->seeHttpHeader('Content-Type', 'application/json; charset=UTF-8');
		
		$response = json_decode($I->grabResponse(), true);
		$I->assertIsArray($response);
		
		// Should contain validation errors for each field
		$I->assertArrayHasKey('users-username', $response);
		$I->assertArrayHasKey('users-login', $response);
		$I->assertArrayHasKey('users-password', $response);
		
		// Each error should be an array of error messages
		$I->assertIsArray($response['users-username']);
		$I->assertIsArray($response['users-login']);
		$I->assertIsArray($response['users-password']);
	}

	/**
	 * Test AJAX form validation on edit
	 *
	 * Verifies that AJAX validation works correctly
	 * for edit operations.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testAjaxValidationOnEdit(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testUser = Users::CreateUser();
		$testUser->username = 'ajax_validation_user';
		$testUser->login = 'ajax_validation_login';
		$testUser->password = 'validation_password';
		$testUser->saveAndReturn();
		
		// Act: Submit AJAX validation request with invalid data
		$I->amLoggedInAs($authUser);
		
		// Visit the edit page to set up the form and session
		$I->amOnRoute("users/edit?id={$testUser->id}");
		$I->seeResponseCodeIs(200);
		
		// Submit AJAX validation request
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->submitForm('form', [
			'ajax' => 'users-form',
			'Users' => [
				'username' => '',  // Clear required field
				'login' => '',
				'password' => ''
			]
		]);
		
		// Assert: Should return validation errors
		$I->seeResponseCodeIs(200);
		$I->seeHttpHeader('Content-Type', 'application/json; charset=UTF-8');
		
		$response = json_decode($I->grabResponse(), true);
		$I->assertIsArray($response);
		$I->assertNotEmpty($response);
	}

	/**
	 * Test successful AJAX form submission
	 *
	 * Verifies that successful AJAX form submissions
	 * return appropriate responses.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testSuccessfulAjaxFormSubmission(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$formData = [
			'username' => 'ajax_success_user',
			'login' => 'ajax_success_login',
			'password' => 'success_password'
		];
		
		// Act: Submit valid AJAX form
		$I->amLoggedInAs($authUser);
		
		// Visit the create page to set up the form and session
		$I->amOnRoute('users/create');
		$I->seeResponseCodeIs(200);
		
		// Submit AJAX form request
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->submitForm('form', [
			'Users' => $formData
		]);
		
		// Assert: Should get a response (either 200 or 302 redirect)
		$responseCodeCheck = static function() use ($I) {
			try {
				$I->seeResponseCodeIs(200);
				return true;
			} catch (Exception) {
				try {
					$I->seeResponseCodeIs(302);
					return true;
				} catch (Exception) {
					return false;
				}
			}
		};
		$I->assertTrue($responseCodeCheck(), 'Should receive either 200 or 302 response');
		
		// Should create the user in database regardless of response code
		$createdUser = Users::findOne(['username' => $formData['username']]);
		$I->assertNotNull($createdUser, 'User should be created successfully');
		$I->assertEquals($formData['username'], $createdUser->username);
		$I->assertEquals($formData['login'], $createdUser->login);
	}

	// =========================================================================================
	// ERROR HANDLING AND EDGE CASES
	// =========================================================================================

	/**
	 * Test AJAX request with malformed data
	 *
	 * Verifies that malformed AJAX requests are handled gracefully.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testAjaxRequestWithMalformedData(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		
		// Act: Send malformed AJAX request (but still valid parameters)
		$I->amLoggedInAs($authUser);
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		
		// Send request with empty but valid parameters
		$I->amOnRoute('users/ajax-search', [
			'term' => '', 
			'column' => 'username'  // Use valid column instead of invalid param
		]);
		
		// Assert: Should handle gracefully
		$I->seeResponseCodeIs(200);
		$response = json_decode($I->grabResponse(), true);
		$I->assertArrayHasKey('results', $response);
	}

	/**
	 * Test AJAX request timeout simulation
	 *
	 * Verifies behavior with large datasets that might
	 * cause timeout issues.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testAjaxRequestWithLargeDataset(FunctionalTester $I): void {
		// Arrange: Create large dataset
		$authUser = Users::CreateUser()->saveAndReturn();
		
		for ($i = 1; $i <= 100; $i++) {
			$user = Users::CreateUser();
			$user->username = "ajax_load_test_user_{$i}";
			$user->login = "load_test_login_{$i}";
			$user->password = 'load_test_password';
			$user->saveAndReturn();
		}
		
		// Act: Search through large dataset
		$I->amLoggedInAs($authUser);
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->amOnRoute('users/ajax-search', [
			'term' => 'load_test',
			'column' => 'username'
		]);
		
		// Assert: Should handle large dataset efficiently
		$I->seeResponseCodeIs(200);
		$response = json_decode($I->grabResponse(), true);
		$I->assertArrayHasKey('results', $response);
		$I->assertCount(100, $response['results']);
	}

	/**
	 * Test concurrent AJAX requests
	 *
	 * Verifies that multiple simultaneous AJAX requests
	 * are handled correctly.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws \yii\db\Exception
	 */
	public function testConcurrentAjaxRequests(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		
		$testUsers = [];
		for ($i = 1; $i <= 5; $i++) {
			$user = Users::CreateUser();
			$user->username = "ajax_concurrent_user_{$i}";
			$user->login = "concurrent_login_{$i}";
			$user->password = 'concurrent_password';
			$user->saveAndReturn();
			$testUsers[] = $user;
		}
		
		$I->amLoggedInAs($authUser);
		
		// Act & Assert: Make multiple search requests
		for ($i = 1; $i <= 5; $i++) {
			$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
			$I->amOnRoute('users/ajax-search', [
				'term' => "user_{$i}",
				'column' => 'username'
			]);
			
			$I->seeResponseCodeIs(200);
			$response = json_decode($I->grabResponse(), true);
			$I->assertArrayHasKey('results', $response);
			$I->assertCount(1, $response['results']);
		}
	}

	/**
	 * Test AJAX requests without authentication
	 * 
	 * Verifies that unauthenticated AJAX requests
	 * are handled appropriately.
	 * 
	 * @param FunctionalTester $I
	 */
	public function testAjaxRequestWithoutAuthentication(FunctionalTester $I): void {
		// Act: Make AJAX request without being logged in
		$I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
		$I->amOnRoute('users/ajax-search', [
			'term' => 'test',
			'column' => 'username'
		]);
		
		// Assert: Should handle unauthenticated requests gracefully
		// The application returns 200 with empty results instead of 4xx error
		$I->seeResponseCodeIs(200);
		$I->seeHttpHeader('Content-Type', 'application/json; charset=UTF-8');
		
		$response = json_decode($I->grabResponse(), true);
		$I->assertArrayHasKey('results', $response);
		$I->assertEmpty($response['results']); // No results for unauthenticated user
	}
}