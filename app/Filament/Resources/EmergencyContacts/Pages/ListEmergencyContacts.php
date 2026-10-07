<?php

namespace App\Filament\Resources\EmergencyContacts\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Throwable;
use App\Enums\EmergencyContact\CoverageMode;
use App\Enums\EmergencyContact\DepartmentType;
use App\Filament\Resources\EmergencyContacts\EmergencyContactResource;
use App\Filament\Resources\EmergencyContacts\EmergencyContactImport;
use App\Models\DistrictEmergencyContact;
use App\Models\EmergencyContact;
use App\Models\Erp\ThailandDistrict;
use Closure;
use Excel;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification as FilamentsNotification;
use Filament\Resources\Pages\ListRecords;
use File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ListEmergencyContacts extends ListRecords
{
    protected static string $resource = EmergencyContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('UploadData')
                ->label(__('app.upload_data'))
                ->action(Closure::fromCallable([$this, 'openImportExcel']))
                ->schema([
                    FileUpload::make('upload')
                        ->preserveFilenames()
                        ->disk('local')
                        ->directory('uploads')
                        ->storeFileNamesIn('uploads'),
                ])
                ->hidden(auth()->user()->hasRole(['Admin'])),
            Action::make('downloadTemplate')
                ->label(__('app.download_template'))
                ->action('downloadFileTemplate')
                ->hidden(auth()->user()->hasRole(['Admin'])),
            CreateAction::make()
                ->label(__('menu.new_emergency_contact')),
        ];
    }

    public function openImportExcel($data)
    {
        try {
            $url = 'app/'.$data['upload'];
            $address = storage_path($url);

            $emergencyContactImport = new EmergencyContactImport;
            Excel::import($emergencyContactImport, $address);
            $array = $emergencyContactImport->getArray();
            $startData = 3;
            $collections = [];
            $errorMessage = '';

            for ($i = $startData; $i < count($array); $i++) {
                if (! $array[$i]['department_type'] && ! $array[$i]['name'] && ! $array[$i]['contact_no'] && ! $array[$i]['coverage_mode'] && $array[$i]['is_active'] === null) {
                    continue;
                }

                if (! $array[$i]['department_type']) {
                    $errorMessage = __('validation.required', ['attribute' => __('department type')]);
                }

                if (! $array[$i]['name']) {
                    $errorMessage = __('validation.required', ['attribute' => __('name')]);
                }

                if (! $array[$i]['contact_no']) {
                    $errorMessage = __('validation.required', ['attribute' => __('contact no')]);
                }

                if (! $array[$i]['coverage_mode']) {
                    $errorMessage = __('validation.required', ['attribute' => __('coverage mode')]);
                }

                if ($array[$i]['is_active'] === null) {
                    $errorMessage = __('validation.required', ['attribute' => __('is active')]);
                }

                $departmentTypeID = $array[$i]['department_type'];
                $departmentType = '';

                if ($departmentTypeID == 1) {
                    $departmentType = DepartmentType::HOSPITAL->value;
                } elseif ($departmentTypeID == 2) {
                    $departmentType = DepartmentType::POLICE->value;
                } elseif ($departmentTypeID == 3) {
                    $departmentType = DepartmentType::FOUNDATION->value;
                } elseif ($departmentTypeID == 4) {
                    $departmentType = DepartmentType::FIRE_STATION->value;
                } else {
                    $departmentType = DepartmentType::OTHERS->value;
                }

                $checkExistsEmergencyContact = EmergencyContact::where('department_type', 'like', '%'.$departmentType.'%')
                    ->where('name', 'like', '%'.$array[$i]['name'].'%')
                    ->first();

                if ($checkExistsEmergencyContact) {
                    $errorMessage = __('Department Type and Name already exist');
                }

                $coverageMode = $array[$i]['coverage_mode'];
                $coverageModeID = '';
                $district = null;

                if ($coverageMode == 2) {
                    $coverageModeID = CoverageMode::PROVINCE->value;
                    if ($array[$i]['district']) {
                        $arrayDistricts = $this->multiexplode([',', ' ,'], $array[$i]['district']);
                        for ($h = 0; $h < count($arrayDistricts); $h++) {
                            $district = ThailandDistrict::where('name_in_english', $arrayDistricts[$h])->first();
                            if (! $district) {
                                $errorMessage = __('District Not Found');
                            }
                        }
                    } else {
                        $errorMessage = __('validation.required', ['attribute' => __('district')]);
                    }
                } else {
                    $coverageModeID = CoverageMode::NATIONWIDE->value;
                }

                if ($errorMessage) {
                    return FilamentsNotification::make()->title($errorMessage)->body('error in row '.++$i)->warning()->persistent()->send();
                }

                $collections[$i] = [
                    'row' => $i,
                    'department_type' => $departmentType,
                    'name' => $array[$i]['name'],
                    'contact_no' => $array[$i]['contact_no'],
                    'coverage_mode' => $coverageModeID,
                    'is_active' => stripos($array[$i]['is_active'], true) !== false,
                    'district' => $district,
                ];
            }

            DB::beginTransaction();

            if (count($collections) > 0) {
                foreach ($collections as $i => $collection) {
                    // Insert data in here
                    $emergencyContact = EmergencyContact::create([
                        'department_type' => $collection['department_type'],
                        'name' => $collection['name'],
                        'contact_no' => $collection['contact_no'],
                        'coverage_mode' => $collection['coverage_mode'],
                        'is_active' => $collection['is_active'],
                    ]);

                    if ($collection['district'] != null) {
                        DistrictEmergencyContact::updateOrCreate([
                            'emergency_contact_id' => $emergencyContact->id,
                            'thailand_district_id' => $collection['district']->id,
                        ]);
                    }
                }
            }

            if (File::exists($address)) {
                File::delete($address);
            }

            DB::commit();

            return redirect('/admin/emergency-contacts');
        } catch (Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function multiexplode($delimiters, $string)
    {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);

        return $launch;
    }

    public function downloadFileTemplate()
    {
        $filePath = storage_path('templates/imports/MyMooBan Emergency Contact Template.xlsx');

        return Response::download($filePath);
    }
}
