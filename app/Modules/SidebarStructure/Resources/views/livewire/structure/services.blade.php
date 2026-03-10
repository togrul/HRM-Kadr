<div class="flex flex-col space-y-2 px-2 py-3">
    <x-services-menu-item key="general" :selected-service="$selectedService" :title="__('services::common.labels.general')">
        <x-icons.settings2-icon size="w-6 h-6" color="text-green-600"></x-icons.settings2-icon>
    </x-services-menu-item>

    <x-services-menu-item key="candidate" :selected-service="$selectedService" :title="__('services::common.labels.candidate_preferences')">
        <x-icons.candidate-icon size="w-6 h-6" color="text-green-600"></x-icons.candidate-icon>
    </x-services-menu-item>

    <x-services-menu-item key="menus" :selected-service="$selectedService" :title="__('services::common.labels.menus')">
        <x-icons.menu-icon size="w-6 h-6" color="text-green-600"></x-icons.menu-icon>
    </x-services-menu-item>

    <x-services-menu-item key="roles" :selected-service="$selectedService" :title="__('services::common.navigation.roles_and_permissions')">
        <x-icons.shield-icon size="w-7 h-7" color="text-green-600"></x-icons.shield-icon>
    </x-services-menu-item>

    <x-services-menu-item key="users" :selected-service="$selectedService" :title="__('services::common.labels.users')">
        <x-icons.users-icon size="w-6 h-6" color="text-green-600"></x-icons.users-icon>
    </x-services-menu-item>

    <x-services-menu-item key="ranks" :selected-service="$selectedService" :title="__('services::common.labels.ranks')">
        <x-icons.double-arrow-icon size="w-6 h-6" color="text-green-600"></x-icons.double-arrow-icon>
    </x-services-menu-item>

    <x-services-menu-item key="order-documents" :selected-service="$selectedService" :title="__('services::common.labels.order_templates')">
        <x-icons.document-icon size="w-6 h-6" color="text-green-600"></x-icons.document-icon>
    </x-services-menu-item>

    <x-services-menu-item key="components" :selected-service="$selectedService" :title="__('services::common.labels.components')">
        <x-icons.components-icon size="w-6 h-6" color="text-green-600"></x-icons.components-icon>
    </x-services-menu-item>
</div>
