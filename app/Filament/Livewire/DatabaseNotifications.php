<?php

namespace App\Filament\Livewire;

use Filament\Actions\Action;
use Filament\Livewire\DatabaseNotifications as BaseDatabaseNotifications;
use Filament\Notifications\Notification;
use Illuminate\Notifications\DatabaseNotification;

class DatabaseNotifications extends BaseDatabaseNotifications
{
    /**
     * Re-translate the notification title/body at render time using the
     * viewer's current locale, so switching the dashboard language flips
     * already-stored notifications between English and Thai.
     *
     * Besides the fixed sentence (translation keys), dynamic values that are
     * themselves language-specific (e.g. a maintenance category or facility
     * name) can be stored under `localizedParams` as `['en' => ..., 'th' => ...]`
     * and are resolved to the viewer's locale here too.
     *
     * Action button labels are stored (frozen) in the database, so they are
     * re-translated here from `actionLabels` (a map of action name => translation
     * key) to keep them in sync with the viewer's locale.
     */
    public function getNotification(DatabaseNotification $notification): Notification
    {
        $filamentNotification = parent::getNotification($notification);

        $viewData = $notification->data['viewData'] ?? [];
        $params = $viewData['params'] ?? [];

        $locale = app()->getLocale();

        foreach (($viewData['localizedParams'] ?? []) as $key => $variants) {
            $params[$key] = $variants[$locale] ?? $variants['en'] ?? null;
        }

        if (! empty($viewData['titleKey'])) {
            $filamentNotification->title(__($viewData['titleKey'], $params));
        }

        if (! empty($viewData['bodyKey'])) {
            $filamentNotification->body(__($viewData['bodyKey'], $params));
        }

        $actionLabels = $viewData['actionLabels'] ?? [];

        if ($actionLabels) {
            foreach ($filamentNotification->getActions() as $action) {
                if ($action instanceof Action && isset($actionLabels[$action->getName()])) {
                    $action->label(__($actionLabels[$action->getName()]));
                }
            }
        }

        return $filamentNotification;
    }
}
