<?php
namespace bricksasp\rbac\controllers;

use Yii;
use bricksasp\rbac\models\BizRule;
use bricksasp\rbac\models\searchs\BizRule as BizRuleSearch;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use bricksasp\rbac\components\Helper;
use bricksasp\rbac\components\Configs;
use bricksasp\base\BaseController;
use bricksasp\helpers\Tools;

/**
 * Description of RuleController
 *
 * @author 649909457@qq.com
 * @since 1.0
 */
class RuleController extends BaseController
{

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ]
        ]);
    }

    /**
     * Lists all AuthItem models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new BizRuleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        return $this->success(array_values($dataProvider->getModels()));
    }

    /**
     * Displays a single AuthItem model.
     * @param  string $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->success($model);
    }

    /**
     * Creates a new AuthItem model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new BizRule(null);
        if ($model->load(Yii::$app->request->post(),'') && $model->save()) {
            Helper::invalidate();

            return $this->success($model);
        }
        return $this->fail('',$model);
    }

    /**
     * Updates an existing AuthItem model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param  string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post(),'') && $model->save()) {
            Helper::invalidate();

            return $this->success($model);
        }
        return $this->fail();
    }

    /**
     * Deletes an existing AuthItem model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param  string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        Configs::authManager()->remove($model->item);
        Helper::invalidate();

        return $this->success();
    }

    /**
     * Finds the AuthItem model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param  string        $id
     * @return AuthItem      the loaded model
     * @throws HttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $item = Configs::authManager()->getRule($id);
        if ($item) {
            return new BizRule($item);
        } else {
            Tools::exceptionBreak('The requested page does not exist.');
        }
    }
}
