<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_teams' => Team::count(),
            'active_subs' => Team::where('subscription_status', 'active')->count(),
            'total_users' => User::count(),
            'total_messages' => \App\Models\Message::count(),
        ];

        $teams = Team::with('owner')->latest()->paginate(20);

        return view('admin.dashboard', compact('stats', 'teams'));
    }

    public function create()
    {
        return view('admin.tenants.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|unique:users,email',
            'owner_password' => 'required|string|min:8',
            'plan' => 'required|in:basic,pro,enterprise',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            // 1. Create Owner User
            $user = User::create([
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'password' => \Illuminate\Support\Facades\Hash::make($validated['owner_password']),
            ]);

            // 2. Create Team (Company Workspace)
            $subscriptionDuration = now()->addMonth(); // Default trial/initial duration

            $team = Team::forceCreate([
                'user_id' => $user->id,
                'name' => $validated['company_name'],
                'personal_team' => false,
                'subscription_plan' => $validated['plan'],
                'subscription_status' => 'active',
                'subscription_ends_at' => $subscriptionDuration,
            ]);

            // 3. Attach User to Team
            $user->switchTeam($team);

            // 4. Fire Jetstream Event
            \Laravel\Jetstream\Events\TeamCreated::dispatch($team);
        });

        return redirect()->route('admin.dashboard')->with('flash.banner', 'Company Workspace Created Successfully!');
    }
}
