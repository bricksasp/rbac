<?php
namespace bricksasp\rbac\models;

use Yii;

/**
 * This is the model class for table "bricksasp_user_info".
 *
 * @property int $user_id
 * @property int $owner_id
 * @property string $avatar
 * @property string $name
 * @property string $nickname
 * @property string $email
 * @property int $birthday
 * @property int $age
 * @property int $gender
 * @property string $last_login_ip
 * @property int $last_login_time
 * @property string $last_login_area
 * @property int $login_count
 * @property int $integration 操作积分
 * @property int $score 消费积分
 * @property int $credit 信用积分
 * @property int $created_at
 * @property int $updated_at
 */
class UserInfo extends \bricksasp\base\BaseActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_info}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'owner_id'], 'required'],
            [['user_id', 'owner_id', 'birthday', 'age', 'gender', 'last_login_time', 'login_count', 'created_at', 'updated_at'], 'integer'],
            [['avatar', 'email'], 'string', 'max' => 255],
            [['name', 'nickname'], 'string', 'max' => 32],
            [['last_login_ip', 'last_login_area'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'owner_id' => 'Owner ID',
            'avatar' => 'Avatar',
            'name' => 'Name',
            'nickname' => 'Nickname',
            'email' => 'Email',
            'birthday' => 'Birthday',
            'age' => 'Age',
            'gender' => 'Gender',
            'last_login_ip' => 'Last Login Io',
            'last_login_time' => 'Last Login Time',
            'last_login_area' => 'Last Login Area',
            'login_count' => 'Login Count',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
