<?php
declare(strict_types = 1);

namespace unit;

use app\models\Users;
use Codeception\Test\Unit;
use cusodede\web\default_controller\helpers\ControllerHelper;
use Exception;
use Throwable;
use Yii;
use yii\db\Exception as DbException;

/**
 * Comprehensive test suite for ControllerHelper utility class
 * 
 * This test class covers all helper methods with positive and negative scenarios,
 * edge cases, and error handling for the ControllerHelper utility functions.
 */
class ControllerHelperTest extends Unit {

	/**
	 * Test user instance for testing
	 * @var Users
	 */
	private Users $testUser;

	/**
	 * Set up test environment before each test
	 * @throws \yii\db\Exception
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->testUser = Users::CreateUser()->saveAndReturn();
	}

	/**
	 * Clean up after each test
	 */
	protected function tearDown(): void {
		if (isset($_POST)) $_POST = [];
		parent::tearDown();
	}

	// =========================================================================================
	// ERROR FORMATTING TESTS
	// =========================================================================================

	/**
	 * @covers ControllerHelper::Errors2String
	 * 
	 * Test error array to string conversion with various formats
	 */
	public function testErrors2StringBasic(): void {
		$errors = [
			'username' => 'Username cannot be blank',
			'email' => 'Email is not a valid email address'
		];

		// Test with default separator (newline)
		$result = ControllerHelper::Errors2String($errors);
		$expected = "username: Username cannot be blank\nemail: Email is not a valid email address";
		$this->assertEquals($expected, $result);

		// Test with custom separator
		$result = ControllerHelper::Errors2String($errors, '<br>');
		$expected = "username: Username cannot be blank<br>email: Email is not a valid email address";
		$this->assertEquals($expected, $result);
	}

	/**
	 * @covers ControllerHelper::Errors2String
	 * 
	 * Test error formatting with array of errors per attribute
	 */
	public function testErrors2StringWithMultipleErrorsPerAttribute(): void {
		$errors = [
			'password' => [
				'Password cannot be blank',
				'Password must be at least 6 characters'
			],
			'email' => 'Email is required'
		];

		// Test with separator for array elements
		$result = ControllerHelper::Errors2String($errors, ' | ');
		$expected = "password: Password cannot be blank | Password must be at least 6 characters | email: Email is required";
		$this->assertEquals($expected, $result);
	}

	/**
	 * @covers ControllerHelper::Errors2String
	 * 
	 * Test error formatting with empty errors
	 */
	public function testErrors2StringWithEmptyErrors(): void {
		$result = ControllerHelper::Errors2String([]);
		$this->assertEquals('', $result);
	}

	/**
	 * @covers ControllerHelper::Errors2String
	 * 
	 * Test error formatting with HTML separators
	 */
	public function testErrors2StringWithHtmlSeparators(): void {
		$errors = [
			'field1' => 'Error 1',
			'field2' => 'Error 2'
		];

		$result = ControllerHelper::Errors2String($errors, '<br/>');
		$expected = "field1: Error 1<br/>field2: Error 2";
		$this->assertEquals($expected, $result);
	}

	// =========================================================================================
	// PRIMARY KEY UTILITY TESTS
	// =========================================================================================

	/**
	 * @covers ControllerHelper::getModelPKName
	 * 
	 * Test primary key name extraction from model
	 */
	public function testGetModelPKName(): void {
		$pkName = ControllerHelper::getModelPKName($this->testUser);
		$this->assertEquals('id', $pkName);
	}

	/**
	 * @covers ControllerHelper::getModelPKValue
	 * 
	 * Test primary key value extraction from model
	 */
	public function testGetModelPKValue(): void {
		$pkValue = ControllerHelper::getModelPKValue($this->testUser);
		$this->assertEquals($this->testUser->id, $pkValue);
		$this->assertIsInt($pkValue);
	}

	/**
	 * @covers ControllerHelper::getModelPKName
	 * 
	 * Test primary key name extraction works correctly
	 */
	public function testGetModelPKNameWorksCorrectly(): void {
		$pkName = ControllerHelper::getModelPKName($this->testUser);
		$this->assertEquals('id', $pkName);
	}

	// =========================================================================================
	// AJAX VALIDATION TESTS
	// =========================================================================================

