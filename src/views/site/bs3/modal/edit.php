<?php /** @noinspection PhpPossiblePolymorphicInvocationInspection */
declare(strict_types = 1);

/**
 * @var View $this
 * @var Model $model
 */
use pozitronik\widgets\BadgeWidget;
use yii\base\Model;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Modal;
use yii\web\View;

$modelName = $model->formName();
?>
<?php
Modal::begin([
	'id' => "{$modelName}-modal-edit-{$model->id}",
	'size' => Modal::SIZE_LARGE,
	'title' => BadgeWidget::widget([
		'items' => $model,
		'subItem' => 'id'
	]),
	'footer' => $this->render('../subviews/editPanelFooter', [
		'model' => $model,
		'form' => "{$modelName}-modal-edit"
	]),//post button outside the form
	'options' => [
		'tabindex' => false, // important for Select2 to work properly
		'class' => 'modal-dialog-large'
	]
]); ?>
<?php $form = ActiveForm::begin(
	[
		'id' => "{$modelName}-modal-edit",
		'enableAjaxValidation' => true,

	])
?>
<?= $this->render('../subviews/editPanelBody', compact('model', 'form')) ?>
<?php ActiveForm::end(); ?>
<?php Modal::end(); ?>