<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class StoreUserDeviceIdJob extends Job implements ShouldQueue
{
    use SerializesModels;

    protected $email;
    protected $deviceId;

    public function __construct($email, $deviceId)
    {
        $this->email = $email;
        $this->deviceId = $deviceId;
    }

    public function handle()
    {
        $user = User::where('email', $this->email)->first();
        if ($user) {
            $userDeviceIds = unserialize($user->device_id);
            if (!in_array($this->deviceId, $userDeviceIds)) {
                array_push($userDeviceIds, $this->deviceId);
                $user->device_id = serialize($userDeviceIds);
                $user->save();
            }
        }
    }
}
