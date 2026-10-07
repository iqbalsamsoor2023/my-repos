<?php

namespace App\Jobs;

use App\Mail\UnitOwnerRegister;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailUnitOwnerVerification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;

    protected $model;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email, Model $model)
    {
        $this->email = $email;
        $this->model = $model;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = [
            'name' => $this->model->name,
        ];

        Mail::to($this->email)
            ->send(new UnitOwnerRegister($data));
    }
}
