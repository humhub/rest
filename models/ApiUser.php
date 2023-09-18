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
 * Is needed because the user model should not be extended directly,
 * but we need some extra validators for auth_mode.
 */
class ApiUser extends Model
{

    public User $user;

    /**
     * @var string
     */
    public $authclient_id;

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
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['authclient_id'], 'string', 'max' => 60];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function load($data, $formName = null)
    {
        if (isset($data['authclient'])) {
            $data['auth_mode'] = $data['authclient'];
            unset($data['authclient']);
        }

        $result = parent::load($data, $formName);
        $this->user->authclient_id = $this->authclient_id;

        $this->user->scenario = User::SCENARIO_EDIT_ADMIN;
        return $this->user->load($data, $formName) && $result;
    }

    /**
     * @inheritdoc
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $result = parent::validate($attributeNames, $clearErrors);

        $userAttributes = ['username', 'email', 'status', 'visibility', 'language', 'tagsField', 'auth_mode', 'authclient_id'];

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
