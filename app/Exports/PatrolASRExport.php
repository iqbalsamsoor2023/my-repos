<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use App\Models\AutoSendReport;
use App\Models\CheckpointLog;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PatrolASRExport implements FromCollection, WithHeadings
{
    protected AutoSendReport $autoSendReport;

    protected Carbon $datetime;

    public function __construct(AutoSendReport $autoSendReport, ?Carbon $datetime = null)
    {
        $this->autoSendReport = $autoSendReport;
        $this->datetime = $datetime;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Mooban',
            'Security Guard Staff Name',
            'Checkpoint Name',
            'Description',
            'Questionnaire',
            'Remark',
            'Image',
            'Created At (Date)',
            'Created At (Time)',
        ];
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $residence = $this->autoSendReport->residence;
        $hour = $this->autoSendReport->hour;

        if ($this->datetime == null) {
            $time = new Carbon($this->autoSendReport->time);
        } else {
            $time = $this->datetime;
        }

        $subtime = $time->copy()->sub("$hour hours");

        $patrolCheckpoints = CheckpointLog::with('checkpoint', 'user')->whereHas('checkpoint', function ($query) use ($residence) {
            $query->where('mmb_residence_id', $residence->id);
        })
            ->where('created_at', '>=', $subtime)
            ->latest('id')
            ->get();

        $collection = collect();
        foreach ($patrolCheckpoints as $key => $patrolCheckpoint) {
            if (! empty($patrolCheckpoint->questionnaires)) {
                $qna = '';
                foreach ($patrolCheckpoint->questionnaires as $questionnaire) {
                    if (! empty($qna)) {
                        $qna = $qna.', '.(__('QUESTION :').$questionnaire['question'].'  '.__('ANSWER :').$questionnaire['answer']);
                    } else {
                        $qna = __('QUESTION :').$questionnaire['question'].'  '.__('ANSWER :').$questionnaire['answer'];
                    }
                }
            } else {
                $qna = '-';
            }

            $row = collect([
                'id' => $patrolCheckpoint->id,
                'mooban' => $residence->name,
                'security_guard_staff_name' => $patrolCheckpoint->user->name,
                'checkpoint_name' => $patrolCheckpoint->checkpoint->name,
                'description' => $patrolCheckpoint->checkpoint ? $patrolCheckpoint->checkpoint->description : '-',
                'questionnaire' => $qna,
                'remark' => $patrolCheckpoint->remark,
                'images' => $patrolCheckpoint->image_url,
                'created_at_date' => $patrolCheckpoint->created_at->format('d/m/Y'),
                'created_at_time' => $patrolCheckpoint->created_at->format('H:i'),
            ]);
            $collection->push($row);
        }

        return $collection;
    }
}
