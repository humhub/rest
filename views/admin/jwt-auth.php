<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/**
 * @var \humhub\components\View $this
 * @var \humhub\modules\rest\models\JwtAuthForm $model
 */

use humhub\widgets\form\ActiveForm;
use yii\helpers\Html;

?>

<?php $form = ActiveForm::begin(['id' => 'configure-form', 'enableClientValidation' => false, 'enableClientScript' => false]); ?>

<?= $form->field($model, 'jwtKey'); ?>
<?= $form->field($model, 'jwtExpire'); ?>

<div class="mb-3">
    <?= Html::submitButton(Yii::t('base', 'Save'), ['class' => 'btn btn-primary', 'data-ui-loader' => '']) ?>
</div>

<?php ActiveForm::end(); ?>
