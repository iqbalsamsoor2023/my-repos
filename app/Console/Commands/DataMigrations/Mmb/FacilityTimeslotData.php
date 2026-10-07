<?php

namespace App\Console\Commands\DataMigrations\Mmb;

use App\Models\AmenityTimeslot;
use App\Models\ResidenceAmenity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FacilityTimeslotData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:facility-timeslot-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'migrate facility_timeslots to amenity_timeslots';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mapping = [
            'ลิฟท์จอดรถอัตโนมัติ' => 'Parking Lift',
            'ถนนในโครงการ' => 'Street',
            'ไฟฟ้าแสงสว่างลานจอดรถ' => 'Parking',
            'งานสวน-งานภูมิทัศน์' => 'Park (Green Area)',
            'งานลิฟท์โดยสาร1' => 'Elevator',
            'งาน CCTV-A' => 'CCTV',
            'Swimming pool' => 'Swimming Pool',
            'ระบบประปา' => 'Swimming Pool',
            'ฟิตเนส / สระว่ายน้ำ' => 'Fitness Room',
            'สระว่ายน้ำ' => 'Swimming Pool',
            'ไฟแสงสว่างถนน' => 'Street',
            'ต้นไม้ในสวน สนามหญ้า ในสวน' => 'Park (Green Area)',
            'ไม้กั้นกระดก ทางเข้า ออกหมู่บ้าน' => 'Access Control',
            'ถนนภายในหมู่บ้าน' => 'Street',
            'ห้องประชุม' => 'Meeting Room',
            'ห้องซาวน่า (ห้องนํ้า หญิง)' => 'Sauna',
            'อ่างจากุชชี่ (ห้องนํ้า ชาย)' => 'Jacuzzi',
            'สนามแบดมินตัน A (Badminton court)' => 'Badminton Court',
            'สระว่ายน้ำ Swimming pool' => 'Swimming Pool',
            'สนามเทนนิส 1 (Tennis Court 1)' => 'Tennis Court',
            'สระว่ายน้ำ (Swimming Pool)' => 'Swimming Pool',
            'ห้องซาวน่าชาย (Sauna-Man)' => 'Sauna',
            'ฟิตเนส (Fitness)' => 'Fitness Room',
            'Fitness' => 'Fitness Room',
            'ห้องออกกำลังกาย' => 'Fitness Room',
            'ถนนส่วนกลาง' => 'Street',
            'ห้องฟิตเนส' => 'Fitness Room',
            'สวน(บริเวณสะพานข้ามเฟส)' => 'Park (Green Area)',
            'สนามเด็กเล่น' => 'Play Ground',
            'อาคารสโมสร' => 'Clubhouse',
            'เครื่องออกกำลังกาย' => 'Fitness Room',
            'ไม้กั้น' => 'Access Control',
            'ถนน' => 'Street',
            'สวนสาธารณะ' => 'Park (Green Area)',
            'Fitness Center' => 'Fitness Room',
            'ห้องสมุด' => 'Library Room',
            'Tennis court-Marina' => 'Tennis Court',
            'ํYoga-5th floor-Large Room' => 'Yoga Room',
            'Steam room - Gentleman' => 'Steam Room',
            'Meeting Room - 3rd floor' => 'Meeting Room',
            'Tridhos Lobby' => 'Lobby',
            'Fitness room' => 'Fitness Room',
            'อาคารสโมสร (Clubhouse)' => 'Clubhouse',
            'ฟิตเนส (Fitness Room)' => 'Fitness Room',
            'สวนสาธารณะ (Public Park)' => 'Park (Green Area)',
            'เลนส์วิ่ง (Jogging Lane)' => 'Jogging Track',
            'คลับเฮาส์ (Clubhouse)' => 'Clubhouse',
            'ห้องสมุด (Library)' => 'Library Room',
            'ร้านอาหาร (Restaurant)' => 'Restaurant',
            'สนามฟุตบอล (Football field)' => 'Football Field',
            'สปา (Spa Club)' => 'Spa',
            'ไฟทางเดิน อาคารB' => 'Street',
            'ลิฟท์ อาคารA' => 'Elevator',
            'พื้นที่จอดรถ' => 'Parking',
            'ฟิตเนส' => 'Fitness Room',
            'สวน' => 'Park (Green Area)',
            'สโมสร' => 'Clubhouse',
            'ระบบไฟฟ้าถนน' => 'Street',
            'สนามเทนนิส' => 'Tennis Court',
            'สนามบาสเกตบอล' => 'Basketball Court',
            'MOVIE STUDIO' => 'Theater Room',
            'COOKING STUDIO' => 'Co-Kitchen Space',
            'SUANA' => 'Sauna',
            'MULTI STUDIO' => 'Yoga Room',
            'ไฟสาธารณะ' => 'Street',
            'ต้นไม้และสวนสาธารณะ' => 'Park (Green Area)',
            'ระบบไม้กั้น' => 'Access Control',
            'ห้องประชุมและอาคารนิติ' => 'Meeting Room',
            'สระว่ายน้ำและห้องน้ำ' => 'Swimming Pool',
            'ล็อบบี้' => 'Lobby',
            'ลานจอดรถชั้น G' => 'Parking',
            'ฟิสเนต' => 'Fitness Room',
            'ห้องอาหาร และห้องบาร์' => 'Restaurant',
            'ทางเดินห้องพักแขกทุกชั้น และทางหนีไฟ' => 'Fire Exit',
            'ต้นไม้และพืชพรรณ' => 'Park (Green Area)',
            'เครื่องออกกำลังกายกลางแจ้ง' => 'Park (Green Area)',
            'คลับเฮ้าท์หมู่บ้าน-ทดสอบ' => 'Clubhouse',
            'สระว่ายน้ำ-ทดสอบ' => 'Swimming Pool',
            'ห้องเกมส์ (Game Room)' => 'Game Room',
            'ห้องดูหนัง (Home Theater)' => 'Theater Room',
            'ห้องทำงาน (Co Working Room)' => 'Co-Working Space',
            'ห้องโยคะ (Yoga Room)' => 'Yoga Room',
            'ห้องฟิตเนส(fitness)' => 'Fitness Room',
            'ห้องคิสรูม(Kids room)' => 'Kids Room',
            'สระว่ายน้ำ(swimming pool)' => 'Swimming Pool',
            'Fitness' => 'Fitness Room',
            'Pools' => 'Swimming Pool',
            'ขอใช้สนามเด็กเล่นทำกิจกรรมชั่วคราว' => 'Play Ground',
            'ห้องซาวน่า' => 'Sauna',
            'ถนนสวนกลาง' => 'Street',
            'กล้องวงจรปิด' => 'CCTV',
            'สนามเด็กเล่น สวนสาธารณะ' => 'Play Ground',
            'GYM' => 'Fitness Room',
            'POOL' => 'Swimming Pool',
            'MEETING ROOM' => 'Meeting Room',
            'Meeting room LKB 128' => 'Meeting Room',
            // 'Bangplee' => '',
            'ส่วนกลาง ถนนทางเท้า' => 'Street',
            'ห้อง Gym ชั้น 30' => 'Fitness Room',
            'แจ้งหลอดไฟดับ' => 'Street',
            'พื้นที่จอดรถบริเวณ Club House' => 'Clubhouse',
            'ห้องออกกำลังกาย / Gym Room' => 'Fitness Room',
            'สระว่ายน้ำ / Swimming pool' => 'Swimming Pool',
            'แจ้งทรัพย์สินส่วนกลางชำรุด' => 'Street',
            'ไฟแสงจันทร์' => 'Street',
            'ห้องประชุม D1 ตึก Diamond' => 'Meeting Room',
            'อุปกรณ์ห้องยิม' => 'Fitness Room',
            'อุปกรณ์ในสวนสาธารณะ' => 'Green (Park Area)',
            'ไฟส่องสว่าง(ถนน)' => 'Street',
            'ไฟส่องสว่าง(ในอาคารส่วนกลาง)' => 'Clubhouse',
            'ฟิตเนต' => 'Fitness Room',
            'ถนนภายในหมู่บ้าน (หลุม, รอยแตก, ยุบตัว)' => 'Street',
            'ไฟถนน / ไฟทางเดินเสีย' => 'Street',
            'ท่อระบายน้ำอุดตัน / น้ำท่วมขัง' => 'Street',
            'ต้นไม้ล้ม, กิ่งไม้พาดสายไฟ' => 'Street',
            'ระบบอ่านป้ายทะเบียนรถ' => 'Access Control',
            'สระว่ายน้ำ' => 'Swimming Pool',
            'ห้องประชุม ชั้น 1 ห้อง 1' => 'Meeting Room',
            'ห้องประชุม ชั้น 1 ห้อง 2' => 'Meeting Room',
            'ห้องประชุม ชั้นลอย ห้องอบรม' => 'Meeting Room',
            'ห้องประชุม ชั้น 2 ห้อง 2' => 'Meeting Room',
            'ห้องประชุม ชั้น 3 ห้อง 301' => 'Meeting Room',
            'หห้องประชุม ชั้น 3 ห้อง 302' => 'Meeting Room',
            'ห้องประชุม ชั้น 3 ห้อง 333' => 'Meeting Room',
            'Co-Working / ห้องสัมนา' => 'Co-Working Space',
            'Sauna / ห้องซาวน่า' => 'Sauna',

        ];

        $query = DB::table('facility_timeslots')
            ->whereNull('deleted_at');

        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        $facilityTimeslots = $query->get();
        foreach ($facilityTimeslots as $facilityTimeslot) {
            $originalName = $facilityTimeslot?->facility?->name;
            $mappedName = $mapping[$originalName] ?? $originalName;

            $residenceId = $facilityTimeslot?->facility?->residence_id;

            $cross_data_checking = ResidenceAmenity::where('residence_id', $residenceId)
                ->whereHas('facilityAndAmenity', function ($query) use ($mappedName) {
                    $query->where('name', $mappedName)
                        ->orWhere('name_in_thai', $mappedName);
                })
                ->first();

            if (! $cross_data_checking) {
                Log::info('Facility item not found for Facility Timeslot ID '.$facilityTimeslot->id.' | Name: '.$originalName.' | Mapped: '.$mappedName.' | Residence ID: '.$residenceId);
                continue;
            }

            $existing = AmenityTimeslot::where('amenity_timeslotable_id', $cross_data_checking->id)
                ->where('amenity_timeslotable_type', ResidenceAmenity::class)
                ->where('day', $facilityTimeslot->day)
                ->first();

            if ($existing) {
                Log::info("Duplicate timeslot skipped for ResidenceAmenity ID {$cross_data_checking->id}, day {$facilityTimeslot->day}");

                continue;
            }

            AmenityTimeslot::create([
                'amenity_timeslotable_id' => $cross_data_checking->id,
                'amenity_timeslotable_type' => ResidenceAmenity::class,
                'quota' => $facilityTimeslot?->facility?->booking_per_hour ?? 1,
                'day' => $facilityTimeslot->day,
                'is_active' => $facilityTimeslot->is_active,
                'start_at' => $facilityTimeslot->start_at,
                'end_at' => $facilityTimeslot->end_at,
                'created_at' => $facilityTimeslot->created_at,
                'updated_at' => $facilityTimeslot->updated_at,
            ]);

            $bar->advance();
        }

        $bar->finish();

        return Command::SUCCESS;
    }
}
