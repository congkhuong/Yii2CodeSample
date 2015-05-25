<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginFrontend;
use app\models\User;
use app\models\ContactForm;
use app\models\ResetPassword;
use app\models\ValidateUser;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        $announcements = \app\models\Announcement::find()
            ->where('dateofexpiry >= :today and is_published = 1', [':today' => date("Y/m/d")])
            ->orderBy('id desc')
            ->limit(5)
            ->all();
        return $this->render('index', [
            'announcements' =>  $announcements,
		]);
    }

    public function actionRegister()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new User();
        $pData = Yii::$app->request->post();
		if(count($pData)) {
			$albma = \app\models\Albmas::find()
				->where(['NOMBOR_GAJI' => $pData['User']['no_gaji']])
				->one();
                
			$user = User::find()
				->where(['no_gaji' => $pData['User']['no_gaji']])
				->one();
			if(!$albma) {
				\Yii::$app->getSession()->setFlash('error', "This No. gaji does not exist!");
			} else if($user) {
				\Yii::$app->getSession()->setFlash('error', "This No. gaji already register. If you are realy owner of this No. gaji, please contact with administrator to resolve this problem.");
				//\Yii::$app->getSession()->setFlash('error', "This No. gaji already register!");
			} else if($albma->STATUS==0) {
				\Yii::$app->getSession()->setFlash('error', "No Gaji anda tidak aktif!");
			} else {
				$pData['User']['name'] = $albma->NAMA? $albma->NAMA: $albma->NOMBOR_GAJI;
                $emel = \app\models\Emel::find()
                    ->where(['NOMBOR_GAJI' => $pData['User']['no_gaji']])
                    ->one();
                if(isset($emel['EMEL']) && $emel['EMEL'] !== null && $emel['EMEL'] !=='') {
                    $pData['User']['email'] = $emel->EMEL;
                }
                $telefon = \app\models\Telefon::find()
                            ->where([ 'NOMBOR_GAJI' => $pData['User']['no_gaji'] ])
                            ->one();
				//$pData['User']['usertype']!=1 &&  
				if ($model->load($pData)) {
                    $no_pusat = $albma->KOD_PUSAT;
                    if (substr($no_pusat, 0, 1)=='0') {
                        $no_pusat = ltrim($no_pusat,"0");
                    } 
                    $model->no_pusat = $no_pusat;
					$no_kp = 'NO_K/P';
					$model->no_kp = $albma->{$no_kp};
					$model->setPassword($model->password);
					$model->password_date = date("Y-m-d H:i:s");
					$model->dateapplication = date("Y-m-d H:i:s");
					//$model->usertype = $model::getMemberKey();
					$model->usertype = $model::ROLE_MEMBSER;
					//$model->status = $model::getUnactiveKey();
					$model->status = $model::STATUS_UNACTIVE;
                    if ($telefon) {
                        $model->telefon = $telefon->TELEFON;
                    }
					if($model->save()) {
						// Add notification
						\app\models\Log::log([
							'model'	=> 'User',
							'model_id'	=> $model->id,
							'creator_id'	=> $model->id,
							'url'	=>	'/admin/users/view?id='.$model->id.'&name='.$model->name,
							'title'	=> "Have new account registerred.",
							'message'	=> "New account have No Gaji is ".$model->no_gaji." (".$model->name.") registerred.",
						]);
						//$group = \app\models\Group::findOne($model::getDefaultGroup());
						//$model->link('groups', $group);
                        if(!isset($emel['EMEL']) || $emel['EMEL'] == null && $emel['EMEL'] ==''){
                            \Yii::$app->getSession()->setFlash('noemel', "Email of this 'No. gaji' is not existing, please contact Admin for further login instruction!");
                        }
                        \Yii::$app->getSession()->setFlash('success', "Register successfull, please wait when administrator check and approve for your account!");
						return $this->redirect(['index']);
					} else {
						//$message = "Please contact admin to solve your problem!";
						$message = "";
						foreach ($model->errors as $errorfileds) {
							foreach ($errorfileds as $k=>$v) {
								$message = $message."<br/>".$v;
							}
						}
						\Yii::$app->getSession()->setFlash('error', $message);
					}
				}
			}
        }
		
		return $this->render('register', [
			'registerModel' => $model,
		]);
    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginFrontend();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $user = User::findOne(Yii::$app->user->getId());
            $user->lastlogin = date("Y-m-d H:i:s");
            $user->save();
            return $this->goBack();
        } else {
			//\Yii::$app->getSession()->setFlash('error', "This NO_GAJI not exist or password not true! Please try again.");
            return $this->render('login', [
                'loginModel' => $model,
            ]);
        }
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    public function actionAbout()
    {
		$about = \app\models\Staticpage::find()
			->where(['alias' => 'about-us'])
			->one();
        return $this->render('about', [
			'about'	=>	$about
		]);
    }
    /**
    *   Return to view to reset password
    *
    */
    public function actionResetpassword($rs_password_token)
    {
        $model = new ResetPassword();
        $user = \app\models\User::find()
            ->where(['rs_password_token' => $rs_password_token])
            ->one();
        if (count($user) == 0) {
            return $this->render('error', [
                'name' => 'Token',
                'message' => 'The token is invalid'
            ]);
        }
        return $this->render('resetpassword', [
            'user' => $user,
            'model' => $model
        ]);
    }

    /**
    *   Change password here
    */
    public function actionPostpassword()
    {
        $pData = Yii::$app->request->post();

        $model = new ResetPassword();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $user = \app\models\User::find()
            ->where(['email' => $pData['ResetPassword']['email']])
            ->one();
            if ($user) {
                $user->rs_password_token = '';
                $user->password_date = date("Y-m-d H:i:s");
                //$user->password = sha1($pData['ResetPassword']['password']);
                $user->password = \Yii::$app->getSecurity()->generatePasswordHash($pData['ResetPassword']['password']);
                $user->save(); 
				return $this->redirect('/site/resetpasswordcomplete');
            } else {
                \Yii::$app->getSession()->setFlash('errorResetPass', "This token is invalid.");
                return $this->redirect('/site/resetpassword?rs_password_token='.Yii::$app->request->post()['ResetPassword']['rs_password_token']);
            }
        } else {
            return $this->redirect('/site/resetpassword?rs_password_token='.Yii::$app->request->post()['ResetPassword']['rs_password_token']);
        }
    }
	
    /**
    *   User change password
    */
    public function actionValidateuser()
    {
        $model = new ValidateUser();
		$iData = Yii::$app->request->post();
        if ($model->load($iData)) {
			//$validateU = $model::validateUser($iData['ValidateUser']['email_or_no_gaji']);
			$validateU = User::find()
				->where(['email' => $iData['ValidateUser']['email_or_no_gaji']])
				->orWhere(['no_gaji' => $iData['ValidateUser']['email_or_no_gaji']])
				->one();
			if($validateU) {
				$validateU->rs_password_token = md5(uniqid());
				//$model->password = \app\models\User::DEFAULT_PASS;
				$validateU->validate_password = false;
				$validateU->save();
				\Yii::$app->mailer->compose('reset-password-email', ['rs_password_token' => $validateU->rs_password_token, 'name' => $validateU->name])
                 ->setFrom('somebody@domain.com')
                 ->setTo($validateU->email)
                 ->setSubject('Reset your password')
                 ->send();
				 return $this->redirect('/site/validateusercomplete');
			} else {
                \Yii::$app->getSession()->setFlash('error', "This email | no_gaji don't exit in system");
			}
		}
		return $this->render('validateuser', [
			'model' => $model
		]);
	}
	
    /**
    *   Validate user complete
    */
    public function actionValidateusercomplete()
    {
		return $this->render('validateusercomplete');
	}
    /**
    *   Validate user complete
    */
    public function actionResetpasswordcomplete()
    {
		return $this->render('resetpasswordcomplete');
	}
	
    public function actionErrornofile() {
		return $this->render('errornofile');
	}
	
    public function actionErrornofileajax() {
		 $this->layout = 'ajax';
		return $this->render('errornofile');
	}
}