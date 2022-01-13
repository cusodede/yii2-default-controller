<?php
declare(strict_types = 1);

namespace cusodede\web\default_controller\models;

use cusodede\web\default_controller\helpers\ErrorHelper;
use cusodede\web\default_controller\models\actions\EditableFieldAction;
use pozitronik\helpers\BootstrapHelper;
use pozitronik\helpers\ControllerHelper;
use pozitronik\helpers\ReflectionHelper;
use pozitronik\traits\traits\ActiveRecordTrait;
use pozitronik\traits\traits\ControllerTrait;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\UnknownClassException;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;
use yii\db\QueryInterface;
use yii\filters\AjaxFilter;
use yii\filters\ContentNegotiator;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class DefaultController
 * Все контроллеры и все вью плюс-минус одинаковые, поэтому можно сэкономить на прототипировании
 * @property string $modelClass Модель, обслуживаемая контроллером
 * @property string $modelSearchClass Поисковая модель, обслуживаемая контроллером
 * @property bool $enablePrototypeMenu Включать ли контроллер в меню списка прототипов
 *
 * @property-read ActiveRecordInterface $searchModel
 * @property-read ActiveRecordInterface $model
 */
class DefaultController extends Controller {
	use ControllerTrait;

	/**
	 * @var string название поля с первичным ключом.
	 * Конечно ключ может быть составным, но пока таких случаев не встречалось.
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
		'update' => 'Редактирование',
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
	 * @return string
	 * @throws InvalidConfigException
	 */
	public static function ViewPath():string {
		return '@cusodede/web/default_controller/views/site'.DIRECTORY_SEPARATOR.(BootstrapHelper::isBs4()?'bs4':'bs3');
	}

