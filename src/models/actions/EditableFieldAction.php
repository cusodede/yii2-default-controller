<?php
declare(strict_types = 1);

namespace cusodede\web\default_controller\models\actions;

use cusodede\web\default_controller\helpers\ControllerHelper;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
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

		$model = $this->findModel($id);

		if ($this->checkAccess) {
			call_user_func($this->checkAccess, $this->id, $model);
		}

		$model->scenario = $this->scenario;

		if ($model->load(Yii::$app->request->post()) && !$model->save()) {
			$result = ['output' => '', 'message' => ControllerHelper::Errors2String($model->getErrors(), '<br>')];
		}

		return Yii::createObject(['class' => Response::class, 'format' => Response::FORMAT_JSON, 'data' => $result]);
	}

}