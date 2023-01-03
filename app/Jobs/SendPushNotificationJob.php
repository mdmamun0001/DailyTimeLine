<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob extends Job implements ShouldQueue {
    use  InteractsWithQueue, Queueable, SerializesModels;

    private $fcm_token = [];
    private $title;
    private $information = [];

    /**
     * Create a new job instance.
     *
     * @param $fcm_token
     * @param $taskUser
     * @param $title
     * @param $message
     * @param $time
     */
    public function __construct($fcm_token, $taskUser, $title, $message, $time) {
        $this->fcm_token   = $fcm_token;
        $this->title       = $title;
        $this->information = [
            'user_name'  => $taskUser->name,
            'user_image' => $taskUser->profile_image,
            'message'    => $message,
            'sharedTime' => $time,
        ];
    }

    /**
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle() {
        $push_notification_key = config('notification.push_notification_key');
        $url = config('notification.push_notification_url');
        $data = [
            "registration_ids" => $this->fcm_token,
            "notification"     => [
                "title" => $this->title,
                "body"  => $this->information,
            ],
        ];
        $post   = new Client([
            'headers' => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'key=' . $push_notification_key,
            ],
        ]);
        $result = $post->request('POST', $url, [
            'body' => json_encode($data),
        ]);
      
    }
}
