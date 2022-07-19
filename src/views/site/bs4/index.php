<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var Model $searchModel
 * @var ActiveRecordInterface $model
 * @var string $modelName
 * @var ControllerTrait|DefaultController $controller
 * @var ActiveDataProvider $dataProvider
 * @var bool $hasCreateAction
 */

use cusodede\web\default_controller\models\DefaultController;
use kartik\base\AssetBundle;
use kartik\grid\GridView;
use pozitronik\grid_config\GridConfig;
use pozitronik\helpers\Utils;
use pozitronik\traits\traits\ControllerTrait;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecordInterface;
use yii\helpers\Html;
use yii\web\View;

AssetBundle::register($this);

$id = "{$modelName}-index-grid";
?>

<?= GridConfig::widget([
	'id' => $id,
	'grid' => GridView::begin([
		'id' => $id,
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'filterOnFocusOut' => false,
		'panel' => [
			'heading' => false,
		],
		'replaceTags' =>
			[
				'{totalCount}' => ($dataProvider->totalCount > 0)?Utils::pluralForm($dataProvider->totalCount, ['запись', 'записи', 'записей']):"Нет записей",
				'{newRecord}' => Html::a('Новая запись', $controller->link('create'), ['class' => 'btn btn-success'])
			],
		'panelBeforeTemplate' => ($hasCreateAction?'':'{newRecord}').'{toolbarContainer}<div class="clearfix"></div>',
		'summary' => null,
		'showOnEmpty' => true,
		'export' => false,
		'resizableColumns' => true,
		'responsive' => true,
		'columns' => $controller->configureGridColumns($model),
	])
]) ?>
