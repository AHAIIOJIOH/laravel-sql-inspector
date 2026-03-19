<?php

namespace Ahaiiojioh\LaravelSqlInspector\Http;

use Ahaiiojioh\LaravelSqlInspector\Profiling\ProfilerManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SqlInspectorMiddleware
{
    public function __construct(
        private ProfilerManager $profiler,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        $requestId = $request->headers->get('X-Request-Id')
            ?: $request->attributes->get('sql_inspector_request_id')
            ?: (string) str()->uuid();

        $this->profiler->startHttpSession([
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'route' => $route?->getName() ?: $route?->uri(),
        ]);

        try {
            return $next($request);
        } finally {
            $this->profiler->finishCurrentSession();
        }
    }
}
