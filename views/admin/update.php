<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $model humhub\modules\rest\models\ApiUser */
?>
<div class="panel panel-default">
    <div class="panel-heading"><?php echo Yii::t('base', '<strong>Update</strong> API User'); ?></div>
    <div class="panel-body">
        <p />

        <?php $form = ActiveForm::begin(); ?>
        <?php echo $hForm->render($form); ?>
        <?php ActiveForm::end(); ?>

    </div>
</div>
