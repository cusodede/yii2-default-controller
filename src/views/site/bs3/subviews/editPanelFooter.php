<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var ActiveRecordInterface $model
 * @var ActiveForm|string $form
 */

use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;
use yii\db\ActiveRecordInterface;
use yii\web\View;

?>

<?= Html::submitButton('Сохранить', [
		'class' => $model->isNewRecord?'btn btn-success float-right':'btn btn-primary float-right',
		'form' => is_object($form)?$form->id:$form
	]
) ?>
