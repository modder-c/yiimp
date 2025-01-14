<?php
/* @var $this SiteController */
/* @var $model LoginForm */
/* @var $loginform CActiveForm  */
 
//$this->pageTitle=Yii::app()->name . ' - Login';
?>
<h1>Login</h1>

<p>Please fill out the following form with your login credentials:</p>

<div class="form">
<?php $loginform=$this->beginWidget('CActiveForm', array(
		'id'=>'login-form',
		'enableClientValidation'=>true,
		'clientOptions'=>array(
				'validateOnSubmit'=>true,
		),
)); ?>
 
    <p class="note">Fields with <span class="required">*</span> are required.</p>
 
    <div class="row">
        <?php echo $loginform->labelEx($model,'username'); ?>
        <?php echo $loginform->textField($model,'username'); ?>
        <?php echo $loginform->error($model,'username'); ?>
    </div>
 
    <div class="row">
        <?php echo $loginform->labelEx($model,'password'); ?>
        <?php echo $loginform->passwordField($model,'password'); ?>
        <?php echo $loginform->error($model,'password'); ?>

    </div>
 
    <div class="row rememberMe">
        <?php echo $loginform->checkBox($model,'rememberMe'); ?>
        <?php echo $loginform->label($model,'rememberMe'); ?>
        <?php echo $loginform->error($model,'rememberMe'); ?>
    </div>
 
    <div class="row buttons">
        <?php echo CHtml::submitButton('Login'); ?>
    </div>
 
<?php $this->endWidget(); ?>
</div><!-- form -->
