<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePersonaExists
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->persona) {
            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
