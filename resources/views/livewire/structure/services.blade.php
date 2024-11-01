<div class="flex flex-col space-y-2 px-2 py-3">
    <x-services-menu-item key="general" :$selectedService :title="__('General')">
        <x-icons.settings2-icon size="w-6 h-6" color="text-green-600"></x-icons.settings2-icon>
    </x-services-menu-item>

    <x-services-menu-item key="menus" :$selectedService :title="__('Menus')">
        <x-icons.menu-icon size="w-6 h-6" color="text-green-600"></x-icons.menu-icon>
    </x-services-menu-item>

    <x-services-menu-item key="roles" :$selectedService :title="__('Roles and permissions')">
        <x-icons.shield-icon size="w-7 h-7" color="text-green-600"></x-icons.shield-icon>
    </x-services-menu-item>

    <x-services-menu-item key="users" :$selectedService :title="__('Users')">
        <x-icons.users-icon size="w-6 h-6" color="text-green-600"></x-icons.users-icon>
    </x-services-menu-item>

    <x-services-menu-item key="ranks" :$selectedService :title="__('Ranks')">
        <x-icons.double-arrow-icon size="w-6 h-6" color="text-green-600"></x-icons.double-arrow-icon>
    </x-services-menu-item>

    <x-services-menu-item key="order-documents" :$selectedService :title="__('Order templates')">
        <x-icons.document-icon size="w-6 h-6" color="text-green-600"></x-icons.document-icon>
    </x-services-menu-item>

    <x-services-menu-item key="components" :$selectedService :title="__('Components')">
        <x-icons.components-icon size="w-6 h-6" color="text-green-600"></x-icons.components-icon>
    </x-services-menu-item>
</div>
