<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/* @var $this \humhub\components\View */

namespace humhub\modules\rest\definitions;

use humhub\components\ActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\file\models\File;

/**
 * Class FileDefinitions
 *
 * @package humhub\modules\rest\definitions
 */
class FileDefinitions
{
    public static function getFiles(ActiveRecord $record)
    {
        if ($record instanceof Content) {
            $record = $record->getPolymorphicRelation();
        }

        return array_map(function ($v) {
            return static::getFile($v);
        }, $record->fileManager->findAll());
    }


    public static function getFile(File $file)
    {
        return [
            'id' => $file->id,
            'guid' => $file->guid,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'file_name' => $file->file_name,
            'url' => $file->getUrl([], true),
        ];
    }
}
