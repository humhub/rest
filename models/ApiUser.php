<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\models;

use humhub\modules\user\models\User;

class ApiUser extends User
{
    const SCENARIO_API_REQUEST = 'apiRequest';

    /**
     * @inheritdoc
     */
    public $scenario = self::SCENARIO_API_REQUEST;

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[static::SCENARIO_API_REQUEST] = ['username', 'email', 'status', 'language', 'tagsField', 'auth_mode', 'authclient_id'];
        return $scenarios;
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

        return parent::load($data, $formName);
    }
}