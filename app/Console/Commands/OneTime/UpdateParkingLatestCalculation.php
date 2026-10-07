<?php

namespace App\Console\Commands\OneTime;

use App\Enums\Parking\DiscountType;
use App\Models\Calculation;
use App\Models\Parking;
use App\Models\VisitorLog;
use App\Models\VisitorParking;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateParkingLatestCalculation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:parking-calculation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update using latest calculation format Jan 2024';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $visitorParkings = VisitorParking::whereDate('created_at', '>=', '2023-12-27')->whereDate('created_at', '<', '2024-01-11'); // 2013
        $bar = $this->output->createProgressBar($visitorParkings->count());
        $bar->start();

        $visitorParkings->chunk(100, function ($visitorParkings) use ($bar) {
            foreach ($visitorParkings as $key => $visitorParking) {
                // Refer in VisitorParkingService/create
                $visitorLog = VisitorLog::find($visitorParking->visitor_log_id);
                $calculation = Calculation::where('id', $visitorParking->calculation_id)->first();

                if ($visitorParking->is_penalty) {
                    $amountToPay = $calculation->penalty;
                } else {
                    $visitorArrived = new Carbon($visitorLog->arrival_time);
                    $visitorDepart = is_null($visitorLog->leave_time) ? Carbon::now() : new Carbon($visitorLog->leave_time);
                    $durationDiffinMinutes = $visitorDepart->diffInMinutes($visitorArrived);
                    $durationInOut = $visitorDepart->diff($visitorArrived);
                    $calculationHours = ($durationInOut->d * 24) + $durationInOut->h;
                    $calculationMinutes = $durationInOut->i;
                    $calculationSeconds = $durationInOut->s;

                    $calculationValue = $calculationMinutes == 0 ? $calculationSeconds : $calculationMinutes;

                    if (isset($calculation)) {
                        $charteredDurationCarbon = new Carbon($calculation->chartered_duration);
                        $charteredDurationInMinutes = ($charteredDurationCarbon->hour * 60) + $charteredDurationCarbon->minute;
                        // 3.1 Step - Chartered
                        $amountToPay = 0;
                        $durationBalanceToCalculateInMinutes = 0;
                        $numberOfIterations = 0;

                        if ($durationDiffinMinutes >= $charteredDurationInMinutes) {
                            $chartered24HoursInMinutes = 1440;
                            $durationDiffinHours = $calculationValue > 0 ? $calculationHours + 1 : 0;
                            $durationBalanceToCalculateInMinutes = $durationDiffinHours * 60;

                            while ($durationBalanceToCalculateInMinutes >= $charteredDurationInMinutes) {
                                $durationBalanceToCalculateInMinutes -= $chartered24HoursInMinutes;
                                $numberOfIterations++;
                            }
                            $amountToPay = $numberOfIterations * $calculation->chartered_price;
                            $durationBalanceToCalculateInMinutes = $durationBalanceToCalculateInMinutes < 0 ? 0 : $durationBalanceToCalculateInMinutes;
                        } else {
                            $durationDiffinHours = $calculationValue > 0 ? $calculationHours + 1 : 0;
                            $durationBalanceToCalculateInMinutes = $durationDiffinHours * 60;
                            // 3.2 Step - Free parking
                            $freeParkingDuration = $calculation->free_parking_minutes;
                            $freeParkingHours = explode(':', $freeParkingDuration);
                            $freeParkingMinutes = intval($freeParkingHours[0]) * 60 + intval($freeParkingHours[1]);
                            $durationBalanceToCalculateInMinutes = $durationBalanceToCalculateInMinutes - $freeParkingMinutes;
                            $amountToPay = 0;
                        }
                    }

                    if ($calculation->parking->is_discount_coupon == 1 && $calculation->parking->discount_type == DiscountType::TIME->value) {
                        $discountValue = $calculation->discount_value * 60;

                        if ($durationBalanceToCalculateInMinutes > 0) {
                            $durationBalanceToCalculateInMinutes = $durationBalanceToCalculateInMinutes - $discountValue;
                        } else {
                            $durationBalanceToCalculateInMinutes = 0;
                        }
                    }

                    if ($durationBalanceToCalculateInMinutes >= 0) {
                        $durationBalanceToCalculateInHours = $durationBalanceToCalculateInMinutes / 60;
                        $calculateBalanceToPay = $durationBalanceToCalculateInHours * $calculation->rate_per_hour;

                        if ($calculation->parking->is_discount_coupon == 1 && $calculation->parking->discount_type == DiscountType::PRICE->value) {
                            $calculateBalanceToPay = $calculateBalanceToPay - (int) $calculation->discount_value;
                        }

                        $finalAmountToPay = $calculateBalanceToPay + $amountToPay;
                    } elseif ($durationBalanceToCalculateInMinutes <= 0) {
                        $finalAmountToPay = 0;
                    }

                    $visitorParking->amount_to_pay = $finalAmountToPay;
                    $visitorParking = $visitorParking->save();

                    $bar->advance();
                }
            }
        });

        $bar->finish();

        return Command::SUCCESS;
    }
}
