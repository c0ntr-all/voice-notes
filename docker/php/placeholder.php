<?php

header('Content-Type: text/plain; charset=utf-8');

echo "Laravel ещё не установлен в ./server.\n\n";
echo "docker compose exec app composer create-project laravel/laravel:^13.0 .\n";
echo "docker compose exec app php artisan key:generate\n";
echo "docker compose restart app\n";
