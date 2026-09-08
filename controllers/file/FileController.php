<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\file;

use humhub\components\fs\LocalMountConfig;
use humhub\modules\file\libs\FileHelper;
use humhub\modules\file\models\File;
use humhub\modules\rest\components\BaseController;
use Yii;

class FileController extends BaseController
{
    public function actionDownload($id)
    {
        $file = File::findOne(['id' => $id]);
        if ($file === null) {
            return $this->returnError(404, 'File not found!');
        }
        if (!$file->canView()) {
            return $this->returnError(403, 'You cannot download this file!');
        }
        if (!$file->store->has()) {
            return $this->returnError(404, 'File doesn\'t not exist!');
        }

        $fileName = $file->file_name;
        $mimeType = FileHelper::getMimeTypeByExtension($fileName);

        $options = ['inline' => false, 'mimeType' => $mimeType];

        if (Yii::$app->getModule('file')->settings->get('useXSendfile')) {
            $dataMountConfig = Yii::$app->fs->getDataMountConfig();
            if ($dataMountConfig instanceof LocalMountConfig) {
                return Yii::$app->response->xSendFile(
                    Yii::getAlias($dataMountConfig->path) . DIRECTORY_SEPARATOR . $file->store->get(),
                    $fileName,
                    $options,
                );
            }

            return $this->returnError(
                403,
                'XSendfile is only supported by ' . LocalMountConfig::class . ' mounts. '
                . get_class($dataMountConfig) . ' given.',
            );
        }

        $options['fileSize'] = $file->store->fileSize();

        return Yii::$app->response->sendStreamAsFile($file->store->getContentStream(), $fileName, $options);
    }

}
