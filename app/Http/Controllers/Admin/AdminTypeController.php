<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Lets an admin change their own focus area (users.admin_type) from the sidebar dropdown,
 * so nobody needs database access to move between OB Admin and Sports & Activity Admin.
 *
 * Allowed values come from config/ssc.php, and every switch is written to the audit log.
 * Switching can be turned off with SSC_ADMIN_TYPE_SWITCHING=false.
 */
class AdminTypeController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        abort_unless(config('ssc.admin_type_switching', true), 403, 'Switching focus areas is turned off.');

        $user = $request->user();
        abort_unless($user && $user->role === 'admin', 403);

        $types = config('ssc.admin_types', []);

        $data = $request->validate([
            'admin_type' => ['required', Rule::in(array_keys($types))],
        ]);

        $from = $user->admin_type;
        $to   = $data['admin_type'];
        $label = $types[$to]['label'] ?? $to;

        if ($from === $to) {
            return redirect()->route('admin.dashboard')->with('focus_status', "You're already in the {$label} focus area.");
        }

        // forceFill so this works even if admin_type isn't in the User model's $fillable.
        $user->forceFill(['admin_type' => $to])->save();

        AuditLog::record(
            'admin.focus_area.switched',
            $user,
            'Admin switched focus area from ' . ($from ? ($types[$from]['label'] ?? $from) : 'not set') . " to {$label}"
        );

        // Always land on the dashboard: the page they were on may belong to the area they just left.
        return redirect()->route('admin.dashboard')->with('focus_status', "Switched to {$label}.");
    }
}