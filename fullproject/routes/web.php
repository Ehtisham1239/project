<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- Milestone 2: Communication Platform ---
    Route::prefix('inbox')->name('inbox.')->group(function () {
        Route::get('/', [InboxController::class, 'index'])->name('index');
        Route::get('/{conversation}', [InboxController::class, 'show'])->name('show');
        Route::post('/{conversation}/message', [InboxController::class, 'sendMessage'])->name('message');
        Route::post('/{conversation}/template', [InboxController::class, 'sendTemplate'])->name('template');
        Route::post('/{conversation}/note', [InboxController::class, 'addNote'])->name('note');
        Route::post('/{conversation}/assign', [InboxController::class, 'assign'])->name('assign');
        Route::patch('/{conversation}/status', [InboxController::class, 'updateStatus'])->name('status');
    });

    // --- Milestone 3: CRM, Automation & AI ---
    Route::middleware('permission:leads.view')->group(function () {
        Route::get('/crm/pipeline', [LeadController::class, 'index'])->name('crm.pipeline');
        Route::get('/crm/appointments', [AppointmentController::class, 'index'])->name('crm.appointments');
    });
    Route::middleware('permission:leads.manage')->group(function () {
        Route::post('/crm/leads', [LeadController::class, 'store'])->name('crm.leads.store');
        Route::patch('/crm/leads/{lead}/move', [LeadController::class, 'move'])->name('crm.leads.move');
        Route::post('/crm/leads/{lead}/summarize', [LeadController::class, 'summarize'])->name('crm.leads.summarize');
        Route::post('/crm/appointments', [AppointmentController::class, 'store'])->name('crm.appointments.store');
        Route::patch('/crm/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('crm.appointments.status');
    });

    // --- Milestone 4: Reports ---
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });
    Route::middleware('permission:reports.export')->group(function () {
        Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
        Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
    });

    // --- Milestone 1: Admin / RBAC ---
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.role');
        Route::patch('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggle');
    });
});
