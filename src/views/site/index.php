<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var Model $searchModel
 * @var string $modelName
 * @var ControllerTrait $controller
 * @var ActiveDataProvider $dataProvider
 */

use app\assets\GridHelperAsset;
use app\assets\ModalHelperAsset;
use app\components\grid\widgets\toolbar_filter_widget\ToolbarFilterWidget;
use app\components\helpers\Html;
use app\components\helpers\TemporaryHelper;
use kartik\grid\ActionColumn;
use kartik\grid\GridView;
use pozitronik\grid_config\GridConfig;
use pozitronik\helpers\Utils;
use pozitronik\traits\traits\ControllerTrait;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\web\JsExpression;
use yii\web\View;

ModalHelperAsset::register($this);
GridHelperAsset::register($this);

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
		'replaceTags' => [
			'{optionsBtn}' => ToolbarFilterWidget::widget(['content' => '{options}']),
			'{totalCount}' => ($dataProvider->totalCount > 0)?Utils::pluralForm($dataProvider->totalCount, ['запись', 'записи', 'записей']):"Нет записей",
			'{newRecord}' => ToolbarFilterWidget::widget([
				'label' => ($dataProvider->totalCount > 0)?Utils::pluralForm($dataProvider->totalCount, ['запись', 'записи', 'записей']):"Нет записей",
				'content' => Html::link('Новая запись', $controller::to('create'), ['class' => 'btn btn-success'])
			]),
			'{filterBtn}' => ToolbarFilterWidget::widget(['content' => Html::button("<i class='fa fa-filter'></i>", ['onclick' => new JsExpression('setFakeGridFilter("#'.$id.'")'), 'class' => 'btn btn-info'])]),
		],
		'toolbar' => [
			'{filterBtn}'
		],
		'panelBeforeTemplate' => '{optionsBtn}{newRecord}{toolbarContainer}{before}<div class="clearfix"></div>',
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
		], TemporaryHelper::GuessDataProviderColumns($dataProvider)),
	])
]) ?>