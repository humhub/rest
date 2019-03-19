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
            'startDateTime' => $entry->start_datetime,
            'endDateTime' => $entry->end_datetime,
            'allDay' => $entry->all_day,
            'participationMode' => $entry->participation_mode,
            'recurring' => $entry->recur,
            'recurringType' => $entry->recur_type,
            'recurringInterval' => $entry->recur_interval,
            'recurringEnd' => $entry->recur_end,
            'color' => $entry->color,
            'allowDecline' => $entry->allow_decline,
            'allowMaybe' => $entry->allow_maybe,
            'timeZone' => $entry->time_zone,
            'participant_info' => $entry->participant_info,
            'closed' => $entry->closed,
            'maxParticipants' => $entry->max_participants,
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