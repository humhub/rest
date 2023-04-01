<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/**
 * @var \humhub\modules\ui\view\components\View $this
 * @var \humhub\modules\rest\models\JwtAuthForm $model
 */

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use humhub\modules\user\widgets\UserPickerField;

?>

<?php $form = ActiveForm::begin(['id' => 'configure-form', 'enableClientValidation' => false, 'enableClientScript' => false]); ?>

<?= $form->field($model, 'jwtKey'); ?>
<?= $form->field($model, 'jwtExpire'); ?>
<?= $form->field($model, 'enabledForAllUsers')->checkbox(); ?>
<?= $form->field($model, 'enabledUsers')->widget(UserPickerField::class); ?>

<div class="form-group">
    <?= Html::submitButton(Yii::t('base', 'Save'), ['class' => 'btn btn-primary', 'data-ui-loader' => '']) ?>
</div>

<?php ActiveForm::end(); ?>

<?php

$js = <<<JS
function enabledusers() {
    if ($("#jwtauthform-enabledforallusers").prop("checked")) {
        $(".field-jwtauthform-enabledusers").hide()
    } else {
        $(".field-jwtauthform-enabledusers").show()
    }
}

enabledusers()

$("#jwtauthform-enabledforallusers").click(enabledusers)
JS;

$this->registerJs($js);
