<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class BlockIPMiddleware
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle($request, Closure $next)
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = 2; // Maximum attempts allowed
        $decaySeconds = 60; // Time window in seconds

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key); // Get time remaining until retry is available
            return $this->buildExceptionResponse($retryAfter);
        }

        // Increase the hit count
        $this->limiter->hit($key, $decaySeconds);

        // Continue with the request
        return $next($request);
    }

    protected function resolveRequestSignature($request)
    {
        return sha1(
            $request->method() . '|' .
            $request->server('SERVER_NAME') . '|' .
            $request->path() . '|' .
            $request->ip()
        );
    }

    protected function buildExceptionResponse($retryAfter)
    {
        $response = new Response(json_encode([
            'error' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter .' detik'
        ]), 429); // Status code 429 indicates "Too Many Requests"
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Retry-After', $retryAfter);
        return $response;
    }
}
