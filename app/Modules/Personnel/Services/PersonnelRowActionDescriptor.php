<?php

namespace App\Modules\Personnel\Services;

final class PersonnelRowActionDescriptor
{
    public function __construct(
        public string $id,
        public string $label,
        /**
         * Blade component name, e.g. 'icons.profile-icon'
         */
        public string $icon,
        /**
         * 'link' or 'action'
         */
        public string $type = 'action',
        /**
         * Optional route target. For type=link.
         */
        public ?string $href = null,
        /**
         * Payload for livewire action.
         */
        public array $actionPayload = [],
        /**
         * Menu bucket. Non-menu actions render inline with primary actions.
         */
        public bool $inMenu = false,
        public ?string $permission = null,
        public ?string $confirmMessage = null,
        public bool $visible = true,
        public ?string $wireTarget = null,
        public array $iconProps = [],
        public bool $targetBlank = false,
    ) {}

    public static function action(
        string $id,
        string $label,
        string $icon,
        array $actionPayload = [],
        bool $inMenu = false,
        ?string $permission = null,
        ?string $confirmMessage = null,
        ?string $wireTarget = null,
        array $iconProps = [],
    ): self {
        return new self(
            id: $id,
            label: $label,
            icon: $icon,
            type: 'action',
            actionPayload: $actionPayload,
            inMenu: $inMenu,
            permission: $permission,
            confirmMessage: $confirmMessage,
            visible: true,
            wireTarget: $wireTarget,
            iconProps: $iconProps,
        );
    }

    public static function link(
        string $id,
        string $label,
        string $icon,
        string $href,
        bool $inMenu = false,
        ?string $permission = null,
        bool $targetBlank = false,
        array $iconProps = [],
    ): self {
        return new self(
            id: $id,
            label: $label,
            icon: $icon,
            type: 'link',
            href: $href,
            inMenu: $inMenu,
            permission: $permission,
            targetBlank: $targetBlank,
            iconProps: $iconProps,
        );
    }

    public function visibleByPermission(): bool
    {
        if ($this->permission === null) {
            return true;
        }

        return auth()->user()?->can($this->permission) ?? false;
    }
}

