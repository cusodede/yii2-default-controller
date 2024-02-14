<?php
declare(strict_types = 1);

namespace cusodede\web\default_controller\models;

use cusodede\web\default_controller\helpers\ControllerHelper;
use cusodede\web\default_controller\models\actions\EditableFieldAction;
use Exception;
use kartik\grid\ActionColumn;
use pozitronik\helpers\BootstrapHelper;
use pozitronik\helpers\ControllerHelper as VendorControllerHelper;
use pozitronik\helpers\ReflectionHelper;
use pozitronik\traits\traits\ControllerTrait;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use ReflectionMethod;
use Throwable;
use Yii;
use yii\base\Action;
use yii\base\InlineAction;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\UnknownClassException;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecordInterface;
use yii\db\QueryInterface;
use yii\filters\AjaxFilter;
use yii\filters\ContentNegotiator;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class DefaultController
 * Все контроллеры и все вью плюс-минус одинаковые, поэтому можно сэкономить на прототипировании
 * @property string $modelClass Модель, обслуживаемая контроллером
 * @property string $modelSearchClass Поисковая модель, обслуживаемая контроллером
 * @property bool $enablePrototypeMenu Включать ли контроллер в меню списка прототипов. Параметр объявлен устаревшим, и будет убран.
 *
 * @property-read ActiveRecordInterface $searchModel
 * @property-read ActiveRecordInterface $model
 */
abstract class DefaultController extends Controller {
	use ControllerTrait;

	/**
	 * @var null|string название поля с первичным ключом.
	 * Конечно ключ может быть составным, но пока таких случаев не встречалось.
	 * null - попытаемся получить из модели
	 */
	protected static ?string $_primaryKeyName = null;

	/**
	 * @var string|null $_afterCreateAction
	 * Экшен, на который будет происходить редирект после создания модели.
	 * Если null - взять глобальную настройку.
	 */
	protected static ?string $_afterCreateAction = null;
	/**
	 * @var string|null
	 * Экшен, на который будет происходить редирект после создания модели.
	 * Если null - взять глобальную настройку.
	 */
	protected static ?string $_afterUpdateAction = null;

	/**
	 * @var string[]
	 * При необходимости отключить дефолтные экшены, перечисляем их в массиве, например
	 * ['actionView', 'actionEdit', 'actionUpdate'] => отключить actionView(), actionEdit(), actionUpdate()
	 * @see CreateAction()
	 */
	protected array $disabledActions = [];

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
	 * @var bool $enablePrototypeMenu
	 * @deprecated since 1.0.8.
	 */
	public bool $enablePrototypeMenu = false;

