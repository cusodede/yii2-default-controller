<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var Model $model
 * @var ActiveForm $form
 */

use kartik\base\Html5Input;
use yii\base\Model;
use yii\bootstrap\ActiveForm;
use yii\web\View;

?>

<?php foreach ($model->attributes() as $attribute): ?>
	<?php if ($model->isAttributeRequired($attribute)): ?>
		<div class="row">
			<div class="col-md-12">
				<?= $form->field($model, $attribute)->widget(Html5Input::class)->label($attribute) ?>
			</div>
		</div>
	<?php endif ?>
<?php endforeach; ?>
