<?php

namespace App\Filament\Widgets;

use App\Repositories\JobStatusRepository;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\On;

/**
 * Reusable "report downloads" widget that polls the report microservice for
 * the current user's generated report files and renders them as a download
 * list. Each page provides its own heading and the job classes it should
 * track by extending this widget; role restrictions and extra lookup filters
 * can be added via the overridable hooks below.
 */
abstract class ReportDownloadWidget extends Widget
{
    /**
     * Dispatched by the table an export is started from, so the list picks the
     * new job up immediately instead of waiting for the next poll.
     */
    public const EXPORT_STARTED_EVENT = 'report-export-started';

    protected string $view = 'filament.widgets.report-download-widget';

    protected int|string|array $columnSpan = 'full';

    /**
     * Fully-qualified microservice job class names whose statuses this widget
     * should list (e.g. the XLSX and PDF export jobs for a report).
     *
     * @return array<int, string>
     */
    abstract protected function jobTypes(): array;

    /**
     * The microservice project the reports belong to.
     */
    protected function projectName(): string
    {
        return 'SGOC';
    }

    /**
     * Section heading shown above the list. Null renders no heading.
     */
    public function heading(): ?string
    {
        return null;
    }

    /**
     * Optional explanatory line under the heading.
     */
    public function description(): ?string
    {
        return null;
    }

    /**
     * Extra parameters merged into the job-status lookup (e.g.
     * ['attendance_type' => 'GTL']).
     *
     * @return array<string, mixed>
     */
    protected function additionalFilters(): array
    {
        return [];
    }

    /**
     * Roles allowed to view this widget. Return null for no restriction.
     *
     * @return array<int, string>|null
     */
    protected function allowedRoles(): ?array
    {
        return null;
    }

    public static function canView(): bool
    {
        return Route::currentRouteName() !== 'filament.admin.pages.dashboard';
    }

    public function mount(): void
    {
        $roles = $this->allowedRoles();

        if ($roles !== null) {
            abort_unless(Auth::user()->hasRole($roles), 403);
        }
    }

    public function getData()
    {
        return (new JobStatusRepository)->index(array_merge([
            'project_name' => $this->projectName(),
            'user_id' => Auth::id(),
            'type' => $this->jobTypes(),
        ], $this->additionalFilters()));
    }

    #[On(self::EXPORT_STARTED_EVENT)]
    public function refreshJobs(): void
    {
        // Re-render so a just-queued export appears and polling starts.
    }

    /**
     * Whether any listed job is still running. The view only polls while this
     * is true, so an idle page does not keep calling the microservice.
     *
     * @param  array<string, mixed>  $response
     */
    public function hasPendingJobs(array $response): bool
    {
        if (($response['message'] ?? null) !== 'Success') {
            return false;
        }

        return collect($response['data'])
            ->contains(fn (array $job): bool => in_array($job['status'], ['queued', 'executing', 'retrying'], true));
    }
}
