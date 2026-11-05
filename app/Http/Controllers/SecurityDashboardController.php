<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $suspiciousAccounts = LoginAttempt::select(
            'identifier', DB::raw('COUNT(*) as total')
        )->where('status', 'failed')
            ->whereNotNull('identifier')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->groupBy('identifier')
            ->havingRaw('COUNT(*) >= 3')
            ->orderByDesc('total')->get();

        $recentEvents = LoginAttempt::orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('security-dashboard', compact(
            'stats', 'suspiciousIps', 'suspiciousAccounts', 'recentEvents'
        ));
    }

    public function export(): StreamedResponse
    {
        $events = LoginAttempt::orderByDesc('created_at')->get();

        return response()->streamDownload(function () use ($events): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['id', 'identifier', 'ip_address', 'status', 'user_agent', 'created_at']);

            foreach ($events as $event) {
                fputcsv($handle, [
                    $event->id,
                    $this->sanitizeCsvValue($event->identifier),
                    $this->sanitizeCsvValue($event->ip_address),
                    $this->sanitizeCsvValue($event->status),
                    $this->sanitizeCsvValue($event->user_agent),
                    $event->created_at,
                ]);
            }

            fclose($handle);
        }, 'security-evidence.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function sanitizeCsvValue(?string $value): string
    {
        $val = (string) $value;
        if ($val !== '' && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$val;
        }

        return $val;
    }
}
