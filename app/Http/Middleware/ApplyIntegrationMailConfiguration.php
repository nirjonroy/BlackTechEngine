<?php

namespace App\Http\Middleware;

use App\Services\Integrations\RuntimeIntegrationConfigurator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyIntegrationMailConfiguration
{
    public function __construct(private readonly RuntimeIntegrationConfigurator $configurator) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->configurator->applyMailConfiguration();

        return $next($request);
    }
}
