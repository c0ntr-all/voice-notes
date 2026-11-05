<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, 'Требуется авторизация.');
        }

        return $user;
    }
}
