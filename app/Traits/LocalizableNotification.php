<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;

trait LocalizableNotification
{
    /**
     * Get localized notification data based on user's language preference
     *
     * @param  array  $notificationData
     * @param  string  $locale
     * @return array
     */
    public function getLocalizedNotificationData($notificationData, $locale = null)
    {
        if (! $locale) {
            $locale = App::getLocale();
        }

        $localizedData = $notificationData;

        // If localized title key exists, translate it
        if (isset($notificationData['localized_title_key'])) {
            $titleParams = isset($notificationData['title_params'])
                ? $notificationData['title_params']
                : [];

            $localizedData['message_title'] = $this->translateWithParams(
                $notificationData['localized_title_key'],
                $titleParams,
                $locale
            );
        }

        // If localized body key exists, translate it
        if (isset($notificationData['localized_body_key'])) {
            $bodyParams = isset($notificationData['body_params'])
                ? $notificationData['body_params']
                : [];

            $localizedData['message_body'] = $this->translateWithParams(
                $notificationData['localized_body_key'],
                $bodyParams,
                $locale
            );
        }

        return $localizedData;
    }

    /**
     * Translate a key with parameters, handling both English and Thai versions
     *
     * @param  string  $key
     * @param  array  $params
     * @param  string  $locale
     * @return string
     */
    private function translateWithParams($key, $params, $locale)
    {
        // Set the locale temporarily
        $originalLocale = App::getLocale();
        App::setLocale($locale);

        // Handle parameters that might have locale-specific versions
        $localizedParams = $this->localizeParams($params, $locale);

        // Translate the key with localized parameters
        $translation = __($key, $localizedParams);

        // Restore original locale
        App::setLocale($originalLocale);

        return $translation;
    }

    /**
     * Localize parameters based on the requested locale
     *
     * @param  array  $params
     * @param  string  $locale
     * @return array
     */
    private function localizeParams($params, $locale)
    {
        $localizedParams = [];

        foreach ($params as $key => $value) {
            // Check if parameter has locale-specific versions
            if (is_array($value)) {
                $localizedParams[$key] = $value;
            } elseif (str_contains($key, '_en') && $locale !== 'en') {
                // Skip English version if not requesting English
                $baseKey = str_replace('_en', '', $key);
                $thKey = $baseKey.'_th';
                if (isset($params[$thKey]) && $locale === 'th') {
                    $localizedParams[$baseKey] = $params[$thKey];
                } else {
                    $localizedParams[$baseKey] = $value; // Fallback to English
                }
            } elseif (str_contains($key, '_th') && $locale !== 'th') {
                // Skip Thai version if not requesting Thai
                $baseKey = str_replace('_th', '', $key);
                $enKey = $baseKey.'_en';
                if (isset($params[$enKey])) {
                    $localizedParams[$baseKey] = $params[$enKey];
                } else {
                    $localizedParams[$baseKey] = $value; // Fallback to Thai
                }
            } elseif (str_contains($key, '_en') && $locale === 'en') {
                // Use English version
                $baseKey = str_replace('_en', '', $key);
                $localizedParams[$baseKey] = $value;
            } elseif (str_contains($key, '_th') && $locale === 'th') {
                // Use Thai version
                $baseKey = str_replace('_th', '', $key);
                $localizedParams[$baseKey] = $value;
            } else {
                // Regular parameter without locale suffix
                $localizedParams[$key] = $value;
            }
        }

        return $localizedParams;
    }

    /**
     * Get user's preferred locale
     *
     * @param  mixed  $user
     * @return string
     */
    public function getUserLocale($user)
    {
        return $user->locale ?? App::getLocale();
    }
}
