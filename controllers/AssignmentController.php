<?php
namespace bricksasp\rbac\controllers;

use Yii;
use bricksasp\rbac\models\Assignment;
use bricksasp\rbac\models\searchs\Assignment as AssignmentSearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use bricksasp\base\BaseController;
use bricksasp\helpers\Tools;

/**
 * AssignmentController implements the CRUD actions for Assignment model.
 *
 * @author 649909457@qq.com
 * @since 1.0
 */
class AssignmentController extends BaseController
{
    public $userClassName;
    public $idField = 'id';
    public $usernameField = 'username';
    public $fullnameField;
    public $searchClass;
    public $extraColumns = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->userClassName === null) {
            $this->userClassName = Yii::$app->getUser()->identityClass;
            $this->userClassName = $this->userClassName ? : 'bricksasp\rbac\models\User';
        }
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'assign' => ['post'],
                    'assign' => ['post'],
                    'revoke' => ['post'],
                ],
            ]
        ]);
    }

    /**
     * Lists all Assignment models.
     * @return mixed
     */
    public function actionIndex()
    {

        if ($this->searchClass === null) {
            $searchModel = new AssignmentSearch;
            $dataProvider = $searchModel->search(Yii::$app->getRequest()->getQueryParams(), $this->userClassName, $this->usernameField);
        } else {
            $class = $this->searchClass;
            $searchModel = new $class;
            $dataProvider = $searchModel->search(Yii::$app->getRequest()->getQueryParams());
        }

        return $this->success(array_values($dataProvider->getModels()));
    }

    /**
     * Displays a single Assignment model.
     * @param  integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->success($model->getItems());
    }

    /**
     * Assign items
     * @param string $id
     * @return array
     */
    public function actionAssign($id)
    {
        $items = Yii::$app->getRequest()->post('items', []);
        $model = new Assignment($id);
        $success = $model->assign($items);
        return $model && $success ? $this->success($model->getItems()) : $this->fail();
    }

    /**
     * Assign items
     * @param string $id
     * @return array
     */
    public function actionRevoke($id)
    {
        $items = Yii::$app->getRequest()->post('items', []);
        $model = new Assignment($id);
        $success = $model->revoke($items);
        return $model && $success ? $this->success($model->getItems()) : $this->fail();
    }

    /**
     * Finds the Assignment model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param  integer $id
     * @return Assignment the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $class = $this->userClassName;
        if (($user = $class::findIdentity($id)) !== null) {
            return new Assignment($id, $user);
        } else {
            Tools::exceptionBreak('The requested page does not exist.');
        }
    }
}