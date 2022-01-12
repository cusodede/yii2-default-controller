<?php
declare(strict_types = 1);

namespace cusodede\DefaultController;

use cusodede\DefaultController\Actions\EditableFieldAction;
use pozitronik\helpers\ControllerHelper;
use pozitronik\traits\traits\ControllerTrait;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use Yii;
use yii\base\UnknownClassException;
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

	/**
	 * Генерирует меню для доступа ко всем контроллерам по указанному пути
	 * @param string $alias
	 * @return array
	 * @throws Throwable
	 * @throws UnknownClassException
	 */
	public static function MenuItems(string $alias = "@app/controllers"):array {
		$items = [];
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Yii::getAlias($alias)), RecursiveIteratorIterator::SELF_FIRST);
		/** @var RecursiveDirectoryIterator $file */
		foreach ($files as $file) {
			/** @var self $model */
			if ($file->isFile()
				&& ('php' === $file->getExtension())
				&& (null !== $model = ControllerHelper::LoadControllerClassFromFile($file->getRealPath(), null, [self::class]))
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
}
