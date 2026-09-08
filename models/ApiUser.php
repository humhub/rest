<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\models;

use humhub\modules\user\models\User;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Thin wrapper around the User model used by the REST API.
 *
 * Since HumHub 1.19 the legacy `auth_mode` / `authclient_id` columns are
 * replaced by `user.user_source` (provenance) and the `user_auth` table
 * (external identity per AuthClient — managed via `POST /user/{id}/auth-client`).
 */
class ApiUser extends Model
{
    public User $user;

    /**
     * @var int User ID
     */
    public $id;

    public function init()
    {
        $this->user = new User();
    }

    /**
     * @inheritdoc
     */
    public function load($data, $formName = null)
    {
        // Back-compat alias: requests written for ≤0.11.x sent `authclient`
        // (carrying the auth-client / source name). Map it to `user_source`.
        if (isset($data['authclient'])) {
            $data['user_source'] = $data['authclient'];
            unset($data['authclient']);
        }

        $result = parent::load($data, $formName);

        return $this->user->load($data, $formName) && $result;
    }

    /**
     * @inheritdoc
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $result = parent::validate($attributeNames, $clearErrors);

        $userAttributes = ['username', 'email', 'status', 'visibility', 'language', 'tagsField', 'user_source'];

        return $this->user->validate($userAttributes, $clearErrors) && $result;
    }

    /**
     * @inheritdoc
     */
    public function hasErrors($attribute = null)
    {
        return parent::hasErrors($attribute) || $this->user->hasErrors($attribute);
    }

    /**
     * @inheritdoc
     */
    public function getErrors($attribute = null)
    {
        return ArrayHelper::merge(parent::getErrors($attribute), $this->user->getErrors($attribute));
    }

    public function save(): bool
    {
        if ($this->user->save()) {
            $this->id = $this->user->id;
            return true;
        }

        return false;
    }

    public static function findOne($condition): self
    {
        $apiUser = new self();
        $apiUser->user = User::findOne($condition);
        $apiUser->id = $apiUser->user->id;

        return $apiUser;
    }
}
