<?php

namespace App\Modules\Personnel\Contracts;

use App\Models\Personnel;

/**
 * Sanctioned cross-module surface for resolving a personnel's HR approval route
 * (manager chain, direct reports, preview cards). Other modules depend on THIS
 * interface — never on the concrete Personnel\...\MyHr implementation — so the
 * Personnel module can evolve its internals without breaking consumers.
 *
 * @see \App\Modules\Personnel\Application\Services\MyHr\ApprovalRouteResolverService
 */
interface ApprovalRouteResolver
{
    /**
     * @return array<string,mixed>
     */
    public function resolve(Personnel $personnel, string $requestType): array;

    /**
     * @return array<string,mixed>
     */
    public function preview(Personnel $personnel, string $requestType, int $chainLimit = 5): array;

    /**
     * @return array<int,mixed>
     */
    public function managerChain(Personnel $personnel): array;

    /**
     * @return array<int,mixed>
     */
    public function directReports(Personnel $manager): array;

    /**
     * @return array<string,mixed>|null
     */
    public function manager(Personnel $personnel): ?array;

    /**
     * @return array<string,mixed>
     */
    public function personnelPreviewCard(Personnel $personnel): array;
}