	/**
	 * {@inheritdoc}
	 */
	public function beforeAction($action):bool {
		$this->view->title = $this->view->title??ArrayHelper::getValue(static::ACTION_TITLES, $action->id,
				self::Title());
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
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getViewPath():string {
		return static::ViewPath();
	}

	/**
	 * @return ActiveRecordInterface
	 * @throws InvalidConfigException(
	 */
	public function getModel():ActiveRecordInterface {
		return null !== $this->modelClass
			?new $this->modelClass()
			:throw new InvalidConfigException('Не установлено свойство $modelClass');
	}

	/**
	 * @return ActiveRecordInterface
	 * @throws InvalidConfigException(
	 */
	public function getSearchModel():ActiveRecordInterface {
		if (null !== $this->modelSearchClass) {
			throw new InvalidConfigException('Не установлено свойство $modelSearchClass');
		}
		if (!method_exists($this->modelSearchClass, 'search')) {
			throw new InvalidConfigException("Класс {$this->modelSearchClass} должен иметь метод search()");
		}
		return new $this->modelSearchClass();
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

	/**
	 * Генерирует меню для доступа ко всем контроллерам по указанному пути
	 * @param string $alias
	 * @return array
	 * @throws Throwable
	 * @throws UnknownClassException
	 */
	public static function MenuItems(string $alias = "@app/controllers"):array {
		$items = [];
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Yii::getAlias($alias)),
			RecursiveIteratorIterator::SELF_FIRST);
		/** @var RecursiveDirectoryIterator $file */
		foreach ($files as $file) {
			/** @var self $model */
			if ($file->isFile()
				&& ('php' === $file->getExtension())
				&& (null !== $model = ControllerHelper::LoadControllerClassFromFile($file->getRealPath(), null,
						[self::class]))
				&& $model->enablePrototypeMenu) {
				$items[] = [
					'label' => $model->id,
					'url' => [$model::to($model->defaultAction)],
					'model' => $model,
				];
			}
		}
		return $items;
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
	 * @param int $id
	 * @return string
	 * @throws Throwable
	 */
	public function actionView(int $id):string {
		$model = $this->getModelByIdOrFail($id);

		return Yii::$app->request->isAjax
			?$this->renderAjax('modal/view', compact('model'))
			:$this->render('view', compact('model'));
	}

	/**
	 * actionEdit <==> actionUpdate
	 * @param int $id
	 * @return string
	 * @throws Throwable
	 */
	public function actionUpdate(int $id):string {
		return $this->actionEdit($id);
	}

	/**
	 * @param int $id
	 * @return string
	 * @throws Throwable
	 */
	public function actionEdit(int $id):string {
		$model = $this->getModelByPKOrFail($id);

		if ($model->load(Yii::$app->request->post())) {
			if ($model->save()) {
				Yii::$app->session->setFlash('success');
			} else {
				Yii::$app->session->setFlash('error', ErrorHelper::Errors2String($model->getErrors(), '<br>'));
			}
			Yii::$app->session->setFlash('error', ErrorHelper::Errors2String($model->getErrors(), '<br>'));
		}

		return Yii::$app->request->isAjax
			?$this->renderAjax('modal/edit', compact('model'))
			:$this->render('edit', compact('model'));
	}

	/**
	 * @return string|Response
	 * @throws Throwable
	 */
	public function actionCreate() {
		$model = $this->model;

		if ($model->load(Yii::$app->request->post())) {
			if (!$model->save()) {
				Yii::$app->session->setFlash('error',ErrorHelper::Errors2String($model->getErrors(), '<br>'));
				return $this->redirect(['create']);
			}
			Yii::$app->session->setFlash('success');
			return $this->redirect(['edit', 'id' => $model->id]);
		}

		return Yii::$app->request->isAjax
			?$this->renderAjax('modal/create', compact('model'))
			:$this->render('create', compact('model'));
	}

	/**
	 * @param int $id
	 * @return Response
	 * @throws Throwable
	 */
	public function actionDelete(int $id):Response {
		$model = $this->getModelByIdOrFail($id);

		/** @var ActiveRecordTrait $model */
		$model->safeDelete();
		return $this->redirect('index');
	}

	/**
	 * Аяксовый поиск в Select2
	 * @param string|null $term
	 * @param string $column
	 * @param string|null $concatFields Это список полей для конкатенации. Если этот параметр передан, то вернем
	 * результат CONCAT() для этих полей вместо поля параметра $column
	 * @return string[][]
	 * @throws Throwable
	 */
	public function actionAjaxSearch(?string $term, string $column = 'name', string $concatFields = null):array {
		$out = [
			'results' => [
				'id' => '',
				'text' => ''
			]
		];
		if (null !== $term) {
			$tableName = $this->model::tableName();
			if ($concatFields) {
				// добавляем название таблицы перед каждым полем
				$concatFieldsArray = preg_filter('/^/', "{$tableName}.", explode(',', $concatFields));
				// CONCAT возвращает пустое значение если хотя бы одно из полей NULL
				$concatFieldsArray = array_map(static function($item) {
					return 'COALESCE('.$item.", '')";
				}, $concatFieldsArray);
				// пихаем COALESCE в  CONCAT() функцию.
				// Конечный формат: SELECT DISTINCT `table`.`id`, CONCAT(COALESCE(table.a, ''), ' ', COALESCE(table.b, ''), ' ',COALESCE(table.c, '')) AS `text`
				$textFields = 'CONCAT('.implode(",' ',", $concatFieldsArray).')';
			} else {
				$textFields = "{$tableName}.{$column}";
			}
			$query = $this->model::find()
				->select(["{$tableName}.{$this->primaryColumnName}", "{$textFields} as text"])
				->where(['like', "{$tableName}.{$column}", "%$term%", false])
				->active()
				->distinct();

			$query = $this->addAdditionalAjaxConditions($query);
			$data = $query->asArray()->all();

			$out['results'] = array_values($data);
		}
		return $out;
	}

	/**
	 * Метод для дополнительных условий поиска аяксом
	 * @param ActiveQueryInterface $query
	 * @return ActiveQueryInterface
	 */
	protected function addAdditionalAjaxConditions(QueryInterface $query):ActiveQueryInterface {
		return $query;
	}

	/**
	 * @return string|Response
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws UnknownClassException
	 * @noinspection PhpUndefinedMethodInspection Существование метода проверяется при инициализации поисковой модели
	 */
	public function actionIndex() {
		$params = Yii::$app->request->queryParams;
		$searchModel = $this->getSearchModel();

		$viewParams = [
			'searchModel' => $searchModel,
			'dataProvider' => $searchModel->search($params),
			'controller' => $this,
			'modelName' => $this->model->formName(),
			'model' => $this->model,
		];

		if (Yii::$app->request->isAjax) {
			return $this->viewExists($this->viewPath.'modal/index') /*если модальной вьюхи для индекса не найдено - редирект*/
				?$this->renderAjax('modal/index', $viewParams)
				:$this->redirect(static::to('index'));/*параметры неважны - редирект произойдёт в modalHelper.js*/
		}

		return $this->render('index', $viewParams);
	}

	/**
	 * Проверяет, существует ли указанная вьюха. Для того, чтобы не дублировать закрытый метод фреймворка, хачим его
	 * через рефлексию.
	 * @param string $view
	 * @return bool
	 * @throws UnknownClassException
	 * @throws ReflectionException
	 */
	protected function viewExists(string $view):bool {
		if (null === $findViewFileReflectionMethod = ReflectionHelper::setAccessible($this->view, 'findViewFile')) {
			return false;
		}

		try {
			return file_exists($findViewFileReflectionMethod->invoke($this->view, $view));
		} catch (InvalidCallException) {
			return false;
		}
	}

	/**
	 * @param int $pk
	 * @return mixed|ActiveRecord
	 * @throws NotFoundHttpException
	 */
	protected function getModelByPKOrFail(int $pk) {
		return $this->model::findOne($pk)?:throw new NotFoundHttpException();
	}

	/**
	 * Вернуть название ключевого атрибута модели
	 * @param ActiveRecordInterface $model
	 * @return string|null
	 */
	protected static function getModelPKName(ActiveRecordInterface $model):?string {
		return $model::primaryKey()[0]??null;
	}

	/**
	 * Вернуть значение ключевого атрибута модели
	 * @param ActiveRecordInterface $model
	 * @return mixed
	 */
	protected static function getModelPKValue(ActiveRecordInterface $model):mixed {
		return $model->{static::getModelPKName($model)};
	}
}