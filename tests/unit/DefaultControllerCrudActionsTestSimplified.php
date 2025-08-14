<?php
declare(strict_types = 1);

namespace unit;

use app\controllers\UsersController;
use app\models\Users;
use Codeception\Test\Unit;
use Throwable;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * Simplified test suite for DefaultController CRUD actions
 * 
 * This test class focuses on testable aspects of CRUD operations
 * without requiring complex web request mocking.
 */
class DefaultControllerCrudActionsTestSimplified extends Unit {

	/**
	 * @var UsersController Test controller instance
	 */
	private UsersController $controller;

	/**
	 * Set up test environment before each test
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->controller = new UsersController('users', Yii::$app);
	}

	/**
	 * Clean up after each test
	 */
	protected function tearDown(): void {
		if (isset($_GET)) $_GET = [];
		if (isset($_POST)) $_POST = [];
		parent::tearDown();
	}

	// =========================================================================================
	// VIEW ACTION TESTS
	// =========================================================================================

	/**
	 * @covers DefaultController::actionView
	 * 
	 * Test view action with valid ID
	 * @throws Throwable
	 */
	public function testActionViewWithValidId(): void {
		$user = Users::CreateUser()->saveAndReturn();
		$_GET['id'] = $user->id;

		$result = $this->controller->actionView();
		
		$this->assertIsString($result);
		$this->assertStringContainsString($user->username, $result);
	}

	/**
	 * @covers DefaultController::actionView
	 * 
	 * Test view action with invalid ID
	 */
	public function testActionViewWithInvalidId(): void {
		$this->expectException(NotFoundHttpException::class);
		
		$_GET['id'] = 99999; // Non-existent ID
		$this->controller->actionView();
	}

	/**
	 * @covers DefaultController::actionView
	 * 
	 * Test view action without ID parameter
	 */
	public function testActionViewWithoutId(): void {
		$this->expectException(BadRequestHttpException::class);
		
		$this->controller->actionView();
	}

	// =========================================================================================
	// DELETE ACTION TESTS
	// =========================================================================================

	/**
	 * @covers DefaultController::actionDelete
	 * 
	 * Test delete action with valid ID
	 * @throws Throwable
	 */
	public function testActionDeleteWithValidId(): void {
		$user = Users::CreateUser()->saveAndReturn();
		$userId = $user->id;
		$_GET['id'] = $userId;

		$result = $this->controller->actionDelete();
		
		// Should return a Response object (redirect)
		$this->assertInstanceOf(\yii\web\Response::class, $result);
		
		// Verify user was deleted
		$deletedUser = Users::findOne($userId);
		$this->assertNull($deletedUser);
	}

	/**
	 * @covers DefaultController::actionDelete
	 * 
	 * Test delete action with non-existent ID
	 */
	public function testActionDeleteWithNonExistentId(): void {
		$this->expectException(NotFoundHttpException::class);
		
		$_GET['id'] = 99999;
		$this->controller->actionDelete();
	}

	/**
	 * @covers DefaultController::actionDelete
	 * 
	 * Test delete action without ID parameter
	 */
	public function testActionDeleteWithoutId(): void {
		$this->expectException(BadRequestHttpException::class);
		
		$this->controller->actionDelete();
	}

	// =========================================================================================
	// MODEL HANDLING TESTS
	// =========================================================================================

	/**
	 * Test model instantiation and basic properties
	 */
	public function testModelHandling(): void {
		$model = $this->controller->getModel();
		$this->assertInstanceOf(Users::class, $model);
		
		$searchModel = $this->controller->getSearchModel();
		$this->assertInstanceOf('app\models\UsersSearch', $searchModel);
	}

	/**
	 * Test primary key handling
	 */
	public function testPrimaryKeyHandling(): void {
		$pkName = $this->controller->getPrimaryKeyName();
		$this->assertEquals('id', $pkName);
	}

	// =========================================================================================
	// UTILITY METHODS TESTS
	// =========================================================================================

	/**
	 * Test controller title and path resolution
	 */
	public function testControllerUtilities(): void {
		$title = UsersController::Title();
		$this->assertEquals('Сотрудники', $title);
		
		$viewPath = UsersController::ViewPath();
		$this->assertStringContainsString('cusodede/web/default_controller/views/site', $viewPath);
	}

	/**
	 * Test grid column configuration
	 */
	public function testGridConfiguration(): void {
		$columns = $this->controller->configureGridColumns();
		$this->assertIsArray($columns);
		$this->assertNotEmpty($columns);
		
		// Should contain action column and model attributes
		$this->assertContains('id', $columns);
		$this->assertContains('username', $columns);
		$this->assertContains('login', $columns);
		$this->assertContains('password', $columns);
	}

