<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var ActiveRecordInterface $model
 */

use yii\bootstrap4\Modal;
use yii\db\ActiveRecordInterface;
use yii\web\View;

$modelName = $model->formName();
?>
<?php Modal::begin([
	'id' => "{$modelName}-modal-view-{$model->getPrimaryKey(false)}",
	'size' => Modal::SIZE_LARGE,
	'title' => $this->title,
	'options' => [
		'tabindex' => false, // important for Select2 to work properly
		'class' => 'modal-dialog-large'
	]
]); ?>
<?= $this->render('../view', compact('model')) ?>
<?php Modal::end(); ?>