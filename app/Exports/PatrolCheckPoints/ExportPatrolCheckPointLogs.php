<?php

namespace App\Exports\PatrolCheckPoints;

use App\Models\Residence;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportPatrolCheckPointLogs implements FromCollection, WithHeadings
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->indexMap($this->data['data']));
    }

    public function headings(): array
    {
        return ['ID', 'Residence', 'Staff name', 'Checkpoint', 'Description', 'Image', 'Qestionnaire', 'Remark', 'Created at (date)', 'Created at (time)'];
    }

    /**
     * Transformer for DT data attributes
     */
    private function indexMap(array $data = []): array
    {
        $modelMapping = [];

        foreach ($data as $data) {
            $residence = Residence::whereId(data_get($data, 'checkpoint.mmb_residence_id', null))->first();
            $questionnaires = json_decode(data_get($data, 'questionnaires'));
            foreach ($questionnaires as $questionnaire) {
                $questioner = null;
                $question = data_get($questionnaire, 'question', '');
                $answer = data_get($questionnaire, 'answer', '');

                if (! empty($questioner)) {
                    $questioner = "$questioner, Question : $question Answer : $answer";
                } else {
                    $questioner = "Question : $question Answer : $answer";
                }
            }

            $modelMapping[] = [
                'id' => data_get($data, 'id', ''),
                'residence' => $residence->name,
                'staff_name' => data_get($data, 'user.name', '-'),
                'checkpoint_name' => data_get($data, 'checkpoint.name', '-'),
                'checkpoint_description' => data_get($data, 'checkpoint.description', '-'),
                'image_url' => data_get($data, 'image_url', '-'),
                'questionnaires' => $questioner,
                'remark' => data_get($data, 'remark', '-'),
                'created_at_date' => Carbon::parse(data_get($data, 'created_at'))->format('Y-m-d'),
                'created_at_time' => Carbon::parse(data_get($data, 'created_at'))->format('H:i'),
            ];
        }

        return $modelMapping;
    }
}
