<?php

namespace App\Modules\Orders\Support\Traits\Templates;

use App\Models\OrderType;
use App\Modules\Orders\Application\UseCases\Templates\SetTypeMetadataBootstrapUseCase;
use App\Models\OrderTemplateVersion;

trait HandlesSetTypeMetadataBootstrap
{
    private function ensureUiMetadataInitialized(
        OrderType $orderType,
        bool $syncMissing = false,
        bool $allowAutoCreateVersion = true
    ): ?OrderTemplateVersion
    {
        return app(SetTypeMetadataBootstrapUseCase::class)->ensureInitialized(
            $orderType,
            $syncMissing,
            $allowAutoCreateVersion,
            auth()->id()
        );
    }

    private function bootstrapLegacyMetadata(OrderType $orderType, OrderTemplateVersion $version, bool $strictSync = false): void
    {
        app(SetTypeMetadataBootstrapUseCase::class)->syncLegacyMetadata(
            $orderType,
            $version,
            $strictSync,
            auth()->id()
        );
    }
}
