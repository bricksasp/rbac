<?php
namespace bricksasp\rbac\models\redis;

use Yii;

/**
 * This is the model class for collection "Token".
 */
class Token extends \yii\redis\ActiveRecord
{
    const TOKEN_DURATION = 7200;    // 有效时间
    const TOKEN_TYPE_FRONTEND = 1;  // 前台用户
    const TOKEN_TYPE_BACKEND = 2;   // 后台用户
    const TOKEN_TYPE_ACCESS = 3;    // 访问对应用户数据标识
    const IDENTITY_LOOK = 1;        // 数据查看身份
    const IDENTITY_CURD = 2;        // 数据操作身份

    const IDENTITY_AUTHORIZE = 3;   // 数据授权身份

    const TOKEN_ENTRANCE_PC = 1;    // pc端入口
    const TOKEN_ENTRANCE_PM = 2;    // 移动端入口

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            'id',
            'uid',
            'token',
            'type',
            'duration',
            'entrance',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'token', 'type', 'duration'], 'required'],
            ['type', 'in', 'range' => [self::TOKEN_TYPE_FRONTEND, self::TOKEN_TYPE_BACKEND, self::TOKEN_TYPE_ACCESS]],
            [['entrance'], 'default', 'value' => self::TOKEN_ENTRANCE_PM]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'uid' => 'uid',
            'token' => 'token',
            'type' => 'type',
            'duration' => 'duration',
        ];
    }

}
