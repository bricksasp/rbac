<?php
namespace bricksasp\rbac\controllers;

use bricksasp\rbac\components\UserStatus;
use bricksasp\rbac\models\form\ChangePassword;
use bricksasp\rbac\models\form\Login;
use bricksasp\rbac\models\form\PasswordResetRequest;
use bricksasp\rbac\models\form\ResetPassword;
use bricksasp\rbac\models\form\Signup;
use bricksasp\rbac\models\searchs\User as UserSearch;
use bricksasp\rbac\models\User;
use bricksasp\rbac\models\redis\Token;
use bricksasp\base\BaseController;
use bricksasp\helpers\Tools;
use Yii;
use yii\base\InvalidParamException;
use yii\base\UserException;
use yii\filters\VerbFilter;
use yii\mail\BaseMailer;
use yii\web\BadRequestHttpException;
use Endroid\QrCode\QrCode;
use yii\helpers\Url;
use GatewayClient\Gateway;

/**
 * User controller
 */
class UserController extends BaseController {
	private $_oldMailPath;

	/**
	 * @inheritdoc
	 */
	public function behaviors() {
		return array_merge(parent::behaviors(), [
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'delete' => ['post'],
					'logout' => ['post'],
					'activate' => ['post'],
				],
			],
		]);
	}

	/**
	 * 登录可访问 其他需授权
	 * @return array
	 */
	public function allowAction() {
		return [
			'info',
			'index',
		];
	}

	/**
	 * 免登录可访问
	 * @return array
	 */
	public function allowNoLoginAction() {
		return [
			'logout',
			'login',
			'signup',
			'captcha',
			'qrlogin',
			'qrscan',
		];
	}

    /**
     * 
     * @OA\Get(path="/user/captcha",
     *   summary="验证码  刷新验证码 例 {{url}}?key=111&refresh",
     *   tags={"全局接口"},
     *   @OA\Response(
     *     response=200,
     *     description="验证码",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *         @OA\Property(
     *           description="验证码图片url",
     *           property="url",
     *           type="string"
     *         ),
     *         @OA\Property(property="params", type="array", description="验证码参数", @OA\Items(
     *              @OA\Property(property="key", type="integer", description="验证码key"),
     *           ),
     *         )
     *       )
     *     ),
     *   ),
     * )
     */
    public function actions()
    {
        return [
            'captcha' => [
                'class' => 'bricksasp\base\actions\CaptchaAction',
                'height' => 50,
                'width' => 80,
                'minLength' => 4,
                'maxLength' => 4,
                'key' => '65e83d23146f1ee056ef2aa622b179dc',
                'fixedVerifyCode' => '1234',
            ],
        ];
    }

	/**
	 * @inheritdoc
	 */
	public function beforeAction($action) {
		if (parent::beforeAction($action)) {
			if (Yii::$app->has('mailer') && ($mailer = Yii::$app->getMailer()) instanceof BaseMailer) {
				/* @var $mailer BaseMailer */
				$this->_oldMailPath = $mailer->getViewPath();
				$mailer->setViewPath('@bricksasp/rbac/mail');
			}
			return true;
		}
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function afterAction($action, $result) {
		if ($this->_oldMailPath !== null) {
			Yii::$app->getMailer()->setViewPath($this->_oldMailPath);
		}
		return parent::afterAction($action, $result);
	}

	/**
	 * Lists all User models.
	 * @return mixed
	 */
	public function actionIndex() {
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);

		return $this->render('index', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}

	/**
	 * Displays a single User model.
	 * @param integer $id
	 * @return mixed
	 */
	public function actionView($id) {
		return $this->render('view', [
			'model' => $this->findModel($id),
		]);
	}

	/**
	 * 用户信息
	 * @return array
	 */
	public function actionInfo() {
		$info = [
			'roles' => array_keys(Yii::$app->authManager->getRolesByUser(Yii::$app->user->getId())),
			'avatar' => 'https://wpimg.wallstcn.com/69a1c46c-eb1c-4b46-8bd4-e9e686ef5251.png',
			'id' => $this->uid,
		];
		return $this->success($info);
	}

	/**
	 * Deletes an existing User model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id
	 * @return mixed
	 */
	public function actionDelete($id) {
		$this->findModel($id)->delete();

		return $this->redirect(['index']);
	}

	/**
	 * Login
     * @OA\Post(path="/user/login",
     *   summary="用户登录",
     *   tags={"全局接口"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         @OA\Property(
     *           description="用户名",
     *           property="username",
     *           type="string",
     *           default="bricksasp"
     *         ),
     *         @OA\Property(
     *           description="密码",
     *           property="password",
     *           type="string",
     *           default="111111"
     *         ),
     *         @OA\Property(
     *           description="验证码 访问地址查看验证码{{url}}/user/captcha?key=65e83d23146f1ee056ef2aa622b179dc",
     *           property="code",
     *           type="string",
     *           default="1234"
     *         ),
     *         @OA\Property(
     *           description="验证码key",
     *           property="key",
     *           type="string",
     *           default="65e83d23146f1ee056ef2aa622b179dc"
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="登录信息",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(ref="#/components/schemas/response"),
     *     @OA\JsonContent(ref="#/components/schemas/user"),
     *     @OA\Link(link="userRepositories", ref="#/components/links/UserRepositories")
     *     ),
     *   ),
     * )
	 */
	public function actionLogin() {
		$model = new Login();
		if ($model->load(Yii::$app->request->post(), '') && $model->login()) {
			$type = Yii::$app->request->post('type', 1);
			$token = User::generateApiToken(Yii::$app->getUser()->id, $type);
			return $token == false ? $this->fail(50002) : $this->success(['token' => $token]);
		};
		return $this->fail($model->errors);
	}

	/**
	 * Qrlogin
     * @OA\Get(path="/user/qrlogin",
     *   summary="二维码登录",
     *   description="移动端扫码后跳转页面",
     *   tags={"全局接口"},
     *   @OA\Response(
     *     response=200,
     *     description="",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(ref="#/components/schemas/response"),
     *     ),
     *   ),
     * )
	 */
	public function actionQrlogin() {
		$client_id = Yii::$app->request->get('client_id');
		Gateway::$registerAddress = Yii::$app->params['workerConfig']['registerAddress'] ?? '127.0.0.1:1238';
		if (Yii::$app->request->isAjax) {
			if (!$this->uid) {
				return $this->fail('请登录');
			}
			$token = User::generateApiToken($this->uid, Yii::$app->request->get('type',Token::TOKEN_TYPE_BACKEND));
			Gateway::sendToClient($client_id, $this->wsuccess(['token'=>$token],'登录成功'));
			return $this->success();
		}
		Gateway::sendToClient($client_id, $this->wsuccess('qrlogin_scan','扫码成功'));
		return $this->render('qrlogin',['client_id'=>$client_id]);
	}

	/**
	 * Qrscan
     * @OA\Get(path="/user/qrscan",
     *   summary="二维码扫码",
     *   description="pc端显示二维码",
     *   tags={"全局接口"},
     *   @OA\Response(
     *     response=200,
     *     description="",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(ref="#/components/schemas/response"),
     *     ),
     *   ),
     * )
	 */
	public function actionQrscan() {
		if ($client_id = Yii::$app->request->get('client_id')) {
			$qrCode = new QrCode(Url::to(['user/qrlogin','client_id'=>$client_id, 'type'=>Yii::$app->request->get('type',Token::TOKEN_TYPE_BACKEND)],true));

			header('Content-Type: '.$qrCode->getContentType());
			echo $qrCode->writeString();
			exit;
		}else{
			return $this->render('qrscan');
		}
	}

	/**
	 * Logout
     * @OA\Post(path="/user/logout",
     *   summary="退出登录",
     *   tags={"全局接口"},
     *   @OA\Response(
     *     description="",
     *     response=200,
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(ref="#/components/schemas/response"),
     *     ),
     *   ),
     * )
	 */
	public function actionLogout() {
		$behaviors = $this->behaviors();
        $tokenHeader = Yii::$app->request->getHeaders()->get($behaviors['authenticator']['tokenHeader']);
        if ($tokenHeader) {
        	User::destroyApiToken($tokenHeader);
        }
		Yii::$app->getUser()->logout();
		return $this->success();
	}

	/**
	 * Signup new user
     * @OA\Post(path="/user/register",
     *   summary="用户注册",
     *   tags={"全局接口"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         @OA\Property(
     *           description="用户名",
     *           property="username",
     *           type="string"
     *         ),
     *         @OA\Property(
     *           description="邮箱",
     *           property="email",
     *           type="string"
     *         ),
     *         @OA\Property(
     *           description="密码",
     *           property="password",
     *           type="string"
     *         ),
     *         @OA\Property(
     *           description="确认密码",
     *           property="retypePassword",
     *           type="string"
     *         ),
     *         @OA\Property(
     *           description="验证码 访问地址查看验证码{{url}}/user/captcha?key=65e83d23146f1ee056ef2aa622b179dc",
     *           property="code",
     *           type="string",
     *           default="1234"
     *         ),
     *         @OA\Property(
     *           description="验证码key.",
     *           property="key",
     *           type="string",
     *           default="65e83d23146f1ee056ef2aa622b179dc"
     *         ),
     *         @OA\Property(
     *           description="返回登录token 1返回",
     *           property="lognin",
     *           type="integer",
     *           default=2,
     *         ),
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="商品详情结构",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(ref="#/components/schemas/response"),
     *     ),
     *   ),
     * )
	 */
	public function actionSignup() {
		$model = new Signup();
		$params = Yii::$app->getRequest()->post();
		$params['ownerId'] = $this->ownerId;

		if ($model->load($params, '')) {
			if ($user = $model->signup()) {
				if (Yii::$app->getRequest()->post('lognin') == 1) {
					return $this->success($user->generateApiToken($user->id));
				}
				return $this->success();
			}
		}

		return $this->fail($model->errors);
	}

	/**
	 * Request reset password
	 * @return string
	 */
	public function actionRequestPasswordReset() {
		$model = new PasswordResetRequest();
		if ($model->load(Yii::$app->getRequest()->post()) && $model->validate()) {
			if ($model->sendEmail()) {
				Yii::$app->getSession()->setFlash('success', 'Check your email for further instructions.');

				return $this->goHome();
			} else {
				Yii::$app->getSession()->setFlash('error', 'Sorry, we are unable to reset password for email provided.');
			}
		}

		return $this->render('requestPasswordResetToken', [
			'model' => $model,
		]);
	}

	/**
	 * Reset password
	 * @return string
	 */
	public function actionResetPassword($token) {
		try {
			$model = new ResetPassword($token);
		} catch (InvalidParamException $e) {
			throw new BadRequestHttpException($e->getMessage());
		}

		if ($model->load(Yii::$app->getRequest()->post()) && $model->validate() && $model->resetPassword()) {
			Yii::$app->getSession()->setFlash('success', 'New password was saved.');

			return $this->goHome();
		}

		return $this->render('resetPassword', [
			'model' => $model,
		]);
	}

	/**
	 * Reset password
	 * @return string
	 */
	public function actionChangePassword() {
		$model = new ChangePassword();
		if ($model->load(Yii::$app->getRequest()->post()) && $model->change()) {
			return $this->goHome();
		}

		return $this->render('change-password', [
			'model' => $model,
		]);
	}

	/**
	 * Activate new user
	 * @param integer $id
	 * @return type
	 * @throws UserException
	 * @throws NotFoundHttpException
	 */
	public function actionActivate($id) {
		/* @var $user User */
		$user = $this->findModel($id);
		if ($user->status == UserStatus::INACTIVE) {
			$user->status = UserStatus::ACTIVE;
			if ($user->save()) {
				return $this->goHome();
			} else {
				$errors = $user->firstErrors;
				throw new UserException(reset($errors));
			}
		}
		return $this->goHome();
	}

	/**
	 * Finds the User model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * @param integer $id
	 * @return User the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id) {
		if (($model = User::findOne($id)) !== null) {
			return $model;
		} else {
			Tools::exceptionBreak('The requested page does not exist.');
		}
	}
}
