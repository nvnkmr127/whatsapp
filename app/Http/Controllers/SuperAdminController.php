<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $stats = [
            'total_teams' => Team::count(),
            'active_subs' => Team::where('subscription_status', 'active')->count(),
            'total_users' => User::count(),
            'total_messages' => \App\Models\Message::count(),
        ];

        $query = Team::with('owner')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('owner', function ($q) use ($search) {
                        $q->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('subscription_status', $request->status);
        }

        $teams = $query->paginate(20)->withQueryString();

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

        try {
            $team = \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
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

                // 3. Attach User to Team as Owner
                $user->teams()->attach($team, ['role' => 'admin']);
                $user->forceFill([
                    'current_team_id' => $team->id,
                ])->save();

                // 4. Log the tenant creation
                \Illuminate\Support\Facades\Log::info('Tenant created', [
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'owner_email' => $user->email,
                    'plan' => $validated['plan'],
                    'created_by' => auth()->user()->email,
                ]);

                return $team;
            });

            return redirect()
                ->route('admin.dashboard')
                ->with('flash.banner', "Company workspace '{$team->name}' created successfully!")
                ->with('flash.bannerStyle', 'success');

        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error('Tenant creation failed - Database error', [
                'error' => $e->getMessage(),
                'company_name' => $validated['company_name'],
                'owner_email' => $validated['owner_email'],
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create tenant. Please try again or contact support.']);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Tenant creation failed - General error', [
                'error' => $e->getMessage(),
                'company_name' => $validated['company_name'],
                'owner_email' => $validated['owner_email'],
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An unexpected error occurred. Please try again.']);
        }
    }

    public function edit($id)
    {
        $team = Team::with('owner')->findOrFail($id);
        return view('admin.tenants.edit', compact('team'));
    }

    public function update(Request $request, $id)
    {
        $team = Team::findOrFail($id);

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'plan' => 'required|in:basic,pro,enterprise',
            'subscription_status' => 'required|in:active,inactive,cancelled',
        ]);

        $team->update([
            'name' => $validated['company_name'],
            'subscription_plan' => $validated['plan'],
            'subscription_status' => $validated['subscription_status'],
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('flash.banner', "Workspace '{$team->name}' updated successfully!")
            ->with('flash.bannerStyle', 'success');
    }

    public function destroy($id)
    {
        $team = Team::findOrFail($id);
        $name = $team->name;

        // Optional: Add logic to delete users if they only belong to this team,
        // or just detach them. For now, we'll just delete the team which might 
        // cascade depending on foreign keys, but let's be safe and simple.
        // Assuming cascade delete is set up on DB level or we just delete team.

        $team->delete();

        return redirect()
            ->route('admin.dashboard')
            ->with('flash.banner', "Workspace '{$name}' deleted successfully!")
            ->with('flash.bannerStyle', 'success');
    }
}
