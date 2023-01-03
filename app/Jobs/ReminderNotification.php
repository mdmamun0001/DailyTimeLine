<?php

namespace App\Jobs;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use App\Models\Task;
use App\Models\User;

class ReminderNotification extends Job {




    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

       $tasks = new Task();
        $tasks->remindered()->chunk(200, function ($tasks) {

                  $push_notification_key = config('notification.push_notification_key');

                  $url = config('notification.push_notification_url');

                   $post = new Client([
                       'headers' => [
                           'Content-Type' => 'application/json',
                           'Accept' => 'application/json',
                           'Authorization' => 'key=' . $push_notification_key
                       ],
                   ]);

                  $tasks = $tasks->groupBy('user_id');


                  foreach ( $tasks as $user_id => $all_task ) {

                     $task_user = Task::with('user')->where('user_id',$user_id)->first();

                     $device_token = unserialize($task_user->user->device_id);

                      $registration_ids = array();
                      foreach ( $device_token as $token ) {
                          if( $token ) {
                              array_push($registration_ids, $token );
                          }
                      }

                      $message = '';
                      foreach ( $all_task as $task ) {

                          $message = $message . 'Up comming task '. $task->name . ' today at ' . convertTimeToUSERzone($task->due_date, $task_user->user->timezone) . '  ';

                      }

                      $data = [
                          "registration_ids" => $registration_ids,
                          "notification" => [
                              "title" => 'ClockDo Reminder' ,
                              "body" => $message,
                          ]
                      ];
                      $dataString = json_encode($data);

                      $result = $post->request('POST', $url, [
                          'body' => $dataString
                      ]);

                      Log::info(print_r($result->getBody()->getContents(), true));
                 }
              });

    }

}
