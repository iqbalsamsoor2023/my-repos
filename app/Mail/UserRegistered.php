<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public ?string $email;

    public ?string $password;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, ?string $email = null, ?string $password = null)
    {
        $this->user = $user;
        $this->email = $email ?? $user->email;
        $this->password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.user.user-registered')
            ->subject('Welcome to '.config('app.name'));
    }
}
