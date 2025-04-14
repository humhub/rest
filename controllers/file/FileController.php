<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\file;

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
        if (!$file->canRead()) {
            return $this->returnError(403, 'You cannot download this file!');
        }
        if (!file_exists($file->store->get())) {
            return $this->returnError(404, 'File doesn\'t not exist!');
        }

        $fileName = $file->file_name;
        $mimeType = FileHelper::getMimeTypeByExtension($fileName);

        $options = ['inline' => false, 'mimeType' => $mimeType];

        if (Yii::$app->getModule('file')->settings->get('useXSendfile')) {
            Yii::$app->response->xSendFile($file->store->get(), $fileName, $options);
        } else {
            Yii::$app->response->sendFile($file->store->get(), $fileName, $options);
        }
    }

}
