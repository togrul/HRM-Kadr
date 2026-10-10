<?php

namespace App\Modules\Notifications\Support;

class NotificationTemplateRenderer
{
    /**
     * Şablondakı {{ açar }} yer tutucularını payload dəyərləri ilə əvəz edir.
     * HTML formatlı məktublarda dəyərlər (işçi adı və s.) escape olunur ki, şablona HTML yeridilməsin.
     */
    public function render(?string $template, array $payload, bool $escapeHtml = false): string
    {
        $template ??= '';

        if ($template === '') {
            return '';
        }

        return preg_replace_callback('/{{\s*([a-zA-Z0-9_\.]+)\s*}}/', function ($matches) use ($payload, $escapeHtml) {
            $value = data_get($payload, $matches[1]);

            if (is_scalar($value)) {
                return $escapeHtml ? e((string) $value) : (string) $value;
            }

            return '';
        }, $template) ?? $template;
    }
}
