<?php

namespace app\models;

//use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
//use yii\base\Security;
//use yii\web\IdentityInterface;
//use app\models\gii\Users as DbUser;
use app\models\Group;
use app\models\Emel;
use app\models\Pusat;
use app\models\Announcement;

class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    const STATUS_UNACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETED = 2;
    const ROLE_ADMIN = 1;
    const ROLE_MEMBSER = 2;
    const DEFAULT_PASS = '1qazxsw@';
    public static $arrStatus = ['Unactive', 'Active', 'Delete'];
    public static $arrRoles = ['Not yet assign', 'Admin', 'Member'];
    public static $arrLocked = ['No', 'Yes'];
    public $old_password;
    public $validate_password = true;
    public $new_password;
    public $re_new_password;
    public $loginUrl = 'admin/users/login';
    public static function tableName()
    {
        return 'users';
    }
	
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * Finds user by name
     *
     * @param  string      $name
     * @return static|null
     */
    public static function findByName($name)
    {
        return static::findOne(['name' => $name, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by no_gaji
     *
     * @param  string      $name
     * @return static|null
     */
    public static function findByGaji($no_gaji)
    {
        return static::findOne(['no_gaji' => $no_gaji, 'status' => self::STATUS_ACTIVE]);
    }
	
    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->usertype;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        //return $this->password === sha1($password);
        return \Yii::$app->getSecurity()->validatePassword($password, $this->password);
    }
    
    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = \Yii::$app->getSecurity()->generatePasswordHash($password);
        //$this->password = sha1($password);
    }
    
    public static function isUserAdmin($name)
    {
        if (static::findOne(['name' => $name, 'usertype' => self::ROLE_ADMIN])){
             return true;
        } else {
             return false;
        }
    }
    
    /**
     * Creates a new user
     *
     * @param  array       $attributes the attributes given by field => value
     * @return static|null the newly created model, or null on failure
     */
    public static function create($attributes)
    {
        /** @var User $user */
        //$user = new static();
        $user = new User();
        $user->setAttributes($attributes);
        $user->setPassword($attributes['password']);
        if(!$user->authKey)  $user->authKey = self::DEFAULT_USER;
        if ($user->save()) {
            return $user;
        } else {
            return null;
        }
    }
    /*
    public function behaviors()
    {
        return [
            'datetime' => [
                'class' => 'yii\behaviors\DatetimeBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['dateapplication', 'date_approved'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['date_approved'],
                ],
            ],
        ];
    }
    */
    
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_UNACTIVE],
            ['status', 'in', 'range' => array_keys(self::$arrStatus)],
            //['role', 'default', 'value' => self::ROLE_USER],
            //['role', 'in', 'range' => [self::ROLE_USER]],
            ['name', 'filter', 'filter' => 'trim'],
			['password', 'string', 'min' => 6, 'max' => 255],
			//['password', 'passwordalphanumeric'],
			['password', 'match', 'pattern'=>'/(?=.*[@!#\$\^%&*()+=\-\[\]\\\';,\.\/\{\}\|\":<>\? ]+?).*[^_\W]+?.*/', 'message' => 'Password contain alphanumeric characters and at least one special character.'],
            [['no_gaji', 'name', 'password'], 'required'],
            //[['old_password', 'new_password', 're_new_password'], 'required'],
            [['name', 'email'], 'unique'],
            ['no_gaji', 'unique'],
            ['name', 'string', 'min' => 2, 'max' => 255],
            [['name', 'no_gaji', 'justification', 'password', 'no_pusat', 'status', 'position', 'no_kp', 'locked', 'usertype', 'dateapplication', 'group', 'rs_password_token', 'last_update'], 'safe'],
        ];
    }
	// Check password with alphanumeric validation
	public function passwordalphanumeric($attribute_name,$params) {
		if(!empty($this->password)) {
			if (!preg_match('~^[a-z0-9]*[0-9][a-z0-9]*$~i',$this->password)) {
				$this->addError($attribute_name,'Please enter password with digits');
			}
		}
	}

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'date_approved' => 'Date Approved',
            'no_gaji' => 'No. gaji',
            'justification' => 'Justification',
            'password' => 'Katalaluan',
            'old_password' => 'Old katalaluan',
            'new_password'  =>  'New katalaluan',
            're_new_password' => 'Re new katalaluan',
            'password_date' => 'Katalaluan Date',
            'no_pusat' => 'Number of pusat',
            'status' => 'Status',
            'position' => 'Position',
            'group' =>  'Group',
            'no_kp' =>  'Number of kp',
            'locked'    =>  'Locked',
            'usertype'  =>  'User Type',
            'lastlogin' =>  'Last login',
            'lastlogin_ip' =>  'Last login ip',
            'dateapplication' =>  'Date application',
            'last_update' =>  'Last updated',
        ];
    }
    
    public function getGroups()
    {
        return $this->hasMany(Group::className(), ['id' => 'group_id'])->viaTable('user_group', ['user_id' => 'id']);
    }
    
    public function getCreatedGroup()
    {
        return $this->hasOne(Group::className(), ['createdby' => 'id']);	// hasMany
    }
	
    public function getPusat()
    {
        return $this->hasOne(Pusat::className(), ['code' => 'no_pusat']);	// hasMany
    }
	
    public function getCreatedAnnouncement()
    {
        return $this->hasOne(Announcement::className(), ['creator' => 'id']);	// hasMany
    }
    
    public function getAlbmas()
    {
        return $this->hasOne(Albmas::className(), ['NOMBOR_GAJI' => 'no_gaji']);	// hasMany
    }
	
    public function getEmel()
    {
        return $this->hasOne(Emel::className(), ['NOMBOR_GAJI' => 'no_gaji']);	// hasMany
    }
	
    public static function getAllGroups() {
        return Group::find()->select(['id','name'])->all();
    }
	
    public static function getMemberKey() {
		return array_search('Member',self::$arrRoles);
	}
	
    public static function getUnactiveKey() {
		return array_search('Unactive',self::$arrStatus);
	}
	
    public function behaviors()
    {
        return [
            'datetime' => [
                'class' => 'yii\behaviors\DatetimeBehavior',
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['dateapplication', 'last_update'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['last_update'],
                ],
            ],
        ];
    }
    
    public function getFolderAssigns()
    {
        return $this->hasMany(Assign::className(), ['des_id' => 'id'])
                    ->where(['source_model'=>'folder', 'des_model'=>'user']);
    }
    
    public function getFileAssigns()
    {
        return $this->hasMany(Assign::className(), ['des_id' => 'id'])
                    ->where(['source_model'=>'file', 'des_model'=>'user']);
    }
}