<?php
declare(strict_types = 1);

namespace app\controllers;

use app\models\ConfigUsers;
use app\models\VanillaUsersSearch;
use cusodede\web\default_controller\models\DefaultController;

/**
 * Class ConfigUsersController
 */
class ConfigUsersController extends DefaultController {

	protected const DEFAULT_TITLE = "Сотрудники";

	public ?string $modelClass = ConfigUsers::class;

	public ?string $modelSearchClass = VanillaUsersSearch::class;

	protected const ACTION_TITLES = [
		'view' => 'Просмотр {username}',
		'edit' => 'Редактирование {username}',
		'update' => 'Редактирование {username}',
		'create' => 'Создание',
		'import' => 'Загрузка',
		'import-status' => 'Статус загрузки'
	];

	/**
	 * {@inheritdoc}
	 */
	public static function ViewPath():string {
		return '@app/views/users';
	}

}