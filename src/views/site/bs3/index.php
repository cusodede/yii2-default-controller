<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var Model $searchModel
 * @var Model $model
 * @var string $modelName
 * @var ControllerTrait $controller
 * @var ActiveDataProvider $dataProvider
 */

use kartik\base\AssetBundle;
use kartik\grid\ActionColumn;
use kartik\grid\GridView;
use pozitronik\grid_config\GridConfig;
use pozitronik\helpers\Utils;
use pozitronik\traits\traits\ControllerTrait;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\View;

AssetBundle::register($this);

$id = "{$modelName}-index-grid";
?>
	<h1>BS3</h1>
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
		'replaceTags' => [
			'{totalCount}' => ($dataProvider->totalCount > 0)?Utils::pluralForm($dataProvider->totalCount, ['запись', 'записи', 'записей']):"Нет записей",
			'{newRecord}' => Html::a('Новая запись', $controller::to('create'), ['class' => 'btn btn-success']),
		],
		'panelBeforeTemplate' => '{newRecord}{toolbarContainer}<div class="clearfix"></div>',
		'summary' => null,
		'showOnEmpty' => true,
		'export' => false,
		'resizableColumns' => true,
		'responsive' => true,
		'columns' => array_merge([
			[
				'class' => ActionColumn::class,
				'template' => '<div class="btn-group">{update}{view}{delete}</div>',
				'dropdown' => true,
			]
		], array_keys($model->attributes)),
	])
]) ?>