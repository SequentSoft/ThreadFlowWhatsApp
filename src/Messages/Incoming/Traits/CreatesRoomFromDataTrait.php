<?php

namespace SequentSoft\ThreadFlowWhatsApp\Messages\Incoming\Traits;

use SequentSoft\ThreadFlow\Chat\Room;

trait CreatesRoomFromDataTrait
{
    public static function createRoomFromData(array $data): Room
    {
        $room = new Room($data['contact']['wa_id']);

        return $room
            ->setName($data['contact']['profile']['name'] ?? '')
            ->setType('personal');
    }
}
