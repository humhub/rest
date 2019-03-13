<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $model humhub\modules\rest\models\ApiUser */
?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('base', '<strong>Add</strong> API User'); ?></div>
    <div class="panel-body">
        <?= \humhub\modules\admin\widgets\UserMenu::widget(); ?>
        <p />

        <?php $form = ActiveForm::begin(); ?>
        <?= $hForm->render($form); ?>
        <?php ActiveForm::end(); ?>

    </div>
</div>
