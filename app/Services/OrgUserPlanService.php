<?php

namespace App\Services;
use App\Models\OrgUser;
use App\Models\OrgPlan;
use App\Models\OrgUserPlan;
use App\Models\Discount;
use App\Services\NotesService;
use App\Services\Yii2QueueDispatcher;
use App\Enums\OrgUserPlanStatus;
use App\Enums\NoteType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OrgUserPlanService
{

    /**
     * @var Yii2QueueDispatcher
     */
    protected $yii2QueueDispatcher;

    /**
     * @var NotesService
     */

    protected $notesService;

    public function __construct(NotesService $notesService)
    {
        $this->notesService = $notesService;
    }

    /**
     * Create a new membership with comprehensive validation and processing
     */
    public function create(array $data, ?string $note = null): ?OrgUserPlan
    {
        DB::beginTransaction();

        try {
            // 1. Data validation and preparation
            $data = $this->prepareCreateData($data);

            // 2. Entity loading and validation
            $plan = OrgPlan::findOrFail($data['orgPlan_id']);
            $user = OrgUser::findOrFail($data['orgUser_id']);
            $discount = $this->loadDiscount($data);

            // 3. Price and date calculations
            $data = $this->calculatePricing($data, $plan, $discount);
            $data = $this->calculateDates($data, $plan);

            // 4. Invoice field processing
            $data = $this->processInvoiceFields($data, $plan);

            // 5. Add plan details to data
            $data['name'] = $plan->name;
            $data['type'] = $plan->type;
            $data['venue'] = $plan->venue;

            // Generate UUID
            $data['uuid'] = \Str::uuid()->toString();

            // 6. Record creation

            $orgUserPlan = new OrgUserPlan($data);
            $orgUserPlan->save();

            // 7. Post-creation processing
            $this->addCreationNote($orgUserPlan, $plan, $user, $note);

            DB::commit();
            return $orgUserPlan;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Calculate final invoice total with discount application
     */
    public static function calculateInvoiceTotal(
        float $basePrice,
        string $discountMode,
        ?int $discountId = null,
        ?float $manualDiscount = null
    ): float {
        if ($discountMode === 'none') {
            return $basePrice;
        }

        if ($discountMode === 'auto' && $discountId) {
            $discount = Discount::find($discountId);
            if ($discount) {
                return $discount->unit === 'percent'
                    ? $basePrice * (1 - $discount->value / 100)
                    : max(0, $basePrice - $discount->value);
            }
        }

        if ($discountMode === 'manual' && $manualDiscount !== null) {
            return max(0, $basePrice - $manualDiscount);
        }

        return $basePrice;
    }

    /**
     * Format plan option for display with price badges
     */
    public static function formatPlanOption($plan): string
    {
        $price = number_format($plan->price, 2);
        return "{$plan->name} - {$price} {$plan->currency}";
    }

    /**
     * Prepare data for creation
     */
    protected function prepareCreateData(array $data): array
    {
        // Set organization context
        $data['org_id'] = Auth::user()->orgUser->org_id;
        $data['created_by'] = Auth::user()->orgUser->id;

        // Set default status - use integer status code
        $data['status'] = OrgUserPlan::STATUS_ACTIVE; // This is integer 2

        // Set other required defaults
        $data['isCanceled'] = false;
        $data['isDeleted'] = false;
        $data['totalQuotaConsumed'] = 0;
        $data['invoiceStatus'] = OrgUserPlan::INVOICE_STATUS_PAID;

        // Set currency from org or plan
        if (empty($data['currency'])) {
            $data['currency'] = 'JOD'; // Default currency for this org
        }

        return $data;
    }

    /**
     * Load discount if applicable
     */
    protected function loadDiscount(array $data): ?Discount
    {
        if (($data['discountMode'] ?? 'none') === 'auto' && !empty($data['orgDiscount_id'])) {
            return Discount::find($data['orgDiscount_id']);
        }

        return null;
    }

    /**
     * Calculate pricing with discounts
     */
    protected function calculatePricing(array $data, OrgPlan $plan, ?Discount $discount): array
    {
        // Always use plan pricing - never use form pricing
        $data['price'] = $plan->price;
        $data['pricePerSession'] = $plan->pricePerSession;
        $data['currency'] = $plan->currency;

        // Calculate invoice total with discount (always use plan price as base)
        if (empty($data['invoiceTotal'])) {
            $data['invoiceTotal'] = $this->calculateInvoiceTotal(
                $plan->price,
                $data['discountMode'] ?? 'none',
                $data['orgDiscount_id'] ?? null,
                $data['discountTotal'] ?? null
            );
        }

        // Set discount details
        if ($discount) {
            $data['orgDiscount_id'] = $discount->id;
            $data['orgDiscount_value'] = $discount->value;
            $data['orgDiscount_unit'] = $discount->unit;
        } elseif (($data['discountMode'] ?? 'none') === 'manual' && isset($data['discountTotal'])) {
            $data['orgDiscount_value'] = $data['discountTotal'];
            $data['orgDiscount_unit'] = 'fixed';
        }

        return $data;
    }
    protected function calculateDates(array $data, OrgPlan $plan): array
    {
        if (!empty($data['startDateLoc'])) {
            // Calculate end date if not provided
            if (empty($data['endDateLoc'])) {
                $data['endDateLoc'] = $this->calculateEndDate($plan, $data['startDateLoc']);
            }

            // Convert local dates to UTC
            $startDate = Carbon::parse($data['startDateLoc']);
            $endDate = Carbon::parse($data['endDateLoc']);

            $startDate->setTimezone('UTC');
            $endDate->setTimezone('UTC');

            $data['startDate'] = $startDate->toDateTimeString();
            $data['endDate'] = $endDate->toDateTimeString();
        }

        return $data;
    }

    /**
     * Process invoice-related fields with conditional defaults
     * Only sets defaults if fields are not already provided
     */
    protected function processInvoiceFields(array $data, OrgPlan $plan): array
    {
        // Set invoice status if not already provided (default: PAID)
        if (empty($data['invoiceStatus'])) {
            $data['invoiceStatus'] = OrgUserPlan::INVOICE_STATUS_PAID;
        }

        // Set invoice total paid if not already provided (default: same as invoice total)
        if (empty($data['invoiceTotalPaid'])) {
            $data['invoiceTotalPaid'] = $data['invoiceTotal'] ?? 0;
        }

        // Set invoice currency if not already provided (default: plan currency)
        if (empty($data['invoiceCurrency'])) {
            $data['invoiceCurrency'] = $plan->currency;
        }

        // Set invoice due date if not already provided (default: now + 1 hour)
        if (empty($data['invoiceDue'])) {
            $data['invoiceDue'] = now()->addHour()->format('Y-m-d');
        }

        // Set invoice method if not already provided (default: from payment method or null)
        if (empty($data['invoiceMethod'])) {
            $data['invoiceMethod'] = $data['paymentMethod'] ?? null;
        }

        // Set invoice receipt if not already provided (default: empty)
        if (!isset($data['invoiceReceipt'])) {
            $data['invoiceReceipt'] = '';
        }

        return $data;
    }

    /**
     * Calculate end date based on plan duration
     * Uses cycleDuration and cycleUnit from database (wodworx-core compatible)
     */
    protected function calculateEndDate(OrgPlan $plan, string $startDate): string
    {
        $start = Carbon::parse($startDate);

        // Use correct database field names: cycleDuration and cycleUnit
        $cycleDuration = $plan->cycleDuration ?? 1;
        $cycleUnit = $plan->cycleUnit ?? 'month';

        if ($cycleDuration && $cycleUnit) {
            switch ($cycleUnit) {
                case 'day':
                    return $start->addDays($cycleDuration)->format('Y-m-d');
                case 'week':
                    return $start->addWeeks($cycleDuration)->format('Y-m-d');
                case 'month':
                    return $start->addMonths($cycleDuration)->format('Y-m-d');
                case 'year':
                    return $start->addYears($cycleDuration)->format('Y-m-d');
            }
        }

        // Default to 1 month if no duration specified
        return $start->addMonth()->format('Y-m-d');
    }

    /**
     * Trigger background jobs for post-processing
     */
    protected function triggerBackgroundJobs(OrgUserPlan $orgUserPlan): void
    {
        $this->yii2QueueDispatcher->dispatch(
            'common\\jobs\\plan\\OrgUserPlanCreatedJob',
            ['id' => $orgUserPlan->id]
        );
    }

    /**
     * Add creation note
     */
    protected function addCreationNote(OrgUserPlan $orgUserPlan, OrgPlan $plan, OrgUser $user, ?string $note): void
    {
        if ($note) {
            $this->notesService->create([
                'org_id' => $orgUserPlan->org_id,
                'orgUserPlan_id' => $orgUserPlan->id,
                'note' => $note,
                'created_by' => Auth::user()->orgUser->id,
            ]);
        }
    }

    /**
     * Calculate dates (local and UTC)
     */
    public function modifyDates(OrgUserPlan $orgUserPlan, array $data, ?string $note = null): ?OrgUserPlan
    {
        // Check if the plan can be modified
        if (!$orgUserPlan->can_be_modified) {
            return null;
        }

        // Begin transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // Store original dates for the note
            $originalStartDate = $orgUserPlan->startDateLoc;
            $originalEndDate = $orgUserPlan->endDateLoc;

            // Calculate the current duration in days between start and end dates
            $currentStartDate = Carbon::parse($orgUserPlan->startDateLoc);
            $currentEndDate = Carbon::parse($orgUserPlan->endDateLoc);
            $currentDurationDays = $currentStartDate->diffInDays($currentEndDate);

            // Prepare data for update
            $updateData = [];
            $updateData['startDateLoc'] = $data['startDateLoc'];

            // If auto_determine_end_date is true, calculate the end date based on the current duration
            if (isset($data['auto_determine_end_date']) && $data['auto_determine_end_date']) {
                // Calculate the new end date based on the start date and the current duration
                $startDate = Carbon::parse($data['startDateLoc']);
                $updateData['endDateLoc'] = $startDate->copy()->addDays($currentDurationDays)->format('Y-m-d');
            } else {
                // Use the provided end date
                $updateData['endDateLoc'] = $data['endDateLoc'];
            }

            // Validate that end date is not before start date
            $startDateCarbon = Carbon::parse($updateData['startDateLoc']);
            $endDateCarbon = Carbon::parse($updateData['endDateLoc']);

            if ($endDateCarbon->lt($startDateCarbon)) {
                DB::rollBack();
                throw new \InvalidArgumentException(__('subscriptions.End date must be after or equal to start date'));
            }

            // Convert the local dates to UTC for storage
            $startDate = Carbon::parse($updateData['startDateLoc']);
            $endDate = Carbon::parse($updateData['endDateLoc']);

            // Set timezone to UTC
            $startDate->setTimezone('UTC');
            $endDate->setTimezone('UTC');

            // Update the data with the UTC dates
            $updateData['startDate'] = $startDate->toDateTimeString();
            $updateData['endDate'] = $endDate->toDateTimeString();

            // Update the plan status based on the new dates (using FOH constants)
            if ($startDate->isFuture()) {
                $updateData['status'] = OrgUserPlan::STATUS_UPCOMING;
            } elseif ($endDate->isPast()) {
                $updateData['status'] = OrgUserPlan::STATUS_EXPIRED;
            } else {
                $updateData['status'] = OrgUserPlan::STATUS_ACTIVE;
            }

            // Update the model
            $orgUserPlan->fill($updateData);
            $success = $orgUserPlan->save();

            if ($success) {
                // Add a note if provided
                if ($note || !empty($data['note'])) {
                    $noteText = $note ?? $data['note'];
                    $noteContent = "Plan dates were modified. Start date changed from {$originalStartDate} to {$orgUserPlan->startDateLoc}. End date changed from {$originalEndDate} to {$orgUserPlan->endDateLoc}.";

                    if ($noteText) {
                        $noteContent .= "\n\nNote: {$noteText}";
                    }

                    $this->notesService->addNote(
                        $orgUserPlan,
                        'Membership Dates Modified',
                        $noteContent,
                        NoteType::GENERAL,
                        auth()->id()
                    );
                }

                // Dispatch Yii2 job for post-modification processing (matching Core system)
                try {
                    $dispatcher = new Yii2QueueDispatcher();
                    if (self::jobExistsInWodWorx('common\jobs\plan\OrgUserPlanUpdateCompleteJob')) {
                        $dispatcher->dispatch('common\jobs\plan\OrgUserPlanUpdateCompleteJob', ['id' => $orgUserPlan->id]);
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Job not found in wod-worx: common\jobs\plan\OrgUserPlanUpdateCompleteJob');
                    }
                } catch (\Exception $e) {
                    // Log warning but don't fail the update for background job issues
                    \Illuminate\Support\Facades\Log::warning('Failed to dispatch OrgUserPlanUpdateCompleteJob: ' . $e->getMessage(), [
                        'plan_id' => $orgUserPlan->id,
                        'exception' => $e
                    ]);
                }

                // Commit the transaction
                DB::commit();
                return $orgUserPlan;
            } else {
                DB::rollBack();
                return null;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Modify membership limits (session limits, etc.)
     *
     * @param OrgUserPlan $orgUserPlan
     * @param array $data
     * @param string|null $note
     * @return OrgUserPlan|null
     */
    public function modifyLimits(OrgUserPlan $orgUserPlan, array $data, ?string $note = null): ?OrgUserPlan
    {
        // Check if the plan can be modified
        if (!$orgUserPlan->can_be_modified) {
            return null;
        }

        // Begin transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // Store original values for the note
            $originalSessionLimit = $orgUserPlan->totalQuota;

            // Prepare data for update
            $updateData = [];

            // Update number of classes if provided
            if (isset($data['numberOfClasses'])) {
                $updateData['totalQuota'] = (int) $data['numberOfClasses'];
                // If set to 0, it means unlimited
                if ($updateData['totalQuota'] === 0) {
                    $updateData['totalQuota'] = null; // null means unlimited in database
                }
            }

            // Store additional settings in note field as JSON for now
            // TODO: Add proper database columns for these settings
            $additionalSettings = [];
            if (isset($data['allowSharing'])) {
                $additionalSettings['allowSharing'] = (bool) $data['allowSharing'];
            }
            if (isset($data['allowHolds'])) {
                $additionalSettings['allowHolds'] = (bool) $data['allowHolds'];
            }
            if (isset($data['numberOfHoldsAllowed'])) {
                $additionalSettings['numberOfHoldsAllowed'] = (int) $data['numberOfHoldsAllowed'];
            }
            if (isset($data['holdDays'])) {
                $additionalSettings['holdDays'] = (int) $data['holdDays'];
            }

            // If we have additional settings, store them in the note field as JSON
            // This is separate from user notes which go to the orgUserNote table
            if (!empty($additionalSettings)) {
                // Get existing settings from note field (ignore non-JSON content)
                $existingNote = $orgUserPlan->note;
                $noteData = [];
                if ($existingNote) {
                    $decoded = json_decode($existingNote, true);
                    if (is_array($decoded)) {
                        $noteData = $decoded;
                    }
                    // If it's not JSON, it means it's an old text note, preserve it
                    // by not overwriting, but we'll move to the new system
                }

                // Merge additional settings
                $noteData = array_merge($noteData, $additionalSettings);
                $updateData['note'] = json_encode($noteData);
            }

            // Update the model
            $orgUserPlan->fill($updateData);
            $success = $orgUserPlan->save();

            if ($success) {
                // Add a note if provided
                if ($note || !empty($data['note'])) {
                    $noteText = $note ?? $data['note'];
                    $noteContent = "Membership limits were modified. Session limit changed from {$originalSessionLimit} to {$orgUserPlan->totalQuota}.";

                    if ($noteText) {
                        $noteContent .= "\n\nNote: {$noteText}";
                    }

                    $this->notesService->addNote(
                        $orgUserPlan,
                        'Membership Limits Modified',
                        $noteContent,
                        NoteType::GENERAL,
                        auth()->id()
                    );
                }

                // Dispatch Yii2 job for post-modification processing (matching Core system)
                try {
                    $dispatcher = new Yii2QueueDispatcher();
                    if (self::jobExistsInWodWorx('common\jobs\plan\OrgUserPlanUpdateCompleteJob')) {
                        $dispatcher->dispatch('common\jobs\plan\OrgUserPlanUpdateCompleteJob', ['id' => $orgUserPlan->id]);
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Job not found in wod-worx: common\jobs\plan\OrgUserPlanUpdateCompleteJob');
                    }
                } catch (\Exception $e) {
                    // Log warning but don't fail the update for background job issues
                    \Illuminate\Support\Facades\Log::warning('Failed to dispatch OrgUserPlanUpdateCompleteJob: ' . $e->getMessage(), [
                        'plan_id' => $orgUserPlan->id,
                        'exception' => $e
                    ]);
                }

                // Commit the transaction
                DB::commit();
                return $orgUserPlan;
            } else {
                DB::rollBack();
                return null;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel a membership
     *
     * @param OrgUserPlan $orgUserPlan
     * @param array $data
     * @param string|null $note
     * @return OrgUserPlan|null
     */
    public function cancelMembership(OrgUserPlan $orgUserPlan, array $data, ?string $note = null): ?OrgUserPlan
    {
        // Check if the plan can be modified
        if (!$orgUserPlan->can_be_modified) {
            // Provide specific error messages
            if ($orgUserPlan->isCanceled) {
                throw new \InvalidArgumentException(__('subscriptions.This membership is already cancelled.'));
            }

            if ($orgUserPlan->isDeleted || $orgUserPlan->status === self::STATUS_DELETED) {
                throw new \InvalidArgumentException(__('subscriptions.This membership has been deleted and cannot be cancelled.'));
            }

            throw new \InvalidArgumentException(__('subscriptions.This membership cannot be cancelled at this time.'));
        }

        // Begin transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // Store original values for the note
            $originalStatus = $orgUserPlan->status;

            // Prepare data for update
            $updateData = [
                'status' => 4, // STATUS_CANCELED
                'isCanceled' => true
            ];

            // Set cancellation date if provided
            if (isset($data['cancelledAt'])) {
                $updateData['cancelledAt'] = $data['cancelledAt'];
            }

            // Update the model
            $orgUserPlan->fill($updateData);
            $success = $orgUserPlan->save();

            if ($success) {
                // Add a note about the cancellation
                if ($note || !empty($data['cancellationNote'])) {
                    $noteText = $note ?? $data['cancellationNote'];
                    $noteContent = "Membership was cancelled. Status changed from {$originalStatus} to cancelled.";

                    if ($noteText) {
                        $noteContent .= "\n\nCancellation Reason: {$noteText}";
                    }


                    try {
                        $this->notesService->addNote(
                            $orgUserPlan,
                            'Membership Cancelled',
                            $noteContent,
                            NoteType::GENERAL,
                            auth()->id()
                        );
                    } catch (\Exception $noteException) {
                        // Don't fail the cancellation if note creation fails
                    }
                }

                // Commit the transaction
                DB::commit();
                return $orgUserPlan;
            } else {
                DB::rollBack();
                return null;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Hold (freeze) a membership
     *
     * @param OrgUserPlan $orgUserPlan
     * @param array $data
     * @param string|null $note
     * @return OrgUserPlan|null
     */
    public function holdMembership(OrgUserPlan $orgUserPlan, array $data, ?string $note = null): ?OrgUserPlan
    {
        // Debug: Log service call details
        \Log::info('OrgUserPlanService::holdMembership called', [
            'membership_id' => $orgUserPlan->id,
            'can_be_modified' => $orgUserPlan->can_be_modified,
            'status' => $orgUserPlan->status,
            'isCanceled' => $orgUserPlan->isCanceled,
            'data' => $data
        ]);

        // Check if the plan can be modified
        if (!$orgUserPlan->can_be_modified) {
            \Log::warning('Hold failed: membership cannot be modified', [
                'membership_id' => $orgUserPlan->id,
                'can_be_modified' => $orgUserPlan->can_be_modified
            ]);
            return null;
        }

        // Check if membership is already on hold or cancelled
        if ($orgUserPlan->status == OrgUserPlan::STATUS_HOLD || $orgUserPlan->isCanceled) {
            \Log::warning('Hold failed: membership already on hold or cancelled', [
                'membership_id' => $orgUserPlan->id,
                'status' => $orgUserPlan->status,
                'isCanceled' => $orgUserPlan->isCanceled
            ]);
            return null;
        }

        // Begin transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // Store original values for the note
            $originalStatus = $orgUserPlan->status;
            $originalEndDate = $orgUserPlan->endDateLoc;

            // Calculate hold duration and determine status
            $holdStartDate = Carbon::parse($data['holdStartDate']);
            $holdEndDate = Carbon::parse($data['holdEndDate']);
            // End date is NOT included in duration calculation
            $holdDuration = $holdStartDate->diffInDays($holdEndDate);
            $today = Carbon::today();

            // Change status to HOLD if we're currently in the hold period
            // Use >= and < because end date is the resume date (not included in hold)
            if ($today->greaterThanOrEqualTo($holdStartDate) && $today->lessThan($holdEndDate)) {
                $newStatus = OrgUserPlan::STATUS_HOLD;
                \Log::info('OrgUserPlanService: Setting membership status to HOLD', [
                    'membership_id' => $orgUserPlan->id,
                    'today' => $today->format('Y-m-d'),
                    'holdStartDate' => $holdStartDate->format('Y-m-d'),
                    'holdEndDate' => $holdEndDate->format('Y-m-d'),
                    'old_status' => $orgUserPlan->status,
                    'new_status' => $newStatus
                ]);
            } else {
                // Keep current status if hold hasn't started yet or has ended
                $newStatus = $orgUserPlan->status;
                \Log::info('OrgUserPlanService: Keeping current membership status', [
                    'membership_id' => $orgUserPlan->id,
                    'today' => $today->format('Y-m-d'),
                    'holdStartDate' => $holdStartDate->format('Y-m-d'),
                    'holdEndDate' => $holdEndDate->format('Y-m-d'),
                    'current_status' => $orgUserPlan->status,
                    'reason' => $today->lessThan($holdStartDate) ? 'Hold not started yet' : 'Hold has ended'
                ]);
            }

            $updateData = [
                'status' => $newStatus
                // Note: currentHoldCount field will be added after database migration
                // 'currentHoldCount' => ($orgUserPlan->currentHoldCount ?? 0) + 1
            ];

            // Extend end date by hold duration if membership has an end date
            if ($orgUserPlan->endDateLoc) {
                $currentEndDate = Carbon::parse($orgUserPlan->endDateLoc);
                $newEndDate = $currentEndDate->addDays($holdDuration);

                $updateData['endDateLoc'] = $newEndDate->format('Y-m-d');
                // Convert to UTC datetime string instead of timestamp
                $updateData['endDate'] = $newEndDate->setTimezone('UTC')->toDateTimeString();
            }

            // Store hold information in note field as JSON
            $newHold = [
                'holdStartDate' => $data['holdStartDate'],
                'holdEndDate' => $data['holdEndDate'],
                'holdDuration' => $holdDuration,
                'autoResume' => $data['autoResume'] ?? true,
                'originalEndDate' => $originalEndDate,
                'holdReason' => $data['holdReason'] ?? '',
                'created_at' => now()->toISOString()
            ];

            // If there's existing note data, merge it
            $existingNote = [];
            if ($orgUserPlan->note) {
                $decoded = json_decode($orgUserPlan->note, true);
                if (is_array($decoded)) {
                    $existingNote = $decoded;
                }
            }

            // Initialize holds array if it doesn't exist
            if (!isset($existingNote['holds']) || !is_array($existingNote['holds'])) {
                $existingNote['holds'] = [];
            }

            // Add the new hold to the holds array
            $existingNote['holds'][] = $newHold;

            // Also store the current hold info for backward compatibility
            $existingNote['holdInfo'] = $newHold;

            $updateData['note'] = json_encode($existingNote);

            // Update the model
            $orgUserPlan->fill($updateData);
            $success = $orgUserPlan->save();

            \Log::info('Hold membership save result', [
                'membership_id' => $orgUserPlan->id,
                'success' => $success,
                'originalStatus' => $originalStatus,
                'newStatus' => $newStatus,
                'holdStartDate' => $data['holdStartDate'],
                'holdEndDate' => $data['holdEndDate'],
                'today' => $today->format('Y-m-d'),
                'isCurrentlyInHoldPeriod' => $today->between($holdStartDate, $holdEndDate),
                'updateData' => $updateData
            ]);

            if ($success) {
                // Add a note about the hold
                if ($note || !empty($data['holdReason'])) {
                    $noteText = $note ?? $data['holdReason'];

                    if ($today->between($holdStartDate, $holdEndDate)) {
                        $noteContent = "Membership is currently on hold from {$data['holdStartDate']} to {$data['holdEndDate']} ({$holdDuration} days).";
                    } else {
                        $noteContent = "Membership hold scheduled from {$data['holdStartDate']} to {$data['holdEndDate']} ({$holdDuration} days).";
                    }

                    if ($orgUserPlan->endDateLoc) {
                        $noteContent .= " End date extended to {$orgUserPlan->endDateLoc}.";
                    }

                    if ($noteText) {
                        $noteContent .= "\n\nReason: {$noteText}";
                    }

                    $this->notesService->addNote(
                        $orgUserPlan,
                        'Membership Held',
                        $noteContent,
                        NoteType::GENERAL,
                        auth()->id() ?? 1 // Fallback to admin user if no auth context
                    );
                }

                // Commit the transaction
                DB::commit();
                return $orgUserPlan;
            } else {
                \Log::error('Hold membership save failed', [
                    'membership_id' => $orgUserPlan->id,
                    'updateData' => $updateData,
                    'errors' => $orgUserPlan->getErrors()
                ]);
                DB::rollBack();
                return null;
            }
        } catch (\Exception $e) {
            \Log::error('Hold membership exception', [
                'membership_id' => $orgUserPlan->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Upgrade a membership to a different plan
     *
     * @param OrgUserPlan $orgUserPlan
     * @param array $data
     * @param string|null $note
     * @return OrgUserPlan|null
     */
    public function upgradeMembership(OrgUserPlan $orgUserPlan, array $data, ?string $note = null): ?OrgUserPlan
    {
        // Check if the plan can be modified
        if (!$orgUserPlan->can_be_modified) {
            return null;
        }

        // Begin transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // Get the new plan details
            $newPlan = \App\Models\OrgPlan::find($data['upgradeToPlan']);
            if (!$newPlan) {
                throw new \InvalidArgumentException('Selected plan not found');
            }

            // Store original values for the note
            $originalPlanId = $orgUserPlan->orgPlan_id;
            $originalPlanName = $orgUserPlan->orgPlan->name ?? 'Unknown Plan';

            // Prepare data for update
            $updateData = [];

            // Update to new plan
            $updateData['orgPlan_id'] = $data['upgradeToPlan'];
            $updateData['name'] = $newPlan->name;
            $updateData['price'] = $newPlan->price;
            $updateData['pricePerSession'] = $newPlan->pricePerSession;
            $updateData['currency'] = $newPlan->currency;
            $updateData['totalQuota'] = $newPlan->totalQuota;
            $updateData['dailyQuota'] = $newPlan->dailyQuota;
            $updateData['type'] = $newPlan->type;
            $updateData['venue'] = $newPlan->venue;

            // Handle payment information and invoice total (always set to avoid Yii2 job errors)
            if (isset($data['paidUpgrade']) && $data['paidUpgrade']) {
                // Paid upgrade: use the upgrade fee as invoice total
                $updateData['invoiceTotal'] = $data['upgradeFee'] ?? 0;
                $updateData['invoiceMethod'] = $data['paymentMethod'] ?? null;
                $updateData['invoiceReceipt'] = $data['receiptReference'] ?? null;
                $updateData['invoiceStatus'] = OrgUserPlan::INVOICE_STATUS_PAID;
            } else {
                // Free upgrade: use the new plan price as invoice total
                $planPrice = $newPlan->price ?? 0;
                $updateData['invoiceTotal'] = $planPrice;
                
                // Set status based on price: FREE if 0, PENDING if > 0
                if ($planPrice == 0) {
                    $updateData['invoiceStatus'] = OrgUserPlan::INVOICE_STATUS_FREE;
                } else {
                    $updateData['invoiceStatus'] = OrgUserPlan::INVOICE_STATUS_PENDING;
                }
            }

            // Update the model
            $orgUserPlan->fill($updateData);
            $success = $orgUserPlan->save();

            if ($success) {
                // Add a note about the upgrade
                if ($note || !empty($data['upgradeNote'])) {
                    $noteText = $note ?? $data['upgradeNote'];
                    $noteContent = "Membership was upgraded from {$originalPlanName} to {$newPlan->name}.";

                    if (isset($data['paidUpgrade']) && $data['paidUpgrade']) {
                        $noteContent .= " Upgrade fee: {$data['upgradeFee']} {$newPlan->currency}.";
                        if (!empty($data['paymentMethod'])) {
                            $noteContent .= " Payment method: {$data['paymentMethod']}.";
                        }
                        if (!empty($data['receiptReference'])) {
                            $noteContent .= " Receipt: {$data['receiptReference']}.";
                        }
                    }

                    if ($noteText) {
                        $noteContent .= "\n\nUpgrade Note: {$noteText}";
                    }

                    $this->notesService->addNote(
                        $orgUserPlan,
                        'Membership Upgraded',
                        $noteContent,
                        NoteType::GENERAL,
                        auth()->id()
                    );
                }

                // Commit the transaction
                DB::commit();
                return $orgUserPlan;
            } else {
                DB::rollBack();
                return null;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Transfer membership to another member
     *
     * @param OrgUserPlan $orgUserPlan The membership to transfer
     * @param array $data Transfer data including new member ID and payment details
     * @param string|null $note Optional transfer note
     * @return OrgUserPlan|null Updated membership or null on failure
     */
    public function transferMembership(OrgUserPlan $orgUserPlan, array $data, ?string $note = null): ?OrgUserPlan
    {
        try {
            DB::beginTransaction();

            // Comprehensive validation
            $this->validateTransferData($orgUserPlan, $data);

            // Validate the target member exists
            $targetMember = \App\Models\OrgUser::find($data['transferToMember']);
            if (!$targetMember) {
                throw new \Exception('Target member not found');
            }

            // Store original member info for notes
            $originalMember = $orgUserPlan->orgUser;
            $originalMemberName = $originalMember->fullName;
            $targetMemberName = $targetMember->fullName;

            // Check if this is a pro-rated transfer
            if (isset($data['createNewPlanAndProRate']) && $data['createNewPlanAndProRate']) {
                // Pro-rated transfer: Create new plan for target, keep original for source
                $result = $this->handleProRatedTransfer($orgUserPlan, $data, $note, $originalMemberName, $targetMemberName);
            } else {
                // Simple transfer: Just change ownership
                $result = $this->handleSimpleTransfer($orgUserPlan, $data, $note, $originalMemberName, $targetMemberName);
            }

            if ($result) {
                DB::commit();
                return $result;
            } else {
                DB::rollBack();
                return null;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate transfer data comprehensively
     *
     * @param OrgUserPlan $orgUserPlan The membership to transfer
     * @param array $data Transfer data
     * @throws \Exception If validation fails
     */
    private function validateTransferData(OrgUserPlan $orgUserPlan, array $data): void
    {
        // Validate membership exists
        if (!$orgUserPlan) {
            throw new \Exception('Membership not found');
        }

        // Validate membership can be transferred
        if (!$this->canTransferMembership($orgUserPlan)) {
            throw new \Exception('This membership cannot be transferred');
        }

        // Validate required fields
        if (empty($data['transferToMember'])) {
            throw new \Exception('Target member is required');
        }

        // Validate target member is different from current member
        if ($orgUserPlan->orgUser_id == $data['transferToMember']) {
            throw new \Exception('Cannot transfer membership to the same member');
        }

        // Validate target member exists and is available for transfer
        $targetMember = \App\Models\OrgUser::find($data['transferToMember']);
        if (!$targetMember) {
            throw new \Exception('Target member not found');
        }

        // Check if target member is archived or deleted
        if ($targetMember->isArchived) {
            throw new \Exception('Cannot transfer to archived member');
        }

        if ($targetMember->deleted_at) {
            throw new \Exception('Cannot transfer to deleted member');
        }

        // Validate pro-rated transfer requirements
        if (isset($data['createNewPlanAndProRate']) && $data['createNewPlanAndProRate']) {
            $this->validateProRatedTransfer($orgUserPlan);
        }

        // Validate paid transfer requirements
        if (isset($data['paidTransfer']) && $data['paidTransfer']) {
            $this->validatePaidTransfer($data);
        }

        \Log::info('TransferMembership: Validation passed', [
            'membershipId' => $orgUserPlan->id,
            'targetMemberId' => $data['transferToMember'],
            'proRated' => $data['createNewPlanAndProRate'] ?? false,
            'paidTransfer' => $data['paidTransfer'] ?? false
        ]);
    }

    /**
     * Check if a membership can be transferred
     *
     * @param OrgUserPlan $orgUserPlan
     * @return bool
     */
    private function canTransferMembership(OrgUserPlan $orgUserPlan): bool
    {
        // Cannot transfer if membership is null
        if (!$orgUserPlan) {
            return false;
        }

        // Cannot transfer cancelled memberships
        if ($orgUserPlan->isCanceled || $orgUserPlan->status == OrgUserPlan::STATUS_CANCELED) {
            return false;
        }

        // Cannot transfer deleted memberships
        if ($orgUserPlan->isDeleted || $orgUserPlan->status == OrgUserPlan::STATUS_DELETED) {
            return false;
        }

        // Cannot transfer expired memberships
        if ($orgUserPlan->status == OrgUserPlan::STATUS_EXPIRED ||
            $orgUserPlan->status == OrgUserPlan::STATUS_EXPIRED_LIMIT) {
            return false;
        }

        // Cannot transfer if no remaining time (for pro-rated transfers)
        if ($orgUserPlan->endDateLoc) {
            $endDate = \Carbon\Carbon::parse($orgUserPlan->endDateLoc);
            if ($endDate->isPast()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate pro-rated transfer requirements
     *
     * @param OrgUserPlan $orgUserPlan
     * @throws \Exception If validation fails
     */
    private function validateProRatedTransfer(OrgUserPlan $orgUserPlan): void
    {
        // Must have start and end dates for pro-rating
        if (!$orgUserPlan->startDateLoc || !$orgUserPlan->endDateLoc) {
            throw new \Exception('Membership must have start and end dates for pro-rated transfer');
        }

        // Calculate remaining time
        $totalDays = $this->calculateTotalDays($orgUserPlan);
        $usedDays = $this->calculateUsedDays($orgUserPlan);
        $remainingDays = max(0, $totalDays - $usedDays);

        if ($remainingDays <= 0) {
            throw new \Exception('No remaining time to transfer');
        }

        // Must have a valid price for pro-rating
        if (!$orgUserPlan->price || $orgUserPlan->price <= 0) {
            throw new \Exception('Membership must have a valid price for pro-rated transfer');
        }
    }

    /**
     * Validate paid transfer requirements
     *
     * @param array $data
     * @throws \Exception If validation fails
     */
    private function validatePaidTransfer(array $data): void
    {
        // Must have transfer fee
        if (!isset($data['transferFee']) || $data['transferFee'] <= 0) {
            throw new \Exception('Transfer fee is required for paid transfers');
        }

        // Must have payment method
        if (empty($data['paymentMethod'])) {
            throw new \Exception('Payment method is required for paid transfers');
        }

        // Validate payment method
        $validMethods = ['cash', 'card', 'bank_transfer', 'online'];
        if (!in_array($data['paymentMethod'], $validMethods)) {
            throw new \Exception('Invalid payment method');
        }
    }

    /**
     * Handle simple ownership transfer
     */
    private function handleSimpleTransfer(OrgUserPlan $orgUserPlan, array $data, ?string $note, string $originalMemberName, string $targetMemberName): ?OrgUserPlan
    {
        // Update the membership with new member
        $updateData = [
            'orgUser_id' => $data['transferToMember'],
            'updated_at' => now(),
        ];

        // Handle payment details if it's a paid transfer
        if ($data['paidTransfer'] && $data['transferFee'] > 0) {
            $updateData['invoiceTotal'] = ($orgUserPlan->invoiceTotal ?? 0) + $data['transferFee'];
            $updateData['invoiceMethod'] = $data['paymentMethod'];
            $updateData['invoiceReceipt'] = $data['receiptReference'];
        }

        $updated = $orgUserPlan->update($updateData);

        if ($updated) {
            // Create transfer note
            if ($this->notesService) {
                $noteContent = "Membership transferred from {$originalMemberName} to {$targetMemberName}";

                if ($data['paidTransfer'] && $data['transferFee'] > 0) {
                    $noteContent .= "\nTransfer Fee: " . number_format($data['transferFee'], 2);
                    $noteContent .= "\nPayment Method: " . ucfirst($data['paymentMethod']);
                    if ($data['receiptReference']) {
                        $noteContent .= "\nReceipt/Reference: " . $data['receiptReference'];
                    }
                }

                if ($note) {
                    $noteContent .= "\n\nTransfer Note: {$note}";
                }

                $this->notesService->addNote(
                    $orgUserPlan,
                    'Membership Transferred',
                    $noteContent,
                    NoteType::GENERAL,
                    auth()->id()
                );
            }

            return $orgUserPlan->fresh();
        }

        return null;
    }

    /**
     * Handle pro-rated transfer - creates new plan for target, modifies original for source
     */
    private function handleProRatedTransfer(OrgUserPlan $orgUserPlan, array $data, ?string $note, string $originalMemberName, string $targetMemberName): ?OrgUserPlan
    {
        // Calculate pro-rated values
        $totalDays = $this->calculateTotalDays($orgUserPlan);
        $usedDays = $this->calculateUsedDays($orgUserPlan);
        $remainingDays = max(0, $totalDays - $usedDays);

        if ($remainingDays <= 0) {
            throw new \Exception('No remaining time to transfer');
        }

        // Calculate pro-rated price for remaining time
        $originalPrice = $orgUserPlan->price ?? 0;
        $proRatedPrice = $totalDays > 0 ? ($originalPrice * $remainingDays / $totalDays) : 0;

        // Create new membership for target member with remaining time
        $newMembershipData = [
            'orgUser_id' => $data['transferToMember'],
            'orgPlan_id' => $orgUserPlan->orgPlan_id,
            'name' => $orgUserPlan->name,
            'type' => $orgUserPlan->type,
            'venue' => $orgUserPlan->venue,
            'price' => $proRatedPrice,
            'currency' => $orgUserPlan->currency,
            'startDateLoc' => now()->format('Y-m-d'),
            'endDateLoc' => $orgUserPlan->endDateLoc,
            'totalQuota' => $orgUserPlan->totalQuota,
            'dailyQuota' => $orgUserPlan->dailyQuota,
            'status' => OrgUserPlan::STATUS_ACTIVE,
            'invoiceStatus' => 7, // Free by default
            'org_id' => $orgUserPlan->org_id,
            'sold_by' => auth()->id(),
        ];

        // Create the new membership
        $newMembership = OrgUserPlan::create($newMembershipData);

        if (!$newMembership) {
            throw new \Exception('Failed to create new membership');
        }

        // Update original membership - mark as transferred/cancelled
        $orgUserPlan->update([
            'status' => OrgUserPlan::STATUS_CANCELED,
            'isCanceled' => true,
            'endDateLoc' => now()->format('Y-m-d'), // End it today
            'updated_at' => now(),
        ]);

        // Add notes to both memberships
        if ($this->notesService) {
            // Note for original membership
            $originalNote = "Membership pro-rated and transferred to {$targetMemberName}";
            $originalNote .= "\nOriginal period: {$totalDays} days";
            $originalNote .= "\nUsed period: {$usedDays} days";
            $originalNote .= "\nRemaining period transferred: {$remainingDays} days";

            if ($note) {
                $originalNote .= "\n\nTransfer Note: {$note}";
            }

            $this->notesService->addNote(
                $orgUserPlan,
                'Membership Pro-rated Transfer (Original)',
                $originalNote,
                NoteType::GENERAL,
                auth()->id()
            );

            // Note for new membership
            $newNote = "New membership created from pro-rated transfer from {$originalMemberName}";
            $newNote .= "\nReceived remaining period: {$remainingDays} days";
            $newNote .= "\nPro-rated price: " . number_format($proRatedPrice, 2);

            if ($note) {
                $newNote .= "\n\nTransfer Note: {$note}";
            }

            $this->notesService->addNote(
                $newMembership,
                'Membership Pro-rated Transfer (New)',
                $newNote,
                NoteType::GENERAL,
                auth()->id()
            );
        }

        return $newMembership;
    }

    /**
     * Calculate total days in membership period
     */
    private function calculateTotalDays(OrgUserPlan $orgUserPlan): int
    {
        if (!$orgUserPlan->startDateLoc || !$orgUserPlan->endDateLoc) {
            return 0;
        }

        $startDate = \Carbon\Carbon::parse($orgUserPlan->startDateLoc);
        $endDate = \Carbon\Carbon::parse($orgUserPlan->endDateLoc);

        // End date is NOT included in duration calculation
        return $startDate->diffInDays($endDate);
    }

    /**
     * Calculate used days in membership period
     */
    private function calculateUsedDays(OrgUserPlan $orgUserPlan): int
    {
        if (!$orgUserPlan->startDateLoc) {
            return 0;
        }

        $startDate = \Carbon\Carbon::parse($orgUserPlan->startDateLoc);
        $now = \Carbon\Carbon::now();

        return max(0, $startDate->diffInDays($now));
    }

    /**
     * Reinstate a cancelled membership (matching Core system logic)
     */
    public function reinstateMembership(OrgUserPlan $orgUserPlan, ?string $note = null): ?OrgUserPlan
    {
        // Check if membership is actually cancelled
        if (!$orgUserPlan->isCanceled) {
            return null;
        }

        DB::beginTransaction();

        try {
            // Determine the appropriate status to restore to (matching Core system logic)
            $newStatus = $this->determineReinstateStatus($orgUserPlan);
            
            // Update membership status with smart status detection
            $orgUserPlan->update([
                'isCanceled' => false,
                'status' => $newStatus
            ]);

            // Create a note about the reinstatement
            $noteContent = __('subscriptions.Membership has been reinstated and is now active.');
            if ($note) {
                $noteContent .= ' Note: ' . $note;
            }
            
            $this->notesService->addNote(
                $orgUserPlan,
                __('subscriptions.Membership Reinstated'),
                $noteContent,
                NoteType::GENERAL
            );

            DB::commit();

            session()->flash('success', __('subscriptions.Membership reinstated successfully'));
            return $orgUserPlan->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', __('subscriptions.Failed to reinstate membership. Please try again.'));
            return null;
        }
    }

    /**
     * Determine the appropriate status when reinstating a membership
     * (Matches Core system logic)
     */
    private function determineReinstateStatus(OrgUserPlan $orgUserPlan): int
    {
        $now = now();
        
        // If the plan is expired, keep it as expired
        if ($orgUserPlan->endDateLoc && \Carbon\Carbon::parse($orgUserPlan->endDateLoc) < $now) {
            return OrgUserPlan::STATUS_EXPIRED;
        } 
        // If the plan hasn't started yet, mark as upcoming
        elseif ($orgUserPlan->startDateLoc && \Carbon\Carbon::parse($orgUserPlan->startDateLoc) > $now) {
            return OrgUserPlan::STATUS_UPCOMING;
        }
        // Otherwise, mark as active
        else {
            return OrgUserPlan::STATUS_ACTIVE;
        }
    }

    /**
     * Check if a Yii2 job exists in the wod-worx project before dispatching
     *
     * @param string $jobClass The job class name (e.g., 'common\jobs\plan\OrgUserPlanUpdateCompleteJob')
     * @return bool
     */
    protected static function jobExistsInWodWorx(string $jobClass): bool
    {
        // Convert namespace to file path
        $filePath = str_replace('\\', '/', $jobClass) . '.php';
        $fullPath = '/Users/macbook1993/wod-worx/' . $filePath;
        
        $exists = file_exists($fullPath);
        
        \Illuminate\Support\Facades\Log::info("Checking job existence: {$jobClass}", [
            'file_path' => $fullPath,
            'exists' => $exists
        ]);
        
        return $exists;
    }
}
