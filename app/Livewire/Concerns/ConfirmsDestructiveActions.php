<?php

namespace App\Livewire\Concerns;

use ReflectionMethod;
use RuntimeException;

trait ConfirmsDestructiveActions
{
    public bool $showDeleteConfirmation = false;

    /**
     * @var array<string,mixed>
     */
    public array $deleteConfirmation = [
        'action' => null,
        'parameters' => [],
        'title' => 'ui::common.destructive.title',
        'message' => null,
        'description' => 'ui::common.destructive.description',
        'confirm_label' => 'ui::common.actions.delete',
    ];

    public function confirmDeletion(
        string $action,
        array $parameters = [],
        ?string $title = null,
        ?string $message = null,
        ?string $description = null,
        ?string $confirmLabel = null
    ): void {
        $this->deleteConfirmation = [
            'action' => $action,
            'parameters' => $parameters,
            'title' => $title ?: 'ui::common.destructive.title',
            'message' => $message,
            'description' => $description ?: 'ui::common.destructive.description',
            'confirm_label' => $confirmLabel ?: 'ui::common.actions.delete',
        ];

        $this->showDeleteConfirmation = true;
    }

    public function closeDeleteConfirmation(): void
    {
        $this->showDeleteConfirmation = false;
        $this->deleteConfirmation = [
            'action' => null,
            'parameters' => [],
            'title' => 'ui::common.destructive.title',
            'message' => null,
            'description' => 'ui::common.destructive.description',
            'confirm_label' => 'ui::common.actions.delete',
        ];
    }

    public function runConfirmedDeletion()
    {
        $action = (string) data_get($this->deleteConfirmation, 'action', '');

        if ($action === '') {
            return null;
        }

        if (! method_exists($this, $action)) {
            throw new RuntimeException(sprintf('Delete confirmation action [%s] is not defined.', $action));
        }

        $result = app()->call([$this, $action], $this->mapDeleteConfirmationParameters($action));

        $this->closeDeleteConfirmation();

        return $result;
    }

    /**
     * @return array<string,mixed>
     */
    private function mapDeleteConfirmationParameters(string $action): array
    {
        $payload = (array) data_get($this->deleteConfirmation, 'parameters', []);
        $method = new ReflectionMethod($this, $action);
        $parameters = [];

        foreach ($method->getParameters() as $index => $parameter) {
            if (array_key_exists($parameter->getName(), $payload)) {
                $parameters[$parameter->getName()] = $payload[$parameter->getName()];

                continue;
            }

            if (array_key_exists($index, $payload)) {
                $parameters[$parameter->getName()] = $payload[$index];
            }
        }

        return $parameters;
    }
}
