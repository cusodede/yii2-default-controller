<?php
declare(strict_types = 1);

namespace cusodede\DefaultController\Actions;

use yii\web\ErrorAction as YiiErrorAction;

/**
 * Class ErrorAction
 */
class ErrorAction extends YiiErrorAction {
	/**
	 * Builds string that represents the exception.
	 * Normally used to generate a response to AJAX request.
	 * @return string
	 * @since 2.0.11
	 */
	protected function renderAjaxResponse():string {
		return $this->controller->renderAjax('@cusodede/DefaultController/views/site/modal/error', [
			'exception' => $this->exception
		]);
	}
}