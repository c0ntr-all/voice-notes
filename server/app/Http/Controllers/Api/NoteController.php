<?php

namespace App\Http\Controllers\Api;

use App\Application\Notes\DeleteNote;
use App\Application\Notes\ListNotes;
use App\Application\Notes\ShowNote;
use App\Application\Notes\UpdateNote;
use App\Application\Notes\UpsertNote;
use App\Http\Requests\Notes\ListNotesRequest;
use App\Http\Requests\Notes\StoreNoteRequest;
use App\Http\Requests\Notes\UpdateNoteRequest;
use App\Http\Resources\NoteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class NoteController extends ApiController
{
    public function index(ListNotesRequest $request, ListNotes $listNotes): AnonymousResourceCollection
    {
        $perPage = (int) ($request->validated('per_page') ?? 50);

        return NoteResource::collection(
            $listNotes->handle($this->user($request), $perPage),
        );
    }

    public function store(StoreNoteRequest $request, UpsertNote $upsertNote): JsonResponse
    {
        $result = $upsertNote->handle($this->user($request), $request->toData());

        return NoteResource::make($result->note)
            ->response()
            ->setStatusCode($result->created ? 201 : 200);
    }

    public function show(Request $request, string $note, ShowNote $showNote): NoteResource
    {
        return NoteResource::make($showNote->handle($this->user($request), $note));
    }

    public function update(UpdateNoteRequest $request, string $note, UpdateNote $updateNote): NoteResource
    {
        return NoteResource::make(
            $updateNote->handle($this->user($request), $note, $request->toData()),
        );
    }

    public function destroy(Request $request, string $note, DeleteNote $deleteNote): Response
    {
        $deleteNote->handle($this->user($request), $note);

        return response()->noContent();
    }
}
