<?php
namespace bricksasp\rbac\controllers;

use bricksasp\rbac\components\ItemController;
use yii\rbac\Item;
use Yii;
use bricksasp\rbac\models\AuthItem;
use bricksasp\rbac\models\searchs\AuthItem as AuthItemSearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use bricksasp\rbac\components\Configs;
use bricksasp\rbac\components\Helper;

/**
 * RoleController implements the CRUD actions for AuthItem model.
 *
 * @author 649909457@qq.com
 * @since 1.0
 */
class RoleController extends ItemController
{
    /**
     * @inheritdoc
     */
    public function labels()
    {
        return[
            'Item' => 'Role',
            'Items' => 'Roles',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return Item::TYPE_ROLE;
    }
}
