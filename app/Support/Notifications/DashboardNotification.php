<?php

namespace App\Support\Notifications;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\App;

/**
 * Builder for translatable PM dashboard (database) notifications.
 *
 * It owns the sender side of the contract shared with the custom
 * {@see \App\Filament\Livewire\DatabaseNotifications} component: the translation
 * keys and params are stored in `viewData` so the title/body (and any language-
 * specific values or action labels) are re-translated at render time in the
 * viewer's current locale — giving an EN/TH toggle on already-stored notifications.
 */
class DashboardNotification
{
    protected string $titleKey;

    protected ?string $bodyKey = null;

    /** @var array<string, mixed> */
    protected array $params = [];

    /** @var array<string, array{en: ?string, th: ?string}> */
    protected array $localizedParams = [];

    protected ?string $actionUrl = null;

    protected string $actionLabelKey = 'app.view';

    protected string|BackedEnum|null $icon = null;

    protected ?string $iconColor = null;

    public static function make(string $titleKey): static
    {
        $static = new static;
        $static->titleKey = $titleKey;

        return $static;
    }

    public function body(string $bodyKey): static
    {
        $this->bodyKey = $bodyKey;

        return $this;
    }

    /**
     * Language-neutral placeholders (name, unit, amount, date, ...).
     *
     * @param  array<string, mixed>  $params
     */
    public function params(array $params): static
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Placeholders whose value is itself language-specific and should toggle with
     * the viewer's locale, each given as ['en' => ..., 'th' => ...].
     *
     * @param  array<string, array{en: ?string, th: ?string}>  $localizedParams
     */
    public function localizedParams(array $localizedParams): static
    {
        $this->localizedParams = $localizedParams;

        return $this;
    }

    /**
     * Leading icon shown next to the notification, so the PM can tell the kind of
     * notification (maintenance, booking, parcel, ...) apart at a glance. Icons are
     * stored with the notification, so only newly sent ones pick up a change here.
     */
    public function icon(string|BackedEnum $icon, ?string $color = null): static
    {
        $this->icon = $icon;
        $this->iconColor = $color;

        return $this;
    }

    /**
     * Add a "View" button linking to the record's detail page.
     */
    public function viewAction(string $url, string $labelKey = 'app.view'): static
    {
        $this->actionUrl = $url;
        $this->actionLabelKey = $labelKey;

        return $this;
    }

    public function build(): Notification
    {
        $locale = App::getLocale();

        // Current-locale values for the stored (fallback) title/body. The reader
        // component re-resolves these on render, so this is only the frozen copy.
        $fallbackParams = $this->params;
        foreach ($this->localizedParams as $key => $variants) {
            $fallbackParams[$key] = $variants[$locale] ?? $variants['en'] ?? null;
        }

        $viewData = array_filter([
            'titleKey' => $this->titleKey,
            'bodyKey' => $this->bodyKey,
            'params' => $this->params,
            'localizedParams' => $this->localizedParams ?: null,
        ], fn ($value) => $value !== null);

        $notification = Notification::make()->title(__($this->titleKey, $fallbackParams));

        if ($this->icon) {
            $notification->icon($this->icon)->iconColor($this->iconColor);
        }

        if ($this->bodyKey) {
            $notification->body(__($this->bodyKey, $fallbackParams));
        }

        if ($this->actionUrl) {
            $viewData['actionLabels'] = ['view' => $this->actionLabelKey];
            $notification->actions([
                Action::make('view')
                    ->label(__($this->actionLabelKey))
                    ->button()
                    ->url($this->actionUrl)
                    ->markAsRead(),
            ]);
        }

        return $notification->viewData($viewData);
    }

    /**
     * @param  mixed  $users
     */
    public function sendToDatabase($users): void
    {
        $this->build()->sendToDatabase($users);
    }
}
