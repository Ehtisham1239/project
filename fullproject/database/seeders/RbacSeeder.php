<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'agent' => 'Agent',
        ];

        foreach ($roles as $name => $label) {
            Role::firstOrCreate(['name' => $name], ['label' => $label]);
        }

        $permissions = [
            // Core / admin
            ['name' => 'users.manage', 'label' => 'Manage Users', 'module' => 'Core'],
            ['name' => 'roles.manage', 'label' => 'Manage Roles', 'module' => 'Core'],
            ['name' => 'settings.manage', 'label' => 'Manage Settings', 'module' => 'Core'],
            // Inbox / WhatsApp (future milestone, defined now so RBAC is extensible)
            ['name' => 'inbox.view', 'label' => 'View Inbox', 'module' => 'Inbox'],
            ['name' => 'inbox.assign', 'label' => 'Assign Conversations', 'module' => 'Inbox'],
            // CRM
            ['name' => 'leads.view', 'label' => 'View Leads', 'module' => 'CRM'],
            ['name' => 'leads.manage', 'label' => 'Manage Leads', 'module' => 'CRM'],
            // Reports
            ['name' => 'reports.view', 'label' => 'View Reports', 'module' => 'Reports'],
            ['name' => 'reports.export', 'label' => 'Export Reports', 'module' => 'Reports'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }

        $admin = Role::where('name', 'admin')->first();
        $admin->permissions()->sync(Permission::pluck('id'));

        $manager = Role::where('name', 'manager')->first();
        $manager->permissions()->sync(
            Permission::whereIn('name', ['inbox.view', 'inbox.assign', 'leads.view', 'leads.manage', 'reports.view', 'reports.export'])->pluck('id')
        );

        $agent = Role::where('name', 'agent')->first();
        $agent->permissions()->sync(
            Permission::whereIn('name', ['inbox.view', 'leads.view'])->pluck('id')
        );

        // Default admin user — CHANGE THIS PASSWORD IMMEDIATELY AFTER FIRST LOGIN
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('ChangeMe123!'),
                'is_active' => true,
            ]
        );
        $adminUser->assignRole('admin');

        // Default CRM pipeline stages
        $stages = ['New' => 0, 'Contacted' => 1, 'Qualified' => 2, 'Proposal' => 3, 'Won' => 4, 'Lost' => 5];
        foreach ($stages as $name => $order) {
            \App\Models\LeadStage::firstOrCreate(['name' => $name], ['order' => $order]);
        }

        // Sample WhatsApp template (must also be created/approved in Meta Business Manager)
        \App\Models\MessageTemplate::firstOrCreate(
            ['name' => 'welcome_message'],
            [
                'language' => 'en_US',
                'category' => 'utility',
                'body' => "Hi {{1}}, thanks for reaching out! A member of our team will be with you shortly.",
                'variables' => ['Customer Name'],
                'approval_status' => 'pending',
            ]
        );
    }
}
