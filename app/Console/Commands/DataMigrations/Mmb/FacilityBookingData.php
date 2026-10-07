<?php

namespace App\Console\Commands\DataMigrations\Mmb;

use App\Models\AmenityBooking;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FacilityBookingData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:facility-booking-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'migrate facility_bookings to amenity_bookings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mapping = [
            'Meeting' => ['category' => 'Meeting Room'],
            'ลิฟท์จอดรถอัตโนมัติ' => ['category' => 'Parking Lift'],
            'Swimming pool' => ['category' => 'Swimming Pool'],
            'Meeting Room' => ['category' => 'Meeting Room'],
            'Air Hockey' => ['category' => 'Game Room', 'sub_name' => 'Air Hockey'],
            'โต๊ะพูล' => ['category' => 'Game Room', 'sub_name' => 'โต๊ะพูล'],
            'Party Room' => ['category' => 'Party Room'],
            'ฟิตเนส' => ['category' => 'Fitness Room'],
            'สระว่ายน้ำ' => ['category' => 'Swimming Pool'],
            'โยคะ' => ['category' => 'Yoga Room'],
            'ห้องออกกำลังกาย' => ['category' => 'Fitness Room'],
            'ห้องสมุด' => ['category' => 'Library Room'],
            'สนามเด็กเล่น' => ['category' => 'Play Ground'],
            'สนามแบดมินตัน A (Badminton court)' => ['category' => 'Badminton Court', 'sub_name' => 'สนามแบดมินตัน A (Badminton court)'],
            'สนามแบดมินตัน B (Badminton court)' => ['category' => 'Badminton Court', 'sub_name' => 'สนามแบดมินตัน B (Badminton court)'],
            'สระว่ายน้ำ Swimming pool' => ['category' => 'Swimming Pool'],
            'สระว่ายน้ำ (Swimming Pool)' => ['category' => 'Swimming Pool'],
            'ฟิตเนส (Fitness)' => ['category' => 'Fitness Room'],
            'Swimming Pool' => ['category' => 'Swimming Pool'],
            'Fitness' => ['category' => 'Fitness Room'],
            'ห้องฟิตเนส' => ['category' => 'Fitness Room'],
            'สวน(บริเวณสะพานข้ามเฟส)' => ['category' => 'Park (Green Area)'],
            'อาคารสโมสร' => ['category' => 'Clubhouse'],
            'สวนสาธารณะ' => ['category' => 'Park (Green Area)'],
            'ห้องสโมสร' => ['category' => 'Clubhouse'],
            'Fitness Center' => ['category' => 'Fitness Room'],
            'ห้องประชุม' => ['category' => 'Meeting Room'],
            'Tennis court-Marina' => ['category' => 'Tennis Court', 'sub_name' => 'Tennis court-Marina'],
            'Squash' => ['category' => 'Tennis Court', 'sub_name' => 'Squash'],
            'Tennis court-Tridhos' => ['category' => 'Tennis Court', 'sub_name' => 'Tennis court-Tridhos'],
            'ํYoga-5th floor-Large Room' => ['category' => 'Yoga Room', 'sub_name' => 'Yoga-5th floor-Large Room'],
            'ํYoga-5th floor-Small Room' => ['category' => 'Yoga Room', 'sub_name' => 'ํYoga-5th floor-Small Room'],
            'Yoga-2th floor' => ['category' => 'Yoga Room', 'sub_name' => 'Yoga-2th floor'],
            'Steam room - Gentleman' => ['category' => 'Steam Room', 'sub_name' => 'Steam room - Gentleman'],
            'Steam room - Lady' => ['category' => 'Steam Room', 'sub_name' => 'Steam room - Lady'],
            'Meeting Room - 3rd floor' => ['category' => 'Meeting Room', 'sub_name' => 'Meeting Room - 3rd floor'],
            'Tridhos Lobby' => ['category' => 'Lobby'],
            'Other Facilities' => ['category' => 'Fitness Room'],
            'Fitness room' => ['category' => 'Fitness Room'],
            'Meeting Room' => ['category' => 'Meeting Room'],
            'อาคารสโมสร (Clubhouse)' => ['category' => 'Clubhouse'],
            'ฟิตเนส (Fitness Room)' => ['category' => 'Fitness Room'],
            'สวนสาธารณะ (Public Park)' => ['category' => 'Park (Green Area)'],
            'เลนส์วิ่ง (Jogging Lane)' => ['category' => 'Jogging Track'],
            'คลับเฮาส์ (Clubhouse)' => ['category' => 'Clubhouse'],
            'ห้องสมุด (Library)' => ['category' => 'Library Room'],
            'ร้านอาหาร (Restaurant)' => ['category' => 'Restaurant'],
            'สนามฟุตบอล (Football field)' => ['category' => 'Football Field'],
            'สปา (Spa Club)' => ['category' => 'Spa'],
            'พื้นที่จอดรถ' => ['category' => 'Parking'],
            'สวน' => ['category' => 'Park (Green Area)'],
            'Co-Working Space' => ['category' => 'Co-Working Space'],
            'สนามเทนนิส' => ['category' => 'Tennis Court'],
            'ห้องฟิตเนต' => ['category' => 'Fitness Room'],
            'สนามบาสเกตบอล' => ['category' => 'Basketball Court'],
            'สโมสรหมู่บ้าน' => ['category' => 'Clubhouse'],
            'MOVIE STUDIO' => ['category' => 'Theater Room'],
            'COOKING STUDIO' => ['category' => 'Co-Kitchen Space'],
            'SUANA' => ['category' => 'Sauna'],
            'MULTI STUDIO' => ['category' => 'Fitness Room'],
            'ห้องประชุมและอาคารนิติ' => ['category' => 'Meeting Room'],
            'สระว่ายน้ำและห้องน้ำ' => ['category' => 'Swimming Pool'],
            'Swimming Pool' => ['category' => 'Swimming Pool'],
            'ฟิตเนท' => ['category' => 'Fitness Room'],
            'ห้อง Co-working' => ['category' => 'Co-Working Space'],
            'ห้องเด็กเล่น' => ['category' => 'Kids Room'],
            'ห้องเกมส์ (Game Room)' => ['category' => 'Game Room'],
            'ห้องดูหนัง (Home Theater)' => ['category' => 'Theater Room'],
            'ห้องทำงาน (Co Working Room)' => ['category' => 'Co-Working Space'],
            'ห้องโยคะ (Yoga Room)' => ['category' => 'Yoga Room'],
            'ห้องฟิตเนส(fitness)' => ['category' => 'Fitness Room'],
            'ห้องคิสรูม(Kids room)' => ['category' => 'Kids Room'],
            'สระว่ายน้ำ(swimming pool)' => ['category' => 'Swimming Pool'],
            'ห้องซาวน่า(sauna room)' => ['category' => 'Sauna'],
            'Fitness' => ['category' => 'Fitness Room'],
            'Pools' => ['category' => 'Swimming Pool'],
            'ขอใช้สนามเด็กเล่นทำกิจกรรมชั่วคราว' => ['category' => 'Play Ground'],
            'ห้องซาวน่า' => ['category' => 'Sauna'],
            'GYM' => ['category' => 'Fitness Room'],
            'POOL' => ['category' => 'Swimming Pool'],
            'MEETING ROOM' => ['category' => 'Meeting Room'],
            'Meeting room LKB 128' => ['category' => 'Meeting Room', 'sub_name' => 'Meeting room LKB 128'],
            'Meeting room 1st floor @ LKB 130' => ['category' => 'Meeting Room', 'sub_name' => 'Meeting room 1st floor @ LKB 130'],
            'Meeting room 2nd floor @ LKB 130' => ['category' => 'Meeting Room', 'sub_name' => 'Meeting room 2nd floor @ LKB 130'],
            'ห้อง Gym ชั้น 30' => ['category' => 'Fitness Room'],
            'ห้องอเนกประสงค์' => ['category' => 'Co-Living & Lounge'],
            'พื้นที่จอดรถบริเวณ Club House' => ['category' => 'Parking'],
            'ห้องออกกำลังกาย / Gym Room' => ['category' => 'Fitness Room'],
            'ห้องนั่งเล่น / Living room' => ['category' => 'Co-Living & Lounge'],
            'สระว่ายน้ำ / Swimming pool' => ['category' => 'Swimming Pool'],
            'ห้องประชุมชั้นลอยสโมสร-ในเวลา' => ['category' => 'Meeting Room'],
            'ห้องประชุมชั้นลอยสโมสร-เช้ามาก' => ['category' => 'Meeting Room'],
            'ชุดลำโพงคาราโอเกะ' => ['category' => 'Meeting Room'],
            'ห้องประชุม ชั้น 1 ห้อง 1' => ['category' => 'Meeting Room', 'sub_name' => 'ห้องประชุม ชั้น 1 ห้อง 1'],
            'ห้องประชุม ชั้น 1 ห้อง 2' => ['category' => 'Meeting Room', 'sub_name' => 'ห้องประชุม ชั้น 1 ห้อง 2'],
            'ห้องประชุม ชั้นลอย ห้องอบรม' => ['category' => 'Meeting Room', 'sub_name' => 'ห้องประชุม ชั้นลอย ห้องอบรม'],
            'ห้องประชุม ชั้น 2 ห้อง 1' => ['category' => 'Meeting Room', 'sub_name' => 'ห้องประชุม ชั้น 2 ห้อง 1'],
            'ห้องประชุม ชั้น 2 ห้อง 2' => ['category' => 'Meeting Room', 'sub_name' => 'ห้องประชุม ชั้น 2 ห้อง 2'],
            'ห้องประชุม ชั้น 3 ห้อง 301' => ['category' => 'Meeting Room', 'sub_name' => 'ห้องประชุม ชั้น 3 ห้อง 301'],
            'ห้องประชุม ชั้น 3 ห้อง 302' => ['category' => 'Meeting Room', 'sub_name' => 'ห้องประชุม ชั้น 3 ห้อง 302'],
            'ห้องประชุม ชั้น 3 ห้อง 333' => ['category' => 'Meeting Room', 'sub_name' => 'ห้องประชุม ชั้น 3 ห้อง 333'],
            'จอดรถบริเวณพื้นที่ส่วนกลาง' => ['category' => 'Parking'],
            'Co-Working / ห้องสัมนา' => ['category' => 'Co-Working Space'],
            'Sauna / ห้องซาวน่า' => ['category' => 'Sauna'],
        ];

        $bookings = DB::table('facility_bookings')
            ->whereNotNull('unit_id')
            ->whereNull('deleted_at')
            ->get();
        $bar = $this->output->createProgressBar($bookings->count());
        $bar->start();

        foreach ($bookings as $booking) {
            $facility = $booking->facility;

            if (! $facility) {
                Log::info("Missing facility for booking ID {$booking->id}");

                continue;
            }

            $originalName = $facility->name;
            $map = $mapping[$originalName] ?? ['category' => $originalName];

            $mappedName = $map['category'];
            $subName = $map['sub_name'] ?? null;

            // Find matching ResidenceAmenity for facility
            $residenceAmenity = ResidenceAmenity::where('residence_id', $facility->residence_id)
                ->whereHas('facilityAndAmenity', function ($query) use ($mappedName) {
                    $query->where('name', $mappedName)
                        ->orWhere('name_in_thai', $mappedName);
                })
                ->first();

            if (! $residenceAmenity) {
                Log::info("No ResidenceAmenity found for Facility ID {$facility->id} | Name: {$originalName} → {$mappedName}");

                continue;
            }

            // If sub_name is present, find matching ResidenceAmenityOption
            $option = null;
            if ($subName) {
                $option = $residenceAmenity->residenceAmenityOptions()
                    ->where('name', $subName)
                    ->orWhere('name_in_thai', $subName)
                    ->first();

                if (! $option) {
                    Log::info("No ResidenceAmenityOption found under ResidenceAmenity ID {$residenceAmenity->id} | Option Name: {$subName}");
                }
            }

            // Create amenity_booking
            AmenityBooking::create([
                'amenity_bookable_id' => $option ? $option->id : $residenceAmenity->id,
                'amenity_bookable_type' => $option ? ResidenceAmenityOption::class : ResidenceAmenity::class,
                'user_id' => $booking->user_id,
                'unit_id' => $booking->unit_id,
                'ref_no' => $booking->ref_no,
                'start_at' => $booking->start_at,
                'end_at' => $booking->end_at,
                'status' => $booking->status,
                'created_by' => $booking->created_by,
                'updated_by' => $booking->updated_by,
                'created_at' => $booking->created_at,
                'updated_at' => $booking->updated_at,
            ]);

            $bar->advance();
        }

        $bar->finish();

        return Command::SUCCESS;
    }
}
