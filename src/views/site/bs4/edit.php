<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var Model $model
 */

use yii\base\Model;
use yii\bootstrap4\ActiveForm;
use yii\web\View;

?>

<?php if (Yii::$app->session->hasFlash('success')): ?>
	<div class="alert alert-success alert-dismissable">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h4>Сохранено</h4>
	</div>
<?php endif; ?>
<?php if (Yii::$app->session->hasFlash('error')): ?>
	<div class="alert alert-danger alert-dismissable">
		<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
		<h4>Ошибка!</h4>
		<?= Yii::$app->session->getFlash('error') ?>
	</div>
<?php endif; ?>

<?php $form = ActiveForm::begin(); ?>
<div class="panel">
	<div class="panel-hdr">
	</div>
	<div class="panel-container show">
		<div class="panel-content">
			<?= $this->render('subviews/editPanelBody', compact('model', 'form')) ?>
		</div>
		<div class="panel-content">
			<?= $form->errorSummary($model) ?>
			<?= $this->render('subviews/editPanelFooter', compact('model', 'form')) ?>
			<div class="clearfix"></div>
		</div>
	</div>

</div>
<?php ActiveForm::end(); ?>
