<?php

namespace App\Services;

use App\Models\OrgUserNote;
use App\Enums\NoteType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotesService
{
    /**
     * Create a new note using the old method signature
     */
    public function create(array $data): OrgUserNote
    {
        // Map the old parameters to the new OrgUserNote structure
        $noteData = [
            'org_id' => $data['org_id'],
            'orgUser_id' => $data['orgUser_id'] ?? null,
            'author_id' => $data['created_by'] ?? (Auth::user()->orgUser ? Auth::user()->orgUser->id : null),
            'note_type' => NoteType::GENERAL->value,
            'title' => 'Membership Note',
            'content' => $data['note'],
            'notify_member' => false,
            'reminder_sent' => false,
            'notable_type' => 'App\Models\OrgUserPlan',
            'orgUserPlan_id' => $data['orgUserPlan_id'] ?? null,
        ];

        // Create the note in the database
        return OrgUserNote::create($noteData);
    }

    /**
     * Format creation note for membership
     */
    public function formatMembershipCreationNote($orgUserPlan, $plan, $user, ?string $customNote = null): string
    {
        $note = "Membership created: {$plan->name} for {$user->fullName}";

        if ($orgUserPlan->orgDiscount_value) {
            $discountText = $orgUserPlan->orgDiscount_unit === 'percent'
                ? $orgUserPlan->orgDiscount_value . '%'
                : number_format($orgUserPlan->orgDiscount_value, 2) . ' ' . $orgUserPlan->currency;
            $note .= " (Discount: {$discountText})";
        }

        $note .= " - Total: " . number_format($orgUserPlan->invoiceTotal, 2) . ' ' . $orgUserPlan->currency;

        if ($customNote) {
            $note .= "\nNote: " . $customNote;
        }

        return $note;
    }
    public function addNote(
        Model $notable,
        string $title,
        string $content,
        NoteType $noteType = NoteType::GENERAL,
        ?int $authorId = null,
        bool $notifyMember = false,
        ?int $notifyStaffId = null,
        ?\DateTime $reminderAt = null,
        ?int $orgUserId = null
    ): OrgUserNote {
        // Get the orgUser_id from the notable entity if not explicitly provided
        if ($orgUserId === null) {
            if (method_exists($notable, 'getOrgUserIdAttribute')) {
                $orgUserId = $notable->orgUser_id;
            } elseif (property_exists($notable, 'orgUser_id')) {
                $orgUserId = $notable->orgUser_id;
            } elseif (method_exists($notable, 'orgUser') && $notable->orgUser) {
                $orgUserId = $notable->orgUser->id;
            }
        }

        // Get the org_id from the notable entity if possible
        $orgId = null;
        if (method_exists($notable, 'getOrgIdAttribute')) {
            $orgId = $notable->org_id;
        } elseif (property_exists($notable, 'org_id')) {
            $orgId = $notable->org_id;
        } elseif (method_exists($notable, 'org') && $notable->org) {
            $orgId = $notable->org->id;
        }

        // Use the current authenticated user's orgUser_id if not provided
        if ($authorId === null && Auth::check()) {
            // Get the orgUser_id from the authenticated user
            if (Auth::user()->orgUser) {
                $authorId = Auth::user()->orgUser->id;
            } elseif (property_exists(Auth::user(), 'orgUser_id') && Auth::user()->orgUser_id) {
                $authorId = Auth::user()->orgUser_id;
            }
        }

        // Create the note data array with only non-null values
        $noteData = [
            'title' => $title,
            'content' => $content,
            'note_type' => $noteType->value,
            'notify_member' => $notifyMember,
            'reminder_sent' => false,
            'notable_type' => get_class($notable),
            'notable_id' => $notable->id,
            'created_at' => now(),
        ];

        // Only add non-null values to avoid database constraint violations
        if ($orgId !== null) {
            $noteData['org_id'] = $orgId;
        }

        if ($orgUserId !== null) {
            $noteData['orgUser_id'] = $orgUserId;
        }

        if ($authorId !== null) {
            $noteData['author_id'] = $authorId;
        }

        if ($notifyStaffId !== null) {
            $noteData['notify_staff_id'] = $notifyStaffId;
        }

        if ($reminderAt !== null) {
            $noteData['reminder_at'] = $reminderAt;
        }

        // Create the note in the database
        return OrgUserNote::create($noteData);
    }

    /**
     * Get notes for a membership
     */
    public function getNotesForMembership(int $orgUserPlanId): array
    {
        // In a real implementation, this would query the database
        // For now, return empty array
        return [];
    }

    /**
     * Get notes for a customer
     */
    public function getNotesForCustomer(int $orgUserId): array
    {
        // In a real implementation, this would query the database
        // For now, return empty array
        return [];
    }
}
