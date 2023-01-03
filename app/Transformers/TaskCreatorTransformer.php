<?php

namespace App\Transformers;

use App\Models\User;
use League\Fractal\TransformerAbstract;

class TaskCreatorTransformer extends TransformerAbstract
{

    /**
     * @param User $user
     * @return array
     */
    public function transform(User $user)
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'profileImage' => $user->profile_image
        ];
    }

}
