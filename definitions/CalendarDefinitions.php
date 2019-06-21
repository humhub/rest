<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;

/**
 * Class CalendarDefinitions
 *
 * @package humhub\modules\rest\definitions
 */
class CalendarDefinitions
{
    public static function getCalendarEntry(CalendarEntry $entry)
    {
        return [
            'id' => $entry->id,
            'title' => $entry->title,
            'description' => $entry->description,
            'start_datetime' => $entry->start_datetime,
            'end_datetime' => $entry->end_datetime,
            'all_day' => $entry->all_day,
            'participation_mode' => $entry->participation_mode,
            'recurring' => $entry->recur,
            'recurring_type' => $entry->recur_type,
            'recurring_interval' => $entry->recur_interval,
            'recurring_end' => $entry->recur_end,
            'color' => $entry->color,
            'allow_decline' => $entry->allow_decline,
            'allow_maybe' => $entry->allow_maybe,
            'time_zone' => $entry->time_zone,
            'participant_info' => $entry->participant_info,
            'closed' => $entry->closed,
            'max_participants' => $entry->max_participants,
            'content' => ContentDefinitions::getContent($entry->content),
            'participants' => static::getParticipantUsers($entry->getParticipants()->with('user')->all())
        ];
    }

    private static function getParticipantUsers($participants)
    {
        $result = [
            'attending' => [],
            'maybe' => [],
            'declined' => []
        ];

        foreach ($participants as $participant) {
            if ($participant->participation_state === CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED) {
                $result['attending'][] = UserDefinitions::getUserShort($participant->user);
            } elseif ($participant->participation_state === CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE){
                $result['maybe'][] = UserDefinitions::getUserShort($participant->user);
            } elseif ($participant->participation_state === CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED){
                $result['declined'][] = UserDefinitions::getUserShort($participant->user);
            }
        }

        return $result;
    }
}