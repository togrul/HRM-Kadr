/**
 * Cell detail popover for the attendance puantaj grid (attendance::livewire.attendance.puantaj-grid).
 *
 * Every cell used to carry its own `@click="openDetail($event, {label, lines})"` with an
 * inline JSON payload (~240 bytes x rows x 31 days). Now a cell only marks itself with
 * `data-d` (day of month) and one delegated click handler rebuilds the same payload:
 *   label = <tr data-name> + ' • ' + dd.mm.YYYY (root `data-month` holds "mm.YYYY")
 *   lines = the cell's `data-lines` JSON when present, else its <td title> split on ' | '
 *           (the title is exactly the lines joined with ' | '; the server only emits
 *           data-lines when a line itself contains '|', so the split is lossless).
 */
window.puantajGrid = () => ({
    detailPopover: null,

    openCell(event) {
        const button = event.target.closest('button[data-d]');
        if (!button || !this.$root.contains(button)) return;

        const row = button.closest('tr');
        const cell = button.closest('td');
        const day = String(button.dataset.d).padStart(2, '0');

        this.openDetail(button, {
            label: `${row?.dataset.name ?? ''} • ${day}.${this.$root.dataset.month}`,
            lines: button.dataset.lines !== undefined
                ? JSON.parse(button.dataset.lines)
                : (cell?.getAttribute('title') ?? '').split(' | '),
        });
    },

    openDetail(anchor, payload) {
        const rect = anchor.getBoundingClientRect();
        this.detailPopover = {
            lines: payload.lines || [],
            label: payload.label || '',
            anchorLeft: rect.left,
            anchorTop: rect.top,
            anchorBottom: rect.bottom,
            anchorWidth: rect.width,
            left: 12,
            top: 12,
            maxHeight: 320,
        };
        this.$nextTick(() => {
            this.positionDetail();
            requestAnimationFrame(() => this.positionDetail());
        });
    },

    positionDetail() {
        if (!this.detailPopover || !this.$refs.detailPanel) {
            return;
        }

        const margin = 12;
        const panel = this.$refs.detailPanel;
        const width = panel.offsetWidth || 256;
        const height = panel.offsetHeight || 220;
        const preferredLeft = this.detailPopover.anchorLeft + (this.detailPopover.anchorWidth / 2) - (width / 2);
        const maxLeft = Math.max(margin, window.innerWidth - width - margin);
        const left = Math.min(maxLeft, Math.max(margin, preferredLeft));
        const gap = 8;
        const availableBelow = Math.max(0, window.innerHeight - this.detailPopover.anchorBottom - margin - gap);
        const availableAbove = Math.max(0, this.detailPopover.anchorTop - margin - gap);
        const preferredBelow = availableBelow >= Math.min(height, 220) || availableBelow >= availableAbove;

        let top = this.detailPopover.anchorBottom + gap;
        let constrainedMaxHeight = Math.max(180, preferredBelow ? availableBelow : availableAbove);

        if (!preferredBelow) {
            top = Math.max(margin, this.detailPopover.anchorTop - Math.min(height, constrainedMaxHeight) - gap);
        }

        if (preferredBelow && top + Math.min(height, constrainedMaxHeight) > window.innerHeight - margin) {
            top = Math.max(margin, window.innerHeight - Math.min(height, constrainedMaxHeight) - margin);
        }

        this.detailPopover = {
            ...this.detailPopover,
            left,
            top,
            maxHeight: Math.max(180, Math.min(constrainedMaxHeight, window.innerHeight - (margin * 2))),
        };
    },

    closeDetail() {
        this.detailPopover = null;
    },
});
