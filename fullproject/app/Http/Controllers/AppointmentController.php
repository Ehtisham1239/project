<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::with(['contact', 'agent'])
            ->orderBy('scheduled_at')
            ->paginate(25);

        return view('crm.appointments', compact('appointments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['required', 'exists:contacts,id'],
            'title' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        Appointment::create($data);

        return back()->with('status', 'Appointment scheduled.');
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'status' => ['required', 'in:scheduled,confirmed,completed,cancelled,no_show'],
        ]);

        $appointment->update($data);

        return back();
    }
}
