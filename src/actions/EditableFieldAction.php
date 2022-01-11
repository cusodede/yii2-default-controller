<?php
declare(strict_types = 1);

namespace cusodede\DefaultController\Actions;

use app\components\db\ActiveRecordTrait;
use app\components\helpers\TemporaryHelper;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\rest\Action;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class EditableFieldAction
 * Экшен для инлайн-редакторов атрибутов, a-la Editable
 */
class EditableFieldAction extends Action {

	/**
	 * @var string the scenario to be assigned to the model before it is validated and updated.
	 */
	public string $scenario = Model::SCENARIO_DEFAULT;

	/**
	 * @param int $id
	 * @return array[]|object|Response[]|string[]|string[][]
	 * @throws Throwable
	 * @throws InvalidConfigException
	 * @throws Exception
	 * @throws NotFoundHttpException
	 */
	public function run(int $id) {
		$result = ['output' => '', 'message' => ''];

		/* @var ActiveRecordTrait|ActiveRecord $model */
		$model = $this->findModel($id);

		if ($this->checkAccess) {
			call_user_func($this->checkAccess, $this->id, $model);
		}

		$model->scenario = $this->scenario;
		$errors = [];
		if (!$model->updateModelFromPost($errors, false)) {
			$result = ['output' => '', 'message' => TemporaryHelper::Errors2String($errors)];
		}
		return Yii::createObject(['class' => Response::class, 'format' => Response::FORMAT_JSON, 'data' => $result]);
	}
}