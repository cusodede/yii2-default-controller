<?php
declare(strict_types = 1);

namespace app\controllers;

use cusodede\web\default_controller\models\DefaultController;

/**
 * Class EmptyController
 */
class EmptyController extends DefaultController {

	protected array $disabledActions = [
		'actionIndex',
		'actionCreate',
		'actionView',
		'actionDelete',
		'actionUpdate',
		'actionEdit'
	];

	/**
	 * @return string
	 */
	public function actionFoo():string {
		return 'foo';
	}
}