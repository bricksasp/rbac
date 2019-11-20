<?php
namespace bricksasp\rbac\controllers;

use Yii;
use bricksasp\rbac\models\Menu;
use bricksasp\rbac\models\searchs\Menu as MenuSearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use bricksasp\rbac\components\Helper;
use bricksasp\base\BaseController;
use bricksasp\helpers\Tools;

/**
 * MenuController implements the CRUD actions for Menu model.
 *
 * @author 649909457@qq.com
 * @since 1.0
 */
class MenuController extends BaseController
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
     * Lists all Menu models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new MenuSearch;
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        return $this->success($dataProvider->getModels());
    }

    /**
     * Displays a single Menu model.
     * @param  integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->success($this->findModel($id));
    }

    /**
     * Creates a new Menu model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Menu;

        if ($model->load(Yii::$app->request->post(),'') && $model->save()) {
            Helper::invalidate();
            return $this->success();
        } 
        return $this->fail($model->errors);
    }

    /**
     * Updates an existing Menu model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param  integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->menuParent) {
            $model->parent_name = $model->menuParent->name;
        }
        if ($model->load(Yii::$app->request->post(),'') && $model->save()) {
            Helper::invalidate();
            return $this->success();
        } 
        return $this->fail($model->errors);
    }

    /**
     * Deletes an existing Menu model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param  integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Helper::invalidate();

        return $this->success();
    }

    /**
     * Finds the Menu model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param  integer $id
     * @return Menu the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Menu::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
