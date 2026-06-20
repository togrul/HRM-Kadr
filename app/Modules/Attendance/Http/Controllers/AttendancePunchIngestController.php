<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Application\Services\AttendancePunchIngestService;
use App\Modules\Attendance\Jobs\ProcessAttendancePunchesJob;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AttendancePunchIngestController extends Controller
{
    public function __invoke(Request $request, AttendancePunchIngestService $service): JsonResponse
    {
        $configuredToken = (string) config('attendance.ingest.token', '');
        $incomingToken = (string) ($request->bearerToken() ?: $request->header('X-Attendance-Token', ''));

        if ($configuredToken === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Attendance ingest token is not configured.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (! hash_equals($configuredToken, $incomingToken)) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized ingest token.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validator = Validator::make($request->all(), [
            'source' => ['nullable', 'string', 'max:24'],
            'device_ref' => ['nullable', 'string', 'max:191'],
            'punches' => ['required', 'array', 'min:1'],
            'punches.*.tabel_no' => ['required', 'string', 'max:255'],
            'punches.*.punched_at' => ['required', 'date'],
            'punches.*.direction' => ['nullable', 'in:in,out,break_in,break_out'],
            'punches.*.external_id' => ['nullable', 'string', 'max:191'],
            'punches.*.meta' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()->toArray(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $source = (string) ($request->input('source') ?: config('attendance.ingest.default_source', 'api'));
        $deviceRef = $request->input('device_ref');
        $punches = $request->input('punches', []);

        $result = $service->ingest($punches, $source, $deviceRef ? (string) $deviceRef : null);

        $queued = false;
        if ((bool) config('attendance.ingest.auto_process', false)) {
            /** @var Collection<int,Carbon> $times */
            $times = collect($punches)
                ->pluck('punched_at')
                ->filter()
                ->map(fn ($value) => Carbon::parse((string) $value))
                ->sortBy(fn (Carbon $carbon) => $carbon->timestamp)
                ->values();

            if ($times->isNotEmpty()) {
                ProcessAttendancePunchesJob::dispatch(
                    fromDate: $times->first()->toDateString(),
                    toDate: $times->last()->toDateString(),
                    source: $source
                );
                $queued = true;
            }
        }

        return response()->json([
            'ok' => true,
            'source' => $source,
            'processing_queued' => $queued,
            'result' => $result,
        ]);
    }
}
