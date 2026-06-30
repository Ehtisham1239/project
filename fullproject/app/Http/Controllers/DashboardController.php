<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Route to a role-specific dashboard view; all share one layout.
        if ($user->hasRole('admin')) {
            return view('dashboard.admin', compact('user'));
        }

        if ($user->hasRole('manager')) {
            return view('dashboard.manager', compact('user'));
        }

        return view('dashboard.agent', compact('user'));
    }
}
