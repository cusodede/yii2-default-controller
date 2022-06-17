<?php
declare(strict_types = 1);

namespace unit;

use app\controllers\ConfigUsersController;
use app\controllers\UsersController;
use app\controllers\VanillaUsersController;
use app\models\ConfigUsers;
use app\models\TestUsers;
use app\models\Users;
use Codeception\Test\Unit;
use kartik\base\Config;
use Yii;
use yii\db\Exception;

/**
 * Class DefaultControllerTest
 */
class DefaultControllerTest extends Unit {

	/**
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
	 * @return void
	 */
	public function testConfigureGridColumns():void {
		/*Колонки никак не сконфигурированы, ожидаем увидеть все по умолчанию*/
		$controller = new UsersController('users', Yii::$app);
		self::assertEquals(['id', 'username', 'login', 'password'], $controller->configureGridColumns());
		$controller = new VanillaUsersController('vanilla-users', Yii::$app);
		/*Колонки сконфигурированы в контроллере*/
		self::assertEquals(['id', 'login:text'], $controller->configureGridColumns());

		/*Колонки сконфигурированы внутри модели*/
		self::assertEquals(['login:text', 'password:text'], $controller->configureGridColumns(new TestUsers()));

		/*Колонки сконфигурированы в конфиге*/
		$controller = new ConfigUsersController('config-users', Yii::$app);
		self::assertEquals(['id', 'username:text', 'password:text'], $controller->configureGridColumns(new ConfigUsers()));
	}

}