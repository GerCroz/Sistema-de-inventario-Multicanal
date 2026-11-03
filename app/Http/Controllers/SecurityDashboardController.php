<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class SecurityDashboardController extends Controller
{
    public function index(): View
    {
        $since = now()->subHours(24);
        $stats = [];

        foreach (['success', 'failed', 'blocked'] as $status) {
            $stats[$status] = LoginAttempt::where('status', $status)
                ->where('created_at', '>=', $since)->count();
        }

        $suspiciousIps = LoginAttempt::select(
            'ip_address', DB::raw('COUNT(*) as total')
        )->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->groupBy('ip_address')
            ->havingRaw('COUNT(*) >= 3')
            ->orderByDesc('total')->get();

        return view('security-dashboard', compact(
            'stats', 'suspiciousIps'
        ));
    }
}
