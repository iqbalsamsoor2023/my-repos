<?php

namespace App\Services\FilamentExport;

use App\Actions\Audit\CreateAuditAction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Livewire\Component;

class FilamentTableExportSupport
{
    /**
     * @return array<int, string>
     */
    public static function visibleColumnNames(Component $livewire): array
    {
        return collect($livewire->getTable()->getColumns())
            ->filter(fn ($column) => $column->isVisible())
            ->map(fn ($column) => $column->getName())
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public static function selectedRecordIds(Component $livewire): array
    {
        return $livewire->getSelectedTableRecords()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public static function excelFileName(?string $requestedFileName, string $fallback, bool $slugify = false): string
    {
        $baseName = trim((string) ($requestedFileName ?? ''));

        if ($baseName === '') {
            $baseName = $fallback;
        }

        if ($slugify) {
            $baseName = Str::slug($baseName);

            if ($baseName === '') {
                $baseName = Str::slug($fallback);
            }
        }

        return $baseName.'.xlsx';
    }

    public static function recordExportAudit(?object $user, string $description): void
    {
        if (! $user || ! isset($user->id)) {
            return;
        }

        $currentRequest = app(Request::class);
        $auditRequest = Request::create('/');

        $auditRequest->replace([
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'event' => 'exported',
            'old_values' => [],
            'new_values' => ['action' => $description],
            'url' => $currentRequest->fullUrl(),
            'ip_address' => $currentRequest->ip(),
            'user_agent' => $currentRequest->userAgent(),
        ]);

        $auditAction = new CreateAuditAction;
        $auditAction->execute($auditRequest);
    }
}