	/**
	 * @var string[]
	 *
	 * Массив сценариев применяемых для каждого указанного action,
	 * ['actionView' => 'SCENARIO_NAME', ...]
	 */
	public array $scenarios = [];

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
	 * @inheritDoc
	 */
	public function createAction($id):?Action {
		if ('' === $id) $id = $this->defaultAction;

		$actionMap = $this->actions();
		if (isset($actionMap[$id])) {
			return Yii::createObject($actionMap[$id], [$id, $this]);
		}

		if (preg_match('/^(?:[a-z\d_]+-)*[a-z\d_]+$/', $id)) {
			$methodName = 'action'.str_replace(' ', '', ucwords(str_replace('-', ' ', $id)));
			if (method_exists($this, $methodName) && !in_array($methodName, $this->disabledActions, true)) {
				$method = new ReflectionMethod($this, $methodName);
				if ($method->isPublic() && $method->getName() === $methodName) {
					return new InlineAction($id, $this, $methodName);
				}
			}
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function beforeAction($action):bool {
		$this->view->title = $this->initViewTitle($this->view->title??ArrayHelper::getValue(static::ACTION_TITLES, $action->id, static::Title()));
		if (!isset($this->view->params['breadcrumbs'])) {
			if ($this->defaultAction === $action->id) {
				$this->view->params['breadcrumbs'][] = static::Title();
			} else {
				$this->view->params['breadcrumbs'][] = ['label' => static::Title(), 'url' => $this->link($this->defaultAction)];
				$this->view->params['breadcrumbs'][] = ['label' => $this->view->title];
			}

		}
		return parent::beforeAction($action);
	}

	/**
	 * @return string|null
	 */
	public function getPrimaryKeyName():?string {
		if (null !== static::$_primaryKeyName) return static::$_primaryKeyName;
		if (method_exists($this->modelClass, 'pkName')) {//probably, ActiveRecordTrait
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			return static::$_primaryKeyName ??= $this->model::pkName();
		}
		//fallback to vanilla ActiveRecord
		return $this->model::primaryKey()[0]??null;

	}

	/**
	 * Возвращает настройку экшена, на который будет переходить редирект после создания модели.
	 * @return string
	 * @throws Exception
	 */
	protected static function getAfterCreateAction():string {
		return static::$_afterCreateAction ??= ArrayHelper::getValue(Yii::$app->components, 'defaultController.afterCreateAction', 'index');
	}

	/**
	 * Возвращает настройку экшена, на который будет переходить редирект после изменения модели.
	 * @return string
	 * @throws Exception
	 */
	protected static function getAfterUpdateAction():string {
		return static::$_afterUpdateAction ??= ArrayHelper::getValue(Yii::$app->components, 'defaultController.afterUpdateAction', 'index');
	}

	/**
	 * @return string
	 */
	public static function Title():string {
		return static::DEFAULT_TITLE??VendorControllerHelper::ExtractControllerId(static::class);
	}

	/**
	 * @return string
	 * @throws InvalidConfigException
	 */
	public static function ViewPath():string {
		return '@cusodede/web/default_controller/views/site'.DIRECTORY_SEPARATOR.(BootstrapHelper::isBs4()?'bs4':'bs3');
	}

	/**
	 * @return string|Response
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws UnknownClassException
	 */
	public function actionIndex() {
		$params = Yii::$app->request->queryParams;
		$searchModel = $this->getSearchModel();

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$viewParams = [
			'searchModel' => $searchModel,
			'dataProvider' => $searchModel->search($params),
			'controller' => $this,
			'modelName' => $this->model->formName(),
			'model' => $this->model,
			'hasCreateAction' => $this->isActionDisabled(['createAction'])
		];

		if (Yii::$app->request->isAjax) {
			return $this->viewExists(static::ViewPath().'modal/index') /*если модальной вьюхи для индекса не найдено - редирект*/
				?$this->renderAjax('modal/index', $viewParams)
				:$this->redirect($this->link('index'));/*параметры неважны - редирект произойдёт в modalHelper.js*/
		}

		return $this->render('index', $viewParams);
	}

	/**
	 * @return string|Response
	 * @throws Throwable
	 */
	public function actionCreate() {
		$model = $this->model;
		$this->applyActionScenario($model, __FUNCTION__);
		if (ControllerHelper::IsAjaxValidationRequest()) {
			return $this->asJson(ControllerHelper::validateModelFromPost($model));
		}
		$errors = [];
		$posting = ControllerHelper::createModelFromPost($model, $errors);/* switch тут нельзя использовать из-за его нестрогости */
		if (true === $posting) {/* Модель была успешно прогружена */
			return ('index' === $redirectAction = static::getAfterCreateAction())
				?$this->redirect(Url::toRoute($redirectAction))/*При редиректе на index get-параметры стоит спрятать*/
				:$this->redirect(Url::toRoute([$redirectAction, $this->getPrimaryKeyName() => ArrayHelper::getValue($model, $this->getPrimaryKeyName())]));
		}
		/* Пришёл постинг, но есть ошибки */
		if ((false === $posting) && Yii::$app->request->isAjax) {
			return $this->asJson($errors);
		}
		/* Постинга не было */
		return (Yii::$app->request->isAjax)
			?$this->renderAjax('modal/create', compact('model'))
			:$this->render('create', compact('model'));
	}

	/**
	 * @return string
	 * @throws Throwable
	 */
	public function actionView():string {
		$model = $this->getModelByPKOrFail($this->checkPrimaryKey());

		return Yii::$app->request->isAjax
			?$this->renderAjax('modal/view', compact('model'))
			:$this->render('view', compact('model'));
	}

	/**
	 * @return string|Response
	 * @throws Throwable
	 */
	public function actionEdit() {
		$model = $this->getModelByPKOrFail($pk = $this->checkPrimaryKey());
		$this->applyActionScenario($model, __FUNCTION__);

		if (ControllerHelper::IsAjaxValidationRequest()) {
			return $this->asJson(ControllerHelper::validateModelFromPost($model));
		}
		$errors = [];
		$posting = ControllerHelper::createModelFromPost($model, $errors);

		if (true === $posting) {/* Модель была успешно прогружена */
			return ('index' === $redirectAction = static::getAfterUpdateAction())
				?$this->redirect(Url::toRoute($redirectAction))/*При редиректе на index get-параметры стоит спрятать*/
				:$this->redirect(Url::toRoute([$redirectAction, $this->getPrimaryKeyName() => $pk]));
		}
		/* Пришёл постинг, но есть ошибки */
		if ((false === $posting) && Yii::$app->request->isAjax) {
			return $this->asJson($errors);
		}
		/* Постинга не было */
		return (Yii::$app->request->isAjax)
			?$this->renderAjax('modal/edit', compact('model'))
			:$this->render('edit', compact('model'));
	}

	/**
	 * actionEdit <==> actionUpdate
	 * @return string|Response
	 * @throws Throwable
	 */
	public function actionUpdate():string|Response {
		return $this->actionEdit();
	}

	/**
	 * @return Response
	 * @throws Throwable
	 */
	public function actionDelete():Response {
		$model = $this->getModelByPKOrFail($this->checkPrimaryKey());

		if ($model->hasAttribute('deleted')) {
			$this->applyActionScenario($model, __FUNCTION__);
			/** @noinspection PhpUndefinedFieldInspection */
			$model->setAttribute('deleted', !$model->deleted);
			$model->save();
			$model->afterDelete();
		} else {
			$model->delete();
		}
		return $this->redirect('index');
	}

	/**
	 * Аяксовый поиск в Select2
	 * @param string|null $term Значение поиска.
	 * @param string $column Атрибут для поиска. Для поиска по нескольким атрибутам, указываем их через запятую.
	 * @return string[][]
	 * @throws Throwable
	 */
	public function actionAjaxSearch(?string $term, string $column = 'name'):array {
		$out = [
			'results' => [
				'id' => '',
				'text' => ''
			]
		];
		if (null !== $term) {
			$tableName = $this->model::tableName();
			$columnsString = implode(",", preg_filter('/^/', "{$tableName}.", array_map(static fn($value) => trim($value), array_filter(explode(',', $column), static fn($value) => !empty(trim($value))))));
			$textFields = 'CONCAT('.$columnsString.')';//CONCAT выполняет также приведение типов в postgresql
			$query = $this->model::find()
				->select(["{$tableName}.{$this->getPrimaryKeyName()}", "{$textFields} as text"])
				->where([match (Yii::$app->db->driverName) {
					'pgsql' => 'ilike',
					default => 'like'
				}, $textFields, "%$term%", false])
				->distinct();

			if (method_exists($query, 'active')) {
				$query->active();
			}

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
		if (null === $this->modelSearchClass) {
			throw new InvalidConfigException('Не установлено свойство $modelSearchClass');
		}
		if (!method_exists($this->modelSearchClass, 'search')) {
			throw new InvalidConfigException("Класс {$this->modelSearchClass} должен иметь метод search()");
		}
		return new $this->modelSearchClass();
	}

	/**
	 * Генерирует меню для доступа ко всем контроллерам по указанному пути
	 * @param string $alias
	 * @return array
	 * @throws Throwable
	 * @throws UnknownClassException
	 * @deprecated since 1.0.8
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
				&& (null !== $model = VendorControllerHelper::LoadControllerClassFromFile($file->getRealPath(), null, [self::class]))
				&& $model->enablePrototypeMenu) {
				$items[] = [
					'label' => $model->id,
					'url' => [$model->link($model->defaultAction)],
					'model' => $model,
				];
			}
		}
		return $items;
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
	 * @param mixed $pk
	 * @return ActiveRecordInterface
	 * @throws NotFoundHttpException
	 */
	protected function getModelByPKOrFail(mixed $pk):ActiveRecordInterface {
		return $this->model::findOne($pk)?:throw new NotFoundHttpException();
	}

	/**
	 * @param bool $required Ключ обязателен, если не найден - то выдать исключение, иначе null
	 * @return mixed
	 * @throws BadRequestHttpException
	 */
	protected function checkPrimaryKey(bool $required = true):mixed {
		if (null === $pkValue = ArrayHelper::getValue($this->request->queryParams, $this->getPrimaryKeyName())) {
			if ($required) {
				throw new BadRequestHttpException(
					Yii::t('yii', 'Missing required parameters: {params}', ['params' => $this->getPrimaryKeyName()])
				);
			}
			return null;
		}
		return $pkValue;
	}

	/**
	 * @param string $title
	 * @return string
	 * @throws BadRequestHttpException
	 */
	public function initViewTitle(string $title):string {
		if ((null === $this->modelClass) || (null === $pk = $this->checkPrimaryKey(false)) || (null === $model = $this->model::findOne($pk))) return $title;
		return preg_replace_callback("/\{(\w+)}/", static fn(array $matches) => ArrayHelper::getValue($model, $matches[1], '%undefined%'), $title);
	}

	/**
	 * Метод пытается сконфигурировать набор колонок для грида в индексном шаблоне по умолчанию.
	 * По порядку проверяются:
	 * - метод gridColumns() в модели,
	 * - метод gridColumns() в контроллере,
	 * - конфигурация Yii::$app->components->default_controller->models->{Model::class}->gridColumns
	 *
	 * и если ничего из этого не существует, то происходит fallback на отображение всех колонок as is.
	 *
	 * @param null|ActiveRecordInterface $model
	 * @return array
	 * @throws Exception
	 */
	public function configureGridColumns(?ActiveRecordInterface $model = null):array {
		if (null === $model) $model = $this->model;
		if (method_exists($model, 'gridColumns') && $result = $model->gridColumns()) {
			return $result;
		}
		if (method_exists($this, 'gridColumns') && $result = $this->gridColumns()) {
			return $result;
		}
		$result = ArrayHelper::getValue(
			Yii::$app->components,
			'default_controller.models.'.$model::class.'.gridColumns',
			array_merge($this->getDefaultActionColumn(), array_keys($model->attributes))
		);

		return (is_callable($result))
			?$result()
			:$result;
	}

	/**
	 * Получение настроек ActionColumn по умолчанию
	 * @return array[]
	 */
	public function getDefaultActionColumn():array {
		$content = sprintf("%s%s%s",
			$this->isActionDisabled(['actionUpdate', 'actionEdit'])?'':'{update}',
			$this->isActionDisabled(['actionView'])?'':'{view}',
			$this->isActionDisabled(['actionDelete'])?'':'{delete}'
		);
		return '' === $content?[]:[
			[
				'class' => ActionColumn::class,
				'template' => '<div class="btn-group">'.$content.'</div>',
				'dropdown' => true,
			]
		];
	}

	/**
	 *
	 * @param string[] $action
	 * @return bool
	 */
	private function isActionDisabled(array $action):bool {
		return ArrayHelper::isSubset($action, $this->disabledActions, true);
	}

	/**
	 * Applies the scenario to the model, if defined
	 * @param ActiveRecordInterface $model
	 * @param string $methodName
	 * @return void
	 * @throws Exception
	 */
	private function applyActionScenario(ActiveRecordInterface $model, string $methodName):void {
		if (null !== $scenario = ArrayHelper::getValue($this->scenarios, $methodName)) {
			$model->setScenario($scenario);
		}
	}

}