	/**
	 * @covers ControllerHelper::validateModelFromPost
	 * 
	 * Test AJAX validation with no POST data
	 * @throws Throwable
	 */
	public function testValidateModelFromPostWithoutData(): void {
		$_POST = [];
		
		$model = new Users();
		$result = ControllerHelper::validateModelFromPost($model);
		
		// Should return null when no POST data
		$this->assertNull($result);
	}

	// =========================================================================================
	// MODEL CREATION TESTS (Simplified)
	// =========================================================================================

	/**
	 * @covers ControllerHelper::createModelFromPost
	 * 
	 * Test model creation with no POST data
	 * @throws DbException
	 */
	public function testCreateModelFromPostWithoutData(): void {
		$_POST = [];
		
		$model = new Users();
		$errors = [];
		$result = ControllerHelper::createModelFromPost($model, $errors);
		
		// Should return null when no POST data
		$this->assertNull($result);
		$this->assertEmpty($errors);
	}

	// =========================================================================================
	// AJAX ERROR FORMATTING TESTS
	// =========================================================================================

	/**
	 * @covers ControllerHelper::errorsAjaxFormat
	 * 
	 * Test AJAX error format conversion
	 */
	public function testErrorsAjaxFormat(): void {
		$model = new Users();
		$model->validate(); // This will generate validation errors
		
		$ajaxErrors = ControllerHelper::errorsAjaxFormat($model);
		
		// Should return array with HTML input IDs as keys
		$this->assertIsArray($ajaxErrors);
		$this->assertArrayHasKey('users-username', $ajaxErrors);
		$this->assertArrayHasKey('users-login', $ajaxErrors);
		$this->assertArrayHasKey('users-password', $ajaxErrors);
		
		// Values should be arrays of error messages
		$this->assertIsArray($ajaxErrors['users-username']);
		$this->assertIsArray($ajaxErrors['users-login']);
		$this->assertIsArray($ajaxErrors['users-password']);
	}

	/**
	 * @covers ControllerHelper::errorsAjaxFormat
	 * 
	 * Test AJAX error format with model that has no errors
	 */
	public function testErrorsAjaxFormatWithNoErrors(): void {
		$model = new Users([
			'username' => 'valid_user',
			'login' => 'valid_login',
			'password' => 'valid_password'
		]);
		$model->validate(); // Should pass validation
		
		$ajaxErrors = ControllerHelper::errorsAjaxFormat($model);
		
		// Should return empty array
		$this->assertIsArray($ajaxErrors);
		$this->assertEmpty($ajaxErrors);
	}

	// =========================================================================================
	// INTEGRATION AND EDGE CASE TESTS
	// =========================================================================================

	/**
	 * Test helper methods with extreme input values
	 */
	public function testHelperMethodsWithExtremeValues(): void {
		// Test with very long error messages
		$longErrors = [
			'field' => str_repeat('This is a very long error message. ', 100)
		];
		
		$result = ControllerHelper::Errors2String($longErrors);
		$this->assertStringContainsString('field:', $result);
		$this->assertGreaterThan(1000, strlen($result));
		
		// Test with Unicode characters in errors
		$unicodeErrors = [
			'поле' => 'Ошибка с русскими символами',
			'字段' => '中文错误消息',
			'フィールド' => '日本語のエラーメッセージ'
		];
		
		$result = ControllerHelper::Errors2String($unicodeErrors);
		$this->assertStringContainsString('поле:', $result);
		$this->assertStringContainsString('字段:', $result);
		$this->assertStringContainsString('フィールド:', $result);
	}

	/**
	 * Test helper methods performance with large datasets
	 */
	public function testPerformanceWithLargeErrorArrays(): void {
		// Create large error array
		$largeErrors = [];
		for ($i = 0; $i < 1000; $i++) {
			$largeErrors["field_{$i}"] = "Error message for field {$i}";
		}
		
		$startTime = microtime(true);
		$result = ControllerHelper::Errors2String($largeErrors);
		$endTime = microtime(true);
		
		// Should complete within reasonable time
		$this->assertLessThan(1.0, $endTime - $startTime);
		$this->assertStringContainsString('field_0:', $result);
		$this->assertStringContainsString('field_999:', $result);
	}
}