<?php

namespace App\Services;

use App\Models\OrgUser;
use App\Models\OrgUserPlan;
use App\Models\AccessEvent;
use App\Enums\AccessEventType;
use App\Enums\OrgUserPlanStatus;
use Illuminate\Support\Facades\Auth;

class CheckInService
{
    /**
     * Check if a user can be checked in (has active membership)
     *
     * @param int $orgUserId The ID of the user to check
     * @return array ['success' => bool, 'message' => string, 'orgUser' => OrgUser|null, 'debug' => array]
     */
    public function canCheckIn(int $orgUserId): array
    {
        $orgUser = OrgUser::find($orgUserId);

        if (!$orgUser) {
            return [
                'success' => false,
                'message' => "Member with ID {$orgUserId} not found in the system.",
                'orgUser' => null,
                'debug' => ['step' => 'user_lookup', 'orgUserId' => $orgUserId]
            ];
        }

        // Check if user is archived
        if ($orgUser->isArchived) {
            return [
                'success' => false,
                'message' => "{$orgUser->fullName} is archived and cannot check in. Please contact an administrator.",
                'orgUser' => $orgUser,
                'debug' => ['step' => 'archived_check', 'isArchived' => true]
            ];
        }

        // Check if user has active plans
        $activePlans = OrgUserPlan::where('orgUser_id', $orgUser->id)
            ->where('isDeleted', false)
            ->where('status', OrgUserPlanStatus::Active->value)
            ->get();

        $hasActivePlans = $activePlans->count() > 0;

        if (!$hasActivePlans) {
            // Check if user has any plans at all
            $allPlans = OrgUserPlan::where('orgUser_id', $orgUser->id)->get();
            $planStatuses = $allPlans->groupBy('status')->map->count();

            return [
                'success' => false,
                'message' => "{$orgUser->fullName} does not have an active membership. Please purchase a membership before checking in.",
                'orgUser' => $orgUser,
                'debug' => [
                    'step' => 'membership_check',
                    'totalPlans' => $allPlans->count(),
                    'planStatuses' => $planStatuses->toArray(),
                    'activeStatus' => OrgUserPlanStatus::Active->value
                ]
            ];
        }

        return [
            'success' => true,
            'message' => 'User can be checked in.',
            'orgUser' => $orgUser,
            'debug' => ['step' => 'success', 'activePlansCount' => $activePlans->count()]
        ];
    }

    /**
     * Create a check-in access event for a user
     *
     * @param int $orgUserId The ID of the user to check in
     * @param int|null $orgId The organization ID (defaults to current user's org)
     * @return array ['success' => bool, 'message' => string, 'accessEvent' => AccessEvent|null, 'debug' => array]
     */
    public function checkIn(int $orgUserId, ?int $orgId = null): array
    {
        // Check if user can be checked in
        $canCheckIn = $this->canCheckIn($orgUserId);

        if (!$canCheckIn['success']) {
            return [
                'success' => false,
                'message' => $canCheckIn['message'],
                'accessEvent' => null,
                'debug' => $canCheckIn['debug']
            ];
        }

        $orgUser = $canCheckIn['orgUser'];

        // Use provided org ID or get from authenticated user
        $orgId = $orgId ?? Auth::user()->orgUser->org_id ?? null;

        if (!$orgId) {
            return [
                'success' => false,
                'message' => 'Organization ID is required. Please ensure you are logged in properly.',
                'accessEvent' => null,
                'debug' => ['step' => 'org_id_missing']
            ];
        }

        // Verify user belongs to the organization
        if ($orgUser->org_id !== $orgId) {
            return [
                'success' => false,
                'message' => "{$orgUser->fullName} does not belong to your organization and cannot check in here.",
                'accessEvent' => null,
                'debug' => [
                    'step' => 'org_mismatch',
                    'userOrgId' => $orgUser->org_id,
                    'checkInOrgId' => $orgId
                ]
            ];
        }

        // Check if a prior check-in exists for the user today
        $today = now()->format('Y-m-d');
        $existingCheckIn = AccessEvent::where('orgUser_id', $orgUserId)
            ->where('eventType', AccessEventType::CHECK_IN->value)
            ->where('org_id', $orgId)
            ->whereBetween('eventTimestamp', [
                strtotime($today . ' 00:00:00'),
                strtotime($today . ' 23:59:59')
            ])
            ->first();

        if ($existingCheckIn) {
            $checkInTime = date('g:i A', $existingCheckIn->eventTimestamp);
            return [
                'success' => false,
                'message' => "{$orgUser->fullName} has already checked in today at {$checkInTime}.",
                'accessEvent' => null,
                'debug' => [
                    'step' => 'already_checked_in',
                    'existingCheckInId' => $existingCheckIn->id,
                    'checkInTime' => $checkInTime
                ]
            ];
        }

        // Create the access event
        try {
            $accessEvent = AccessEvent::create([
                'org_id' => $orgId,
                'orgUser_id' => $orgUserId,
                'eventTimestamp' => time(),
                'eventType' => AccessEventType::CHECK_IN->value,
                'accessPoint' => 'admin_dashboard',
                'checked_in_by' => Auth::user()->orgUser->id ?? null,
            ]);

            return [
                'success' => true,
                'message' => "{$orgUser->fullName} has been checked in successfully.",
                'accessEvent' => $accessEvent,
                'debug' => ['step' => 'success', 'accessEventId' => $accessEvent->id]
            ];

        } catch (
            \Exception $e) {
            return [
                'success' => false,
                'message' => 'Could not create check-in record. Please try again or contact support.',
                'accessEvent' => null,
                'debug' => [
                    'step' => 'access_event_creation_failed',
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Process a check-in request and return full result with actual error reasons
     *
     * @param array $data The form data containing orgUser_id and org_id
     * @return array The full check-in result with success status and message
     */
    public function processCheckIn(array $data): array
    {
        $result = $this->checkIn(
            $data['orgUser_id'],
            $data['org_id'] ?? null
        );

        return $result;
    }
}
