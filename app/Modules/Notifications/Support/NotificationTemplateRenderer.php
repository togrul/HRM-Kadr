<?php

namespace App\Modules\Notifications\Support;

class NotificationTemplateRenderer
{
    public function render(?string $template, array $payload): string
    {
        $template ??= '';

        if ($template === '') {
            return '';
        }

        return preg_replace_callback('/{{\s*([a-zA-Z0-9_\.]+)\s*}}/', function ($matches) use ($payload) {
            $value = data_get($payload, $matches[1]);

            if (is_scalar($value)) {
                return (string) $value;
            }

            return '';
        }, $template) ?? $template;
    }
}
