<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookmarkService;
use App\Support\LampaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BookmarkController extends Controller
{
    public function __construct(protected BookmarkService $bookmarks)
    {
    }

    public function dump(Request $request): Response
    {
        return LampaResponse::plainJson($this->bookmarks->dump($request->attributes->get('activeProfile')));
    }

    public function changelog(Request $request): JsonResponse
    {
        return response()->json($this->bookmarks->changelog(
            $request->attributes->get('activeProfile'),
            (int) $request->integer('since', 0),
        ));
    }

    public function add(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'data' => ['required'],
            'card_id' => ['required', 'integer'],
            'id' => ['nullable', 'integer'],
        ]);

        $this->bookmarks->add($request->attributes->get('activeProfile'), $payload);

        return response()->json(LampaResponse::success());
    }

    public function remove(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'type' => ['nullable', 'string', 'max:50'],
            'data' => ['nullable'],
            'card_id' => ['nullable', 'integer'],
            'id' => ['nullable', 'integer'],
        ]);

        $this->bookmarks->remove($request->attributes->get('activeProfile'), $payload);

        return response()->json(LampaResponse::success());
    }

    public function clear(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'group' => ['required', 'string', 'max:50'],
        ]);

        $this->bookmarks->clear($request->attributes->get('activeProfile'), $payload['group']);

        return response()->json(LampaResponse::success());
    }

    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file'],
        ]);

        $this->bookmarks->sync($request->attributes->get('activeProfile'), $request->file('file'));

        return response()->json(LampaResponse::success());
    }
}
