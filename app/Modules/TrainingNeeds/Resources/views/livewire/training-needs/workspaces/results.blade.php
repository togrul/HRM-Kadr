    @if ($activeTab === 'results')
        <div class="grid gap-4 xl:grid-cols-2">
            <x-surface-card :title="__('training_needs::dashboard.cards.feedback_forms')" icon="icons.folder-plus-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                @if ($editingFeedbackFormId)
                    <div class="mb-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        {{ __('training_needs::dashboard.labels.editing_feedback_form_hint') }}
                    </div>
                @endif
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.session')"
                            placeholder="---"
                            mode="gray"
                            direction="auto"
                            class="w-full"
                            wire:model.live="feedbackForm.training_session_id"
                            :model="$this->sessionOptions()"
                            search-model="searchSession"
                        ></x-ui.select-dropdown>
                        @error('feedbackForm.training_session_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="feedback-title">{{ __('training_needs::dashboard.fields.feedback_title') }}</x-label>
                        <x-livewire-input mode="gray" id="feedback-title" wire:model.defer="feedbackForm.title" />
                        @error('feedbackForm.title') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="feedback-status">{{ __('training_needs::dashboard.fields.status') }}</x-label>
                        <select id="feedback-status" wire:model.defer="feedbackForm.status" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="draft">{{ __('training_needs::dashboard.feedback_statuses.draft') }}</option>
                            <option value="open">{{ __('training_needs::dashboard.feedback_statuses.open') }}</option>
                            <option value="closed">{{ __('training_needs::dashboard.feedback_statuses.closed') }}</option>
                        </select>
                        @error('feedbackForm.status') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="feedback-question-type">{{ __('training_needs::dashboard.fields.default_question_type') }}</x-label>
                        <select id="feedback-question-type" wire:model.defer="feedbackForm.default_question_type" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="rating">{{ __('training_needs::dashboard.question_types.rating') }}</option>
                            <option value="text">{{ __('training_needs::dashboard.question_types.text') }}</option>
                            <option value="multiple_choice">{{ __('training_needs::dashboard.question_types.multiple_choice') }}</option>
                        </select>
                        @error('feedbackForm.default_question_type') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="feedback-questions">{{ __('training_needs::dashboard.fields.feedback_questions') }}</x-label>
                        <textarea id="feedback-questions" wire:model.defer="feedbackForm.questions_text" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('feedbackForm.questions_text') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <div class="flex flex-wrap gap-2">
                            <x-button mode="black" wire:click="storeFeedbackForm">
                                {{ $editingFeedbackFormId ? __('training_needs::dashboard.actions.update_feedback_form') : __('training_needs::dashboard.actions.save_feedback_form') }}
                            </x-button>
                            @if ($editingFeedbackFormId)
                                <x-button mode="secondary" wire:click="cancelFeedbackFormEdit">{{ __('training_needs::dashboard.actions.cancel_edit') }}</x-button>
                            @endif
                        </div>
                    </div>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('training_needs::dashboard.cards.feedback_responses')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.feedback_form')"
                            placeholder="---"
                            mode="gray"
                            direction="auto"
                            class="w-full"
                            wire:model.live="feedbackResponseForm.training_feedback_form_id"
                            :model="$this->feedbackFormOptions()"
                            search-model="searchFeedbackForm"
                        ></x-ui.select-dropdown>
                        @error('feedbackResponseForm.training_feedback_form_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.personnel')"
                            placeholder="---"
                            mode="gray"
                            direction="auto"
                            class="w-full"
                            wire:model.live="feedbackResponseForm.personnel_id"
                            :model="$this->personnelOptions()"
                            search-model="searchPersonnel"
                        ></x-ui.select-dropdown>
                        @error('feedbackResponseForm.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="feedback-score">{{ __('training_needs::dashboard.fields.overall_score') }}</x-label>
                        <x-livewire-input mode="gray" id="feedback-score" type="number" min="1" max="5" wire:model.defer="feedbackResponseForm.overall_score" />
                        @error('feedbackResponseForm.overall_score') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="feedback-comments">{{ __('training_needs::dashboard.fields.comments') }}</x-label>
                        <textarea id="feedback-comments" wire:model.defer="feedbackResponseForm.comments" class="min-h-20 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('feedbackResponseForm.comments') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="feedback-answers">{{ __('training_needs::dashboard.fields.feedback_answers') }}</x-label>
                        <textarea id="feedback-answers" wire:model.defer="feedbackResponseForm.answers_text" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('feedbackResponseForm.answers_text') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-button mode="black" wire:click="submitFeedbackResponse">{{ __('training_needs::dashboard.actions.save_feedback_response') }}</x-button>
                    </div>
                </div>
            </x-surface-card>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
            <x-surface-card :title="__('training_needs::dashboard.cards.delivered_trainings')" icon="icons.clock-icon">
                <div class="space-y-3">
                    @forelse ($this->recentDeliveryRecords as $record)
                        <x-ui.list-card>
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $record->personnel?->fullname ?? '---' }}</p>
                                    <p class="text-sm text-zinc-600">{{ $record->program?->title ?? __('training_needs::dashboard.labels.no_program') }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-ui.action-pill wire:click="selectDeliveryRecord({{ $record->id }})" icon="icons.edit-icon">{{ __('training_needs::dashboard.actions.replace_certificate') }}</x-ui.action-pill>
                                    @if ($record->certificate_path)
                                        <x-ui.action-pill mode="secondary" wire:click="previewDeliveryCertificate({{ $record->id }})">{{ __('training_needs::dashboard.actions.preview_certificate') }}</x-ui.action-pill>
                                        <x-ui.action-pill mode="secondary" wire:click="downloadDeliveryCertificate({{ $record->id }})">{{ __('training_needs::dashboard.actions.download_certificate') }}</x-ui.action-pill>
                                        <x-ui.action-pill mode="delete" wire:click="confirmDeleteDeliveryCertificate({{ $record->id }})" icon="icons.delete-icon">{{ __('training_needs::dashboard.actions.delete_certificate') }}</x-ui.action-pill>
                                    @endif
                                    <x-small-badge mode="green">{{ __('training_needs::dashboard.delivery_result_statuses.'.$record->result_status) }}</x-small-badge>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.delivery_meta', ['session' => $record->session?->title ?? '—', 'date' => optional($record->completed_at)->format('d.m.Y H:i') ?: '—', 'hours' => $record->attended_hours ?: 0]) }}</p>
                        </x-ui.list-card>
                    @empty
                        <x-ui.empty-state icon="icons.document-icon" :message="__('training_needs::dashboard.empty.deliveries')" />
                    @endforelse
                </div>
            </x-surface-card>

            <div class="space-y-4">
                <livewire:training-needs.results-summary :key="'training-needs-results-summary-'.$resultsSummaryVersion" lazy />

                <x-surface-card :title="__('training_needs::dashboard.cards.delivery_documents')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                    <div class="grid gap-3">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.delivery_record')"
                            placeholder="---"
                            mode="gray"
                            direction="auto"
                            class="w-full"
                            wire:model.live="deliveryDocumentForm.training_delivery_record_id"
                            :model="$this->deliveryRecordOptions()"
                            search-model="searchDeliveryRecord"
                        ></x-ui.select-dropdown>
                        @error('deliveryDocumentForm.training_delivery_record_id') <x-validation>{{ $message }}</x-validation> @enderror

                        <div>
                            <x-label for="delivery-certificate">{{ __('training_needs::dashboard.fields.certificate_file') }}</x-label>
                            <x-ui.file-upload
                                model="deliveryDocumentForm.certificate_file"
                                :data="data_get($deliveryDocumentForm, 'certificate_file')"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp"
                            />
                            @error('deliveryDocumentForm.certificate_file') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>

                        @if ($selectedDeliveryRecord = $this->selectedDeliveryRecord)
                            @php
                                $pendingCertificate = data_get($deliveryDocumentForm, 'certificate_file');
                                $hasPendingCertificate = $pendingCertificate instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
                                $pendingCertificateName = $hasPendingCertificate ? $pendingCertificate->getClientOriginalName() : null;
                                $pendingCertificateExtension = $pendingCertificateName
                                    ? strtolower(pathinfo($pendingCertificateName, PATHINFO_EXTENSION) ?: 'file')
                                    : null;
                                $pendingCertificatePreviewUrl = $hasPendingCertificate
                                    && in_array($pendingCertificateExtension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
                                    ? $pendingCertificate->temporaryUrl()
                                    : null;
                                $pendingCertificateKey = $hasPendingCertificate ? $pendingCertificate->getFilename() : 'persisted';
                            @endphp
                            <livewire:training-needs.certificate-viewer
                                :delivery-record-id="(int) $selectedDeliveryRecord->id"
                                :record-snapshot="[
                                    'id' => $selectedDeliveryRecord->id,
                                    'certificate_path' => $selectedDeliveryRecord->certificate_path,
                                    'certificate_name' => $selectedDeliveryRecord->certificate_name,
                                    'result_status' => $selectedDeliveryRecord->result_status,
                                    'completed_at' => optional($selectedDeliveryRecord->completed_at)?->toISOString(),
                                    'session' => ['title' => $selectedDeliveryRecord->session?->title],
                                    'program' => ['title' => $selectedDeliveryRecord->program?->title],
                                    'personnel' => ['fullname' => $selectedDeliveryRecord->personnel?->fullname],
                                ]"
                                :temporary-certificate-name="$pendingCertificateName"
                                :temporary-certificate-preview-url="$pendingCertificatePreviewUrl"
                                :temporary-certificate-extension="$pendingCertificateExtension"
                                :has-pending-upload="$hasPendingCertificate"
                                :key="'training-certificate-viewer-'.(int) $selectedDeliveryRecord->id.'-'.$pendingCertificateKey"
                            />
                        @endif

                        <x-button mode="black" wire:click="storeDeliveryDocument">{{ __('training_needs::dashboard.actions.save_certificate') }}</x-button>
                    </div>
                </x-surface-card>
            </div>
        </div>
    @endif

