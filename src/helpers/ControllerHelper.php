<?php
declare(strict_types = 1);

namespace cusodede\web\default_controller\helpers;

use Yii;
use yii\db\ActiveRecordInterface;

/**
 * Вспомогательный хелпер
 */
class ControllerHelper {

	/**
	 * Вернуть название ключевого атрибута модели
	 * @param ActiveRecordInterface $model
	 * @return string|null
	 */
	public static function getModelPKName(ActiveRecordInterface $model):?string {
		return $model::primaryKey()[0]??null;
	}

	/**
	 * Вернуть значение ключевого атрибута модели
	 * @param ActiveRecordInterface $model
	 * @return mixed
	 */
	public static function getModelPKValue(ActiveRecordInterface $model):mixed {
		return $model->{static::getModelPKName($model)};
	}

	/**
	 * Текущий запрос - ajax-валидация формы?
	 * Метод делается публичной статикой, он нужен не только в наследниках
	 * @return bool
	 */
	public static function isAjaxValidationRequest():bool {
		return null !== Yii::$app->request->post('ajax');
	}
}