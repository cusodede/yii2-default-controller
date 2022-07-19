<?php /** @noinspection UsingInclusionReturnValueInspection */
declare(strict_types = 1);

use app\models\ConfigUsers;
use app\models\Users;
use cusodede\web\default_controller\models\DefaultController;
use yii\caching\DummyCache;
use yii\log\FileTarget;
use yii\web\AssetManager;
use yii\web\ErrorHandler;
use kartik\grid\Module as GridModule;

$db = require __DIR__.'/db.php';

$config = [
	'id' => 'basic',
	'basePath' => dirname(__DIR__),
	'bootstrap' => ['log'],
	'aliases' => [
		'@vendor' => './vendor',
		'@bower' => '@vendor/bower-asset',
		'@npm' => '@vendor/npm-asset',
	],
	'modules' => [
		'gridview' => [
			'class' => GridModule::class,
		],
	],
	'components' => [
		'default_controller' => [
			'class' => DefaultController::class,
			'models' => [
				ConfigUsers::class => [
					'gridColumns' => [
						'id',
						'username:text',
						'password:text'
					]
				]
			]
		],
		'request' => [
			'cookieValidationKey' => 'sosijopu',
		],
		'cache' => [
			'class' => DummyCache::class,
		],
		'user' => [
			'identityClass' => Users::class,
			'enableAutoLogin' => true,
		],
		'errorHandler' => [
			'class' => ErrorHandler::class,
			'errorAction' => 'site/error',
		],
		'log' => [
			'traceLevel' => YII_DEBUG?3:0,
			'targets' => [
				[
					'class' => FileTarget::class,
					'levels' => ['error', 'warning'],
				],
			],
		],
		'urlManager' => [
			'enablePrettyUrl' => true,
			'showScriptName' => false,
			'rules' => [
			],
		],
		'assetManager' => [
			'class' => AssetManager::class,
			'basePath' => '@app/assets'
		],
		'db' => $db
	],
	'params' => [
		'bsVersion' => '4'
	],
];

return $config;