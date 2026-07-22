<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use App\Services\OfferEligibilityService;
use App\Services\UniqueIdentityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        private readonly UniqueIdentityService $identity,
    ) {}

    /**
     * Validate, enforce identity uniqueness, then create the user + team.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // ── Step 1: Standard field validation ──────────────────────────
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'company' => ['nullable', 'string', 'max:255'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature()
                ? ['accepted', 'required']
                : '',
        ])->validate();

        // ── Step 2: Unique Identity Enforcement ────────────────────────
        // All six checks must pass before we touch the database.
        $ip = request()?->ip() ?? '0.0.0.0';

        $identityResult = $this->identity->check(
            email: $input['email'],
            ip: $ip,
            wabaId: null,   // unknown yet; re-checked at WA connect
            phoneNumberId: null,   // same
            paymentFingerprint: $input['payment_fingerprint'] ?? null,
        );

        if (! $identityResult->passed) {
            throw ValidationException::withMessages([
                'email' => $identityResult->denial_reason,
            ]);
        }

        // ── Step 3: Create user + team atomically ──────────────────────
        return DB::transaction(function () use ($input, $ip) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => ($input['phone'] ?? null) ?: null,
                'company_name' => ($input['company'] ?? null) ?: null,
                'password' => Hash::make($input['password']),
                'utm_source' => $input['utm_source'] ?? null,
                'utm_medium' => $input['utm_medium'] ?? null,
                'utm_campaign' => $input['utm_campaign'] ?? null,
                'utm_content' => $input['utm_content'] ?? null,
                'utm_term' => $input['utm_term'] ?? null,
            ]);

            $team = $this->createTeam($user);

            // ── Step 4: Record fingerprints AFTER successful creation ───
            $this->identity->record(
                team: $team,
                user: $user,
                ip: $ip,
                paymentFingerprint: $input['payment_fingerprint'] ?? null,
            );

            return $user;
        });
    }

    /**
     * Create a personal team for the user with trial + launch offer gift.
     */
    protected function createTeam(User $user): Team
    {
        $team = Team::forceCreate([
            'user_id' => $user->id,
            // Prefer the company they gave us at signup so onboarding doesn't ask again.
            'name' => $user->company_name ?: explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addMonths(
                (int) get_setting('offer_trial_months', 6)
            ),
        ]);

        $user->ownedTeams()->save($team);
        $user->forceFill(['current_team_id' => $team->id])->save();

        // Launch gift credit – only when all 5 offer eligibility rules pass
        if (app(OfferEligibilityService::class)->isEligible($team)) {
            $offerSettings = app(\App\Services\OfferSettingsService::class);
            $initialCredit = (float) $offerSettings->get('offer_initial_credit', 5.00);

            // Deposit credit
            if ($initialCredit > 0) {
                app(\App\Services\BillingService::class)->deposit(
                    $team,
                    $initialCredit,
                    'Welcome Gift (Launch Offer)'
                );
            }

            // Snapshot all offer limits to lock them in for this team
            $snapshot = [
                'message_limit' => $offerSettings->get('offer_message_limit'),
                'agent_limit' => $offerSettings->get('offer_agent_limit'),
                'whatsapp_limit' => $offerSettings->get('offer_whatsapp_limit'),
                'contacts_limit' => $offerSettings->get('offer_contacts_limit'),
                'campaign_limit' => $offerSettings->get('offer_campaign_limit'),
                'automation_limit' => $offerSettings->get('offer_automation_limit'),
                'call_minutes_limit' => $offerSettings->get('offer_call_minutes_limit'),
                'included_features' => $offerSettings->get('offer_included_features'),
            ];

            // Stamp offer as claimed on the team immediately + save snapshot
            app(OfferEligibilityService::class)->markClaimed($team, $snapshot);
        }

        return $team;
    }
}
