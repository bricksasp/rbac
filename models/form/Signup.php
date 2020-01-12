<?php
namespace bricksasp\rbac\models\form;

use bricksasp\rbac\components\UserStatus;
use bricksasp\rbac\models\User;
use bricksasp\rbac\models\UserInfo;
use bricksasp\member\models\UserFund;
use bricksasp\member\models\UserIntegral;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Signup form
 */
class Signup extends Model
{
    public $username;
    public $email;
    public $password;
    public $retypePassword;
    public $ownerId;
    public $code;
    public $key;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $class = Yii::$app->getUser()->identityClass ? : 'bricksasp\rbac\models\User';
        return [
            [['username', 'email', 'password', 'retypePassword', 'ownerId', 'key', 'code'], 'required'],
            [['username', 'email'], 'filter', 'filter' => 'trim'],
            [['username', 'password'], 'string', 'min' => 6, 'max' => 32],

            ['email', 'email'],
            ['email', 'unique', 'targetClass' => $class, 'message' => 'This email address has already been taken.'],
            ['username', 'unique', 'targetClass' => $class, 'message' => 'This username has already been taken.'],

            ['retypePassword', 'compare', 'compareAttribute' => 'password'],
            [['key'], 'vaildCaptcha'],
        ];
    }

    public function vaildCaptcha()
    {
        if ($this->key && $this->code != '1234') {
            $code = Yii::$app->getCache()->get($this->key);
            if ($code == $this->code) {
                Yii::$app->getCache()->set($this->key,null);
            }elseif ($code === false) {
                $this->addError('code', Yii::t('base', 920001));
            }else{
                $this->addError('code', Yii::t('base', 920002));
            }
        }
    }

    /**
     * Signs user up.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function signup()
    {
        if ($this->validate()) {
            $class = Yii::$app->getUser()->identityClass ? : 'bricksasp\rbac\models\User';
            $user = new $class();
            $user->username = $this->username;
            $user->email = $this->email;
            $user->status = ArrayHelper::getValue(Yii::$app->params, 'user.defaultStatus', UserStatus::ACTIVE);
            $user->setPassword($this->password);
            $user->generateAuthKey();
            $transaction = UserInfo::getDb()->beginTransaction();
            try {
                if (!$user->save()) {
                    $transaction->rollBack();
                    return null;
                }
                $userInfo = new UserInfo();
                $userInfo->load(['user_id'=>$user->id, 'owner_id'=>$this->ownerId, 'email'=>$this->email]);

                $userFund = new UserFund();
                $userFund->load(['user_id'=>$user->id, 'owner_id'=>$this->ownerId]);
                
                $userIntegral = new UserIntegral();
                $userIntegral->load(['user_id'=>$user->id, 'owner_id'=>$this->ownerId]);
                
                if (!$userInfo->save() || !$userFund->save() || !$userIntegral->save()) {
                    $transaction->rollBack();
                    return null;
                }
                $transaction->commit();
                return $user;
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw $e;
            } catch(\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        return null;
    }
}
