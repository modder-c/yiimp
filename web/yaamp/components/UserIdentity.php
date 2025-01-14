<?php

class UserIdentity extends CUserIdentity
{
	public $username;
	private $_id;
	private $_fullname;

	public function __construct($user)
	{
		$this->_id = $user->id;
		$this->_fullname = $user->name;
		$this->username = $user->username;
	}

	public function getId()
	{
		return $this->_id;
	}

	public function getFullname()
	{
		return $this->_fullname;
	}

	public function authenticate()
	{
		$model=User::model()->findByAttributes(array('username'=>$this->username));
		if($model===null)
			$this->errorCode=self::ERROR_USERNAME_INVALID;
			elseif(!crypt($this->password, $model->password))
			$this->errorCode=self::ERROR_PASSWORD_INVALID;
			else
				$this->_id=$model->id;
				$this->username=$model->username;
				$this->errorCode=self::ERROR_NONE;
				return !$this->errorCode;
	}
}

