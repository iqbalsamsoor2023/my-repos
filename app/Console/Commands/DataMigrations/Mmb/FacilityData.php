<?php

namespace App\Console\Commands\DataMigrations\Mmb;

use App\Models\FacilityAndAmenity;
use App\Models\Residence;
use App\Models\ResidenceAmenity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FacilityData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:facility-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'migrate facilities to residence_amenity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mapping = [
            'Meeting' => 'Meeting Room',
            'สระว่ายน้ำ' => 'Swimming Pool',
            'สระวายน้ำ' => 'Swimming Pool',
            'ห้องสโมสร' => 'Clubhouse',
            'ห้องฟิตเนต' => 'Fitness Room',
            'ลิฟท์จอดรถอัตโนมัติ' => 'Parking Lift',
            'ไฟฟ้าแสงสว่างลานจอดรถ' => 'Parking',
            'งานสวน-งานภูมิทัศน์' => 'Park (Green Area)',
            'Swimming pool' => 'Swimming Pool',
            'ระบบประปา' => 'Swimming Pool',
            'ฟิตเนส / สระว่ายน้ำ' => 'Fitness Room',
            'สระว่ายน้ำ' => 'Swimming Pool',
            'ต้นไม้ในสวน สนามหญ้า ในสวน' => 'Park (Green Area)',
            'ห้องประชุม' => 'Meeting Room',
            'ห้องประชุมชั้นลอยสโมสร-ในเวลา' => 'Meeting Room',
            'ห้องประชุมชั้นลอยสโมสร-เช้ามาก' => 'Meeting Room',
            'สนามแบดมินตัน A (Badminton court)' => 'Badminton Court',
            'สระว่ายน้ำ Swimming pool' => 'Swimming Pool',
            'สนามเทนนิส 1 (Tennis Court 1)' => 'Tennis Court',
            'สระว่ายน้ำ (Swimming Pool)' => 'Swimming Pool',
            'ห้องซาวน่าชาย (Sauna-Man)' => 'Sauna',
            'ฟิตเนส (Fitness)' => 'Fitness Room',
            'Fitness' => 'Fitness Room',
            'ห้องออกกำลังกาย' => 'Fitness Room',
            'ห้องฟิตเนส' => 'Fitness Room',
            'สวน(บริเวณสะพานข้ามเฟส)' => 'Park (Green Area)',
            'สนามเด็กเล่น' => 'Play Ground',
            'อาคารสโมสร' => 'Clubhouse',
            'เครื่องออกกำลังกาย' => 'Fitness Room',
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
            'ลิฟท์ อาคารA' => 'Elevator',
            'พื้นที่จอดรถ' => 'Parking',
            'ฟิตเนส' => 'Fitness Room',
            'สวน' => 'Park (Green Area)',
            'สโมสร' => 'Clubhouse',
            'สนามเทนนิส' => 'Tennis Court',
            'สนามบาสเกตบอล' => 'Basketball Court',
            'MOVIE STUDIO' => 'Theater Room',
            'COOKING STUDIO' => 'Co-Kitchen Space',
            'SUANA' => 'Sauna',
            'MULTI STUDIO' => 'Fitness Room',
            'ต้นไม้และสวนสาธารณะ' => 'Park (Green Area)',
            'ระบบไม้กั้น' => 'Access Control',
            'ห้องประชุมและอาคารนิติ' => 'Meeting Room',
            'สระว่ายน้ำและห้องน้ำ' => 'Swimming Pool',
            'ล็อบบี้' => 'Lobby',
            'ลานจอดรถชั้น G' => 'Parking',
            'ฟิสเนต' => 'Fitness Room',
            'ห้องอาหาร และห้องบาร์' => 'Restaurant',
            'ต้นไม้และพืชพรรณ' => 'Park (Green Area)',
            'เครื่องออกกำลังกายกลางแจ้ง' => 'Park (Green Area)',
            'คลับเฮ้าท์หมู่บ้าน-ทดสอบ' => 'Clubhouse',
            'สระว่ายน้ำ-ทดสอบ' => 'Swimming Pool',
            'ห้องเกมส์ (Game Room)' => 'Game Room',
            'ห้องดูหนัง (Home Theater)' => 'Theater Room',
            'ห้องทำงาน (Co Working Room)' => 'Co-Working Space',
            'โยคะ' => 'Yoga Room',
            'ห้องโยคะ (Yoga Room)' => 'Yoga Room',
            'ห้องฟิตเนส(fitness)' => 'Fitness Room',
            'ห้องคิสรูม(Kids room)' => 'Kids Room',
            'ห้องซาวน่า(sauna room)' => 'Sauna',
            'สระว่ายน้ำ(swimming pool)' => 'Swimming Pool',
            'Fitness' => 'Fitness Room',
            'Pools' => 'Swimming Pool',
            'ขอใช้สนามเด็กเล่นทำกิจกรรมชั่วคราว' => 'Play Ground',
            'ห้องซาวน่า' => 'Sauna',
            'กล้องวงจรปิด' => 'CCTV',
            'สนามเด็กเล่น สวนสาธารณะ' => 'Play Ground',
            'GYM' => 'Fitness Room',
            'POOL' => 'Swimming Pool',
            'MEETING ROOM' => 'Meeting Room',
            'Meeting room LKB 128' => 'Meeting Room',
            'ห้อง Gym ชั้น 30' => 'Fitness Room',
            'พื้นที่จอดรถบริเวณ Club House' => 'Parking',
            'ห้องออกกำลังกาย / Gym Room' => 'Fitness Room',
            'ห้องนั่งเล่น / Living room' => 'Co-Living & Lounge',
            'สระว่ายน้ำ / Swimming pool' => 'Swimming Pool',
            'ห้องประชุม D1 ตึก Diamond' => 'Meeting Room',
            'อุปกรณ์ห้องยิม' => 'Fitness Room',
            'อุปกรณ์ในสวนสาธารณะ' => 'Park (Green Area)',
            'ไฟส่องสว่าง(ในอาคารส่วนกลาง)' => 'Clubhouse',
            'ฟิตเนต' => 'Fitness Room',
            'Air Hockey' => 'Game Room',
            'โต๊ะพูล' => 'Game Room',
            'Other Facilities' => 'Fitness Room',
            'สโมสรหมู่บ้าน' => 'Clubhouse',
            'ฟิตเนท' => 'Fitness Room',
            'ห้อง Co-working' => 'Co-Working Space',
            'ห้องเด็กเล่น' => 'Kids Room',
            'ห้องอเนกประสงค์' => 'Co-Living & Lounge',
            'ห้องประชุม ชั้น 1 ห้อง 2' => 'Meeting Room',
            'จอดรถบริเวณพื้นที่ส่วนกลาง' => 'Parking',
            'Clubhouse' => 'Clubhouse',
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

        $query = DB::table('facilities')
            ->whereNull('deleted_at');

        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        $facilities = $query->get();
        foreach ($facilities as $facility) {
            $originalName = $facility->name;
            $mappedName = $mapping[$originalName] ?? $originalName;

            $cross_data_checking = ResidenceAmenity::where('residence_id', $facility->residence_id)
                ->whereHas('facilityAndAmenity', function ($query) use ($mappedName) {
                    $query->where('name', $mappedName);
                })->first();

            if (! $cross_data_checking) {
                $facilityAndAmenity = FacilityAndAmenity::where('name', $mappedName)
                    ->first();

                if (! $facilityAndAmenity) {
                    Log::info('Mapped Facility name not found for facility ID '.$facility->id.' ('.$mappedName.')');

                    continue;
                }

                // Check if residence is deleted or not
                $residence = Residence::whereId($facility->residence_id)->whereNull('deleted_at')->first();

                if ($residence) {
                    ResidenceAmenity::create([
                        'residence_id' => $facility->residence_id,
                        'facility_and_amenity_id' => $facilityAndAmenity->id,
                        'is_active' => true,
                        'is_bookable' => true,
                        'created_at' => $facility->created_at,
                        'updated_at' => $facility->updated_at,
                    ]);
                }
            } else {
                $cross_data_checking->update([
                    'is_bookable' => true,
                ]);
            }

            $bar->advance();
        }

        $bar->finish();

        return Command::SUCCESS;
    }
}
