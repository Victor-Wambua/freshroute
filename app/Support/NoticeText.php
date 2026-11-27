<?php

namespace App\Support;

class NoticeText
{
    /** Renders a stored notification in the given (or current) locale, translating embedded status keys. */
    public static function render(string $type, array $params = [], ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        if (isset($params['status'])) {
            $prefix = $type === 'delivery_update' ? 'ds_' : 'es_';
            $params['status'] = __('ui.'.$prefix.$params['status'], [], $locale);
        }

        return __('notice.'.$type, $params, $locale);
    }
}
