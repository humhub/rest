<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;

/**
 * Class CfileDefinitions
 *
 * @package humhub\modules\rest\definitions
 */
class CfileDefinitions
{
    public static function getFolderShort(Folder $folder)
    {
        return [
            'id' => $folder->id,
            'title' => $folder->title,
            'description' => $folder->description,
        ];
    }

    public static function getFolder(Folder $folder)
    {
        return [
            'id' => $folder->id,
            'title' => $folder->title,
            'description' => $folder->description,
            'parent_folder_id' => $folder->parent_folder_id,
            'type' => $folder->type,
            'created_at' => $folder->content->created_at,
            'created_by' => UserDefinitions::getUserShort($folder->getOwner()),
            'content' => ContentDefinitions::getContent($folder->content),
        ];
    }

    public static function getFile(File $file)
    {
        return [
            'id' => $file->id,
            'description' => $file->description,
            'parent_folder' => static::getFolderShort($file->parentFolder),
            'created_at' => $file->content->created_at,
            'created_by' => UserDefinitions::getUserShort($file->getOwner()),
            'content' => ContentDefinitions::getContent($file->content),
        ];
    }

}