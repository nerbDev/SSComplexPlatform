<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\MaintenanceSchedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Walk-in display monitor (kiosk) for the lobby screen: /display (+ /display/data for the 60-second refresh).
 *
 * Public and read-only. It only ever exposes today's event titles/times, blocked slots, facility info and posters —
 * never client names or contact details. Set SSC_KIOSK_KEY in .env to require ?key=... on the URL, and
 * SSC_KIOSK_SHOW_TITLES=false to show "Reserved" instead of event titles.
 */
class KioskController extends Controller
{
    /** Posters with these statuses are never shown on the screen. */
    private const HIDDEN_POSTER_STATUSES = ['cancelled', 'finished'];

    private const POSTER_STATUS_LABELS = [
        'open'    => 'Open for booking',
        'full'    => 'Fully booked',
        'ongoing' => 'Happening now',
    ];

    public function index(Request $request): View
    {
        $this->authorizeKey($request);

        $mapRel = 'images/map/facility-map.png';

        return view('kiosk.index', [
            'data'     => $this->payload(),
            'dataUrl'  => route('kiosk.data', array_filter(['key' => $request->query('key')])),
            'mapImage' => file_exists(public_path($mapRel)) ? asset($mapRel) : null,
            'logo'     => file_exists(public_path('images/logo/subic_lgu.png')) ? asset('images/logo/subic_lgu.png') : null,
            'bg'       => asset('images/hero/SSCfrontview.jpg'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizeKey($request);

        return response()->json($this->payload())->header('Cache-Control', 'no-store');
    }

    private function authorizeKey(Request $request): void
    {
        $key = config('ssc.kiosk_key');

        if ($key && !hash_equals((string) $key, (string) $request->query('key'))) {
            abort(403, 'This display needs its access key.');
        }
    }

    private function payload(): array
    {
        $today        = Carbon::today();
        $showTitles   = (bool) config('ssc.kiosk_show_event_titles', true);
        $facilities   = Facility::where('is_active', true)->orderBy('id')->get();

        // ---- today's confirmed events, per facility
        $events = Appointment::whereDate('event_date', $today)
            ->whereIn('status', ['scheduled', 'finished'])
            ->orderBy('start_time')->get(['facility_id', 'activity_title', 'start_time', 'end_time', 'status'])
            ->groupBy('facility_id');

        // ---- planned maintenance today
        $maintenance = Schema::hasTable('maintenance_schedules')
            ? MaintenanceSchedule::whereDate('scheduled_date', $today)->whereIn('status', MaintenanceSchedule::OPEN)
                ->get(['facility_id', 'start_time', 'end_time'])->groupBy('facility_id')
            : collect();

        // ---- admin-blocked slots (the same table the booking wizard checks)
        $blocks = Schema::hasTable('unavailable_slots')
            ? DB::table('unavailable_slots')->whereDate('date', $today->toDateString())->get()
            : collect();

        $facilityData = $facilities->map(function (Facility $f) use ($events, $maintenance, $blocks, $showTitles) {
            $items = [];

            foreach ($events->get($f->id, collect()) as $e) {
                $items[] = [
                    'type'  => 'event',
                    'start' => substr($e->start_time, 0, 5),
                    'end'   => substr($e->end_time, 0, 5),
                    'label' => $showTitles ? $e->activity_title : 'Reserved',
                    'done'  => $e->status === 'finished',
                ];
            }

            foreach ($maintenance->get($f->id, collect()) as $m) {
                $items[] = ['type' => 'maintenance', 'start' => substr($m->start_time, 0, 5), 'end' => substr($m->end_time, 0, 5), 'label' => 'Facility maintenance', 'done' => false];
            }

            foreach ($blocks as $b) {
                if ($b->unit !== null && $b->unit !== $f->name) {
                    continue;
                }
                $whole = $b->start_time === null && $b->end_time === null;
                $items[] = [
                    'type'  => 'blocked',
                    'start' => $whole ? '00:00' : substr($b->start_time, 0, 5),
                    'end'   => $whole ? '24:00' : substr($b->end_time, 0, 5),
                    'label' => $b->reason ?: 'Not available',
                    'done'  => false,
                ];
            }

            usort($items, fn($a, $b) => strcmp($a['start'], $b['start']));

            return [
                'id'          => $f->id,
                'slug'        => $f->slug,
                'name'        => $f->name,
                'location'    => $f->location,
                'capacity'    => (int) $f->capacity,
                'description' => $f->description,
                'image'       => $this->imageUrl($f->image_path ?? null),
                'items'       => $items,
            ];
        })->values()->all();

        // ---- posters (same table the landing page / staff dashboard carousel reads)
        $posters = Schema::hasTable('posters')
            ? DB::table('posters')
                ->where('is_active', true)
                ->whereNotIn('status', self::HIDDEN_POSTER_STATUSES)
                ->where(fn($q) => $q->whereNull('event_date')->orWhereDate('event_date', '>=', $today->toDateString()))
                ->orderBy('sort_order')->orderBy('event_date')->limit(12)->get()
                ->map(fn($p) => [
                    'id'          => $p->id,
                    'title'       => $p->title,
                    'image'       => $this->imageUrl($p->image_path),
                    'date'        => $p->event_date ? Carbon::parse($p->event_date)->format('l, F j, Y') : null,
                    'time'        => $p->event_time,
                    'unit'        => $p->unit,
                    'description' => $p->description,
                    'status'      => $p->status,
                    'status_label' => self::POSTER_STATUS_LABELS[$p->status] ?? ucfirst((string) $p->status),
                ])->values()->all()
            : [];

        return [
            'today'      => $today->format('l, F j, Y'),
            'generated'  => now()->toIso8601String(),
            'facilities' => $facilityData,
            'posters'    => $posters,
        ];
    }

    /** Works whether the image lives in public/ (e.g. images/…) or on the public storage disk (storage:link). */
    private function imageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $path = ltrim($path, '/');

        return file_exists(public_path($path)) ? asset($path) : asset('storage/' . $path);
    }
}