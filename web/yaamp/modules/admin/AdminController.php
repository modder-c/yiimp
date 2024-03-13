<?php

class AdminController extends CommonController {

	public $defaultAction='login';

	///////////////////////////////////////////////////

	public function actionDashboard()
	{
		if(!$this->admin) $this->redirect("/site/mining");
		$this->redirect("/site/common");
		//$this->render('dashboard');
	}

	///////////////////////////////////////////////////

	public function actionLogin()
	{
		$model = new LoginForm;
 
        // if it is ajax validation request
        if(isset($_POST['ajax']) && $_POST['ajax']==='login-form')
        {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
 
        // collect user input data
        if(isset($_POST['LoginForm']))
        {
            $model->attributes=$_POST['LoginForm'];
            // validate user input and redirect to the previous page if valid
             if(($model->username === YAAMP_ADMIN_USER) &&
             	($model->password === YAAMP_ADMIN_PASS) &&
             	$model->login() ) {

					$client_ip = arraySafeVal($_SERVER,'REMOTE_ADDR');
					$valid = isAdminIP($client_ip);
			
					if (arraySafeVal($_SERVER,'HTTP_X_FORWARDED_FOR','') != '') {
						debuglog("admin access attempt via IP spoofing!");
						$valid = false;
					}
			
					if ($valid)
						debuglog("admin connect from $client_ip");
					else
						debuglog("admin connect failure from $client_ip");
			
					user()->setState('yaamp_admin', $valid);
					
            		$this->redirect("/site/common");
             }
        }
        // display the login form
        $this->render('login',array('model'=>$model));
	}
	public function actionLogout()
	{
		user()->setState('yaamp_admin', false);
		$this->redirect("/site/mining");
	}

}
