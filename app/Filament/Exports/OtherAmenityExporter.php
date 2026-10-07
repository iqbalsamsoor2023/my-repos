<?php

namespace App\Filament\Exports;

use App\Models\OtherAmenity;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class OtherAmenityExporter extends Exporter
{
    protected static ?string $model = OtherAmenity::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Residence Name'),
            ExportColumn::make('name_th')->label('Residence Name (Thailand)'),
            ExportColumn::make('otherAmenity.is_other_amenity')->label('Is Other Amenity'),
            ExportColumn::make('otherAmenity.remark')->label('Remark'),
            ExportColumn::make('otherAmenity.is_show_warranty_reminder')->label('Show Warranty Reminder'),
            ExportColumn::make('otherAmenity.remind_day')->label('Remind Day'),
            ExportColumn::make('otherAmenity.warranty_handbook_url')->label('Warranty Handbook URL'),
            ExportColumn::make('otherAmenity.created_at')->label('Created At'),
            ExportColumn::make('otherAmenity.updated_at')->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your other amenity export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
