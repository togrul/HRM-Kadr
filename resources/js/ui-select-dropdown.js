/**
 * Shared behaviour for <x-ui.select-dropdown>.
 *
 * This used to live inside the component's x-data attribute, which meant every dropdown
 * on a page shipped its own ~9KB copy of the source — three of them accounted for 40%
 * of the evaluator workspace's HTML. Defined once here, an instance only carries its
 * own config; the Livewire entanglement stays inline, where it has to be.
 */
window.uiSelectDropdown = (config) => ({
    uid: config.uid,
    currentValue: null,
    lastValue: null,
    localSearch: '',
    cachedOptions: [],
    placeholder: config.placeholder,
    isOpen: false,
    positioned: false,
    openUp: false,
    alignRight: false,
    panelMaxHeight: 224,
    panelStyles: {},
    preferredDirection: config.preferredDirection,
    isDisabled: config.isDisabled,
    loadOnOpen: config.loadOnOpen,
    pendingReopen: false,
    pendingSelectionClose: false,
    selectedCache: { id: null, label: '' },
    initialSelectedLabel: null,
    toId(v){ return (v===null||v===undefined||v==='') ? null : String(v).trim(); },
    toWireValue(v){
      if (v===null || v===undefined || v==='') return null;
      const s = String(v).trim();
      return /^[0-9]+$/.test(s) ? Number(s) : s;
    },
    optionLabel(option){
      if (!option) return '';
      return option.label ?? option.name ?? option.title ?? option.text ?? '';
    },
    optionId(option){
      if (!option) return null;
      return option.id ?? option.value ?? null;
    },
    normalizeOptions(options){
      return (Array.isArray(options) ? options : [])
        .map((option) => {
          const id = this.optionId(option);
          const normalizedId = this.toId(id);
          if (normalizedId === null) return null;

          return {
            ...option,
            id: normalizedId,
            label: String(this.optionLabel(option) ?? '').trim(),
          };
        })
        .filter(Boolean);
    },
    normalizeSearchValue(value){
      return String(value ?? '')
        .toLocaleLowerCase('az')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/ə/g, 'e')
        .replace(/ı/g, 'i')
        .replace(/ö/g, 'o')
        .replace(/ü/g, 'u')
        .replace(/ş/g, 's')
        .replace(/ç/g, 'c')
        .replace(/ğ/g, 'g');
    },
    matchesSearch(label){
      const query = this.normalizeSearchValue(this.localSearch).trim();
      if (query === '') return true;
      return this.normalizeSearchValue(label).includes(query);
    },
    syncSelectedCache(currentId = this.toId(this.currentValue)){
      const found = this.cachedOptions.find(o => this.toId(o.id) === currentId);
      if (found) {
        this.selectedCache = { id: this.toId(found.id), label: found.label };
        return;
      }
      if (currentId === null) {
        this.selectedCache = { id: null, label: '' };
      }
    },
    syncOptionsFromDom(){
      const optionRoot = this.$refs.panel ?? this.$root;
      const optionNodes = Array.from(optionRoot.querySelectorAll('[data-option-id]'));
      const domOptions = optionNodes.map((node) => ({
        id: node.dataset.optionId,
        label: node.dataset.optionLabel ?? '',
      }));
      this.cachedOptions = this.normalizeOptions(domOptions);
      this.syncSelectedCache();
    },
    observeOptions(){
      const target = this.$refs.panel ?? this.$root;
      if (!target || typeof MutationObserver === 'undefined') return;

      const observer = new MutationObserver(() => {
        this.$nextTick(() => {
          this.syncOptionsFromDom();
          if (this.isOpen) {
            requestAnimationFrame(() => this.repositionPanel());
          }
        });
      });

      observer.observe(target, {
        childList: true,
        subtree: true,
      });

      this.$root._uiSelectObserver = observer;
    },

    init(){
      this.initialSelectedLabel = this.$root.dataset.selectedLabel || null;
      this.cachedOptions = this.normalizeOptions(this.cachedOptions);
      this.lastValue = this.toId(this.currentValue);
      const currentId = this.toId(this.currentValue);
      if (this.initialSelectedLabel && currentId !== null) {
        const found = this.cachedOptions.find(o => this.toId(o.id) === currentId);
        if (!found) {
          this.selectedCache = { id: currentId, label: this.initialSelectedLabel };
        }
      }
      this.$nextTick(() => {
        this.syncOptionsFromDom();
        this.observeOptions();
      });
      this.$watch('currentValue', (next) => {
        const normalizedNext = this.toId(next);
        this.syncSelectedCache(normalizedNext);
        if (this.pendingSelectionClose || normalizedNext !== this.lastValue) {
          this.pendingSelectionClose = false;
          this.isOpen = false;
        }
        this.lastValue = normalizedNext;
        if (this.isOpen) {
          this.scheduleReposition();
        }
      });
      // Whenever the panel opens, position it reliably (covers every open path,
      // not just toggle()). Hide it until positioned so it never flashes at the
      // teleport origin, and re-run after layout settles (e.g. a Livewire morph
      // reflow that shifts the trigger button) so it can't detach from the field.
      this.$watch('isOpen', (open) => {
        if (open) {
          this.scheduleReposition();
        } else {
          this.positioned = false;
        }
      });
    },

    setOpen(next){
      this.isOpen = !!next;
    },

    scheduleReposition(){
      this.$nextTick(() => {
        requestAnimationFrame(() => {
          this.repositionPanel();
          setTimeout(() => { if (this.isOpen) this.repositionPanel(); }, 60);
          setTimeout(() => { if (this.isOpen) this.repositionPanel(); }, 180);
        });
      });
    },

    repositionPanel(){
      const button = this.$refs.button;
      const panel = this.$refs.panel;
      if (!button || !panel) return;

      const buttonRect = button.getBoundingClientRect();
      const viewportHeight = window.visualViewport?.height || window.innerHeight;
      const viewportWidth = window.visualViewport?.width || window.innerWidth;
      const gap = 8;
      const viewportPadding = 12;
      const naturalHeight = Math.min(panel.scrollHeight || 224, 320);
      const availableBelow = Math.max(140, viewportHeight - buttonRect.bottom - gap - viewportPadding);
      const availableAbove = Math.max(140, buttonRect.top - gap - viewportPadding);

      if (this.preferredDirection === 'up') {
        this.openUp = true;
      } else if (this.preferredDirection === 'down') {
        this.openUp = false;
      } else {
        this.openUp = naturalHeight > availableBelow && availableAbove > availableBelow;
      }
      this.panelMaxHeight = this.openUp
        ? Math.min(320, availableAbove)
        : Math.min(320, availableBelow);

      const desiredWidth = Math.max(buttonRect.width, 220);
      const clampedWidth = Math.min(desiredWidth, viewportWidth - (viewportPadding * 2));
      this.alignRight = buttonRect.left + clampedWidth > viewportWidth - viewportPadding;
      const left = this.alignRight
        ? Math.max(viewportPadding, buttonRect.right - clampedWidth)
        : Math.min(Math.max(viewportPadding, buttonRect.left), viewportWidth - viewportPadding - clampedWidth);
      const top = this.openUp
        ? Math.max(viewportPadding, buttonRect.top - gap - this.panelMaxHeight)
        : Math.min(buttonRect.bottom + gap, viewportHeight - viewportPadding - this.panelMaxHeight);

      this.panelStyles = {
        left: `${Math.round(left)}px`,
        top: `${Math.round(top)}px`,
        width: `${Math.round(clampedWidth)}px`,
        maxHeight: `${Math.round(this.panelMaxHeight)}px`,
      };
      this.positioned = true;
    },

    selectedLabel(){
      const currentId = this.toId(this.currentValue);
      if (currentId == null || currentId === '') return this.placeholder;
      const found = this.cachedOptions.find(o => this.toId(o.id) === currentId);
      if (found) return found.label;
      if (this.toId(this.selectedCache.id) === currentId && this.selectedCache.label) {
        return this.selectedCache.label;
      }
      if (this.initialSelectedLabel && currentId !== null) {
        return this.initialSelectedLabel;
      }
      return this.placeholder;
    },

    select(id, label = null){
      const wireValue = this.toWireValue(id);
      const val = this.toId(wireValue);
      this.pendingReopen = false;
      this.pendingSelectionClose = true;
      if (label !== null && label !== undefined) {
        this.selectedCache = { id: val, label: String(label) };
      } else {
        const found = this.cachedOptions.find(o => this.toId(o.id) === val);
        this.selectedCache = found ? { id: this.toId(found.id), label: found.label } : { id: val, label: '' };
      }
      this.currentValue = wireValue;
      this.initialSelectedLabel = null;
      this.isOpen = false;
      queueMicrotask(() => { this.isOpen = false; });
      requestAnimationFrame(() => { this.isOpen = false; });
      setTimeout(() => { this.isOpen = false; }, 0);
    },

    toggle(){
      if (this.isDisabled) return;
      if (this.isOpen) {
        this.setOpen(false);
        return;
      }

      this.setOpen(true);
      window.dispatchEvent(new CustomEvent('ui-select-opened', { detail: { uid: this.uid } }));
      this.$nextTick(() => {
        requestAnimationFrame(() => this.repositionPanel());
      });
      if (this.isOpen && this.loadOnOpen && this.$wire && typeof this.$wire.loadOptionGroup === 'function') {
        this.pendingReopen = true;
        this.$wire.loadOptionGroup(this.loadOnOpen);
      }
    },
});
