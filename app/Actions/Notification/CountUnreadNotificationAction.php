<?php

namespace App\Actions\Notification;

use App\Models\Notification;
use App\Models\Unit;

class CountUnreadNotificationAction
{
    // need to organize all these and put inside repo, then call each of these function as new action file
    public function execute(int $user_id, ?int $unit_id, ?string $module)
    {
        $query = Notification::where('notifiable_id', $user_id);

        $data = [];

        if (isset($module)) {
            switch ($module) {
                case 'Notification':
                    $data['total_unread_notification'] = $this->totalUnreadNotification($query);
                    break;

                case 'Parcel':
                    $data['not_my_parcel'] = $this->totalUnreadNotMyParcel($query);
                    if (isset($unit_id)) {
                        $data['parcel'] = $this->totalUnreadParcel($query, $unit_id);
                    }
                    break;

                case 'Announcement':
                    $data['announcement'] = $this->totalUnreadAnnouncement($query);
                    if (isset($unit_id)) {
                        $data['announcement_event'] = $this->totalUnreadAnnouncementEvent($query, $unit_id);
                        $data['announcement_event_developer'] = $this->totalUnreadAnnouncementEventDeveloper($query, $unit_id);
                    }
                    break;

                case 'Event':
                    $data['event'] = $this->totalUnreadEvent($query);
                    break;

                case 'Maintenance':
                    if (isset($user_id) && ! isset($unit_id)) {
                        $data['maintenance_pm'] = $this->totalUnreadMaintenancePm($query);
                    }
                    if (isset($unit_id)) {
                        $data['maintenance'] = $this->totalUnreadMaintenance($query, $unit_id);
                    }
                    break;

                case 'Support Ticket':
                    if (isset($user_id) && ! isset($unit_id)) {
                        $data['support_ticket_pm'] = $this->totalUnreadSupportTicketPm($query);
                    }
                    if (isset($unit_id)) {
                        $data['support_ticket'] = $this->totalUnreadSupportTicket($query, $unit_id);
                    }
                    break;

                case 'Visitor':
                    if (isset($unit_id)) {
                        $data['visitor_record'] = $this->totalUnreadVisitorRecord($query, $unit_id);
                    }
                    break;
            }
        }

        // $data = [
        //     'total_unread_notification' => $this->totalUnreadNotification($query),
        //     'announcement' => $this->totalUnreadAnnouncement($query),
        //     'maintenance_pm' => $this->totalUnreadMaintenancePm($query),
        //     'event' => $this->totalUnreadEvent($query),
        //     'not_my_parcel' => $this->totalUnreadNotMyParcel($query),
        //     'support_ticket_pm' => $this->totalUnreadSupportTicketPm($query),
        // ];

        // if (isset($unit_id)) {
        //     $data['announcement_event'] = $this->totalUnreadAnnouncementEvent($query, $unit_id);
        //     $data['announcement_event_developer'] = $this->totalUnreadAnnouncementEventDeveloper($query, $unit_id);
        //     $data['maintenance'] = $this->totalUnreadMaintenance($query, $unit_id);
        //     $data['parcel'] = $this->totalUnreadParcel($query, $unit_id);
        //     $data['visitor_record'] = $this->totalUnreadVisitorRecord($query, $unit_id);
        //     $data['support_ticket'] = $this->totalUnreadSupportTicket($query, $unit_id);
        // }

        return $data;
    }

    private function totalUnreadNotification($query): int
    {
        return $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')->whereNull('read_at')->count();
    }

    private function totalUnreadAnnouncement($query): int
    {
        return $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->whereJsonContains('data->model_type', 'App\Models\Announcement')
            ->whereNull('read_at')
            ->count();
    }

    private function totalUnreadEvent($query): int
    {
        return $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->whereJsonContains('data->model_type', 'App\Models\Event')
            ->whereNull('read_at')
            ->count();
    }

