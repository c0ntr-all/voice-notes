<?php

namespace App\Domain\Recordings\Contracts;

use App\Domain\Recordings\RecordingData;
use App\Models\Recording;
use App\Models\User;

interface RecordingRepository
{
    public function findForUser(User $user, string $clientUuid): ?Recording;

    public function create(User $user, RecordingData $data, string $path): Recording;
}
