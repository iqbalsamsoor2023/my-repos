<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRegisteredViaMyFamily extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public $randomPassword;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $randomPassword)
    {
        $this->user = $user;
        $this->randomPassword = $randomPassword;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.user.user-registered-via-myfamily', [$this->user, $this->randomPassword])
            ->subject('Welcome to '.config('app.name'));
    }
}
