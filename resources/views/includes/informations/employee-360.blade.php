<div class="w-full">
    <livewire:personnel.employee-360-timeline
        :personnel-id="$this->personnel->id"
        :key="'employee-360-timeline-'.$this->personnel->id"
        lazy
    />
</div>
