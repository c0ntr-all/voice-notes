<?php

namespace App\Providers;

use App\Domain\Notes\Contracts\NoteRepository;
use App\Domain\Users\Contracts\UserRepository;
use App\Infrastructure\Persistence\EloquentNoteRepository;
use App\Infrastructure\Persistence\EloquentUserRepository;
use App\Models\Note;
use App\Policies\NotePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NoteRepository::class, EloquentNoteRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(Note::class, NotePolicy::class);

        Password::defaults(fn () => Password::min(8)->max(255));

        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->getAuthIdentifier() ?: $request->ip();

            return Limit::perMinute(60)->by((string) $key);
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by((string) $request->ip());
        });
    }
}
