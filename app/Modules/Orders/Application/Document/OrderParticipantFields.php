<?php

namespace App\Modules\Orders\Application\Document;

use App\Models\OrderWordTemplate;

/**
 * The field values that hold for one participant of a multi-participant order: the order's
 * shared values, each overridable one replaced by the participant's own value when they have
 * one, plus their per-participant values. The same merge feeds the document row, the period
 * checks and the approval effect, so all three see the same dates for the same person.
 */
final class OrderParticipantFields
{
    /**
     * Key put into an effect's fields when it runs for one participant of a multi-participant
     * order (1-based position) — lets an effect keep per-person keys apart (e.g. a payroll line).
     */
    public const EFFECT_CONTEXT = '_participant';

    /**
     * @param  array<string,mixed>  $orderFields  manual field key => value (order level)
     * @param  array<string,mixed>  $own  the participant's own field values
     * @return array<string,mixed>
     */
    public static function effective(OrderWordTemplate $template, array $orderFields, array $own): array
    {
        $fields = $orderFields;

        foreach ($template->participantFields() as $field) {
            $key = $field['key'];
            $value = $own[$key] ?? null;
            $filled = $value !== null && ! is_array($value) && trim((string) $value) !== '';

            if ($field['scope'] === OrderWordTemplate::SCOPE_PARTICIPANT) {
                $fields[$key] = $filled ? $value : '';
            } elseif ($filled) {
                $fields[$key] = $value;
            }
        }

        return $fields;
    }

    /**
     * Keep only the participant's values for fields the template lets them set, dropping
     * blank overrides (a blank override means "same as the order").
     *
     * @param  array<string,mixed>  $own
     * @return array<string,mixed>
     */
    public static function clean(OrderWordTemplate $template, array $own): array
    {
        $clean = [];
        foreach ($template->participantFields() as $field) {
            $value = $own[$field['key']] ?? null;
            if ($value !== null && ! is_array($value) && trim((string) $value) !== '') {
                $clean[$field['key']] = $value;
            }
        }

        return $clean;
    }
}
