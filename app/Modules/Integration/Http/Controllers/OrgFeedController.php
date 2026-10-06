<?php

namespace App\Modules\Integration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Integration\Application\Services\AttendanceFeedService;
use App\Modules\Integration\Application\Services\CompensationFeedService;
use App\Modules\Integration\Application\Services\LeaveBalanceFeedService;
use App\Modules\Integration\Application\Services\OrderFeedService;
use App\Modules\Integration\Application\Services\OrgFeedService;
use App\Modules\Integration\Support\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * The cursor-paged feeds: structure tree, positions, orders.
 *
 * One controller because they share the cursor contract exactly; the only
 * difference is which service answers.
 */
class OrgFeedController extends Controller
{
    public function units(Request $request, OrgFeedService $feed): JsonResponse
    {
        return $this->serve($request, fn (int $after, int $limit) => $feed->units($after, $limit));
    }

    public function positions(Request $request, OrgFeedService $feed): JsonResponse
    {
        return $this->serve($request, fn (int $after, int $limit) => $feed->positions($after, $limit));
    }

    public function orders(Request $request, OrderFeedService $feed): JsonResponse
    {
        return $this->serve($request, fn (int $after, int $limit) => $feed->page($after, $limit));
    }

    public function compensation(Request $request, CompensationFeedService $feed): JsonResponse
    {
        return $this->serve($request, fn (int $after, int $limit) => $feed->page($after, $limit));
    }

    /**
     * Leave balances are per calendar year; the year defaults to the current one
     * so the feed can be listed without arguments alongside the others.
     */
    public function leaveBalance(Request $request, LeaveBalanceFeedService $feed): JsonResponse
    {
        $year = (int) ($request->query('year') ?: now()->year);

        return $this->serve($request, fn (int $after, int $limit) => $feed->page($year, $after, $limit));
    }

    /**
     * The attendance feed needs a period as well as a cursor: a month is the
     * unit payroll works in, and asking for "everything" would mean streaming
     * every ledger row ever recorded.
     */
    public function attendance(Request $request, AttendanceFeedService $feed): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'after' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.Contract::MAX_LIMIT],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => __('integration::api.errors.invalid_query'),
                'errors' => $validator->errors()->toArray(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['data' => $feed->page(
            (int) $request->query('year'),
            (int) $request->query('month'),
            (int) $request->query('after', 0),
            (int) $request->query('limit', (string) Contract::DEFAULT_LIMIT),
        )]);
    }

    /** @param  callable(int, int): array{items: list<array<string, mixed>>, last_sequence: int, has_more: bool}  $page */
    private function serve(Request $request, callable $page): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'after' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.Contract::MAX_LIMIT],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => __('integration::api.errors.invalid_query'),
                'errors' => $validator->errors()->toArray(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['data' => $page(
            (int) $request->query('after', 0),
            (int) $request->query('limit', (string) Contract::DEFAULT_LIMIT),
        )]);
    }
}
