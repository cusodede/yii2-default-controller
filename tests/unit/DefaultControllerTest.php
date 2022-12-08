<?php
declare(strict_types = 1);

namespace unit;

use app\controllers\ConfigUsersController;
use app\controllers\EmptyController;
use app\controllers\UsersController;
use app\controllers\VanillaUsersController;
use app\models\ConfigUsers;
use app\models\TestUsers;
use app\models\Users;
use Codeception\Test\Unit;
use pozitronik\helpers\ReflectionHelper;
use Yii;
use yii\db\Exception;
use yii\web\BadRequestHttpException;

/**
 * Class DefaultControllerTest
 */
class DefaultControllerTest extends Unit {

	/**
	 * @covers UsersController::actionAjaxSearch
	 * @return void
	 */
	public function testAjaxSearch():void {
		for ($i = 0; $i < 100; $i++) {
			$user = Users::CreateUser()->saveAndReturn();
			$user->username = "user_{$user->id}";
			$user->login = "i{$i}";
			$user->save();
		}
		$usersController = new UsersController('users', Yii::$app);
		$result = $usersController->actionAjaxSearch('user', 'username');
		self::assertCount(100, $result['results']);

		$result = $usersController->actionAjaxSearch('uSeR', 'username');
		self::assertCount(100, $result['results']);

		$result = $usersController->actionAjaxSearch('9', 'username');
		self::assertCount(19, $result['results']);

		$result = $usersController->actionAjaxSearch('0', 'username, login');
		self::assertCount(20, $result['results']);

		$result = $usersController->actionAjaxSearch('9', 'id');
		self::assertCount(19, $result['results']);

		$result = $usersController->actionAjaxSearch('9', 'username, ,');
		self::assertCount(19, $result['results']);

		$this->expectException(Exception::class);
		$usersController->actionAjaxSearch('9', 'username, "non-existent-field"');
	}

	/**
	 * @covers UsersController::configureGridColumns
	 * @return void
	 */
	public function testConfigureGridColumns():void {
		/*Колонки никак не сконфигурированы, ожидаем увидеть все по умолчанию*/
		$controller = new UsersController('users', Yii::$app);
		self::assertEquals(array_merge($controller->getDefaultActionColumn(), ['id', 'username', 'login', 'password']), $controller->configureGridColumns());
		$controller = new VanillaUsersController('vanilla-users', Yii::$app);
		/*Колонки сконфигурированы в контроллере*/
		self::assertEquals(['id', 'login:text'], $controller->configureGridColumns());

		/*Колонки сконфигурированы внутри модели*/
		self::assertEquals(['login:text', 'password:text'], $controller->configureGridColumns(new TestUsers()));

		/*Колонки сконфигурированы в конфиге*/
		$controller = new ConfigUsersController('config-users', Yii::$app);
		self::assertEquals(['id', 'username:text', 'password:text'], $controller->configureGridColumns(new ConfigUsers()));
	}

	/**
	 * @covers UsersController::initViewTitle
	 * @return void
	 * @throws Exception
	 * @throws BadRequestHttpException
	 */
	public function testInitViewTitle():void {
		for ($i = 0; $i < 100; $i++) {
			$user = Users::CreateUser()->saveAndReturn();
			$user->username = "user_{$user->id}";
			$user->login = "i{$i}";
			$user->save();
		}
		$usersController = new UsersController('users', Yii::$app);
		$_GET['id'] = 1;
		$this->assertEquals('Просмотр: user_1', $usersController->initViewTitle('Просмотр: {username}'));

		$emptyController = new EmptyController('empty', Yii::$app);
		$this->assertEquals('Просмотр: {username}', $emptyController->initViewTitle('Просмотр: {username}'));
	}

	/**
	 * @covers UsersController::applyActionScenario
	 * @return void
	 * @throws Exception
	 */
	public function testActionScenario():void {
		$user = Users::CreateUser()->saveAndReturn();
		$usersController = new UsersController('users', Yii::$app);
		$usersController->scenarios = ['actionEdit' => 'onEditScenario'];

		$applyActionScenarioMethod = ReflectionHelper::setAccessible($usersController, 'applyActionScenario', false);
		static::assertNotNull($applyActionScenarioMethod);

		$applyActionScenarioMethod->invoke($usersController, $user, 'actionEdit');
		static::assertEquals($user->scenario, 'onEditScenario');

		$user = Users::CreateUser()->saveAndReturn();
		$applyActionScenarioMethod->invoke($usersController, $user, 'actionDelete');
		static::assertEquals($user->scenario, 'default');
	}

}