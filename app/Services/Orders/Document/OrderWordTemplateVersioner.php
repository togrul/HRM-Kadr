<?php

namespace App\Services\Orders\Document;

use App\Models\OrderWordTemplate;
use Illuminate\Support\Facades\Storage;

/**
 * Archives a Word template's current master before it is overwritten by a re-upload, so
 * the previous document + its variable mapping stay available as version history.
 */
class OrderWordTemplateVersioner
{
    public function archive(OrderWordTemplate $template): void
    {
        if (! $template->docx_path || ! Storage::disk('local')->exists($template->docx_path)) {
            return;
        }

        $version = (int) $template->versions()->max('version') + 1;
        $versionedPath = 'order-templates/versions/'.$template->code.'-v'.$version.'.docx';

        Storage::disk('local')->put($versionedPath, Storage::disk('local')->get($template->docx_path));

        $template->versions()->create([
            'version' => $version,
            'label' => $template->label,
            'effect' => $template->effect ?? 'none',
            'docx_path' => $versionedPath,
            'variables' => $template->variables,
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);
    }
}
