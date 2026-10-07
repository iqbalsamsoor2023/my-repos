<?php

namespace App\Console\Commands\DataMigrations\Mmb;

use App\Models\FacilityAndAmenity;
use App\Models\ResidenceAmenity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClaimableItemData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:claimable-item-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'migrate claimable_items to claimable_titles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mapping = [
            'ถนนภายในหมู่บ้าน' => 'Street',
            'Swimming Pool' => 'Swimming Pool',
            'Fitness Room (Gym)' => 'Fitness Room',
            'ลิฟท์จอดรถอัตโนมัติ' => 'Parking Lift',
            'ถนนในโครงการ' => 'Street',
            'ไฟฟ้าแสงสว่างลานจอดรถ' => 'Parking',
            'งานสวน-งานภูมิทัศน์' => 'Park (Green Area)',
            'งานลิฟท์โดยสาร1' => 'Elevator',
            'งาน CCTV-A' => 'CCTV',
            'Swimming pool' => 'Swimming Pool',
            'ระบบประปา' => 'Swimming Pool',
            'ฟิตเนส / สระว่ายน้ำ' => 'Fitness Room',
            'ไฟแสงสว่างถนน' => 'Street',
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
            'สระว่ายน้ำ' => 'Swimming Pool',
            'ห้องออกกำลังกาย' => 'Fitness Room',
            'ถนนส่วนกลาง' => 'Street',
            'ห้องฟิตเนส' => 'Fitness Room',
            'สวน(บริเวณสะพานข้ามเฟส)' => 'Park (Green Area)',
            'สนามเด็กเล่น' => 'Play Ground',
            'อาคารสโมสร' => 'Clubhouse',
            'Clubhouse' => 'Clubhouse',
            'เครื่องออกกำลังกาย' => 'Fitness Room',
            'ไม้กั้น' => 'Access Control',
            'ถนน' => 'Street',
            'สวนสาธารณะ' => 'Park (Green Area)',
            'Fitness Center' => 'Fitness Room',
            'ห้องสมุด' => 'Library Room',
            'Tennis court-Marina' => 'Tennis Court',
            'ํYoga-5th floor-Large Room' => 'Yoga Room',
            'Steam room - Gentleman' => 'Steam Room ',
            'Meeting Room - 3rd floor' => 'Meeting Room',
            'Tridhos Lobby' => 'Lobby',
            'Fitness room' => 'Fitness Room',
            'ฟิตเนส' => 'Fitness Room',
            'Meeting Room' => 'Meeting Room',
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
            'สวน' => 'Park (Green Area)',
            'สโมสร' => 'Clubhouse',
            'Co-Working Space' => 'Co-Working Space',
            'ระบบไฟฟ้าถนน' => 'Street',
            'สนามเทนนิส' => 'Tennis Court',
            'ห้องฟิตเนต' => 'Fitness Room',
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
            'Pools' => 'Swimming Pool',
            'ขอใช้สนามเด็กเล่นทำกิจกรรมชั่วคราว' => 'Play Ground',
            'ห้องซาวน่า' => 'Sauna',
            'ถนนสวนกลาง' => 'Street',
            'กล้องวงจรปิด' => 'CCTV',
            'สนามเด็กเล่น สวนสาธารณะ' => 'Play Ground',
            'GYM' => 'Fitness Room',
            'POOL' => 'Swimming Pool',
            'MEETING ROOM' => 'Meeting Room',
            'ฟิตเนท' => 'Fitness Room',
            'Meeting room LKB 128' => 'Meeting Room',
            'ส่วนกลาง ถนนทางเท้า' => 'Street',
            'ห้อง Gym ชั้น 30' => 'Fitness Room',
            'แจ้งหลอดไฟดับ' => 'Street',
            'พื้นที่จอดรถบริเวณ Club House' => 'Parking',
            'ห้องออกกำลังกาย / Gym Room' => 'Fitness Room',
            'ห้องนั่งเล่น / Living room' => 'Co-Living & Lounge',
            'สระว่ายน้ำ / Swimming pool' => 'Swimming Pool',
            'แจ้งทรัพย์สินส่วนกลางชำรุด' => 'Street',
            'ไฟแสงจันทร์' => 'Street',
            'ห้องประชุม D1 ตึก Diamond' => 'Meeting Room',
            'อุปกรณ์ห้องยิม' => 'Fitness Room',
            'อุปกรณ์ในสวนสาธารณะ' => 'Park (Green Area)',
            'ไฟส่องสว่าง(ถนน)' => 'Street',
            'ไฟส่องสว่าง(ในอาคารส่วนกลาง)' => 'Clubhouse',
            'Clubhouse' => 'Clubhouse',
            'ฟิตเนต' => 'Fitness Room',
            'ถนนภายในหมู่บ้าน (หลุม, รอยแตก, ยุบตัว)' => 'Street',
            'ไฟถนน / ไฟทางเดินเสีย' => 'Street',
            'ท่อระบายน้ำอุดตัน / น้ำท่วมขัง' => 'Street',
            'ต้นไม้ล้ม, กิ่งไม้พาดสายไฟ' => 'Street',
            'ระบบอ่านป้ายทะเบียนรถ' => 'Access Control',
            'ต้นไม้ในสวน สนามหญ้า ในสวน' => 'Park (Green Area)',
            'ไม้กั้นกระดก ทางเข้า ออกหมู่บ้าน' => 'Access Control',
            'สระวายน้ำ' => 'Swimming Pool',
            'สนามบอล' => 'Football Field',
            'ระบบกล้องวงจรปิด' => 'CCTV',
            'สวนส่วนกลาง' => 'Park (Green Area)',
            'ไม้กั้น/แผงเหล็ก เข้า-ออกหมู่บ้าน' => 'Access Control',
            'งานสถาปัตฯ/งานทั่วไป' => 'Street',
            'งานโครงสร้าง' => 'Swimming Pool',
            'ถนนโครงการ' => 'Street',
            'ไฟฟ้าส่วนกลาง' => 'Street',
            'ป้อม รปภ.' => 'Access Control',
            'ห้องน้ำส่วนกลาง' => 'Public Restroom',
            'ห้องน้ำ' => 'Public Restroom',
            'น้ำ' => 'Swimming Pool',
            'ฝาท่อ' => 'Street',
            'ระบบไฟฟ้า' => 'Street',
            'งานประปา สุขาภิบาล' => 'Street',
            'ไฟส่องสว่าง' => 'Street',
            'ไฟส่องถนน ในโครงการ' => 'Street',
            'พื้นที่จอดรถบริเวณ Club House' => 'Parking',
            'CM-Plumbing (120)' => 'Street',
            'CM-Electrical (30)' => 'Street',
            'GBR (120)' => 'Street',
            'GBM' => 'Street',
            'แจ้งข่าว' => 'Street',
            'ร้องเรียน / Complaint' => 'Street',
            'รถยนต์ Nissan Almera ฏร 115' => 'Meeting Room',
            'ห้องประชุม เพชรปัญญา ตึกผลิต' => 'Meeting Room',
            'รถกระบะ Mazda วพ 1999' => 'Meeting Room',
            'ห้องประชุมจัดส่ง ตึกจัดส่ง' => 'Meeting Room',
            'งานนอกประกัน (มีค่าใช้จ่าย)' => 'Street',
            'งานเปลี่ยนอุปกรณ์ที่ชำรุด' => 'Street',
            'LKB 128' => 'Street',
            'LKB 130' => 'Street',
        ];

        $query = DB::table('claimable_items')->whereNull('deleted_at');
        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        $claimableItems = $query->get();

        foreach ($claimableItems as $claimableItem) {
            $originalName = $claimableItem->item;
            $mappedName = $mapping[$originalName] ?? $originalName;

            $facilityAndAmenity = FacilityAndAmenity::where('name', $mappedName)->first();

            if (! $facilityAndAmenity) {
                Log::info("Mapped amenity not found for ClaimableItem ID {$claimableItem->id} | Name: {$originalName}");

                continue;
            }

            $residenceAmenity = ResidenceAmenity::where('residence_id', $claimableItem->residence_id)
                ->where('facility_and_amenity_id', $facilityAndAmenity->id)
                ->first();

            if (! $residenceAmenity) {
                ResidenceAmenity::create([
                    'residence_id' => $claimableItem->residence_id,
                    'facility_and_amenity_id' => $facilityAndAmenity->id,
                    'is_active' => true,
                    'is_claimable' => true,
                    'created_at' => $claimableItem->created_at,
                    'updated_at' => $claimableItem->updated_at,
                ]);
            } else {
                $residenceAmenity->update([
                    'is_claimable' => true,
                ]);
            }

            $bar->advance();
        }

        $bar->finish();

        return Command::SUCCESS;
    }
}
