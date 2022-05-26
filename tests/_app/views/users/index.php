<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var UsersSearch $searchModel
 * @var Users $model
 * @var string $modelName
 * @var ControllerTrait $controller
 * @var ActiveDataProvider $dataProvider
 */

use app\models\Users;
use app\models\UsersSearch;
use kartik\grid\ActionColumn;
use kartik\grid\GridView;
use pozitronik\traits\traits\ControllerTrait;
use pozitronik\widgets\BadgeWidget;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\View;

$id = "{$modelName}-index-grid";
?>

<?= GridView::widget([
	'id' => $id,
	'dataProvider' => $dataProvider,
	'filterModel' => $searchModel,
	'filterOnFocusOut' => false,
	'panel' => [
		'heading' => false,
	],
	'panelBeforeTemplate' => '{optionsBtn}{newRecord}{toolbarContainer}{before}<div class="clearfix"></div>',
	'summary' => null,
	'showOnEmpty' => true,
	'export' => false,
	'resizableColumns' => true,
	'responsive' => true,
	'columns' => [
		[
			'class' => ActionColumn::class,
			'template' => '<div class="btn-group">{view}{edit}{delete}</div>',
			'buttons' => [
				'edit' => static fn(string $url, $model) => Html::a('<i class="fas fa-edit"></i>', $url, [
					'class' => 'btn btn-sm btn-outline-primary',
					'data' => ['trigger' => 'hover', 'toggle' => 'tooltip', 'placement' => 'top', 'original-title' => 'Редактирование']
				]),
				'view' => static fn(string $url, $model) => Html::a('<i class="fas fa-eye"></i>', $url, [
					'class' => 'btn btn-sm btn-outline-primary',
					'data' => [
						'trigger' => 'hover',
						'toggle' => 'tooltip',
						'placement' => 'top',
						'original-title' => 'Просмотр'
					]
				]),
				'delete' => static fn(string $url, $model) => Html::a('<i class="fa fa-trash"></i>', $url, [
					'class' => ['btn btn-sm btn-outline-primary'],
					'data' => [
						'method' => "post",
						'confirm' => 'Вы действительно хотите удалить данную запись?',
						'trigger' => 'hover',
						'toggle' => 'tooltip',
						'placement' => 'top',
						'original-title' => 'Удалить'
					]
				]),
			],
		],
		[
			'attribute' => 'id',
			'options' => [
				'style' => 'width:36px'
			]
		],
		[
			'attribute' => 'name',
			'value' => static fn(Users $model):string => BadgeWidget::widget([
				'items' => $model,
				'subItem' => 'username',
			]),
			'format' => 'raw',
		],
	]
]) ?>