<?php

namespace App\Modules\Integration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Integration\Application\Services\EmployeeFeedService;
use App\Modules\Integration\Support\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * The employee feed, one cursor page at a time.
 *
 * `after` is the last id the caller applied, not a page number: rows are added
 * while a large first load is in progress, and a page number would slide over
 * them without anyone noticing a gap.
 */
class EmployeeFeedController extends Controller
{
    public function __invoke(Request $request, EmployeeFeedService $feed): JsonResponse
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

        $page = $feed->page(
            after: (int) $request->query('after', 0),
            limit: (int) $request->query('limit', (string) Contract::DEFAULT_LIMIT),
        );

        return response()->json(['data' => $page]);
    }
}
