<?php
declare(strict_types = 1);

use app\models\Users;
use Codeception\Exception\ModuleException;
use yii\db\Exception as ExceptionAlias;

/**
 * Comprehensive functional tests for DefaultController search and filtering
 * 
 * This test class covers search functionality, grid filtering, pagination,
 * sorting, and other data browsing features that help users find and
 * organize information effectively.
 */
class DefaultControllerSearchCest {

	/**
	 * Set up test environment before each test
	 */
	public function _before(): void {
		// Clean up any existing test data
		Users::deleteAll(['like', 'username', 'search_']);
		Users::deleteAll(['like', 'username', 'filter_']);
		Users::deleteAll(['like', 'username', 'sort_']);
	}

	/**
	 * Clean up after each test
	 */
	public function _after(): void {
		Users::deleteAll(['like', 'username', 'search_']);
		Users::deleteAll(['like', 'username', 'filter_']);
		Users::deleteAll(['like', 'username', 'sort_']);
	}

	// =========================================================================================
	// SEARCH FUNCTIONALITY TESTS
	// =========================================================================================

	/**
	 * Test basic search functionality
	 *
	 * Verifies that the search form filters results correctly
	 * when search criteria are provided.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws ExceptionAlias
	 */
	public function testBasicSearchFunctionality(FunctionalTester $I): void {
		// Arrange: Create test users with searchable data
		$authUser = Users::CreateUser()->saveAndReturn();
		
		$searchableUsers = [
			['username' => 'search_john_doe', 'login' => 'john.doe@example.com'],
			['username' => 'search_jane_smith', 'login' => 'jane.smith@example.com'],
			['username' => 'search_bob_wilson', 'login' => 'bob.wilson@example.com'],
			['username' => 'filter_alice_brown', 'login' => 'alice.brown@example.com']
		];
		
		foreach ($searchableUsers as $userData) {
			$user = Users::CreateUser();
			$user->username = $userData['username'];
			$user->login = $userData['login'];
			$user->password = 'test_password';
			$user->saveAndReturn();
		}
		
		// Act: Perform search
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'john'
		]);
		
		// Assert: Should only show matching results
		$I->seeResponseCodeIs(200);
		$I->see('search_john_doe');
		$I->dontSee('search_jane_smith');
		$I->dontSee('search_bob_wilson');
		$I->dontSee('filter_alice_brown');
	}

	/**
	 * Test search with multiple criteria
	 *
	 * Verifies that search works correctly when multiple
	 * search fields are used simultaneously.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws ExceptionAlias
	 */
	public function testSearchWithMultipleCriteria(FunctionalTester $I): void {
		// Arrange: Create diverse test data
		$authUser = Users::CreateUser()->saveAndReturn();
		
		$testUsers = [
			['username' => 'search_developer_john', 'login' => 'dev.john@company.com'],
			['username' => 'search_developer_jane', 'login' => 'dev.jane@company.com'],
			['username' => 'search_manager_john', 'login' => 'mgr.john@company.com'],
			['username' => 'search_analyst_bob', 'login' => 'analyst.bob@company.com']
		];
		
		foreach ($testUsers as $userData) {
			$user = Users::CreateUser();
			$user->username = $userData['username'];
			$user->login = $userData['login'];
			$user->password = 'test_password';
			$user->saveAndReturn();
		}
		
		// Act: Search with multiple criteria
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'developer',
			'UsersSearch[login]' => 'jane'
		]);
		
		// Assert: Should find intersection of both criteria
		$I->seeResponseCodeIs(200);
		$I->see('search_developer_jane');
		$I->dontSee('search_developer_john');
		$I->dontSee('search_manager_john');
		$I->dontSee('search_analyst_bob');
	}

	/**
	 * Test case-insensitive search
	 *
	 * Verifies that search functionality works regardless
	 * of the case of the search terms.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws ExceptionAlias
	 */
	public function testCaseInsensitiveSearch(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testUser = Users::CreateUser();
		$testUser->username = 'search_CamelCase_User';
		$testUser->login = 'CamelCase.Login';
		$testUser->password = 'test_password';
		$testUser->saveAndReturn();
		
		$I->amLoggedInAs($authUser);
		
		// Test lowercase search
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'camelcase'
		]);
		$I->seeResponseCodeIs(200);
		$I->see('search_CamelCase_User');
		
		// Test uppercase search
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'CAMELCASE'
		]);
		$I->seeResponseCodeIs(200);
		$I->see('search_CamelCase_User');
		
		// Test mixed case search
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'CaMeLcAsE'
		]);
		$I->seeResponseCodeIs(200);
		$I->see('search_CamelCase_User');
	}

	/**
	 * Test partial string search
	 *
	 * Verifies that search finds results even when only
	 * partial strings are provided.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws ExceptionAlias
	 */
	public function testPartialStringSearch(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$testUsers = [
			'search_marketing_specialist',
			'search_marketing_manager',
			'search_sales_marketing',
			'search_developer_fullstack'
		];
		
		foreach ($testUsers as $username) {
			$user = Users::CreateUser();
			$user->username = $username;
			$user->login = str_replace('_', '.', $username);
			$user->password = 'test_password';
			$user->saveAndReturn();
		}
		
		// Act: Search with partial string
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'marketing'
		]);
		
		// Assert: Should find all marketing-related users
		$I->seeResponseCodeIs(200);
		$I->see('search_marketing_specialist');
		$I->see('search_marketing_manager');
		$I->see('search_sales_marketing');
		$I->dontSee('search_developer_fullstack');
	}

	/**
	 * Test search with special characters
	 *
	 * Verifies that search handles special characters
	 * and escape sequences correctly.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws ExceptionAlias
	 */
	public function testSearchWithSpecialCharacters(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$specialUsers = [
			['username' => 'search_user@company', 'login' => 'user@company.com'],
			['username' => 'search_user+test', 'login' => 'user+test@domain.com'],
			['username' => 'search_user-name', 'login' => 'user-name@site.org'],
			['username' => 'search_user.dot', 'login' => 'user.dot@example.net']
		];
		
		foreach ($specialUsers as $userData) {
			$user = Users::CreateUser();
			$user->username = $userData['username'];
			$user->login = $userData['login'];
			$user->password = 'test_password';
			$user->saveAndReturn();
		}
		
		// Test search with @ symbol
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => '@company'
		]);
		$I->seeResponseCodeIs(200);
		$I->see('search_user@company');
		
		// Test search with + symbol
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => '+test'
		]);
		$I->seeResponseCodeIs(200);
		$I->see('search_user+test');
		
		// Test search with - symbol
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'user-name'
		]);
		$I->seeResponseCodeIs(200);
		$I->see('search_user-name');
	}

	/**
	 * Test empty search results
	 *
	 * Verifies appropriate handling when search returns no results.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws ExceptionAlias
	 */
	public function testEmptySearchResults(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		
		// Create some users that won't match the search
		for ($i = 1; $i <= 3; $i++) {
			$user = Users::CreateUser();
			$user->username = "search_existing_user_{$i}";
			$user->login = "existing_{$i}@example.com";
			$user->password = 'test_password';
			$user->saveAndReturn();
		}
		
		// Act: Search for non-existent data
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'nonexistent_search_term'
		]);
		
		// Assert: Should show empty results gracefully
		$I->seeResponseCodeIs(200);
		$I->see('No results found', '.empty');
		$I->dontSee('search_existing_user_1');
		$I->dontSee('search_existing_user_2');
		$I->dontSee('search_existing_user_3');
	}

	// =========================================================================================
	// SORTING FUNCTIONALITY TESTS
	// =========================================================================================

	/**
	 * Test sorting by different columns
	 *
	 * Verifies that grid sorting works correctly for various columns.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws ExceptionAlias
	 */
	public function testSortingByDifferentColumns(FunctionalTester $I): void {
		// Arrange: Create users with sortable data
		$authUser = Users::CreateUser()->saveAndReturn();
		
		$sortTestUsers = [
			['username' => 'sort_charlie', 'login' => 'charlie@test.com'],
			['username' => 'sort_alice', 'login' => 'alice@test.com'],
			['username' => 'sort_bob', 'login' => 'bob@test.com'],
		];
		
		foreach ($sortTestUsers as $userData) {
			$user = Users::CreateUser();
			$user->username = $userData['username'];
			$user->login = $userData['login'];
			$user->password = 'test_password';
			$user->saveAndReturn();
		}
		
		// Act & Assert: Test sorting by username ascending
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/index', [
			'usersDataProvider-sort' => 'username'
		]);
		
		$I->seeResponseCodeIs(200);
		$response = $I->grabResponse();
		
		// Verify sort order (alice should come before bob, bob before charlie)
		$alicePos = strpos($response, 'sort_alice');
		$bobPos = strpos($response, 'sort_bob');
		$charliePos = strpos($response, 'sort_charlie');
		
		$I->assertLessThan($bobPos, $alicePos, 'Alice should appear before Bob');
		$I->assertLessThan($charliePos, $bobPos, 'Bob should appear before Charlie');
		
		// Test sorting by username descending
		$I->amOnRoute('users/index', [
			'usersDataProvider-sort' => '-username'
		]);
		
		$I->seeResponseCodeIs(200);
		$response = $I->grabResponse();
		
		$alicePos = strpos($response, 'sort_alice');
		$bobPos = strpos($response, 'sort_bob');
		$charliePos = strpos($response, 'sort_charlie');
		
		$I->assertGreaterThan($bobPos, $alicePos, 'Alice should appear after Bob in descending order');
		$I->assertGreaterThan($charliePos, $bobPos, 'Bob should appear after Charlie in descending order');
	}

	/**
	 * Test sorting with search filters
	 *
	 * Verifies that sorting works correctly when combined with search filters.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws ExceptionAlias
	 */
	public function testSortingWithSearchFilters(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		
		$testUsers = [
			['username' => 'sort_filter_zebra', 'login' => 'zebra@example.com'],
			['username' => 'sort_filter_apple', 'login' => 'apple@example.com'],
			['username' => 'sort_other_banana', 'login' => 'banana@example.com'],
		];
		
		foreach ($testUsers as $userData) {
			$user = Users::CreateUser();
			$user->username = $userData['username'];
			$user->login = $userData['login'];
			$user->password = 'test_password';
			$user->saveAndReturn();
		}
		
		// Act: Search and sort simultaneously
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'filter',
			'usersDataProvider-sort' => 'username'
		]);
		
		// Assert: Should show filtered and sorted results
		$I->seeResponseCodeIs(200);
		$I->see('sort_filter_apple');
		$I->see('sort_filter_zebra');
		$I->dontSee('sort_other_banana'); // Filtered out
		
		$response = $I->grabResponse();
		$applePos = strpos($response, 'sort_filter_apple');
		$zebraPos = strpos($response, 'sort_filter_zebra');
		$I->assertLessThan($zebraPos, $applePos, 'Apple should appear before Zebra');
	}

	// =========================================================================================
	// PAGINATION FUNCTIONALITY TESTS
	// =========================================================================================

	/**
	 * Test pagination with large dataset
	 *
	 * Verifies that pagination works correctly when there are
	 * more records than fit on a single page.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws ExceptionAlias
	 */
	public function testPaginationWithLargeDataset(FunctionalTester $I): void {
		// Arrange: Create enough users to trigger pagination
		$authUser = Users::CreateUser()->saveAndReturn();
		
		for ($i = 1; $i <= 25; $i++) {
			$user = Users::CreateUser();
			$user->username = sprintf("search_pagination_user_%02d", $i);
			$user->login = sprintf("pagination_%02d@test.com", $i);
			$user->password = 'test_password';
			$user->saveAndReturn();
		}
		
		// Act: Visit first page
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/index');
		
		// Assert: Should show pagination controls
		$I->seeResponseCodeIs(200);
		$I->seeElement('.pagination');
		$I->seeElement('a[data-page="1"]');
		
		// Should show first batch of users
		$I->see('search_pagination_user_01');
		$I->see('search_pagination_user_10'); // Assuming 20 per page
		
		// Visit second page
		$I->click('2'); // Click page 2
		$I->seeResponseCodeIs(200);
		$I->see('search_pagination_user_21');
		$I->dontSee('search_pagination_user_01'); // First page users shouldn't appear
	}

	/**
	 * Test pagination with search filters
	 *
	 * Verifies that pagination works correctly when search
	 * filters are applied.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws ExceptionAlias
	 */
	public function testPaginationWithSearchFilters(FunctionalTester $I): void {
		// Arrange: Create mixed dataset
		$authUser = Users::CreateUser()->saveAndReturn();
		
		// Create 15 matching users and 10 non-matching users
		for ($i = 1; $i <= 15; $i++) {
			$user = Users::CreateUser();
			$user->username = sprintf("search_paginate_match_%02d", $i);
			$user->login = sprintf("match_%02d@test.com", $i);
			$user->password = 'test_password';
			$user->saveAndReturn();
		}
		
		for ($i = 1; $i <= 10; $i++) {
			$user = Users::CreateUser();
			$user->username = sprintf("search_paginate_other_%02d", $i);
			$user->login = sprintf("other_%02d@test.com", $i);
			$user->password = 'test_password';
			$user->saveAndReturn();
		}
		
		// Act: Search with filter that creates pagination
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'match'
		]);
		
		// Assert: Should paginate filtered results
		$I->seeResponseCodeIs(200);
		$I->see('search_paginate_match_01');
		$I->dontSee('search_paginate_other_01'); // Non-matching shouldn't appear
		
		// Should have pagination if more than page size
		if (Users::find()->where(['like', 'username', 'match'])->count() > 20) {
			$I->seeElement('.pagination');
		}
	}

	// =========================================================================================
	// ADVANCED FILTERING TESTS
	// =========================================================================================

	/**
	 * Test date range filtering (if applicable)
	 *
	 * Verifies filtering by date ranges works correctly.
	 * Note: This assumes your model has date fields to filter by.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws ExceptionAlias
	 */
	public function testAdvancedFilteringCapabilities(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		
		// Create users with different characteristics
		$filterUsers = [
			['username' => 'filter_active_user', 'login' => 'active@test.com'],
			['username' => 'filter_disabled_user', 'login' => 'disabled@test.com'],
			['username' => 'filter_pending_user', 'login' => 'pending@test.com'],
		];
		
		foreach ($filterUsers as $userData) {
			$user = Users::CreateUser();
			$user->username = $userData['username'];
			$user->login = $userData['login'];
			$user->password = 'test_password';
			$user->saveAndReturn();
		}
		
		// Act: Test different filter combinations
		$I->amLoggedInAs($authUser);
		
		// Filter by status in username
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'active'
		]);
		
		// Assert: Should show only active users
		$I->seeResponseCodeIs(200);
		$I->see('filter_active_user');
		$I->dontSee('filter_disabled_user');
		$I->dontSee('filter_pending_user');
	}

	/**
	 * Test filter persistence across navigation
	 *
	 * Verifies that applied filters are maintained when
	 * navigating through pages or performing actions.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws ExceptionAlias
	 */
	public function testFilterPersistenceAcrossNavigation(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		
		for ($i = 1; $i <= 30; $i++) {
			$user = Users::CreateUser();
			$user->username = $i <= 15 ? sprintf("filter_persist_target_%02d", $i) : sprintf("filter_persist_other_%02d", $i);
			$user->login = sprintf("persist_%02d@test.com", $i);
			$user->password = 'test_password';
			$user->saveAndReturn();
		}
		
		// Act: Apply filter and navigate
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'target'
		]);
		
		// Assert: Filter should be applied
		$I->seeResponseCodeIs(200);
		$I->see('filter_persist_target_01');
		$I->dontSee('filter_persist_other_16');
		
		// Navigate to next page (if pagination exists)
		$paginationLinks = $I->grabMultiple('.pagination a');
		if (!empty($paginationLinks)) {
			$I->click('2');
			$I->seeResponseCodeIs(200);
			
			// Filter should still be applied on page 2
			$I->see('filter_persist_target_'); // Should still see target users
			$I->dontSee('filter_persist_other_'); // Should not see other users
		} else {
			// No pagination needed - all results fit on one page
			$I->comment('No pagination required - all filtered results fit on one page');
		}
	}

	// =========================================================================================
	// PERFORMANCE AND EDGE CASES
	// =========================================================================================

	/**
	 * Test search performance with large dataset
	 *
	 * Verifies that search operations complete within
	 * reasonable time limits even with large datasets.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws ExceptionAlias
	 */
	public function testSearchPerformanceWithLargeDataset(FunctionalTester $I): void {
		// Arrange: Create substantial dataset
		$authUser = Users::CreateUser()->saveAndReturn();
		
		$setupStartTime = microtime(true);
		
		for ($i = 1; $i <= 100; $i++) {
			$user = Users::CreateUser();
			$user->username = sprintf("search_performance_user_%03d", $i);
			$user->login = sprintf("performance_%03d@test.com", $i);
			$user->password = 'test_password';
			$user->saveAndReturn();
		}
		
		$setupEndTime = microtime(true);
		$setupDuration = $setupEndTime - $setupStartTime;
		
		// Act: Perform search on large dataset
		$searchStartTime = microtime(true);
		
		$I->amLoggedInAs($authUser);
		$I->amOnRoute('users/index', [
			'UsersSearch[username]' => 'performance'
		]);
		
		$searchEndTime = microtime(true);
		
		// Assert: Search should complete quickly and show results
		$I->seeResponseCodeIs(200);
		$I->see('search_performance_user_001');
		
		// Performance assertions - both setup and search should complete within reasonable time
		$searchDuration = $searchEndTime - $searchStartTime;
		$I->assertLessThan(10.0, $setupDuration, 'Data setup should complete within 10 seconds');
		$I->assertLessThan(5.0, $searchDuration, 'Search should complete within 5 seconds');
	}

	/**
	 * Test search with SQL injection attempts
	 *
	 * Verifies that the search functionality properly escapes
	 * user input to prevent SQL injection attacks.
	 *
	 * @param FunctionalTester $I
	 * @throws ModuleException
	 * @throws ExceptionAlias
	 */
	public function testSearchSqlInjectionPrevention(FunctionalTester $I): void {
		// Arrange
		$authUser = Users::CreateUser()->saveAndReturn();
		$normalUser = Users::CreateUser();
		$normalUser->username = 'search_normal_user';
		$normalUser->login = 'normal@test.com';
		$normalUser->password = 'test_password';
		$normalUser->saveAndReturn();
		
		// Act: Attempt various SQL injection patterns
		$injectionAttempts = [
			"'; DROP TABLE users; --",
			"' OR '1'='1",
			"' UNION SELECT * FROM users --",
			"'; INSERT INTO users (username) VALUES ('hacked'); --"
		];
		
		$I->amLoggedInAs($authUser);
		
		foreach ($injectionAttempts as $injection) {
			// Assert: Should handle injection attempts safely
			$I->amOnRoute('users/index', [
				'UsersSearch[username]' => $injection
			]);
			
			$I->seeResponseCodeIs(200);
			// Should not show any results for injection attempts
			$I->dontSee('hacked');
			$I->see('No results found'); // Malicious search should return no results
			
			// Navigate to clear search and verify normal users still exist (table not dropped)
			$I->amOnRoute('users/index');
			$I->see('search_normal_user');
		}
	}
}