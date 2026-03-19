<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Services\OfferAuditService;
use App\Services\OfferEligibilityService;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    protected $analytics;
    protected $tenantService;

    public function __construct(
        \App\Services\AdminAnalyticsService $analytics,
        \App\Services\TenantService $tenantService
    ) {
        $this->analytics = $analytics;
        $this->tenantService = $tenantService;
    }

    public function dashboard(Request $request)
    {
        $stats = $this->analytics->getGlobalStats();
        $searchData = $this->analytics->search($request->search ?? '', $request->status);
        $teams = $searchData['teams'];
        $matchingUsers = $searchData['matchingUsers'];

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
            $team = $this->tenantService->create($validated, auth()->user());

            // 6. Offer audit trail (admin attribution)
            // Still in controller for legacy context? Actually, move this to service too.
            // For now, let's keep it minimal.
            \App\Services\OfferAuditService::logManualTenantCreated($team, auth()->user(), true);

            return redirect()
                ->route('admin.dashboard')
                ->with('flash.banner', "Company workspace '{$team->name}' created successfully!")
                ->with('flash.bannerStyle', 'success');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Tenant creation failed', [
                'error' => $e->getMessage(),
                'company_name' => $validated['company_name'],
                'owner_email' => $validated['owner_email'],
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create tenant: ' . $e->getMessage()]);
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
