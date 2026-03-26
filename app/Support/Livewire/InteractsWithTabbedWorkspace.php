<?php

namespace App\Support\Livewire;

trait InteractsWithTabbedWorkspace
{
    protected function bootActiveTabFromRequest(?string $requestedTab = null): void
    {
        $requestedTab ??= (string) request()->query('tab', $this->defaultTab());

        if (in_array($requestedTab, $this->allowedTabs(), true)) {
            $this->activeTab = $requestedTab;

            return;
        }

        $this->activeTab = $this->defaultTab();
    }

    public function switchTab(string $tab): void
    {
        if (! in_array($tab, $this->allowedTabs(), true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    /**
     * @return array<int, string>
     */
    abstract protected function allowedTabs(): array;

    protected function defaultTab(): string
    {
        return $this->allowedTabs()[0] ?? 'overview';
    }
}
