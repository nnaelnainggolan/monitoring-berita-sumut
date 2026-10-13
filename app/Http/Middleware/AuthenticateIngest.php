<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateIngest
{
    public function handle(Request $request, Closure $next)
    {
        $expected = config('services.n8n.ingest_token');
        $provided = $request->header('X-API-Key');
        abort_unless(is_string($expected) && $expected !== '' && is_string($provided)
            && $provided !== '' && hash_equals($expected, $provided), 401, 'Unauthorized');
        return $next($request);
    }
}
