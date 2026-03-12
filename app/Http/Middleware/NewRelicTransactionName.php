<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NewRelicTransactionName
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (extension_loaded('newrelic')) {
            $route = $request->route();
            if ($route) {
                $name = $route->getActionName();
                if ($name === 'Closure' || !$name) {
                    $name = $request->method() . ' ' . $route->uri();
                }
                newrelic_name_transaction($name);
            }
        }

        return $next($request);
    }
}
