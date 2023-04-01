<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/**
 * @var \humhub\modules\ui\view\components\View $this
 * @var \humhub\modules\rest\models\ConfigureForm $model
 */

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

$apiModuleOptions = $model->getApiModuleOptions();
?>

<?php $form = ActiveForm::begin(['id' => 'configure-form', 'enableClientValidation' => false, 'enableClientScript' => false]); ?>

<?= $form->field($model, 'enableBasicAuth')->checkbox(); ?>
<?= $form->field($model, 'enableJwtAuth')->checkbox(); ?>
<?= $form->field($model, 'enableBearerAuth')->checkbox(); ?>
<?= $form->field($model, 'enableQueryParamAuth')->checkbox(); ?>

<br/>

<?= $form->field($model, 'apiModules')->checkboxList($apiModuleOptions)
    ->hint(empty($apiModuleOptions) ? Yii::t('RestModule.base', 'No enabled modules found with additional REST API endpoints.') : false); ?>

<div class="form-group">
    <?= Html::submitButton(Yii::t('base', 'Save'), ['class' => 'btn btn-primary', 'data-ui-loader' => '']) ?>
</div>

<?php ActiveForm::end(); ?>
