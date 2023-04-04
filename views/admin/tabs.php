<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/**
 * @var \humhub\modules\ui\view\components\View $this
 * @var string $tab
 */

use humhub\modules\rest\models\ConfigureForm;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use humhub\widgets\Tabs;

$tabs = [
    [
        'label' => Yii::t('RestModule.base', 'General'),
        'url' => ['index'],
        'active' => StringHelper::startsWith(Url::current(), Url::to(['index'])),
    ]
];

if (ConfigureForm::getInstance()->enableJwtAuth) {
    $tabs[] = [
        'label' => Yii::t('RestModule.base', 'JWT Auth'),
        'url' => ['jwt-auth'],
        'active' => StringHelper::startsWith(Url::current(), Url::to(['jwt-auth'])),
    ];
}

if (ConfigureForm::getInstance()->enableBearerAuth) {
    $tabs[] = [
        'label' => Yii::t('RestModule.base', 'Bearer Auth'),
        'url' => ['bearer-auth'],
        'active' => StringHelper::startsWith(Url::current(), Url::to(['bearer-auth'])),
    ];
}

?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('RestModule.base', '<strong>REST</strong> Configuration'); ?></div>
    <div class="panel-body">

        <?= Tabs::widget([
            'renderTabContent' => false,
            'options' => [
                'style' => ['margin-bottom' => 0]
            ],
            'items' => $tabs,
        ]) ?>
        <br/>
        <?= $tab ?>
    </div>
</div>