    private function totalUnreadAnnouncementEvent($query, int $unit_id): int
    {
        $unit = Unit::whereId($unit_id)->first();

        $event = $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->whereJsonContains('data->model_type', 'App\Models\Event')
            ->whereJsonContains('data->created_by_role', 'Property Management')
            ->whereJsonContains('data->residence_id', $unit->residence_id)
            ->whereNull('read_at')
            ->count();

        $residence_announcement = $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->whereJsonContains('data->model_type', 'App\Models\Announcement')
            ->whereJsonContains('data->created_by_role', 'Property Management')
            ->whereJsonContains('data->residence_id', $unit->residence_id)
            ->whereNull('read_at')
            ->count();

        $announcement = $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->whereJsonContains('data->model_type', 'App\Models\Announcement')
            ->whereJsonContains('data->created_by_role', 'Property Management')
            ->whereJsonContains('data->unit_id', $unit_id)
            ->whereNull('read_at')
            ->count();

        return $event + $residence_announcement + $announcement;
    }

    private function totalUnreadAnnouncementEventDeveloper($query, int $unit_id): int
    {
        $unit = Unit::whereId($unit_id)->first();

        $event = $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->whereJsonContains('data->model_type', 'App\Models\Event')
            ->whereJsonContains('data->created_by_role', 'Developer')
            ->whereJsonContains('data->residence_id', $unit->residence_id)
            ->whereNull('read_at')
            ->count();

        $announcement = $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->whereJsonContains('data->model_type', 'App\Models\Announcement')
            ->whereJsonContains('data->created_by_role', 'Developer')
            ->whereJsonContains('data->residence_id', $unit->residence_id)
            ->whereNull('read_at')
            ->count();

        return $event + $announcement;
    }

    private function totalUnreadMaintenance($query, int $unit_id): int
    {
        return $query->clone()
            ->whereIn('type', ['App\Notifications\MaintenanceUpdated', 'App\Notifications\MaintenanceCreated'])
            ->where(function ($q) use ($unit_id) {
                $q->whereJsonContains('data', ['model_type' => 'App\Models\Maintenance'])
                    ->orWhereJsonContains('data', ['unit_id' => $unit_id]);
            })
            ->whereNull('read_at')
            ->count();
    }

    private function totalUnreadMaintenancePm($query)
    {
        return $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->whereJsonContains('data->model_type', 'App\Models\Maintenance')
            ->whereNull('read_at')
            ->count();
    }

    private function totalUnreadParcel($query, int $unit_id): int
    {
        return $query->clone()
            ->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->where('type', 'App\Notifications\ParcelArrived')
            ->where(function ($q) use ($unit_id) {
                $q->whereJsonContains('data', ['model_type' => 'App\Models\Parcel'])
                    ->whereJsonContains('data', ['unit_id' => $unit_id]);
            })
            ->whereNull('read_at')
            ->count();
    }

    private function totalUnreadNotMyParcel($query): int
    {
        return $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->where('type', 'App\Notifications\WrongParcel')
            ->whereJsonContains('data->model_type', 'App\Models\Parcel')
            ->whereNull('read_at')
            ->count();
    }

    private function totalUnreadVisitorRecord($query, int $unit_id): int
    {
        return $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->whereJsonContains('data->model_type', 'App\Models\VisitorLog')
            ->whereJsonContains('data->unit_id', $unit_id)
            ->whereNull('read_at')
            ->count();
    }

    private function totalUnreadSupportTicket($query, int $unit_id): int
    {
        $support_ticket = $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->whereJsonContains('data->model_type', 'App\Models\SupportTicket')
            ->whereJsonContains('data->unit_id', $unit_id)
            ->whereNull('read_at')
            ->count();

        $support_ticket_comment = $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->whereJsonContains('data->model_type', 'App\Models\Comment')
            ->where('type', 'App\Notifications\SupportTicketCommentPosted')
            ->whereJsonContains('data->unit_id', $unit_id)
            ->whereNull('read_at')
            ->count();

        return $support_ticket + $support_ticket_comment;
    }

    private function totalUnreadSupportTicketPm($query): int
    {
        $support_ticket = $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->whereJsonContains('data->model_type', 'App\Models\SupportTicket')
            ->whereNull('read_at')
            ->count();

        $support_ticket_comment = $query->clone()->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
            ->whereJsonContains('data->model_type', 'App\Models\Comment')
            ->where('type', 'App\Notifications\SupportTicketCommentPosted')
            ->whereNull('read_at')
            ->count();

        return $support_ticket + $support_ticket_comment;
    }
}
