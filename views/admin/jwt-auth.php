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

use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;
?>

<?php $form = ActiveForm::begin(['id' => 'configure-form', 'enableClientValidation' => false, 'enableClientScript' => false]); ?>

<?= $form->field($model, 'jwtKey'); ?>
<?= $form->field($model, 'jwtExpire'); ?>

<?= Button::save()->submit() ?>

<?php ActiveForm::end(); ?>
