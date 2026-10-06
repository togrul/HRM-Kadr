<x-ui.confirmation-modal
    :title="__('ui::common.comment.title')"
    :confirm="__('ui::common.comment.save')"
    :cancel="__('ui::common.actions.cancel')"
    confirmAction="confirmComment"
>
    <div class="flex flex-col">
        <label for="comment-ta" class="sr-only">{{ __('ui::common.comment.label') }}</label>
        <x-textarea
            x-ref="ta"
            name="comment"
            mode="gray"
            x-model="comment"
            class="w-full min-h-[140px] ... "
            :placeholder="__('ui::common.comment.label')"
        ></x-textarea>
    </div>
</x-ui.confirmation-modal>
