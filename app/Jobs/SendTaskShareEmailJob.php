<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendTaskShareEmailJob extends Job implements ShouldQueue
{
    use SerializesModels;

    protected $subject;
    protected $user;
    protected $data;

    public function __construct($subject, $user, $data)
    {
        $this->subject = $subject;
        $this->user = $user;
        $this->data = $data;
    }

    public function handle()
    {
        Mail::send('mail', $this->data, function($message) {
            $message->to($this->user->email, $this->user->name)->subject($this->subject);
        });
    }
}
