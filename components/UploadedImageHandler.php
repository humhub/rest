<?php

namespace humhub\modules\rest\components;

use Yii;
use yii\base\StaticInstanceInterface;
use yii\base\StaticInstanceTrait;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use humhub\models\forms\UploadProfileImage;
use humhub\libs\ProfileImage;

class UploadedImageHandler implements StaticInstanceInterface
{
    use StaticInstanceTrait;

    public const SUPPORTED_MIMES = [
        'image/png' => 'png',
        'image/tiff' => 'tif',
        'image/jpeg' => 'jpg',
    ];

    private function convertBase64ImgToUploadedFile($value): ?UploadedFile
    {
        if (!(is_string($value) && preg_match('/^data:image\/([^;]+);base64,/', $value))) {
            return null;
        }

        [$mime, $dataString] = explode(';base64,', $value);
        $mime = str_replace('data:', '', $mime);

        $tempName = tempnam(sys_get_temp_dir(), '');

        $tmpFile = fopen($tempName, 'wb');
        fwrite($tmpFile, base64_decode($dataString));
        fclose($tmpFile);

        if (empty(FileHelper::getExtensionsByMimeType($mime))) {
            $mime = FileHelper::getMimeType($tempName, FileHelper::$mimeMagicFile, false);
        }

        return new UploadedFile([
            'name' => sprintf(
                '%s.%s',
                Yii::$app->security->generateRandomString(13),
                ArrayHelper::getValue(
                    self::SUPPORTED_MIMES,
                    $mime,
                    ArrayHelper::getValue(FileHelper::getExtensionsByMimeType($mime), 0, 'jpg'),
                ),
            ),
            'tempName' => $tempName,
            'type' => $mime,
            'size' => filesize($tempName),
            'error' => $mime ? UPLOAD_ERR_OK : UPLOAD_ERR_NO_FILE,
        ]);
    }

    public function handle(ProfileImage $image, string $imageData): void
    {
        $uploadedFile = $this->convertBase64ImgToUploadedFile($imageData);

        if (!$uploadedFile) {
            throw new \InvalidArgumentException('Invalid Image');
        }

        $model = new UploadProfileImage(['image' => $uploadedFile]);

        if (!$model->validate()) {
            throw new \InvalidArgumentException($model->getFirstError('image'));
        }

        $image->setNew($model->image);
    }
}
