<?php
declare(strict_types = 1);

namespace cusodede\web\default_controller\helpers;

use Throwable;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecordInterface;
use yii\db\Exception as DbException;
use yii\widgets\ActiveForm;

/**
 * Вспомогательный хелпер
 */
class ControllerHelper {

	/**
	 * Вывести человекочитаемый список ошибок
	 * @param array $errors
	 * @param array|string $separator
	 * @return string
	 */
	public static function Errors2String(array $errors, array|string $separator = "\n"):string {
		$output = [];
		foreach ($errors as $attribute => $attributeErrors) {
			$error = is_array($attributeErrors)?implode($separator, $attributeErrors):$attributeErrors;
			$output[] = "{$attribute}: {$error}";
		}

		return implode($separator, $output);
	}

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

	/**
	 * Валидация для ajax-validation
	 * @param ActiveRecordInterface|Model $model
	 * @return null|array
	 * @throws Throwable
	 */
	public static function validateModelFromPost(Model|ActiveRecordInterface $model):?array {
		return ($model->load(Yii::$app->request->post()))
			?ActiveForm::validate($model)
			:null;
	}

	/**
	 * @param ActiveRecordInterface|Model $model
	 * @param array $errors Возвращаемый список ошибок.
	 * @param null|bool $AJAXErrorsFormat Формат возврата ошибок: true: для ajax-валидации, false - as is, null (default) - в зависимости от типа запроса
	 * @param array $relationAttributes Массив с перечисление relational-моделей, приходящих отдельной формой
	 * @return null|bool true: модель сохранена, false: модель не сохранена, null: постинга не было
	 * @throws DbException
	 * @param-out array $errors На выходе всегда будет массив
	 */
	public static function createModelFromPost(Model|ActiveRecordInterface $model, array &$errors = [], bool $AJAXErrorsFormat = null, array $relationAttributes = []):?bool {
		$errors = [];
		if ($model->load(Yii::$app->request->post()) && null !== $transaction = Yii::$app->getDb()->beginTransaction()) {
			/**
			 * Все изменения заключаются в транзакцию с тем, чтобы откатывать сохранения записей, задаваемых в relational-атрибутах
			 * Если сохранение одной модели завязано на сохранение другой модели, привязанной через relational-атрибут,
			 * то пытаемся сохранить связанную модель, при неудаче - откатываемся.
			 */
			if ([] !== $relationAttributes) {
				foreach ($relationAttributes as $relationAttributeName) {
					if ($model->hasProperty($relationAttributeName) && $model->canSetProperty($relationAttributeName)
						&& false === $model->$relationAttributeName->createModelFromPost($errors, $AJAXErrorsFormat)) {
						/*Ошибка сохранения связанной модели, откатим изменения*/
						$transaction->rollBack();
						return false;
					}
				}
			}

			if (false !== $result = $model->save()) {
				$transaction->commit();
			} else {
				if (null === $AJAXErrorsFormat) $AJAXErrorsFormat = Yii::$app->request->isAjax;
				$errors = $AJAXErrorsFormat
					?ActiveForm::validate($model)
					:$model->errors;
				$transaction->rollBack();
			}

			return $result;
		}
		return null;
	}
}