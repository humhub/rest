<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/**
 * @var \humhub\components\View $this
 * @var \humhub\modules\rest\models\ConfigureForm $model
 */

use humhub\modules\user\widgets\UserPickerField;
use humhub\widgets\form\ActiveForm;
use yii\helpers\Html;

$apiModuleOptions = $model->getApiModuleOptions();
?>

<?php $form = ActiveForm::begin(['id' => 'configure-form', 'enableClientValidation' => false, 'enableClientScript' => false]); ?>

<?= $form->field($model, 'enableBasicAuth')->checkbox(); ?>
<?= $form->field($model, 'enableJwtAuth')->checkbox(); ?>

<?= Html::tag(
    'blockquote',
    $form->field($model, 'enabledForAllUsers')->checkbox() . $form->field($model, 'enabledUsers')->widget(UserPickerField::class),
    ['id' => 'enabledusers', 'style' => ['font-size' => 'inherit']]
) ?>

<?= $form->field($model, 'enableBearerAuth')->checkbox(); ?>
<?= $form->field($model, 'enableQueryParamAuth')->checkbox(); ?>

<br/>

<?= $form->field($model, 'apiModules')->checkboxList($apiModuleOptions)
    ->hint(empty($apiModuleOptions) ? Yii::t('RestModule.base', 'No enabled modules found with additional REST API endpoints.') : false); ?>

<div class="mb-3">
    <?= Html::submitButton(Yii::t('base', 'Save'), ['class' => 'btn btn-primary', 'data-ui-loader' => '']) ?>
</div>

<?php ActiveForm::end(); ?>

<?php

$js = <<<JS
function enabledUsers() {
    if ($('#configureform-enabledforallusers').prop('checked')) {
        $('.field-configureform-enabledusers').hide()
    } else {
        $('.field-configureform-enabledusers').show()
    }
}

function enabledUsersBlockquote() {
    if ($('#configureform-enablebasicauth').prop('checked') || $('#configureform-enablejwtauth').prop('checked')) {
        $('#enabledusers').show()

        $('#enabledusers').insertAfter($('#configureform-enablejwtauth').prop('checked') ? $('.field-configureform-enablejwtauth') : $('.field-configureform-enablebasicauth'))
    } else {
        $('#enabledusers').hide()
    }
}

function checkBearerAuth() {
    if (!$('#configureform-enablebearerauth').prop('checked')) {
        $('#configureform-enablequeryparamauth').prop('checked', false)
    }
}

function checkQueryParamBearerAuth() {
    if ($('#configureform-enablequeryparamauth').prop('checked')) {
        $('#configureform-enablebearerauth').prop('checked', true)
    }
}

enabledUsers()
enabledUsersBlockquote()

$('#configureform-enabledforallusers').change(enabledUsers)
$('#configureform-enablebasicauth').change(enabledUsersBlockquote)
$('#configureform-enablejwtauth').change(enabledUsersBlockquote)
$('#configureform-enablebearerauth').change(checkBearerAuth)
$('#configureform-enablequeryparamauth').change(checkQueryParamBearerAuth)
JS;

$this->registerJs($js);
