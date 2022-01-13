<?php /** @noinspection PhpPossiblePolymorphicInvocationInspection */
declare(strict_types = 1);

/**
 * @var View $this
 * @var Model $model
 */

use pozitronik\widgets\BadgeWidget;
use yii\base\Model;
use yii\bootstrap\Modal;
use yii\web\View;

$modelName = $model->formName();
?>
<?php Modal::begin([
	'id' => "{$modelName}-modal-view-{$model->id}",
	'size' => Modal::SIZE_LARGE,
	'title' => BadgeWidget::widget([
		'items' => $model,
		'subItem' => 'id'
	]),
	'options' => [
		'tabindex' => false, // important for Select2 to work properly
		'class' => 'modal-dialog-large'
	]
]); ?>
<?= $this->render('../view', compact('model')) ?>
<?php Modal::end(); ?>