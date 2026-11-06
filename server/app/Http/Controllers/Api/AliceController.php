<?php

namespace App\Http\Controllers\Api;

use App\Application\Alice\HandleAliceRequest;
use App\Application\Alice\IssueAliceLinkCode;
use App\Http\Requests\Alice\AliceWebhookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AliceController extends ApiController
{
    public function webhook(AliceWebhookRequest $request, HandleAliceRequest $handleAliceRequest): JsonResponse
    {
        return response()->json($handleAliceRequest->handle($request->toAliceRequest())->toArray());
    }

    public function linkCode(Request $request, IssueAliceLinkCode $issueAliceLinkCode): JsonResponse
    {
        $user = $issueAliceLinkCode->handle($this->user($request));

        return response()->json([
            'code' => $user->alice_link_code,
            'expires_at' => $user->alice_link_code_expires_at?->toISOString(),
            'alice_linked' => $user->alice_user_id !== null,
        ]);
    }
}
