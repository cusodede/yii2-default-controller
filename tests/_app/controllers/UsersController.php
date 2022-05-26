<?php
declare(strict_types = 1);

namespace app\controllers;

use app\models\Users;
use app\models\UsersSearch;
use cusodede\web\default_controller\models\DefaultController;

/**
 * Class UsersController
 */
class UsersController extends DefaultController {

	protected const DEFAULT_TITLE = "Сотрудники";

	public ?string $modelClass = Users::class;

	public ?string $modelSearchClass = UsersSearch::class;

	/**
	 * {@inheritdoc}
	 */
	public static function ViewPath():string {
		return '@app/views/users';
	}
}