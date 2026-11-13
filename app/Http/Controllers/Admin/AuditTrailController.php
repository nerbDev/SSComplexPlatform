<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Read-only audit trail for admins: who did what, when, from where — across appointment status changes,
 * appointment actions, payments/refunds, staff actions, admin actions and sign-ins.
 * Entries are written by AuditLog::record(...) throughout the app and are never edited or deleted here.
 */
class AuditTrailController extends Controller
{
    /** Category chips: which action prefixes belong where (SQL LIKE patterns). */
    private const CATEGORIES = [
        'status'       => ['Status changes',         ['appointment.status_changed']],
        'appointments' => ['Appointments',           ['reservation.%', 'appointment.%', 'client.appointment.%', 'admin.appointment.%', 'admin.appointments.%', 'client.reschedule.%']],
        'payments'     => ['Payments & refunds',     ['payment.%', 'receipt.%', 'ob.refund.%']],
        'staff'        => ['Staff actions',          ['staff.%']],
        'admin'        => ['Admin actions',          ['admin.%', 'ob.%', 'sa.%']],
        'maintenance'  => ['Maintenance & inventory', ['sa.maintenance%', 'staff.maintenance%', 'staff.inventory.%']],
        'damage'       => ['Damage & incidents',     ['ob.damage.%', 'staff.incident.%']],
        'feedback'     => ['Feedback',               ['feedback.%']],
        'security'     => ['Sign-ins',               ['auth.%', 'admin.focus_area.%']],
    ];

    public function index(Request $request): View
    {
        $this->validateFilters($request);

        $page = $this->query($request)->orderByDesc('audit_logs.created_at')->orderByDesc('audit_logs.id')
            ->paginate(25)->withQueryString();

        $rows = $page->getCollection()->map(fn($l) => $this->row($l))->values()->all();

        $base = fn() => $this->query($request);

        $users = DB::table('audit_logs')->join('users', 'users.id', '=', 'audit_logs.user_id')
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.role')
            ->distinct()->orderBy('users.first_name')->limit(300)->get()
            ->map(fn($u) => ['id' => $u->id, 'label' => trim($u->first_name . ' ' . $u->last_name) . ' (' . $u->role . ')'])->all();

        return view('admin.audit.index', [
            'rows'       => $rows,
            'page'       => $page,
            'categories' => collect(self::CATEGORIES)->map(fn($c) => $c[0])->all(),
            'users'      => $users,
            'filters'    => [
                'from'     => $request->query('from'),
                'to'       => $request->query('to'),
                'user_id'  => $request->query('user_id'),
                'role'     => $request->query('role'),
                'category' => $request->query('category'),
                'q'        => trim((string) $request->query('q', '')),
            ],
            'stats'      => [
                'entries'  => $base()->count(),
                'people'   => $base()->whereNotNull('audit_logs.user_id')->distinct()->count('audit_logs.user_id'),
                'status'   => $base()->where('audit_logs.action', 'appointment.status_changed')->count(),
                'failed'   => $base()->where('audit_logs.action', 'auth.login_failed')->count(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->validateFilters($request);

        $logs = $this->query($request)->orderByDesc('audit_logs.created_at')->orderByDesc('audit_logs.id')->limit(10000)->get();

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', 'When', 'User', 'Role', 'Action', 'Description', 'Subject', 'IP address']);

            foreach ($logs as $l) {
                $r = $this->row($l);
                fputcsv($out, [$r['id'], $r['when_full'], $r['user'], $r['role'], $r['action'], $r['description'], $r['subject'], $r['ip']]);
            }
            fclose($out);
        }, 'audit-trail-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'from'     => ['nullable', 'date'],
            'to'       => ['nullable', 'date'],
            'user_id'  => ['nullable', 'string', 'max:10'],
            'role'     => ['nullable', 'in:admin,staff,client,guest'],
            'category' => ['nullable', 'in:' . implode(',', array_keys(self::CATEGORIES))],
            'q'        => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function query(Request $request): Builder
    {
        $q = trim((string) $request->query('q', ''));

        return AuditLog::query()
            ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->select('audit_logs.*', 'users.first_name', 'users.last_name', 'users.role as user_role', 'users.admin_type')
            ->when($request->filled('from'), fn($x) => $x->whereDate('audit_logs.created_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn($x) => $x->whereDate('audit_logs.created_at', '<=', $request->query('to')))
            ->when($request->query('user_id') === 'none', fn($x) => $x->whereNull('audit_logs.user_id'))
            ->when($request->filled('user_id') && $request->query('user_id') !== 'none', fn($x) => $x->where('audit_logs.user_id', (int) $request->query('user_id')))
            ->when($request->query('role') === 'guest', fn($x) => $x->whereNull('audit_logs.user_id'))
            ->when(in_array($request->query('role'), ['admin', 'staff', 'client'], true), fn($x) => $x->where('users.role', $request->query('role')))
            ->when($request->filled('category'), function ($x) use ($request) {
                $patterns = self::CATEGORIES[$request->query('category')][1];
                $x->where(function ($w) use ($patterns) {
                    foreach ($patterns as $p) {
                        $w->orWhere('audit_logs.action', 'like', $p);
                    }
                });
            })
            ->when($q !== '', fn($x) => $x->where(fn($w) => $w->where('audit_logs.description', 'like', "%{$q}%")->orWhere('audit_logs.action', 'like', "%{$q}%")));
    }

    private function row($l): array
    {
        $created = Carbon::parse($l->created_at);
        $adminTypes = config('ssc.admin_types', []);

        $role = $l->user_role
            ? ucfirst($l->user_role) . ($l->admin_type ? ' · ' . ($adminTypes[$l->admin_type]['label'] ?? $l->admin_type) : '')
            : 'Guest / system';

        $subject = $l->subject_type ? class_basename($l->subject_type) . ($l->subject_id ? ' #' . $l->subject_id : '') : '—';

        return [
            'id'          => $l->id,
            'when'        => $created->format('M j, Y · g:i A'),
            'when_full'   => $created->format('Y-m-d H:i:s'),
            'user'        => $l->user_id ? (trim(($l->first_name ?? '') . ' ' . ($l->last_name ?? '')) ?: 'User #' . $l->user_id) : '—',
            'role'        => $role,
            'action'      => $l->action,
            'action_label' => collect(explode('.', $l->action))->map(fn($p) => Str::headline($p))->implode(' › '),
            'description' => $l->description,
            'subject'     => $subject,
            'ip'          => $l->ip_address,
            'tone'        => str_contains($l->action, 'failed') ? 'bad' : (str_starts_with($l->action, 'auth.') ? 'gray' : (str_contains($l->action, 'status_changed') ? 'info' : 'ok')),
        ];
    }
}