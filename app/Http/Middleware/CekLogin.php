<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CekLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Jika session 'logged_in' tidak ada, tendang paksa ke halaman login
        if (!session()->has('logged_in')) {
            return redirect('/login');
        }

        return $next($request);
    }
}