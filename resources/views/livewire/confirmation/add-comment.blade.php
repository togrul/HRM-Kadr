<x-ui.confirmation-modal
    title="Add comment"
    confirm="Save"
    cancel="Cancel"
    confirmAction="confirmComment"
>
    <div class="flex flex-col">
        <label for="comment-ta" class="sr-only">Şərh</label>
        <x-textarea
            x-ref="ta"
            name="comment"
            mode="gray"
            x-model="comment"
            class="w-full min-h-[140px] ... "
            placeholder="Comment"
        ></x-textarea>
    </div>
</x-ui.confirmation-modal>
