<?php
namespace bricksasp\rbac\models;

use bricksasp\rbac\components\Configs;
use bricksasp\rbac\components\UserStatus;
use Yii;
use bricksasp\helpers\Tools;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use bricksasp\rbac\models\redis\Token;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 *
 * @property UserProfile $profile
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 10;

    public $token_type = null;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return Configs::instance()->userTable;
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['status', 'in', 'range' => [UserStatus::ACTIVE, UserStatus::INACTIVE]],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => UserStatus::ACTIVE]);
    }

    /**
     * token登录验证
     * @param  string $token 
     * @param  int $type  区分 access-token | X-Token
     * @param  int $token_type  区分 请求身份
     * @return user        model
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $map['token'] = $token;
        $map['entrance'] = Yii::$app->devicedetect->isMobile() || Yii::$app->devicedetect->isTablet() ? Token::TOKEN_ENTRANCE_PM : Token::TOKEN_ENTRANCE_PC;
        if ( $type ) $map['type'] = $type;
        $t = Token::find()->where($map)->one();
        if (!$t || !$type && $t->duration < time()) return null;
        $identity = new User();
        $identity->id = $t->uid;
        $identity->token_type = $t->type; //token类型 区分 前后台登录
        return $identity;
    }

    /**
     * 
     * 生成 token
     * @param  int  $uid  
     * @param  int $type 登录类型 1:前台用户 2:后台用户
     * @param  integer $time [description]
     * @return [type]        [description]
     */
    public static function generateApiToken($uid, $type=Token::TOKEN_TYPE_FRONTEND)
    {
        $map['entrance'] = Yii::$app->devicedetect->isMobile() || Yii::$app->devicedetect->isTablet() ? Token::TOKEN_ENTRANCE_PM : Token::TOKEN_ENTRANCE_PC;
        $map['uid'] = $uid;
        $map['type'] = $type;
        $model = Token::find()->where($map)->one();
        $tstr = Yii::$app->security->generateRandomString();
        $dtime = time() + Token::TOKEN_DURATION;
        if ($model) {
            $model->duration = $dtime;
            $model->token = $tstr;
        }else{
            $model = new Token();
            $map['token'] = $tstr;
            $map['duration'] = $dtime;
            $model->load($map,'');
        }
        
        return $model->save() ? $tstr : false;
    }

    /**
     * 销毁token
     */
    public static function destroyApiToken($token)
    {
        return Token::deleteAll(['token'=>$token]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => UserStatus::ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
                'password_reset_token' => $token,
                'status' => UserStatus::ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        $parts = explode('_', $token);
        $timestamp = (int) end($parts);
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    public static function getDb()
    {
        return Configs::userDb();
    }
}
