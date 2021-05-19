<?php
return [
    ['class' => 'humhub\modules\comment\notifications\NewComment', 'user_id' => 1, 'seen' => 0, 'source_class' => 'humhub\modules\comment\models\Comment', 'source_pk' => 1, 'space_id' => 1, 'created_at' => '2021-05-14 19:16:00', 'originator_user_id' => 3, 'module' => 'comment', 'group_key' => 'humhub\modules\post\models\Post-1'],
    ['class' => 'humhub\modules\comment\notifications\NewComment', 'user_id' => 2, 'seen' => 0, 'source_class' => 'humhub\modules\comment\models\Comment', 'source_pk' => 1, 'space_id' => 1, 'created_at' => '2021-05-14 19:16:00', 'originator_user_id' => 3, 'module' => 'comment', 'group_key' => 'humhub\modules\post\models\Post-1'],
    ['class' => 'humhub\modules\like\notifications\NewLike', 'user_id' => 1, 'seen' => 0, 'source_class' => 'humhub\modules\like\models\Like', 'source_pk' => 1, 'space_id' => 1, 'created_at' => '2021-05-14 19:16:00', 'originator_user_id' => 3, 'module' => 'comment', 'group_key' => 'humhub\modules\comment\models\Comment-1'],
    ['class' => 'humhub\modules\user\notifications\Mentioned', 'user_id' => 1, 'seen' => 1, 'source_class' => 'humhub\modules\comment\models\Comment', 'source_pk' => 1, 'space_id' => 1, 'created_at' => '2021-05-14 19:16:00', 'originator_user_id' => 4, 'module' => 'comment', 'group_key' => 'humhub\modules\comment\models\Comment-1'],
];
