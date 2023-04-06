<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

return [
    'modules' => ['rest'],
    'fixtures' => [
        'default',
        'humhub\modules\rest\tests\codeception\fixtures\ActivityFixture',
        'humhub\modules\rest\tests\codeception\fixtures\CommentFixture',
        'humhub\modules\rest\tests\codeception\fixtures\ContentFixture',
        'humhub\modules\rest\tests\codeception\fixtures\FileFixture',
        'humhub\modules\rest\tests\codeception\fixtures\LikeFixture',
        'humhub\modules\rest\tests\codeception\fixtures\NotificationFixture',
        'humhub\modules\rest\tests\codeception\fixtures\TopicFixture',
        'humhub\modules\rest\tests\codeception\fixtures\SettingFixture',
        'humhub\modules\rest\tests\codeception\fixtures\BearerAccessTokenFixture',
    ],
];



