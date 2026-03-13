<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Services\OfferAuditService;
use App\Services\OfferEligibilityService;
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
            'total_backups' => \App\Models\TenantBackup::count(),
            'global_backups' => \App\Models\TenantBackup::whereNull('team_id')->count(),
        ];

        $query = Team::with('owner')->latest();
        $matchingUsers = collect();

        if ($request->filled('search')) {
            $search = $request->search;

            // 1. Search Teams
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('owner', function ($q) use ($search) {
                        $q->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });

            // 2. Search Users (Independent of teams)
            $matchingUsers = User::where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->with('ownedTeams')
                ->limit(10)
                ->get();
        }

        if ($request->filled('status')) {
            $query->where('subscription_status', $request->status);
        }

        $teams = $query->paginate(20, ['*'], 'teams')->withQueryString();

        $globalBackups = \App\Models\TenantBackup::whereNull('team_id')
            ->latest()
            ->paginate(10, ['*'], 'backups');

        return view('admin.dashboard', compact('stats', 'teams', 'globalBackups', 'matchingUsers'));
    }

    public function create()
    {
        $plans = \App\Models\Plan::all();
        return view('admin.tenants.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|unique:users,email',
            'owner_password' => 'required|string|min:8',
            'plan' => 'required|exists:plans,name',
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
                    'subscription_status' => 'active',      // Admin creations start active
                    'subscription_ends_at' => $subscriptionDuration,
                ]);

                // 3. Attach User to Team as Owner
                $user->teams()->attach($team, ['role' => 'admin']);
                $user->forceFill(['current_team_id' => $team->id])->save();

                // 4. Offer gift – only when ALL 6 eligibility rules pass
                $offerApplied = app(OfferEligibilityService::class)->isEligible($team);
                if ($offerApplied) {
                    $credit = (float) get_setting('offer_initial_credit', 5.00);
                    if ($credit > 0) {
                        app(\App\Services\BillingService::class)->deposit(
                            $team,
                            $credit,
                            'Welcome Gift (Launch Offer – Admin Created)'
                        );
                    }
                    app(OfferEligibilityService::class)->markClaimed($team);
                }

                // 5. Log the tenant creation
                \Illuminate\Support\Facades\Log::info('Tenant created', [
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'owner_email' => $user->email,
                    'plan' => $validated['plan'],
                    'created_by' => auth()->user()->email,
                ]);

                // 6. Offer audit trail (admin attribution)
                OfferAuditService::logManualTenantCreated($team, auth()->user(), $offerApplied);

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
        $team = Team::with(['owner', 'addOns', 'billingOverrides.creator'])->findOrFail($id);
        $plans = \App\Models\Plan::all();
        return view('admin.tenants.edit', compact('team', 'plans'));
    }

    public function storeOverride(Request $request, $id)
    {
        $team = Team::findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|in:limit_increase,feature_enable',
            'key' => 'required|string',
            'value' => 'required|string',
            'reason' => 'required|string|max:500',
            'duration' => 'nullable|integer|min:1',
        ]);

        app(\App\Services\BillingService::class)->createOverride(
            $team,
            $validated['type'],
            $validated['key'],
            $validated['value'],
            $validated['reason'],
            $validated['duration'] ?? 30
        );

        return redirect()->back()->with('flash.banner', "Billing override created for {$team->name}.")->with('flash.bannerStyle', 'success');
    }

    public function deleteOverride($id, $overrideId)
    {
        $override = \App\Models\BillingOverride::where('team_id', $id)->findOrFail($overrideId);
        $override->delete();

        return redirect()->back()->with('flash.banner', "Billing override removed.")->with('flash.bannerStyle', 'success');
    }

    public function update(Request $request, $id)
    {
        $team = Team::findOrFail($id);

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'plan' => 'required|exists:plans,name',
            'subscription_status' => 'required|in:active,inactive,cancelled',
            'features' => 'nullable|array',
            'features.*' => 'string|in:backups,cloud_backups',
        ]);

        $team->update([
            'name' => $validated['company_name'],
            'subscription_plan' => $validated['plan'],
            'subscription_status' => $validated['subscription_status'],
        ]);

        // Sync Add-ons
        $requestedFeatures = $validated['features'] ?? [];

        // Remove features not in request
        $team->addOns()->whereNotIn('type', $requestedFeatures)->delete();

        // Add features not in DB
        foreach ($requestedFeatures as $feature) {
            $team->addOns()->updateOrCreate(['type' => $feature]);
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('flash.banner', "Workspace '{$team->name}' updated successfully!")
            ->with('flash.bannerStyle', 'success');
    }

    public function destroy($id)
    {
        $team = Team::findOrFail($id);
        $name = $team->name;
        $team->delete();

        return redirect()
            ->route('admin.dashboard')
            ->with('flash.banner', "Workspace '{$name}' deleted successfully!")
            ->with('flash.bannerStyle', 'success');
    }

    public function grantOffer($id)
    {
        $team = Team::findOrFail($id);

        if ($team->offer_claimed_at) {
            return redirect()->back()->with('flash.banner', "Offer already claimed by {$team->name}.")->with('flash.bannerStyle', 'danger');
        }

        $months = (int) get_setting('offer_trial_months', 6);
        $credit = (float) get_setting('offer_initial_credit', 5.00);

        // 1. Force Trial State
        $team->update([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addMonths($months),
        ]);

        // 2. Deposit Credit
        if ($credit > 0) {
            app(\App\Services\BillingService::class)->deposit(
                $team,
                $credit,
                'Welcome Gift (Launch Offer – Manually Granted)'
            );
        }

        // 3. Mark Claimed (Snapshot + Timestamp)
        // We use current global settings for the snapshot
        $svc = app(\App\Services\OfferSettingsService::class);
        $snapshot = [];
        foreach ($svc->limitMap() as $limitSlug => $settingsKey) {
            $snapshot[$settingsKey] = $svc->get($settingsKey);
        }

        app(\App\Services\OfferEligibilityService::class)->markClaimed($team, $snapshot);

        return redirect()->back()->with('flash.banner', "Launch Offer ($months Months) granted to {$team->name}.")->with('flash.bannerStyle', 'success');
    }

    public function auditLogs(Request $request)
    {
        $query = \App\Models\AuditLog::with('user')->latest();

        if ($request->filled('event')) {
            $query->where('event_type', 'like', "%{$request->event}%");
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('identifier', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->paginate(50);

        return view('admin.audit-logs', compact('logs'));
    }
}
