<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Facades\Auth;

class TenantService
{
    /**
     * Get the current tenant (Team) for the authenticated user.
     */
    public function getTenant(): ?Team
    {
        return Auth::user()?->currentTeam;
    }

    /**
     * Get the Tenant ID.
     */
    public function getTenantId(): ?int
    {
        return $this->getTenant()?->id;
    }

    /**
     * Get the WABA (WhatsApp Business Account) ID for the current tenant.
     */
    public function getWabaId(): ?string
    {
        return $this->getTenant()?->whatsapp_business_account_id;
    }

    /**
     * Get the Phone Number ID for the current tenant.
     */
    public function getPhoneNumberId(): ?string
    {
        return $this->getTenant()?->whatsapp_phone_number_id;
    }

    /**
     * Get the WhatsApp Access Token.
     */
    public function getAccessToken(): ?string
    {
        return $this->getTenant()?->whatsapp_access_token;
    }

    /**
     * Get the current user's role in the active tenant.
     */
    public function getUserRole(): ?string
    {
        // teamRole() returns a Role object, we want the key (string) usually, or null
        $role = Auth::user()?->teamRole($this->getTenant());
        return $role?->key;
    }

    /**
     * Check if the tenant is connected to WhatsApp.
     */
    public function isConnected(): bool
    {
        return (bool) $this->getTenant()?->whatsapp_connected;
    }
}
