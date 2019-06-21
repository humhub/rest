<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/* @var $this \humhub\components\View */

use humhub\modules\user\widgets\UserPickerField;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('RestModule.base', '<strong>API</strong> Configuration'); ?></div>
    <div class="panel-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form', 'enableClientValidation' => false, 'enableClientScript' => false]); ?>

        <?= $form->field($model, 'enabledForAllUsers')->checkbox(); ?>

        <?= $form->field($model, 'enabledUsers')->widget(UserPickerField::class); ?>
        <br />
        <?= $form->field($model, 'jwtKey'); ?>
        <?= $form->field($model, 'jwtExpire'); ?>

        <br />
        <?= $form->field($model, 'enableBasicAuth')->checkbox(); ?>

        <br />

        <div class="form-group">
            <?= Html::submitButton(Yii::t('base', 'Save'), ['class' => 'btn btn-primary', 'data-ui-loader' => '']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>

<script>
    $(document).ready(function() {
        $("#configureform-enabledforallusers").click(function() {
            if ($(this).prop("checked")) {
                $(".field-configureform-enabledusers").hide();
            } else {
                $(".field-configureform-enabledusers").show();
            }
        });
        if ($(this).prop("checked")) {
            $(".field-configureform-enabledusers").hide();
        } else {
            $(".field-configureform-enabledusers").show();
        }
    });
</script>