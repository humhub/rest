<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\tasks\models\Task;

/**
 * Class TaskDefinitions
 *
 * @package humhub\modules\rest\definitions
 */
class TaskDefinitions
{
    public static function getTask(Task $task)
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'start_datetime' => $task->start_datetime,
            'end_datetime' => $task->end_datetime,
            'scheduling' => $task->scheduling,
            'all_day' => $task->all_day,
            'reminders' => $task->taskReminder,
            'max_users' => $task->max_users,
            'color' => $task->color,
            'task_list' => static::getTaskList($task->list),
            'cal_mode' => $task->cal_mode,
            'review' => $task->review,
            'request_sent' => $task->request_sent,
            'time_zone' => $task->time_zone,
            'created_at' => $task->created_at,
            'created_by' => UserDefinitions::getUserShort($task->getOwner()),
            'content' => ContentDefinitions::getContent($task->content),
            'percentage' => $task->getPercent(),
            'checklist' => $task->items,
            'assigned_users' => static::getUsers($task->taskAssignedUsers),
            'responsible_users' => static::getUsers($task->taskResponsibleUsers)
        ];
    }

    public static function getTaskShort(Task $task)
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'start_datetime' => $task->start_datetime,
            'end_datetime' => $task->end_datetime,
        ];
    }

    public static function getTaskList($list)
    {
        if (! $list) {
            return null;
        }
        return [
            'id' => $list->id,
            'name' => $list->name,
            'contentcontainer_id' => $list->contentcontainer_id,
            'parent_id' => $list->parent_id,
            'color' => $list->color,
            'settings' => static::getListSettings($list->addition)
        ];
    }

    private static function getListSettings($addition)
    {
        return [
            'hide_if_completed' => $addition->hide_if_completed,
            'sort_order' => $addition->sort_order
        ];
    }

    private static function getUsers($users)
    {
        $result = [];

        foreach ($users as $user) {
            $result[] = UserDefinitions::getUserShort($user);
        }

        return $result;
    }
}