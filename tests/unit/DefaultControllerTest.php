<?php
declare(strict_types = 1);

namespace unit;

use app\controllers\UsersController;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

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
			$user->save();
		}
		$usersController = new UsersController('users', Yii::$app);
		$result = $usersController->actionAjaxSearch('user', 'username');
		self::assertCount(100, $result['results']);

	}

}