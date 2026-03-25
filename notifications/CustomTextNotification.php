<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\notifications;

use humhub\helpers\Html;
use humhub\modules\notification\components\BaseNotification;
use yii\helpers\Json;
use yii\helpers\Url;
use humhub\helpers\ArrayHelper;

class CustomTextNotification extends BaseNotification
{
    /**
     * @inheritdoc
     */
    public $moduleId = 'rest';

    /**
     * @inheritdoc
     */
    public $requireSource = false;

    /**
     * Ensure custom payload survives queue serialization.
     */
    public function __serialize(): array
    {
        return array_merge(parent::__serialize(), [
            'payload' => $this->payload,
        ]);
    }

    /**
     * Restore custom payload after queue deserialization.
     */
    public function __unserialize($unserializedArr): void
    {
        $payload = $unserializedArr['payload'] ?? null;
        unset($unserializedArr['payload']);

        parent::__unserialize($unserializedArr);

        if ($payload !== null) {
            $this->payload = $payload;
            $this->record->payload = $payload;
        }
    }

    public function getUrl()
    {
        $url = $this->getPayloadValue('url');

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return Url::to($url, true);
    }

    public function html()
    {
        return Html::encode($this->getPayloadValue('text'));
    }

    private function getPayloadValue(string $key): string
    {
        $payload = $this->payload;

        if (!is_array($payload) && !empty($this->record->payload)) {
            $payload = Json::decode($this->record->payload);
        }

        return ArrayHelper::getValue($payload, $key, '');
    }
}
