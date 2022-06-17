<?php
/** @noinspection PhpUndefinedFieldInspection */
declare(strict_types = 1);

use app\models\Users;
use app\models\VanillaUsers;
use Codeception\Exception\ModuleException;
use yii\base\InvalidConfigException;

/**
 * Class ManagersCest
 */
class UsersCest {

	/**
	 * @param FunctionalTester $I
	 * @throws Throwable
	 * @throws ModuleException
	 * @throws InvalidConfigException
	 * @throws Exception
	 */
	public function create(FunctionalTester $I):void {
		$user = Users::CreateUser()->saveAndReturn();

		$I->amLoggedInAs($user);
		$I->amOnRoute('users/create');
		$I->seeResponseCodeIs(200);
		$I->submitForm("#users-create", [
			'Users' => [
				'username' => 'Test Successful',
				'login' => 'test_user_2',
				'password' => '123',
			]
		]);
		$I->seeResponseCodeIs(200);
		$I->seeInCurrentUrl('users/index');
		$I->assertCount(2, Users::find()->all());
		$model = Users::findOne(['username' => 'Test Successful']);
		$I->assertNotNull($model);
		$I->assertEquals(2, $model->id);
		$I->assertEquals('test_user_2', $model->login);
		$I->assertEquals('123', $model->password);
	}

	/**
	 * @param FunctionalTester $I
	 * @return void
	 * @throws ModuleException
	 * @throws Exception
	 */
	public function testViewTitles(FunctionalTester $I):void {
		$user = Users::CreateUser()->saveAndReturn();
		$I->amLoggedInAs($user);
		$I->amOnRoute('users/view?id=1');
		$I->seeResponseCodeIs(200);
		$I->canSeeInTitle("Просмотр {$user->username}");
	}

	/**
	 * Проверка "ванильного" ActiveRecord
	 * @param FunctionalTester $I
	 * @throws Throwable
	 * @throws ModuleException
	 * @throws InvalidConfigException
	 * @throws Exception
	 */
	public function createVanilla(FunctionalTester $I):void {
		$user = VanillaUsers::CreateUser()->saveAndReturn();

		$I->amLoggedInAs($user);
		$I->amOnRoute('vanilla-users/create');
		$I->seeResponseCodeIs(200);
		$I->submitForm("#users-create", [
			'VanillaUsers' => [
				'username' => 'Test Successful',
				'login' => 'test_user_3',
				'password' => '124',
			]
		]);
		$I->seeResponseCodeIs(200);
		$I->seeInCurrentUrl('vanilla-users/index');
		$I->assertCount(2, VanillaUsers::find()->all());
		$model = VanillaUsers::findOne(['username' => 'Test Successful']);
		$I->assertNotNull($model);
		$I->assertEquals(2, $model->id);
		$I->assertEquals('test_user_3', $model->login);
		$I->assertEquals('124', $model->password);
	}
}
