<?php

namespace App\Imports;

use App\Models\PrivateClaimItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Row;

class PrivateClaimSettingsImport implements OnEachRow, WithChunkReading
{
    /**
     * @param Collection $collection
     */

    public function onRow(Row $row)
    {
        $index = $row->getIndex();
        $row = $row->toArray();
    
        if ($index === 1) return; // skip header
    
        try {

            $private_claim_item = PrivateClaimItem::find($row[3]);

            DB::table('private_claim_item_settings')->insert([
                'residence_id' => $row[0],
                'private_claim_item_id' => $row[3],
                'private_claim_item_details' => $private_claim_item ?? null,
                'warranty_period' => $row[4],
                'supplier_company_name' => $row[5],
                'supplier_company_name_th' => $row[6],
                'supplier_item_brand' => $row[7],
                'pic_name' => $row[8],
                'pic_mobile_no' => $row[9],
                'pic_email' => $row[10],
                'is_out_warranty' => $row[11],
                'remark' => $row[12],
                'created_at' => !empty($row[13])
                ? Carbon::instance(Date::excelToDateTimeObject($row[13]))
                : now(),
                'updated_at' => !empty($row[14])
                ? Carbon::instance(Date::excelToDateTimeObject($row[14]))
                : now(),
                'deleted_at' => !empty($row[15])
                ? Carbon::instance(Date::excelToDateTimeObject($row[15]))
                : null,
            ]);
    
        } catch (\Throwable $e) {
            Log::error('Row failed', [
                'row' => $index,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // public function collection(Collection $rows)
    // {
    //     foreach ($rows as $index => $row) {
    
    //         if ($index === 0) continue; // skip header
    
    //         try {
    
    //             $private_claim_item = PrivateClaimItem::find($row[3]);
    
    //             DB::table('private_claim_item_settings')->insert([
    //                 'residence_id' => $row[0],
    //                 'private_claim_item_id' => $row[3],
    //                 'private_claim_item_details' => optional($private_claim_item)->toJson(), // ⚠️ fix here
    //                 'warranty_period' => $row[4],
    //                 'supplier_company_name' => $row[5],
    //                 'supplier_company_name_th' => $row[6],
    //                 'supplier_item_brand' => $row[7],
    //                 'pic_name' => $row[8],
    //                 'pic_mobile_no' => $row[9],
    //                 'pic_email' => $row[10],
    //                 'is_out_warranty' => $row[11],
    //                 'remark' => $row[12],
    //                 'created_at' => !empty($row[13])
    //                 ? Carbon::instance(Date::excelToDateTimeObject($row[13]))
    //                 : now(),
    //                 'updated_at' => !empty($row[14])
    //                 ? Carbon::instance(Date::excelToDateTimeObject($row[14]))
    //                 : now(),
    //                 'deleted_at' => !empty($row[15])
    //                 ? Carbon::instance(Date::excelToDateTimeObject($row[15]))
    //                 : null,
    //             ]);
    
    //         } catch (\Throwable $e) {
    
    //             Log::error('Import row failed', [
    //                 'row_index' => $index + 1, // +1 = actual Excel row
    //                 'data' => $row->toArray(),
    //                 'error' => $e->getMessage(),
    //             ]);
    //         }
    //     }
    // }

    public function chunkSize(): int
    {
        return 100;
    }
}



