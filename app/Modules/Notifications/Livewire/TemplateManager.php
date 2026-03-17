<?php

namespace App\Modules\Notifications\Livewire;

use App\Mail\NotificationTemplatePreviewMail;
use App\Models\NotificationTemplate;
use App\Modules\Notifications\Livewire\Concerns\InteractsWithNotificationAuthorization;
use App\Modules\Notifications\Support\NotificationTemplateRenderer;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class TemplateManager extends Component
{
    use InteractsWithNotificationAuthorization;

    public ?int $editingId = null;

    public string $search = '';

    public string $testEmail = '';

    public ?string $testStatus = null;

    public array $form = [
        'key' => '',
        'category' => 'birthday',
        'channel' => 'database',
        'format' => 'text',
        'subject_template' => '',
        'body_template' => '',
        'is_active' => true,
    ];

    public function mount(): void
    {
        $this->authorizeNotificationSettingsView();
    }

    protected function rules(): array
    {
        $uniqueRule = 'unique:notification_templates,key';

        if ($this->editingId) {
            $uniqueRule .= ','.$this->editingId;
        }

        return [
            'form.key' => ['required', 'string', 'max:255', $uniqueRule],
            'form.category' => ['required', 'string', 'max:100'],
            'form.channel' => ['required', 'string', 'in:database,mail'],
            'form.format' => ['required', 'string', 'in:text,html'],
            'form.subject_template' => ['nullable', 'string', 'max:255'],
            'form.body_template' => ['required', 'string'],
            'form.is_active' => ['required', 'boolean'],
            'testEmail' => ['nullable', 'email:rfc', 'max:255'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.key' => __('notifications::common.fields.key'),
            'form.category' => __('notifications::common.fields.category'),
            'form.channel' => __('notifications::common.fields.channel'),
            'form.format' => __('notifications::common.fields.format'),
            'form.subject_template' => __('notifications::common.fields.subject'),
            'form.body_template' => __('notifications::common.fields.body'),
            'testEmail' => __('notifications::common.fields.test_email'),
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => __('notifications::common.validation.required'),
            'string' => __('notifications::common.validation.string'),
            'max' => __('notifications::common.validation.max'),
            'in' => __('notifications::common.validation.in'),
            'unique' => __('notifications::common.validation.unique', ['attribute' => ':attribute']),
            'email' => __('notifications::common.validation.email', ['attribute' => ':attribute']),
        ];
    }

    public function save(): void
    {
        $this->authorizeTemplateManagement();
        $validated = $this->validate()['form'];

        $payload = [
            'key' => trim($validated['key']),
            'category' => $validated['category'],
            'channel' => $validated['channel'],
            'format' => $validated['format'],
            'subject_template' => filled($validated['subject_template']) ? trim($validated['subject_template']) : null,
            'body_template' => trim($validated['body_template']),
            'is_active' => (bool) $validated['is_active'],
            'updated_by' => auth()->id(),
        ];

        if ($this->editingId) {
            NotificationTemplate::query()->findOrFail($this->editingId)->update($payload);
        } else {
            $payload['created_by'] = auth()->id();
            NotificationTemplate::query()->create($payload);
        }

        $this->resetForm();
        $this->dispatch('notification-template-changed');
        $this->dispatch('notify', type: 'success', message: __('notifications::common.messages.template_saved'));
    }

    public function sendTest(): void
    {
        $this->authorizeTemplateManagement();
        $validated = $this->validate([
            'testEmail' => ['required', 'email:rfc', 'max:255'],
            'form.category' => ['required', 'string', 'max:100'],
            'form.format' => ['required', 'string', 'in:text,html'],
            'form.subject_template' => ['nullable', 'string', 'max:255'],
            'form.body_template' => ['required', 'string'],
        ], $this->messages(), $this->validationAttributes());

        $payload = $this->samplePayload();
        $renderer = app(NotificationTemplateRenderer::class);
        $subject = $renderer->render((string) ($validated['form']['subject_template'] ?? ''), $payload);
        $body = $renderer->render((string) $validated['form']['body_template'], $payload);

        Mail::to($validated['testEmail'])->send(new NotificationTemplatePreviewMail(
            subjectLine: $subject !== '' ? $subject : __('notifications::common.mail.subject_notification'),
            body: $body,
            isHtml: $validated['form']['format'] === 'html',
        ));

        $this->testStatus = __('notifications::common.helpers.test_email_sent', ['email' => $validated['testEmail']]);
        $this->dispatch('notify', type: 'success', message: $this->testStatus);
    }

    public function edit(int $id): void
    {
        $this->authorizeTemplateManagement();
        $template = NotificationTemplate::query()->findOrFail($id);

        $this->editingId = $template->id;
        $this->form = [
            'key' => $template->key,
            'category' => $template->category,
            'channel' => $template->channel,
            'format' => $template->format,
            'subject_template' => $template->subject_template ?? '',
            'body_template' => $template->body_template,
            'is_active' => (bool) $template->is_active,
        ];
    }

    public function delete(int $id): void
    {
        $this->authorizeTemplateManagement();
        NotificationTemplate::query()->whereKey($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        $this->dispatch('notification-template-changed');
    }

    public function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->testStatus = null;
        $this->form = [
            'key' => '',
            'category' => 'birthday',
            'channel' => 'database',
            'format' => 'text',
            'subject_template' => '',
            'body_template' => '',
            'is_active' => true,
        ];
    }

    public function render()
    {
        $templates = NotificationTemplate::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($inner) {
                    $inner->where('key', 'like', '%'.$this->search.'%')
                        ->orWhere('category', 'like', '%'.$this->search.'%')
                        ->orWhere('channel', 'like', '%'.$this->search.'%');
                });
            })
            ->latest('id')
            ->limit(12)
            ->get();

        $previewPayload = $this->samplePayload();
        $renderer = app(NotificationTemplateRenderer::class);
        $previewSubject = $renderer->render($this->form['subject_template'] ?: '', $previewPayload);
        $previewBody = $renderer->render($this->form['body_template'] ?: '', $previewPayload);

        return view('notification::livewire.notification.template-manager', [
            'templates' => $templates,
            'canManageTemplates' => $this->canManageTemplates(),
            'categories' => ['birthday', 'position_change', 'holiday', 'announcement', 'training_result', 'leave_status'],
            'categoryLabels' => $this->categoryLabels(),
            'previewSubject' => $previewSubject,
            'previewBody' => $previewBody,
            'previewPayload' => $previewPayload,
            'availableVariables' => $this->availableVariables(),
        ]);
    }

    public function placeholder()
    {
        return view('notification::livewire.notification.placeholders.settings-panel');
    }

    protected function samplePayload(): array
    {
        return match ($this->form['category']) {
            'birthday' => [
                'name' => 'Murad Əliyev',
                'position' => 'Baş məsləhətçi',
                'structure' => 'İnsan resursları şöbəsi',
                'birthday_label' => '16.03.2026',
            ],
            'position_change' => [
                'name' => 'Leyla Məmmədova',
                'old_position' => 'Məsləhətçi',
                'new_position' => 'Aparıcı məsləhətçi',
                'old_structure' => 'Maliyyə şöbəsi',
                'new_structure' => 'İnsan resursları şöbəsi',
                'change_reason' => 'Daxili rotasiya',
                'effective_date' => now()->format('d.m.Y'),
            ],
            'holiday' => [
                'holiday_name' => 'Novruz bayramı',
                'holiday_date' => '20.03.2026',
                'duration' => '3 gün',
                'scope' => 'Bütün əməkdaşlar',
                'holiday_rules' => 'Rəsmi qeyri-iş günləri',
            ],
            'announcement' => [
                'title' => 'Daxili elan',
                'name' => 'Daxili elan',
                'body' => 'Bu gün saat 18:00-da sistem yenilənməsi olacaq.',
                'message' => 'elan yayımlandı',
            ],
            default => [
                'name' => 'Nümunə istifadəçi',
                'message' => 'Nümunə bildiriş mətni',
            ],
        };
    }

    protected function categoryLabels(): array
    {
        return [
            'birthday' => __('notifications::common.categories.birthday'),
            'position_change' => __('notifications::common.categories.position_change'),
            'holiday' => __('notifications::common.categories.holiday'),
            'announcement' => __('notifications::common.categories.announcement'),
            'training_result' => __('notifications::common.categories.training_result'),
            'leave_status' => __('notifications::common.categories.leave_status'),
        ];
    }

    protected function availableVariables(): array
    {
        return match ($this->form['category']) {
            'birthday' => [
                ['token' => '{{ name }}', 'description' => __('notifications::common.variables.name')],
                ['token' => '{{ position }}', 'description' => __('notifications::common.variables.position')],
                ['token' => '{{ structure }}', 'description' => __('notifications::common.variables.structure')],
                ['token' => '{{ birthday_label }}', 'description' => __('notifications::common.variables.birthday_label')],
            ],
            'position_change' => [
                ['token' => '{{ name }}', 'description' => __('notifications::common.variables.name')],
                ['token' => '{{ old_position }}', 'description' => __('notifications::common.variables.old_position')],
                ['token' => '{{ new_position }}', 'description' => __('notifications::common.variables.new_position')],
                ['token' => '{{ old_structure }}', 'description' => __('notifications::common.variables.old_structure')],
                ['token' => '{{ new_structure }}', 'description' => __('notifications::common.variables.new_structure')],
                ['token' => '{{ change_reason }}', 'description' => __('notifications::common.variables.change_reason')],
                ['token' => '{{ effective_date }}', 'description' => __('notifications::common.variables.effective_date')],
            ],
            'holiday' => [
                ['token' => '{{ holiday_name }}', 'description' => __('notifications::common.variables.holiday_name')],
                ['token' => '{{ holiday_date }}', 'description' => __('notifications::common.variables.holiday_date')],
                ['token' => '{{ duration }}', 'description' => __('notifications::common.variables.duration')],
                ['token' => '{{ scope }}', 'description' => __('notifications::common.variables.scope')],
                ['token' => '{{ holiday_rules }}', 'description' => __('notifications::common.variables.holiday_rules')],
            ],
            'announcement' => [
                ['token' => '{{ title }}', 'description' => __('notifications::common.variables.title')],
                ['token' => '{{ body }}', 'description' => __('notifications::common.variables.body')],
                ['token' => '{{ message }}', 'description' => __('notifications::common.variables.message')],
            ],
            default => [
                ['token' => '{{ name }}', 'description' => __('notifications::common.variables.name')],
                ['token' => '{{ message }}', 'description' => __('notifications::common.variables.message')],
            ],
        };
    }
}
