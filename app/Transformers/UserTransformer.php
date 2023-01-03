<?php

namespace App\Transformers;

use App\Models\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{

    /**
     * @param User $user
     * @return array
     */
    public function transform(User $user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'deviceId' => $user->device_id,
            'profileImage' => $user->profile_image,
            'dateOfBirth' => $user->date_of_birth,
            'timezone' => $user->timezone,
            'registrationType' => $user->registration_type,
            'start_week' => $user->start_week,
            'use_device_timezoon' => $user->use_device_timezoon,
            'device_notification' => $user->device_notification,
            'email_reminder' => $user->email_reminder
        ];
    }

}
