<?php

namespace App\Http\Controllers\Api;

use App\Application\Recordings\AcceptRecording;
use App\Http\Requests\Recordings\StoreRecordingRequest;
use App\Http\Resources\RecordingResource;
use Illuminate\Http\JsonResponse;

class RecordingController extends ApiController
{
    public function store(StoreRecordingRequest $request, AcceptRecording $acceptRecording): JsonResponse
    {
        $result = $acceptRecording->handle($this->user($request), $request->toData());

        return RecordingResource::make($result->recording)
            ->response()
            ->setStatusCode($result->created ? 201 : 200);
    }
}
