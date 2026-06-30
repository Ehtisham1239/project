<?php

/*
|--------------------------------------------------------------------------
| Middleware & scheduling registration — already wired in this package
|--------------------------------------------------------------------------
| This file is reference documentation only; the actual registration already
| lives in the package, so you don't need to copy anything by hand:
|
|   - Middleware aliases ('role', 'permission') + SecurityHeaders:
|     -> bootstrap/app.php (withMiddleware callback)
|
|   - Scheduled tasks (nightly backup):
|     -> routes/console.php (Schedule::command(...))
|
|   - Service provider registration:
|     -> bootstrap/providers.php
|
| Laravel 11 dropped app/Http/Kernel.php and app/Console/Kernel.php in favor
| of bootstrap/app.php + routes/console.php — if you're merging this into an
| existing Laravel 10-or-below project instead of a fresh Laravel 11 install,
| register these in Kernel.php's $routeMiddleware / $schedule methods instead.
*/
