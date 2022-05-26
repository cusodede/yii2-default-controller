<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var Users $model
 * @var ActiveForm $form
 */

use app\models\Users;
use yii\web\View;
use yii\widgets\ActiveForm;

?>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'username')->textInput(['maxlength' => 50]) ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
	</div>
</div>
