<?php
declare(strict_types = 1);

namespace cusodede\web\default_controller\models\actions;

use cusodede\web\default_controller\models\DefaultController;
use yii\web\ErrorAction as YiiErrorAction;

/**
 * Class ErrorAction
 */
class ErrorAction extends YiiErrorAction {
	/**
	 * @inheritDoc
	 */
	protected function renderAjaxResponse():string {
		return $this->controller->renderAjax(DefaultController::ViewPath().'error', [
			'exception' => $this->exception
		]);
	}
}