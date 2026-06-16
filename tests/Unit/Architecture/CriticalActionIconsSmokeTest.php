<?php

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CriticalActionIconsSmokeTest extends TestCase
{
    public function test_critical_livewire_views_use_icon_components_for_actions(): void
    {
        $targets = [
            app_path('Modules/Orders/Resources/views/livewire/orders/all-orders.blade.php') => [
                '<x-icons.print-file',
                '<x-icons.delete-icon',
            ],
            app_path('Modules/Leaves/Resources/views/livewire/leaves/leaves.blade.php') => [
                '<x-icons.check-icon',
                '<x-icons.x-circle-icon',
                '<x-icons.document-icon',
                '<x-icons.delete-icon',
            ],
            app_path('Modules/Candidates/Resources/views/livewire/candidates/candidate-list.blade.php') => [
                '<x-icons.profile-icon',
                '<x-icons.recover',
                '<x-icons.delete-icon',
            ],
            app_path('Modules/Staff/Resources/views/livewire/staff-schedule/staffs.blade.php') => [
                '<x-icons.add-icon',
                '<x-icons.excel-icon',
            ],
        ];

        foreach ($targets as $path => $requiredTags) {
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
            $this->assertTrue(File::exists($path), sprintf('%s not found', $relative));

            $content = (string) File::get($path);
            $this->assertStringNotContainsString("@include('components.icons.", $content, $relative);
            $this->assertStringNotContainsString('@include("components.icons.', $content, $relative);

            foreach ($requiredTags as $tag) {
                $this->assertStringContainsString($tag, $content, sprintf('%s missing %s', $relative, $tag));
            }
        }
    }

    public function test_additional_action_heavy_views_use_icons_for_row_and_toolbar_actions(): void
    {
        $targets = [
            app_path('Modules/Vacation/Resources/views/livewire/vacation/vacations.blade.php') => [
                '<x-icons.excel-icon',
                '<x-icons.document-icon',
            ],
            app_path('Modules/BusinessTrips/Resources/views/livewire/business-trips/business-trips.blade.php') => [
                '<x-icons.excel-icon',
                '<x-icons.document-icon',
            ],
            app_path('Modules/Services/Resources/views/livewire/services/users/all-users.blade.php') => [
                '<x-icons.add-user',
                '<x-icons.edit-icon',
                '<x-icons.delete-icon',
                '<x-icons.recover',
            ],
            app_path('Modules/Services/Resources/views/livewire/services/components/all-components.blade.php') => [
                '<x-icons.add-icon',
                '<x-icons.edit-icon',
                '<x-icons.delete-icon',
            ],
        ];

        foreach ($targets as $path => $requiredTags) {
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
            $this->assertTrue(File::exists($path), sprintf('%s not found', $relative));

            $content = (string) File::get($path);
            $this->assertStringNotContainsString("@include('components.icons.", $content, $relative);
            $this->assertStringNotContainsString('@include("components.icons.', $content, $relative);

            foreach ($requiredTags as $tag) {
                $this->assertStringContainsString($tag, $content, sprintf('%s missing %s', $relative, $tag));
            }
        }
    }

    public function test_modal_components_contain_explicit_icon_markup(): void
    {
        $targets = [
            resource_path('views/components/modal-confirm.blade.php'),
            resource_path('views/components/modal-delete.blade.php'),
            resource_path('views/components/modal-confirm-lg.blade.php'),
            resource_path('views/components/ui/confirmation-modal.blade.php'),
            resource_path('views/components/side-modal.blade.php'),
        ];

        foreach ($targets as $path) {
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
            $this->assertTrue(File::exists($path), sprintf('%s not found', $relative));

            $content = (string) File::get($path);
            $this->assertStringNotContainsString("@include('components.icons.", $content, $relative);
            $this->assertStringNotContainsString('@include("components.icons.', $content, $relative);

            $hasInlineSvg = str_contains($content, '<svg');
            $hasIconComponent = str_contains($content, '<x-icons.');
            $this->assertTrue($hasInlineSvg || $hasIconComponent, sprintf('%s has no icon markup', $relative));
        }
    }

    public function test_core_icon_component_files_contain_svg_markup(): void
    {
        $samples = [
            resource_path('views/components/icons/print-file.blade.php'),
            resource_path('views/components/icons/document-icon.blade.php'),
            resource_path('views/components/icons/delete-icon.blade.php'),
            resource_path('views/components/icons/recover.blade.php'),
            resource_path('views/components/icons/root.blade.php'),
        ];

        foreach ($samples as $sample) {
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $sample);
            $this->assertTrue(File::exists($sample), sprintf('%s not found', $relative));
            $content = (string) File::get($sample);

            if (str_ends_with($sample, 'root.blade.php')) {
                $this->assertStringContainsString('<svg', $content, sprintf('%s does not contain svg markup', $relative));
                continue;
            }

            $hasInlineSvg = str_contains($content, '<svg');
            $hasRootWrapper = str_contains($content, '<x-icons.root');
            $this->assertTrue($hasInlineSvg || $hasRootWrapper, sprintf('%s has no icon render markup', $relative));
        }
    }
}
