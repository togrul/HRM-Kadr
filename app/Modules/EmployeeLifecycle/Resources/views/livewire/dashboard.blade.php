<div class="space-y-6 px-6 py-6">
    <section class="rounded-[28px] border border-zinc-200 bg-zinc-50 p-6 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="space-y-2">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.kicker') }}</x-ui.field-label>
                <h1 class="text-3xl font-semibold tracking-tight text-zinc-950">{{ __('employee-lifecycle::dashboard.title') }}</h1>
                <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('employee-lifecycle::dashboard.description') }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            @foreach (['active_templates', 'active_events', 'overdue_tasks', 'probation_queue', 'movement_queue', 'offboarding_queue'] as $metric)
                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-4">
                    <x-ui.field-label as="div" class="tracking-tight">{{ __('employee-lifecycle::dashboard.summary.'.$metric) }}</x-ui.field-label>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">{{ $summary[$metric] ?? 0 }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <x-ui.filter-panel>
            <x-ui.filter-field :label="__('employee-lifecycle::dashboard.fields.search')">
                <x-ui.filter-input wire:model.live.debounce.300ms="search" placeholder="{{ __('employee-lifecycle::dashboard.placeholders.search') }}" />
            </x-ui.filter-field>
            <x-ui.filter-field :label="__('employee-lifecycle::dashboard.fields.type')">
                <x-ui.filter-native-select wire:model.live="type">
                    <option value="">{{ __('employee-lifecycle::dashboard.filters.all_types') }}</option>
                    @foreach (['onboarding', 'probation', 'movement', 'offboarding', 'profile_change'] as $option)
                        <option value="{{ $option }}">{{ __('employee-lifecycle::dashboard.types.'.$option) }}</option>
                    @endforeach
                </x-ui.filter-native-select>
            </x-ui.filter-field>
            <x-ui.filter-field :label="__('employee-lifecycle::dashboard.fields.status')">
                <x-ui.filter-native-select wire:model.live="status">
                    <option value="">{{ __('employee-lifecycle::dashboard.filters.all_statuses') }}</option>
                    @foreach (['planned', 'in_progress', 'blocked', 'completed', 'cancelled'] as $option)
                        <option value="{{ $option }}">{{ __('employee-lifecycle::dashboard.statuses.'.$option) }}</option>
                    @endforeach
                </x-ui.filter-native-select>
            </x-ui.filter-field>
            <div class="flex items-end">
                <x-ui.filter-reset-button wire:click="resetFilters" :label="__('employee-lifecycle::dashboard.actions.reset_filters')">
                    {{ __('employee-lifecycle::dashboard.actions.reset_short') }}
                </x-ui.filter-reset-button>
            </div>
    </x-ui.filter-panel>

    @can('manage-employee-lifecycle')
        <section class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-2 border-b border-zinc-100 pb-5">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.labels.management_center') }}</x-ui.field-label>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('employee-lifecycle::dashboard.labels.manual_actions') }}</h2>
                <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('employee-lifecycle::dashboard.labels.manual_actions_note') }}</p>
            </div>

            <div class="mt-6 grid gap-5 xl:grid-cols-2">
                <form wire:submit="createTemplate" class="rounded-[24px] border border-zinc-200 bg-zinc-50/70 p-5">
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.forms.template') }}</x-ui.field-label>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.template_name')" :error="$errors->first('templateForm.name')">
                            <input wire:model.defer="templateForm.name" type="text" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.type')" :error="$errors->first('templateForm.type')">
                            <select wire:model.defer="templateForm.type" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950">
                                @foreach (['onboarding', 'probation', 'movement', 'offboarding'] as $option)
                                    <option value="{{ $option }}">{{ __('employee-lifecycle::dashboard.types.'.$option) }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.default_duration_days')" :error="$errors->first('templateForm.default_duration_days')">
                            <input wire:model.defer="templateForm.default_duration_days" type="number" min="1" max="365" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.description')" :error="$errors->first('templateForm.description')">
                            <input wire:model.defer="templateForm.description" type="text" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950" />
                        </x-ui.input-shell>
                        <x-ui.input-shell class="sm:col-span-2" :label="__('employee-lifecycle::dashboard.fields.task_lines')" :error="$errors->first('templateForm.tasks')">
                            <textarea wire:model.defer="templateForm.tasks" rows="4" class="w-full rounded-2xl border-0 bg-white px-4 py-3 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950"></textarea>
                        </x-ui.input-shell>
                    </div>
                    <button type="submit" class="mt-4 inline-flex h-12 items-center rounded-2xl bg-zinc-950 px-5 text-sm font-semibold text-white shadow-[0_18px_35px_-20px_rgba(24,24,27,0.85)]">{{ __('employee-lifecycle::dashboard.actions.create_template') }}</button>
                </form>

                <form wire:submit="launchTemplate" class="rounded-[24px] border border-zinc-200 bg-zinc-50/70 p-5">
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.forms.launch_plan') }}</x-ui.field-label>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.template')" :error="$errors->first('launchForm.template_id')">
                            <select wire:model.defer="launchForm.template_id" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950">
                                <option value="">---</option>
                                @foreach ($planTemplates as $template)
                                    <option value="{{ $template['id'] }}">{{ $template['name'] }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.personnel')" :error="$errors->first('launchForm.personnel_id')">
                            <select wire:model.defer="launchForm.personnel_id" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950">
                                <option value="">---</option>
                                @foreach ($personnelOptions as $personnel)
                                    <option value="{{ $personnel['id'] }}">{{ $personnel['label'] }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.start_date')" :error="$errors->first('launchForm.start_date')">
                            <input wire:model.defer="launchForm.start_date" type="date" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.owner')" :error="$errors->first('launchForm.owner_user_id')">
                            <select wire:model.defer="launchForm.owner_user_id" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950">
                                <option value="">---</option>
                                @foreach ($userOptions as $user)
                                    <option value="{{ $user['id'] }}">{{ $user['label'] }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                    </div>
                    <button type="submit" class="mt-4 inline-flex h-12 items-center rounded-2xl bg-zinc-950 px-5 text-sm font-semibold text-white shadow-[0_18px_35px_-20px_rgba(24,24,27,0.85)]">{{ __('employee-lifecycle::dashboard.actions.launch_plan') }}</button>
                </form>

                <form wire:submit="scheduleProbation" class="rounded-[24px] border border-zinc-200 bg-zinc-50/70 p-5">
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.forms.probation') }}</x-ui.field-label>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.personnel')" :error="$errors->first('probationForm.personnel_id')">
                            <select wire:model.defer="probationForm.personnel_id" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950">
                                <option value="">---</option>
                                @foreach ($personnelOptions as $personnel)
                                    <option value="{{ $personnel['id'] }}">{{ $personnel['label'] }}</option>
                                @endforeach
                            </select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.review_due_at')" :error="$errors->first('probationForm.review_due_at')">
                            <input wire:model.defer="probationForm.review_due_at" type="date" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.manager')" :error="$errors->first('probationForm.manager_user_id')">
                            <select wire:model.defer="probationForm.manager_user_id" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950"><option value="">---</option>@foreach ($userOptions as $user)<option value="{{ $user['id'] }}">{{ $user['label'] }}</option>@endforeach</select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.hr_reviewer')" :error="$errors->first('probationForm.hr_reviewer_user_id')">
                            <select wire:model.defer="probationForm.hr_reviewer_user_id" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950"><option value="">---</option>@foreach ($userOptions as $user)<option value="{{ $user['id'] }}">{{ $user['label'] }}</option>@endforeach</select>
                        </x-ui.input-shell>
                    </div>
                    <button type="submit" class="mt-4 inline-flex h-12 items-center rounded-2xl bg-zinc-950 px-5 text-sm font-semibold text-white shadow-[0_18px_35px_-20px_rgba(24,24,27,0.85)]">{{ __('employee-lifecycle::dashboard.actions.schedule_probation') }}</button>
                </form>

                <form wire:submit="scheduleMovement" class="rounded-[24px] border border-zinc-200 bg-zinc-50/70 p-5">
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.forms.movement') }}</x-ui.field-label>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.personnel')" :error="$errors->first('movementForm.personnel_id')">
                            <select wire:model.defer="movementForm.personnel_id" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950"><option value="">---</option>@foreach ($personnelOptions as $personnel)<option value="{{ $personnel['id'] }}">{{ $personnel['label'] }}</option>@endforeach</select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.movement_type')" :error="$errors->first('movementForm.movement_type')">
                            <select wire:model.defer="movementForm.movement_type" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950">@foreach (['transfer', 'promotion', 'role_change'] as $option)<option value="{{ $option }}">{{ __('employee-lifecycle::dashboard.movement_types.'.$option) }}</option>@endforeach</select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.target_structure')" :error="$errors->first('movementForm.target_structure_id')">
                            <select wire:model.defer="movementForm.target_structure_id" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950"><option value="">---</option>@foreach ($structureOptions as $structure)<option value="{{ $structure['id'] }}">{{ $structure['label'] }}</option>@endforeach</select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.target_position')" :error="$errors->first('movementForm.target_position_id')">
                            <select wire:model.defer="movementForm.target_position_id" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950"><option value="">---</option>@foreach ($positionOptions as $position)<option value="{{ $position['id'] }}">{{ $position['label'] }}</option>@endforeach</select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.effective_date')" :error="$errors->first('movementForm.effective_date')">
                            <input wire:model.defer="movementForm.effective_date" type="date" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.owner')" :error="$errors->first('movementForm.owner_user_id')">
                            <select wire:model.defer="movementForm.owner_user_id" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950"><option value="">---</option>@foreach ($userOptions as $user)<option value="{{ $user['id'] }}">{{ $user['label'] }}</option>@endforeach</select>
                        </x-ui.input-shell>
                        <x-ui.input-shell class="sm:col-span-2" :label="__('employee-lifecycle::dashboard.fields.reason')" :error="$errors->first('movementForm.reason')">
                            <textarea wire:model.defer="movementForm.reason" rows="2" class="w-full rounded-2xl border-0 bg-white px-4 py-3 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950"></textarea>
                        </x-ui.input-shell>
                    </div>
                    <button type="submit" class="mt-4 inline-flex h-12 items-center rounded-2xl bg-zinc-950 px-5 text-sm font-semibold text-white shadow-[0_18px_35px_-20px_rgba(24,24,27,0.85)]">{{ __('employee-lifecycle::dashboard.actions.schedule_movement') }}</button>
                </form>

                <form wire:submit="openOffboarding" class="rounded-[24px] border border-zinc-200 bg-zinc-50/70 p-5">
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.forms.offboarding') }}</x-ui.field-label>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.personnel')" :error="$errors->first('offboardingForm.personnel_id')">
                            <select wire:model.defer="offboardingForm.personnel_id" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950"><option value="">---</option>@foreach ($personnelOptions as $personnel)<option value="{{ $personnel['id'] }}">{{ $personnel['label'] }}</option>@endforeach</select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.last_working_date')" :error="$errors->first('offboardingForm.last_working_date')">
                            <input wire:model.defer="offboardingForm.last_working_date" type="date" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.owner')" :error="$errors->first('offboardingForm.owner_user_id')">
                            <select wire:model.defer="offboardingForm.owner_user_id" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950"><option value="">---</option>@foreach ($userOptions as $user)<option value="{{ $user['id'] }}">{{ $user['label'] }}</option>@endforeach</select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.reason')" :error="$errors->first('offboardingForm.reason')">
                            <input wire:model.defer="offboardingForm.reason" type="text" class="h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950" />
                        </x-ui.input-shell>
                    </div>
                    <button type="submit" class="mt-4 inline-flex h-12 items-center rounded-2xl bg-zinc-950 px-5 text-sm font-semibold text-white shadow-[0_18px_35px_-20px_rgba(24,24,27,0.85)]">{{ __('employee-lifecycle::dashboard.actions.open_offboarding') }}</button>
                </form>

                <div class="rounded-[24px] border border-zinc-200 bg-zinc-50/70 p-5 xl:col-span-2">
                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.forms.completion') }}</x-ui.field-label>
                    <div class="mt-4 grid gap-4 lg:grid-cols-3">
                        <form wire:submit="completeProbationReview" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-zinc-200">
                            <select wire:model.defer="completionForm.probation_review_id" class="h-12 w-full rounded-2xl border-0 bg-zinc-50 px-4 text-sm font-semibold text-zinc-950 ring-1 ring-zinc-200"><option value="">{{ __('employee-lifecycle::dashboard.forms.probation') }}</option>@foreach ($probationReviews as $review)<option value="{{ $review['id'] }}">{{ $review['employee_name'] }} · {{ $review['review_due_at'] }}</option>@endforeach</select>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <select wire:model.defer="completionForm.probation_decision" class="h-12 rounded-2xl border-0 bg-zinc-50 px-4 text-sm font-semibold text-zinc-950 ring-1 ring-zinc-200">@foreach (['confirm', 'extend', 'terminate'] as $decision)<option value="{{ $decision }}">{{ __('employee-lifecycle::dashboard.probation_decisions.'.$decision) }}</option>@endforeach</select>
                                <input wire:model.defer="completionForm.probation_score" type="number" min="0" max="100" placeholder="0-100" class="h-12 rounded-2xl border-0 bg-zinc-50 px-4 text-sm font-semibold text-zinc-950 ring-1 ring-zinc-200" />
                            </div>
                            <textarea wire:model.defer="completionForm.probation_note" rows="2" class="mt-3 w-full rounded-2xl border-0 bg-zinc-50 px-4 py-3 text-sm font-semibold text-zinc-950 ring-1 ring-zinc-200"></textarea>
                            <button type="submit" class="mt-3 h-11 rounded-2xl bg-zinc-950 px-4 text-sm font-semibold text-white">{{ __('employee-lifecycle::dashboard.actions.complete_probation') }}</button>
                        </form>
                        <form wire:submit="completeMovement" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-zinc-200">
                            <select wire:model.defer="completionForm.movement_id" class="h-12 w-full rounded-2xl border-0 bg-zinc-50 px-4 text-sm font-semibold text-zinc-950 ring-1 ring-zinc-200"><option value="">{{ __('employee-lifecycle::dashboard.forms.movement') }}</option>@foreach ($movements as $movement)<option value="{{ $movement['id'] }}">{{ $movement['employee_name'] }} · {{ $movement['movement_type_label'] }}</option>@endforeach</select>
                            <button type="submit" class="mt-3 h-11 rounded-2xl bg-zinc-950 px-4 text-sm font-semibold text-white">{{ __('employee-lifecycle::dashboard.actions.complete_movement') }}</button>
                        </form>
                        <form wire:submit="completeOffboarding" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-zinc-200">
                            <select wire:model.defer="completionForm.offboarding_case_id" class="h-12 w-full rounded-2xl border-0 bg-zinc-50 px-4 text-sm font-semibold text-zinc-950 ring-1 ring-zinc-200"><option value="">{{ __('employee-lifecycle::dashboard.forms.offboarding') }}</option>@foreach ($offboardingCases as $case)<option value="{{ $case['id'] }}">{{ $case['employee_name'] }} · {{ $case['last_working_date'] }}</option>@endforeach</select>
                            <textarea wire:model.defer="completionForm.exit_summary" rows="3" class="mt-3 w-full rounded-2xl border-0 bg-zinc-50 px-4 py-3 text-sm font-semibold text-zinc-950 ring-1 ring-zinc-200"></textarea>
                            <button type="submit" class="mt-3 h-11 rounded-2xl bg-zinc-950 px-4 text-sm font-semibold text-white">{{ __('employee-lifecycle::dashboard.actions.complete_offboarding') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    @else
        <section class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-2">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.labels.management_center') }}</x-ui.field-label>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('employee-lifecycle::dashboard.labels.management_locked') }}</h2>
                <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('employee-lifecycle::dashboard.labels.management_locked_note') }}</p>
            </div>
            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach (['forms.template', 'forms.launch_plan', 'forms.probation', 'forms.movement'] as $key)
                    <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-4 py-4 text-sm font-semibold text-zinc-500">
                        {{ __('employee-lifecycle::dashboard.'.$key) }}
                    </div>
                @endforeach
            </div>
        </section>
    @endcan

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <section class="relative min-h-[220px] -my-2 overflow-x-auto">
            <div class="inline-block min-w-full py-2 align-middle">
                <x-table.tbl
                    :headers="[
                        __('employee-lifecycle::dashboard.columns.employee'),
                        __('employee-lifecycle::dashboard.columns.process'),
                        __('employee-lifecycle::dashboard.columns.owner'),
                        __('employee-lifecycle::dashboard.columns.deadline'),
                        __('employee-lifecycle::dashboard.columns.status'),
                    ]"
                    :title="__('employee-lifecycle::dashboard.labels.result_count', ['count' => $events->count()])"
                >
                    @forelse ($events as $event)
                        <tr>
                            <x-table.td>
                                    <div class="font-semibold text-zinc-950">{{ $event['employee_name'] }}</div>
                                    <div class="mt-1 text-xs text-zinc-500">{{ $event['tabel_no'] }} · {{ $event['structure_name'] }} · {{ $event['position_name'] }}</div>
                            </x-table.td>
                            <x-table.td>
                                    <div class="font-semibold text-zinc-800">{{ $event['title'] }}</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                        <span>{{ __('employee-lifecycle::dashboard.types.'.$event['type']) }}</span>
                                        @if ($event['source_is_order'])
                                            <span class="rounded-full bg-zinc-950 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-tight text-white">
                                                {{ $event['source_label'] }}
                                            </span>
                                        @endif
                                    </div>
                            </x-table.td>
                            <x-table.td>{{ $event['owner_name'] }}</x-table.td>
                            <x-table.td>
                                <div class="font-semibold text-zinc-800">{{ $event['deadline_at'] ?? '—' }}</div>
                                @if ($event['is_overdue'])
                                    <div class="mt-1 text-xs font-semibold text-rose-600">{{ __('employee-lifecycle::dashboard.labels.overdue') }}</div>
                                @endif
                            </x-table.td>
                            <x-table.td>
                                <x-notification.chip mode="{{ $event['is_overdue'] || $event['status'] === 'blocked' ? 'rose' : ($event['status'] === 'completed' ? 'emerald' : 'amber') }}">
                                    {{ __('employee-lifecycle::dashboard.statuses.'.$event['status']) }}
                                </x-notification.chip>
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty :rows="5" />
                    @endforelse
                </x-table.tbl>
            </div>
        </section>

        <aside class="space-y-5">
            <section class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.labels.plan_templates') }}</x-ui.field-label>
                <div class="mt-4 space-y-3">
                    @forelse ($planTemplates as $template)
                        <button
                            type="button"
                            wire:click="selectTemplate({{ $template['id'] }})"
                            class="w-full rounded-2xl border px-4 py-4 text-left transition hover:-translate-y-0.5 hover:shadow-sm {{ $selectedTemplateId === $template['id'] ? 'border-zinc-950 bg-zinc-950 text-white shadow-sm' : 'border-zinc-200 bg-zinc-50/70 text-zinc-950' }}"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold">{{ $template['name'] }}</div>
                                    <div class="mt-2 text-xs {{ $selectedTemplateId === $template['id'] ? 'text-zinc-300' : 'text-zinc-500' }}">
                                        {{ $template['type_label'] }} · {{ __('employee-lifecycle::dashboard.labels.template_meta', ['tasks' => $template['tasks_count'], 'days' => $template['default_duration_days']]) }}
                                    </div>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-tight {{ $template['is_active'] ? ($selectedTemplateId === $template['id'] ? 'bg-white text-zinc-950' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200') : ($selectedTemplateId === $template['id'] ? 'bg-zinc-700 text-zinc-100' : 'bg-zinc-100 text-zinc-500 ring-1 ring-zinc-200') }}">
                                    {{ $template['is_active'] ? __('employee-lifecycle::dashboard.labels.active') : __('employee-lifecycle::dashboard.labels.inactive') }}
                                </span>
                            </div>
                            <div class="mt-3 text-[11px] font-semibold uppercase tracking-tight {{ $selectedTemplateId === $template['id'] ? 'text-zinc-300' : 'text-zinc-400' }}">
                                {{ __('employee-lifecycle::dashboard.labels.template_usage_count', ['count' => $template['events_count'] ?? 0]) }}
                            </div>
                        </button>
                    @empty
                        <p class="text-sm text-zinc-500">{{ __('employee-lifecycle::dashboard.empty') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.labels.probation_reviews') }}</x-ui.field-label>
                <div class="mt-4 space-y-3">
                    @forelse ($probationReviews as $review)
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-zinc-950">{{ $review['employee_name'] }}</div>
                                    <div class="mt-2 text-xs text-zinc-500">{{ __('employee-lifecycle::dashboard.labels.probation_meta', ['manager' => $review['manager_name'], 'hr_user' => $review['reviewer_name']]) }}</div>
                                </div>
                                <span class="text-xs font-semibold {{ $review['is_overdue'] ? 'text-rose-600' : 'text-zinc-500' }}">{{ $review['review_due_at'] }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">{{ __('employee-lifecycle::dashboard.empty') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.labels.movements') }}</x-ui.field-label>
                <div class="mt-4 space-y-3">
                    @forelse ($movements as $movement)
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-zinc-950">{{ $movement['employee_name'] }}</div>
                                    <div class="mt-2 text-xs text-zinc-500">{{ $movement['movement_type_label'] }} · {{ $movement['tabel_no'] }}</div>
                                </div>
                                <span class="text-xs font-semibold {{ $movement['is_overdue'] ? 'text-rose-600' : 'text-zinc-500' }}">{{ $movement['effective_date'] }}</span>
                            </div>
                            <div class="mt-3 rounded-2xl border border-zinc-200 bg-white px-3 py-2 text-xs leading-5 text-zinc-600">
                                {{ $movement['current_structure_name'] }} · {{ $movement['current_position_name'] }}
                                <span class="mx-1 text-zinc-400">-&gt;</span>
                                {{ $movement['target_structure_name'] }} · {{ $movement['target_position_name'] }}
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">{{ __('employee-lifecycle::dashboard.empty') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.labels.offboarding_cases') }}</x-ui.field-label>
                <div class="mt-4 space-y-3">
                    @forelse ($offboardingCases as $case)
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-zinc-950">{{ $case['employee_name'] }}</div>
                                    <div class="mt-2 text-xs text-zinc-500">{{ $case['tabel_no'] }} · {{ $case['structure_name'] }} · {{ $case['position_name'] }}</div>
                                </div>
                                <span class="text-xs font-semibold {{ $case['is_overdue'] ? 'text-rose-600' : 'text-zinc-500' }}">{{ $case['last_working_date'] }}</span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <x-notification.chip mode="{{ $case['status'] === 'completed' ? 'emerald' : ($case['is_overdue'] ? 'rose' : 'amber') }}">
                                    {{ __('employee-lifecycle::dashboard.offboarding_statuses.'.$case['status']) }}
                                </x-notification.chip>
                                <x-notification.chip mode="{{ $case['exit_interview_done'] ? 'emerald' : 'zinc' }}">
                                    {{ $case['exit_interview_done'] ? __('employee-lifecycle::dashboard.labels.exit_interview_done') : __('employee-lifecycle::dashboard.labels.exit_interview_pending') }}
                                </x-notification.chip>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">{{ __('employee-lifecycle::dashboard.empty') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.labels.event_mix') }}</x-ui.field-label>
                <div class="mt-4 space-y-3">
                    @forelse ($typeBreakdown as $item)
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-semibold text-zinc-950">{{ $item['label'] }}</span>
                                <span class="text-lg font-semibold text-zinc-950">{{ $item['count'] }}</span>
                            </div>
                            @if ($item['overdue'] > 0)
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ __('employee-lifecycle::dashboard.labels.overdue') }}: {{ $item['overdue'] }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">{{ __('employee-lifecycle::dashboard.empty') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.labels.overdue_tasks') }}</x-ui.field-label>
                <div class="mt-4 space-y-3">
                    @forelse ($overdueTasks as $task)
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4">
                            <div class="text-sm font-semibold text-zinc-950">{{ $task['title'] }}</div>
                            <div class="mt-2 text-xs text-zinc-600">{{ $task['employee_name'] }} · {{ $task['owner_label'] }} · {{ $task['due_at'] }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">{{ __('employee-lifecycle::dashboard.empty_tasks') }}</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>

    @if ($selectedTemplateId && $isTemplateEditorOpen)
        <x-ui.side-panel
            title-id="lifecycle-template-editor-title"
            close-action="$wire.closeTemplateEditor()"
            :close-label="__('employee-lifecycle::dashboard.actions.close_editor')"
            width="3xl"
        >
                <div class="border-b border-zinc-100 px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.labels.template_detail') }}</x-ui.field-label>
                            <h3 id="lifecycle-template-editor-title" class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">
                                {{ $editingTemplateForm['name'] ?: __('employee-lifecycle::dashboard.forms.template') }}
                            </h3>
                            <p class="mt-2 text-xs leading-5 text-zinc-500">
                                {{ __('employee-lifecycle::dashboard.labels.template_usage_count', ['count' => $editingTemplateForm['usage_count'] ?? 0]) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <x-notification.chip mode="{{ ($editingTemplateForm['is_active'] ?? false) ? 'emerald' : 'zinc' }}">
                                {{ ($editingTemplateForm['is_active'] ?? false) ? __('employee-lifecycle::dashboard.labels.active') : __('employee-lifecycle::dashboard.labels.inactive') }}
                            </x-notification.chip>
                            <button
                                x-ref="closeButton"
                                type="button"
                                x-on:click="close()"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-zinc-50 text-xl font-semibold text-zinc-500 ring-1 ring-zinc-200 transition hover:bg-zinc-100 hover:text-zinc-950"
                                aria-label="{{ __('employee-lifecycle::dashboard.actions.close_editor') }}"
                                title="{{ __('employee-lifecycle::dashboard.actions.close_editor') }}"
                            >
                                &times;
                            </button>
                        </div>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    @can('manage-employee-lifecycle')
                        <form wire:submit="updateTemplate" class="space-y-4">
                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.template_name')" :error="$errors->first('editingTemplateForm.name')">
                                <input wire:model.defer="editingTemplateForm.name" type="text" class="h-12 w-full rounded-2xl border-0 bg-zinc-50 px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950" />
                            </x-ui.input-shell>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.type')" :error="$errors->first('editingTemplateForm.type')">
                                    <select wire:model.defer="editingTemplateForm.type" class="h-12 w-full rounded-2xl border-0 bg-zinc-50 px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950">
                                        @foreach (['onboarding', 'probation', 'movement', 'offboarding'] as $option)
                                            <option value="{{ $option }}">{{ __('employee-lifecycle::dashboard.types.'.$option) }}</option>
                                        @endforeach
                                    </select>
                                </x-ui.input-shell>
                                <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.default_duration_days')" :error="$errors->first('editingTemplateForm.default_duration_days')">
                                    <input wire:model.defer="editingTemplateForm.default_duration_days" type="number" min="1" max="365" class="h-12 w-full rounded-2xl border-0 bg-zinc-50 px-4 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950" />
                                </x-ui.input-shell>
                            </div>

                            <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.description')" :error="$errors->first('editingTemplateForm.description')">
                                <textarea wire:model.defer="editingTemplateForm.description" rows="2" class="w-full rounded-2xl border-0 bg-zinc-50 px-4 py-3 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950"></textarea>
                            </x-ui.input-shell>

                            <div class="rounded-3xl border border-zinc-200 bg-zinc-50/80 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('employee-lifecycle::dashboard.labels.template_tasks') }}</x-ui.field-label>
                                    <button type="button" wire:click="addTemplateTaskRow" class="h-10 rounded-2xl bg-white px-4 text-xs font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200">
                                        {{ __('employee-lifecycle::dashboard.actions.add_task') }}
                                    </button>
                                </div>

                                <div class="mt-3 space-y-3">
                                    @foreach (($editingTemplateForm['tasks'] ?? []) as $index => $task)
                                        <div class="rounded-2xl border border-zinc-200 bg-white p-3">
                                            <div class="grid gap-3">
                                                <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.task_title')" :error="$errors->first('editingTemplateForm.tasks.'.$index.'.title')">
                                                    <input wire:model.defer="editingTemplateForm.tasks.{{ $index }}.title" type="text" class="h-11 w-full rounded-2xl border-0 bg-zinc-50 px-4 text-sm font-semibold text-zinc-950 ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950" />
                                                </x-ui.input-shell>
                                                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_7rem_auto] sm:items-end">
                                                    <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.task_owner_type')" :error="$errors->first('editingTemplateForm.tasks.'.$index.'.owner_type')">
                                                        <select wire:model.defer="editingTemplateForm.tasks.{{ $index }}.owner_type" class="h-11 w-full rounded-2xl border-0 bg-zinc-50 px-4 text-sm font-semibold text-zinc-950 ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950">
                                                            @foreach (['hr', 'manager', 'it', 'employee'] as $ownerType)
                                                                <option value="{{ $ownerType }}">{{ __('employee-lifecycle::dashboard.owner_types.'.$ownerType) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </x-ui.input-shell>
                                                    <x-ui.input-shell :label="__('employee-lifecycle::dashboard.fields.task_due_offset_days')" :error="$errors->first('editingTemplateForm.tasks.'.$index.'.due_offset_days')">
                                                        <input wire:model.defer="editingTemplateForm.tasks.{{ $index }}.due_offset_days" type="number" min="0" max="365" class="h-11 w-full rounded-2xl border-0 bg-zinc-50 px-4 text-sm font-semibold text-zinc-950 ring-1 ring-zinc-200 focus:ring-2 focus:ring-zinc-950" />
                                                    </x-ui.input-shell>
                                                    <button type="button" wire:click="removeTemplateTaskRow({{ $index }})" class="h-11 rounded-2xl bg-white px-4 text-xs font-semibold text-rose-600 ring-1 ring-rose-100">
                                                        {{ __('employee-lifecycle::dashboard.actions.remove_task') }}
                                                    </button>
                                                </div>
                                                <label class="inline-flex items-center gap-2 text-xs font-semibold text-zinc-600">
                                                    <input wire:model.defer="editingTemplateForm.tasks.{{ $index }}.is_required" type="checkbox" class="rounded border-zinc-300 text-zinc-950 focus:ring-zinc-300" />
                                                    {{ __('employee-lifecycle::dashboard.fields.task_required') }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('editingTemplateForm.tasks') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>

                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-xs leading-5 text-zinc-500">
                                {{ ($editingTemplateForm['usage_count'] ?? 0) > 0 ? __('employee-lifecycle::dashboard.labels.template_used_archive_note') : __('employee-lifecycle::dashboard.labels.template_unused_delete_note') }}
                            </div>
                        </form>
                    @else
                        <div class="space-y-3">
                            @foreach (($editingTemplateForm['tasks'] ?? []) as $task)
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4">
                                    <div class="text-sm font-semibold text-zinc-950">{{ $task['title'] }}</div>
                                    <div class="mt-2 text-xs text-zinc-500">
                                        {{ __('employee-lifecycle::dashboard.owner_types.'.$task['owner_type']) }} · {{ __('employee-lifecycle::dashboard.fields.task_due_offset_days') }}: {{ $task['due_offset_days'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endcan
                </div>

                @can('manage-employee-lifecycle')
                    <div class="border-t border-zinc-100 bg-white px-6 py-4">
                        <div class="flex flex-wrap justify-end gap-3">
                            <button type="button" x-on:click="close()" class="h-11 rounded-2xl bg-white px-5 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200">
                                {{ __('employee-lifecycle::dashboard.actions.close_editor') }}
                            </button>
                            <button type="button" wire:click="toggleTemplateActive" class="h-11 rounded-2xl bg-white px-5 text-sm font-semibold text-zinc-950 shadow-sm ring-1 ring-zinc-200">
                                {{ ($editingTemplateForm['is_active'] ?? false) ? __('employee-lifecycle::dashboard.actions.deactivate_template') : __('employee-lifecycle::dashboard.actions.activate_template') }}
                            </button>
                            <button type="button" wire:click="deleteOrArchiveTemplate" class="h-11 rounded-2xl bg-rose-50 px-5 text-sm font-semibold text-rose-700 ring-1 ring-rose-100">
                                {{ ($editingTemplateForm['usage_count'] ?? 0) > 0 ? __('employee-lifecycle::dashboard.actions.archive_template') : __('employee-lifecycle::dashboard.actions.delete_template') }}
                            </button>
                            <button type="button" wire:click="updateTemplate" class="h-11 rounded-2xl bg-zinc-950 px-5 text-sm font-semibold text-white shadow-[0_18px_35px_-20px_rgba(24,24,27,0.85)]">
                                {{ __('employee-lifecycle::dashboard.actions.update_template') }}
                            </button>
                        </div>
                    </div>
                @endcan
        </x-ui.side-panel>
    @endif
</div>
