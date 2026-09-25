<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Poster;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $hasAppointments = Schema::hasTable('appointments');

        return view('staff.dashboard', [
            'posters'               => $this->posters(),
            'pendingCount'          => $hasAppointments ? DB::table('appointments')->where('status', 'pending')->count() : 0,
            'pendingVerificationCount' => $hasAppointments ? DB::table('appointments')->where('status', 'pending')->count() : 0,
            'verifiedTodayCount'    => $hasAppointments ? DB::table('appointments')->where('status', 'verified')->whereDate('verified_at', Carbon::today())->count() : 0,
            'thisWeekCount'         => $hasAppointments ? DB::table('appointments')
                ->whereBetween('event_date', [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->toDateString()])
                ->count() : 0,
            'pendingPreview'        => $this->pendingPreview($hasAppointments),
        ]);
    }

    private function posters(): array
    {
        if (!Schema::hasTable('posters')) {
            return [];
        }

        return Poster::active()->ordered()->get()->map(function (Poster $poster) {
            return [
                'image'       => $poster->image_path ? 'storage/' . $poster->image_path : null,
                'title'       => $poster->title,
                'date'        => optional($poster->event_date)->format('M j, Y'),
                'time'        => $poster->event_time,
                'unit'        => $poster->unit,
                'description' => $poster->description,
                'status'      => ucfirst($poster->status),
            ];
        })->toArray();
    }

    private function pendingPreview(bool $hasAppointments): array
    {
        if (!$hasAppointments) {
            return [];
        }

        $typeLabels = [
            'appointment'      => 'Appointment',
            'room_reservation' => 'Room Reservation',
            'free_use'         => 'Free Use',
        ];

        return DB::table('appointments')
            ->join('users', 'users.id', '=', 'appointments.user_id')
            ->join('facilities', 'facilities.id', '=', 'appointments.facility_id')
            ->where('appointments.status', 'pending')
            ->orderBy('appointments.event_date')
            ->orderBy('appointments.start_time')
            ->limit(5)
            ->get([
                'appointments.activity_title', 'appointments.booking_type',
                'appointments.event_date', 'appointments.start_time',
                'facilities.name as facility_name',
                'users.first_name', 'users.last_name',
            ])
            ->map(fn($row) => [
                'client_name' => trim($row->first_name . ' ' . $row->last_name) ?: 'Unnamed client',
                'activity'    => $row->activity_title,
                'facility'    => $row->facility_name,
                'date_time'   => Carbon::parse($row->event_date)->format('M j, Y') . ' · ' . Carbon::parse($row->start_time)->format('g:i A'),
                'type_label'  => $typeLabels[$row->booking_type] ?? ucfirst($row->booking_type),
            ])->toArray();
    }
}