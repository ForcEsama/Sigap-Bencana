<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOfficer
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            return response()->json([
                'message' => 'Akun tidak aktif atau belum login.',
            ], 403);
        }

        if (! in_array($user->role, ['petugas_lapangan', 'admin'], true)) {
            return response()->json([
                'message' => 'Akses hanya untuk petugas lapangan atau admin.',
            ], 403);
        }

        return $next($request);
    }
}
