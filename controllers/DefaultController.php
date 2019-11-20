<?php
namespace bricksasp\rbac\controllers;

use yii\web\Controller;

class DefaultController extends Controller {

	public function actions() {
		return [
			'error' => [
				'class' => \bricksasp\base\actions\ErrorAction::className(),
			],
			'api-docs' => [
				'class' => 'genxoft\swagger\ViewAction',
				'apiJsonUrl' => \yii\helpers\Url::to(['api-json'], true),
			],
			'api-json' => [
				'class' => 'genxoft\swagger\JsonAction',
				'dirs' => [
					dirname(__DIR__),
				],
			],
		];
	}

	public function actionIndex() {
		return ['error'];
	}
}
