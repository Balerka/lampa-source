<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TimelineService;
use App\Support\LampaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TimelineController extends Controller
{
    public function __construct(protected TimelineService $timelines)
    {
    }

    public function dump(Request $request): Response
    {
        return LampaResponse::plainJson($this->timelines->dump($request->attributes->get('activeProfile')));
    }

    public function changelog(Request $request): JsonResponse
    {
        return response()->json($this->timelines->changelog(
            $request->attributes->get('activeProfile'),
            (int) $request->integer('since', 0),
        ));
    }

    public function update(Request $request): JsonResponse
    {
        $result = $this->timelines->update(
            $request->attributes->get('activeProfile'),
            $request->only(['hash', 'percent', 'time', 'duration']),
        );

        return response()->json(LampaResponse::success([
            'version' => $result['version'],
            'timeline' => $result['timeline'],
        ]));
    }
}
