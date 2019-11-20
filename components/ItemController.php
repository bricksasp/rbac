<?php
namespace bricksasp\rbac\components;

use Yii;
use bricksasp\rbac\models\AuthItem;
use bricksasp\rbac\models\searchs\AuthItem as AuthItemSearch;
use yii\web\NotFoundHttpException;
use yii\base\NotSupportedException;
use yii\filters\VerbFilter;
use yii\rbac\Item;
use bricksasp\base\BaseController;
use bricksasp\helpers\Tools;

/**
 * AuthItemController implements the CRUD actions for AuthItem model.
 *
 * @property integer $type
 * @property array $labels
 * 
 * @author 649909457@qq.com
 * @since 1.0
 */
class ItemController extends BaseController
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
                    'assign' => ['post'],
                    'remove' => ['post'],
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
        $searchModel = new AuthItemSearch(['type' => $this->type]);
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        $menus = [];
        $i=0;
        foreach ($dataProvider->getModels() as $k => $v) {
            $menus[$i]['description'] = $v->description;
            $menus[$i]['name'] = $k;
            $menus[$i]['rule_name'] = $v->ruleName;
            $menus[$i]['data'] = $v->data;
            /*$menus[$i]['routes'] = MenuHelper::getAssignedMenuByRole($k, null, function ($menu)
            {
                $data = json_decode($menu['data'],true);
                $children = array_filter($menu['children']);
                if ($children) $item['children'] = $children;

                $item['path'] = $menu['route'] ?? '';
                if (!empty($data['component']))     $item['component'] = $data['component'];

                if (!empty($data['hidden']))        $item['hidden'] = $data['hidden'];
                if (!empty($data['alwaysShow']))    $item['alwaysShow'] = $data['alwaysShow'];
                if (!empty($data['redirect']))      $item['redirect'] = $data['redirect'];
                if (!empty($data['meta']))          $item['meta'] = $data['meta'];
                return $item;
            });*/
            $i++;
        }
        return $this->success($menus);
    }

    /**
     * Displays a single AuthItem model.
     * @param  string $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $data = $this->format($model->getItems());
        return $this->success($data);
    }

    /**
     * Creates a new AuthItem model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new AuthItem(null);
        $model->type = $this->type;
        if ($model->load(Yii::$app->getRequest()->post(),'') && $model->save()) {
            return $this->success();
        }
        return $this->fail();
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
        if ($model->load(Yii::$app->getRequest()->post(),'') && $model->save()) {
            return $this->success();
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
     * Assign items
     * @param string $id
     * @return array
     */
    public function actionAssign($id)
    {
        $items = Yii::$app->getRequest()->post('items', []);
        $model = $this->findModel($id);
        $success = $model->addChildren($items);
        $data = $this->format($model->getItems());

        return $model && $success ? $this->success($data) : $this->fail('授权失败');
    }

    /**
     * Assign or remove items
     * @param string $id
     * @return array
     */
    public function actionRemove($id)
    {
        $items = Yii::$app->getRequest()->post('items', []);
        $model = $this->findModel($id);
        $success = $model->removeChildren($items);
        $data = $this->format($model->getItems());
        return $model && $success ? $this->success($data) : $this->fail('授权失败');
    }

    protected function format($items)
    {
        $res = [];
        foreach ($items['available'] as $k => $v) {
            $res['available'][$v][] = $k;
        }
        foreach ($items['assigned'] as $k => $v) {
            $res['assigned'][$v][] = $k;
        }
        return $res;
    }

    /**
     * @inheritdoc
     */
    public function getViewPath()
    {
        return $this->module->getViewPath() . DIRECTORY_SEPARATOR . 'item';
    }

    /**
     * Label use in view
     * @throws NotSupportedException
     */
    public function labels()
    {
        throw new NotSupportedException(get_class($this) . ' does not support labels().');
    }

    /**
     * Type of Auth Item.
     * @return integer
     */
    public function getType()
    {
        
    }

    /**
     * Finds the AuthItem model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return AuthItem the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $auth = Configs::authManager();
        $item = $this->type === Item::TYPE_ROLE ? $auth->getRole($id) : $auth->getPermission($id);
        if ($item) {
            return new AuthItem($item);
        } else {
            Tools::exceptionBreak('The requested page does not exist.');
        }
    }
}