	/**
	 * Test default action column generation
	 */
	public function testDefaultActionColumn(): void {
		$actionColumn = $this->controller->getDefaultActionColumn();
		$this->assertIsArray($actionColumn);
		$this->assertNotEmpty($actionColumn);
		
		// Should contain ActionColumn configuration
		$this->assertArrayHasKey('class', $actionColumn[0]);
		$this->assertStringContainsString('ActionColumn', $actionColumn[0]['class']);
	}

	// =========================================================================================
	// AJAX SEARCH TESTS
	// =========================================================================================

	/**
	 * @covers DefaultController::actionAjaxSearch
	 * 
	 * Test AJAX search functionality
	 * @throws Throwable
	 */
	public function testAjaxSearch(): void {
		// Create test data
		for ($i = 0; $i < 5; $i++) {
			$user = Users::CreateUser();
			$user->username = "search_user_{$i}";
			$user->login = "search_login_{$i}";
			$user->saveAndReturn();
		}

		$result = $this->controller->actionAjaxSearch('search_user', 'username');
		
		$this->assertIsArray($result);
		$this->assertArrayHasKey('results', $result);
		$this->assertCount(5, $result['results']);
	}

	/**
	 * @covers DefaultController::actionAjaxSearch
	 * 
	 * Test AJAX search with empty term
	 * @throws Throwable
	 */
	public function testAjaxSearchWithEmptyTerm(): void {
		$result = $this->controller->actionAjaxSearch(null, 'username');
		
		$this->assertIsArray($result);
		$this->assertArrayHasKey('results', $result);
		$this->assertEmpty($result['results']['id']);
	}

	/**
	 * @covers DefaultController::actionAjaxSearch
	 * 
	 * Test AJAX search case insensitive
	 * @throws Throwable
	 */
	public function testAjaxSearchCaseInsensitive(): void {
		$user = Users::CreateUser();
		$user->username = "CaseSensitive";
		$user->saveAndReturn();

		$result1 = $this->controller->actionAjaxSearch('case', 'username');
		$result2 = $this->controller->actionAjaxSearch('CASE', 'username');
		
		$this->assertCount(1, $result1['results']);
		$this->assertCount(1, $result2['results']);
		$this->assertEquals($result1['results'], $result2['results']);
	}

	// =========================================================================================
	// EDGE CASES AND ERROR HANDLING
	// =========================================================================================

	/**
	 * Test handling of special characters in search
	 * @throws Throwable
	 */
	public function testAjaxSearchWithSpecialCharacters(): void {
		$user = Users::CreateUser();
		$user->username = "special!@#$%characters";
		$user->saveAndReturn();

		$result = $this->controller->actionAjaxSearch('special', 'username');
		
		$this->assertIsArray($result);
		$this->assertArrayHasKey('results', $result);
		$this->assertCount(1, $result['results']);
	}

	/**
	 * Test multi-column search
	 * @throws Throwable
	 */
	public function testAjaxSearchMultiColumn(): void {
		$user = Users::CreateUser();
		$user->username = "multitest";
		$user->login = "multilogin";
		$user->saveAndReturn();

		$result = $this->controller->actionAjaxSearch('multi', 'username,login');
		
		$this->assertIsArray($result);
		$this->assertArrayHasKey('results', $result);
		$this->assertCount(2, $result['results']); // Should find in both username and login
	}

	/**
	 * Test performance with reasonable dataset
	 * @throws Throwable
	 */
	public function testAjaxSearchPerformance(): void {
		// Create test dataset
		for ($i = 0; $i < 50; $i++) {
			$user = Users::CreateUser();
			$user->username = "perf_user_{$i}";
			$user->saveAndReturn();
		}

		$startTime = microtime(true);
		$result = $this->controller->actionAjaxSearch('perf', 'username');
		$endTime = microtime(true);

		// Should complete within reasonable time
		$this->assertLessThan(2.0, $endTime - $startTime);
		$this->assertCount(50, $result['results']);
	}

	// =========================================================================================
	// BREADCRUMBS AND TITLE TESTS
	// =========================================================================================

	/**
	 * Test view title initialization
	 * @throws \yii\db\Exception
	 * @throws BadRequestHttpException
	 */
	public function testInitViewTitle(): void {
		$user = Users::CreateUser()->saveAndReturn();
		$user->username = "title_test_user";
		$user->save();
		
		$_GET['id'] = $user->id;
		
		$title = $this->controller->initViewTitle('User: {username}');
		$this->assertEquals('User: title_test_user', $title);
	}

	/**
	 * Test view title with non-existent variable
	 * @throws \yii\db\Exception
	 * @throws BadRequestHttpException
	 */
	public function testInitViewTitleWithNonExistentVariable(): void {
		$user = Users::CreateUser()->saveAndReturn();
		$_GET['id'] = $user->id;
		
		$title = $this->controller->initViewTitle('User: {nonexistent}');
		$this->assertEquals('User: %undefined%', $title);
	}
}