<?php
declare(strict_types = 1);

namespace cusodede\DefaultController;

use cusodede\DefaultController\Actions\EditableFieldAction;
use app\models\sys\permissions\filters\PermissionFilter;
use app\modules\import\models\ImportAction;
use app\modules\import\models\ImportStatusAction;
use pozitronik\helpers\ControllerHelper;
use pozitronik\traits\traits\ControllerTrait;
use Yii;
use yii\filters\AjaxFilter;
use yii\filters\ContentNegotiator;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;

/**
 * This is just an example.
 */
class DefaultController extends Controller {
	use ControllerTrait;

	/**
	 * @var string название поля с первичным ключом.
	 * Конечно ключ может быть составным, но пока таких случаев не встречалось
	 */
	protected string $primaryColumnName = 'id';

	/**
	 * Название контроллера
	 */
	protected const DEFAULT_TITLE = null;

	/**
	 * Дефолтные названия экшенов
	 */
	protected const ACTION_TITLES = [
		'view' => 'Просмотр',
		'edit' => 'Редактирование',
		'create' => 'Создание',
		'import' => 'Загрузка',
		'import-status' => 'Статус загрузки'
	];

	/**
	 * @var string|null $modelClass
	 */
	public ?string $modelClass = null;
	/**
	 * @var string|null $modelSearchClass
	 */
	public ?string $modelSearchClass = null;

	/**
	 * @var bool enablePrototypeMenu
	 */
	public bool $enablePrototypeMenu = true;

	/**
	 * @return string
	 */
	public static function Title():string {
		return static::DEFAULT_TITLE??ControllerHelper::ExtractControllerId(static::class);
	}

	/**
	 * {@inheritdoc}
	 */
	public function beforeAction($action):bool {
		$this->view->title = $this->view->title??ArrayHelper::getValue(static::ACTION_TITLES, $action->id, self::Title());
		if (!isset($this->view->params['breadcrumbs'])) {
			if ($this->defaultAction === $action->id) {
				$this->view->params['breadcrumbs'][] = self::Title();
			} else {
				$actionForUrl = Yii::$app->id !== $this->module->id?'/'.$this->module->id.$this::to($this->defaultAction):$this::to($this->defaultAction);
				$this->view->params['breadcrumbs'][] = ['label' => self::Title(), 'url' => $actionForUrl];
				$this->view->params['breadcrumbs'][] = ['label' => $this->view->title];
			}

		}
		return parent::beforeAction($action);
	}
	/**
	 * @inheritDoc
	 */
	public function behaviors():array {
		return [
			[
				'class' => ContentNegotiator::class,
				'only' => ['ajax-search'],
				'formats' => [
					'application/json' => Response::FORMAT_JSON
				]
			],
			[
				'class' => AjaxFilter::class,
				'only' => ['ajax-search']
			],
			'access' => [
				'class' => PermissionFilter::class
			]
		];
	}


	/**
	 * @inheritDoc
	 */
	public function actions():array {
		return ArrayHelper::merge(parent::actions(), [
			'editAction' => [
				'class' => EditableFieldAction::class,
				'modelClass' => $this->modelClass
			],
		]);
	}
}
