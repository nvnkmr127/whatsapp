<?php

namespace App\Validators;

use App\DTOs\ValidationError;
use App\DTOs\ValidationResult;
use App\Models\Contact;

class OptInValidator
{
    /**
     * Validate contact opt-in status
     */
    public function validate(Contact $contact, string $messageType = 'transactional'): ValidationResult
    {
        $result = new ValidationResult();

        // Check if opted out
        if ($contact->opt_out_status === 'opted_out') {
            $result->addError(new ValidationError(
                code: 'CONTACT_OPTED_OUT',
                message: "Contact {$contact->phone} has opted out",
                severity: 'error',
                field: 'contact_id',
                suggestion: 'Remove from campaign - contact has explicitly opted out',
                metadata: [
                    'opted_out_at' => $contact->opted_out_at?->toIso8601String(),
                    'opt_out_reason' => $contact->opt_out_reason,
                ]
            ));

            return $result; // No point checking further
        }

        // Check opt-in expiration
        if ($contact->opt_in_expires_at) {
            if ($contact->opt_in_expires_at->isPast()) {
                $result->addError(new ValidationError(
                    code: 'OPT_IN_EXPIRED',
                    message: "Opt-in expired on {$contact->opt_in_expires_at->format('Y-m-d')}",
                    severity: 'error',
                    field: 'contact_id',
                    suggestion: 'Request fresh opt-in from contact',
                    metadata: [
                        'expired_at' => $contact->opt_in_expires_at->toIso8601String(),
                        'days_expired' => $contact->opt_in_expires_at->diffInDays(),
                    ]
                ));
            } elseif ($contact->opt_in_expires_at->diffInDays() < 7) {
                // Warning: expiring soon
                $result->addError(new ValidationError(
                    code: 'OPT_IN_EXPIRING_SOON',
                    message: "Opt-in expires in {$contact->opt_in_expires_at->diffInDays()} days",
                    severity: 'warning',
                    field: 'contact_id',
                    suggestion: 'Consider requesting opt-in renewal soon',
                    metadata: [
                        'expires_at' => $contact->opt_in_expires_at->toIso8601String(),
                        'days_remaining' => $contact->opt_in_expires_at->diffInDays(),
                    ]
                ));
            }
        }

        // Check marketing consent for marketing messages
        if ($messageType === 'marketing' && !$contact->marketing_consent) {
            $result->addError(new ValidationError(
                code: 'NO_MARKETING_CONSENT',
                message: 'Contact has not consented to marketing messages',
                severity: 'error',
                field: 'message_type',
                suggestion: 'Use transactional template or obtain marketing consent first'
            ));
        }

        // Check if contact has any opt-in at all
        if (!$contact->opt_in_status || $contact->opt_in_status === 'pending') {
            $result->addError(new ValidationError(
                code: 'NO_OPT_IN',
                message: 'Contact has not opted in',
                severity: 'error',
                field: 'contact_id',
                suggestion: 'Obtain opt-in consent before sending messages'
            ));
        }

        return $result;
    }

    /**
     * Validate multiple contacts
     */
    public function validateBatch(array $contacts, string $messageType = 'transactional'): array
    {
        $results = [];

        foreach ($contacts as $contact) {
            $results[$contact->id] = [
                'contact' => $contact,
                'validation' => $this->validate($contact, $messageType),
            ];
        }

        return $results;
    }
}
