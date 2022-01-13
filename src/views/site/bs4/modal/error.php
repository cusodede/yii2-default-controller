<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var HttpException $exception
 *
 * Вью, отрабатывающее при ошибке после ajax-запроса, @see ErrorAction
 */

use yii\bootstrap4\Modal;
use yii\helpers\BaseHtml as Html;
use yii\web\HttpException;
use yii\web\View;

?>
<?php Modal::begin([
	'id' => "modal-error",
	'size' => Modal::SIZE_LARGE,
	'title' => "<h1 class='error-code text-primary'>".Html::encode($exception->statusCode)."</h1>",
	'footer' => false,
	'options' => [
		'tabindex' => false, // important for Select2 to work properly
		'class' => 'modal-dialog-large'
	]
]); ?>
	<div class="text-center">
		<p><?= nl2br(Html::encode($exception->getMessage())) ?></p>
		<div><i class="fa fa-spinner fa-pulse fa-3x fa-fw text-primary"></i></div>
	</div>
<?php Modal::end(); ?>