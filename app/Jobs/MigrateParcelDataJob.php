<?php

namespace App\Jobs;

use App\Models\CompaniesLogo;
use App\Models\Parcel;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MigrateParcelDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    protected $residence_id;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->onQueue('migrateParcelDataQueue');
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $value = $this->data;
        if (isset($value->courier_company_id)) {
            $courier = CompaniesLogo::where('name', $value->courier_company_id)->first();
            if (! isset($courier)) {
                $courierCompany = CompaniesLogo::firstOrCreate(
                    ['name' => $value->courier_company_id],
                    [
                        'modes' => 'Local',
                        'category' => 'Courier',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]
                );
                $courier_id = $courierCompany->id;
            } else {
                $courier_id = $courier->id;
            }
        }

        $mmb2_unit = Unit::withTrashed()->where('unit_number', $value->unit)->where('home_id', $value->home_id)->first();
        $mmb2_user = User::withTrashed()->where('email', $value->email)->first();

        $parcel = Parcel::withoutEvents(function () use ($mmb2_unit, $mmb2_user, $value, $courier_id) {
            return Parcel::create([
                'unit_id' => $mmb2_unit->id,
                'courier_id' => isset($courier_id) ? $courier_id : null,
                'receiver_id' => $mmb2_user->id ?? null,
                'receiver_name' => $value->receiver_name,
                'pickup_person_contact_no' => $value->contact_no,
                'pickup_person_name' => $value->full_name,
                'qr_code' => $value->code,
                'parcel_generated_no' => $value->parcel_id,
                'tracking_no' => $value->tracking_no,
                'description' => $value->description,
                'status' => $value->status,
                'pickup_type' => $value->pickup_type,
                'pickup_time' => $value->pickup_at == '0000-00-00 00:00:00' ? null : $value->pickup_at,
                'created_at' => $value->created_at,
                'updated_at' => $value->updated_at,
            ]);
        });
    }
}
