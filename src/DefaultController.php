<?php
declare(strict_types = 1);

namespace cusodede\DefaultController;

use cusodede\DefaultController\Actions\EditableFieldAction;
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
use yii\base\UnknownPropertyException;
use yii\db\ActiveRecord;
use yii\filters\AjaxFilter;
use yii\filters\ContentNegotiator;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class DefaultController
 * Все контроллеры и все вью плюс-минус одинаковые, поэтому можно сэкономить на прототипировании
 * @property string $modelClass Модель, обслуживаемая контроллером
 * @property string $modelSearchClass Поисковая модель, обслуживаемая контроллером
 * @property bool $enablePrototypeMenu Включать ли контроллер в меню списка прототипов
 *
 * @property-read ActiveRecord $searchModel
 * @property-read ActiveRecord|ActiveRecordTrait $model
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
		return '@cusodede/DefaultController/views/site';
	}

	/**
	 * @return ActiveRecord
	 * @throws UnknownPropertyException
	 */
	public function getModel():ActiveRecord {
		return null !== $this->modelClass
			?new $this->modelClass()
			:throw new UnknownPropertyException('Не установлено свойство $modelClass');
	}

	/**
	 * @return ActiveRecord
	 * @throws UnknownPropertyException
	 */
	public function getSearchModel():ActiveRecord {
		return null !== $this->modelSearchClass
			?new $this->modelSearchClass()
			:throw new UnknownPropertyException('Не установлено свойство $modelSearchClass');
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
		if (null === $model = $this->model::findOne($id)) {
			throw new NotFoundHttpException();
		}
		return Yii::$app->request->isAjax
			?$this->renderAjax('modal/view', compact('model'))
			:$this->render('view', compact('model'));
	}

	/**
	 * @param int $id
	 * @return string|Response
	 * @throws Throwable
	 */
	public function actionEdit(int $id) {
		if (null === $model = $this->model::findOne($id)) {
			throw new NotFoundHttpException();
		}

		/** @var ActiveRecordTrait $model */
		if (static::isAjaxValidationRequest()) {
			return $this->asJson($model->validateModelFromPost());
		}

		$errors = [];
		$posting = $model->updateModelFromPost($errors);

		return match (true) {
			/* Модель была успешно прогружена */
			true === $posting => $this->redirect('index'),
			/* Пришёл постинг, но есть ошибки */
			(false === $posting) && Yii::$app->request->isAjax => $this->asJson($errors),
			/* Постинга не было */
			Yii::$app->request->isAjax => $this->renderAjax('modal/edit', compact('model')),
			default => $this->render('edit', compact('model')),
		};
	}

	/**
	 * @return string|Response
	 * @throws Throwable
	 */
	public function actionCreate() {
		$model = $this->model;
		if (static::isAjaxValidationRequest()) {
			return $this->asJson($model->validateModelFromPost());
		}

		$errors = [];
		$posting = $model->createModelFromPost($errors);/* switch тут нельзя использовать из-за его нестрогости */

		return match (true) {
			/* Модель была успешно прогружена */
			true === $posting => $this->redirect('index'),
			/* Пришёл постинг, но есть ошибки */
			(false === $posting) && Yii::$app->request->isAjax => $this->asJson($errors),
			/* Постинга не было */
			Yii::$app->request->isAjax => $this->renderAjax('modal/create', compact('model')),
			default => $this->render('create', compact('model')),
		};
	}

	/**
	 * @param int $id
	 * @return Response
	 * @throws Throwable
	 */
	public function actionDelete(int $id):Response {
		if (null === $model = $this->model::findOne($id)) {
			throw new NotFoundHttpException();
		}
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
	 * @throws ForbiddenHttpException
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
			$data = $this->model::find()
				->select(["{$tableName}.{$this->primaryColumnName}", "{$textFields} as text"])
				->where(['like', "{$tableName}.{$column}", "%$term%", false])
				->active()
				->distinct()
				->scope()
				->asArray()
				->all();
			$out['results'] = array_values($data);
		}
		return $out;
	}

	/**
	 * @return string|Response
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws UnknownClassException
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function actionIndex() {
		$params = Yii::$app->request->queryParams;
		$searchModel = $this->searchModel;
		$viewParams = [
			'searchModel' => $searchModel,
			'dataProvider' => $searchModel->search($params),
			'controller' => $this,
			'modelName' => $this->model->formName(),
			'model' => $this->model,
		];

		if (Yii::$app->request->isAjax) {
			return $this->viewExists($this->viewPath.'modal/index')  /*если модальной вьюхи для индекса не найдено - редирект*/
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
}
