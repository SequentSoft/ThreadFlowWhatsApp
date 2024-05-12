<?php

namespace SequentSoft\ThreadFlowWhatsApp\Messages\Incoming\Traits;

use SequentSoft\ThreadFlow\Chat\Participant;

trait CreatesParticipantFromDataTrait
{
    public static function createParticipantFromData(array $data): Participant
    {
        $participant = new Participant($data['contact']['wa_id']);

        return $participant
            ->setFirstName($data['contact']['profile']['name'] ?? '');
    }
}
