<?php

namespace App\Http\Controllers;

use Creagia\LaravelSignPad\Actions\GenerateSignatureDocumentAction;
use Creagia\LaravelSignPad\Contracts\CanBeSigned;
use Creagia\LaravelSignPad\Contracts\ShouldGenerateSignatureDocument;
use Creagia\LaravelSignPad\Exceptions\ModelHasAlreadyBeenSigned;
use Exception;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomSignPadController extends Controller
{
    use ValidatesRequests;

    public function __invoke(Request $request, GenerateSignatureDocumentAction $generateSignatureDocumentAction): \Illuminate\Http\RedirectResponse
    {
        $validatedData = $this->validate($request, [
            'model' => ['required'],
            'sign' => ['required'],
            'id' => ['required'],
            'token' => ['required'],
            'redirect_url' => ['nullable', 'url'], // Allow custom redirect URL
        ]);

        $modelClass = $validatedData['model'];
        $decodedImage = base64_decode(explode(',', $validatedData['sign'])[1]);

        if (! $decodedImage) {
            throw new Exception(__('gym.invalid_signature'));
        }

        $model = app($modelClass)->findOrFail($validatedData['id']);

        $requiredToken = md5(config('app.key').$modelClass);
        if ($validatedData['token'] !== $requiredToken) {
            abort(403, __('gym.invalid_token'));
        }

        if ($model instanceof CanBeSigned && $model->hasBeenSigned()) {
            throw new ModelHasAlreadyBeenSigned;
        }

        $uuid = Str::uuid()->toString();
        $filename = "{$uuid}.png";
        $signature = $model->signature()->create([
            'uuid' => $uuid,
            'from_ips' => $request->ips(),
            'filename' => $filename,
            'certified' => config('sign-pad.certify_documents'),
        ]);

        Storage::disk(config('sign-pad.disk_name'))->put($signature->getSignatureImagePath(), $decodedImage);

        if ($model instanceof ShouldGenerateSignatureDocument) {
            ($generateSignatureDocumentAction)(
                $signature,
                $model->getSignatureDocumentTemplate(),
                $decodedImage
            );
        } else {
            // Generate PDF manually for OrgUser models
            $this->generateSignedPdf($model, $signature);
        }

        // Send SMS notification with PDF download link
        $this->sendSignatureCompletionSms($model, $signature);
        
        // Send email notification with PDF download link
        $this->sendSignatureCompletionEmail($model, $signature);

        // Check for custom redirect URL, otherwise use default config
        if (!empty($validatedData['redirect_url'])) {
            return redirect($validatedData['redirect_url']);
        }

        return redirect()->route(config('sign-pad.redirect_route_name'), ['uuid' => $uuid]);
    }

    /**
     * Generate signed PDF for OrgUser models
     */
    private function generateSignedPdf($model, $signature): void
    {
        // Only generate PDF for OrgUser models
        if (!($model instanceof \App\Models\OrgUser)) {
            return;
        }

        try {
            $pdfService = new \App\Services\TermsPdfService();
            $pdfFilename = $pdfService->generateSignedTermsPdf($model, $signature);

            // Update the signature record with the PDF filename
            $signature->update(['document_filename' => $pdfFilename]);
        } catch (\Exception $e) {
            // Log error but don't fail the signature process
            \Log::error('Failed to generate signed PDF', [
                'org_user_id' => $model->id,
                'signature_uuid' => $signature->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send SMS notification with signed PDF download link
     */
    private function sendSignatureCompletionSms($model, $signature): void
    {
        // Only send SMS for OrgUser models
        if (!($model instanceof \App\Models\OrgUser)) {
            return;
        }

        $orgUser = $model;

        // Check if user has a phone number
        if (!$orgUser->fullPhone) {
            return;
        }

        // Get the signed document URL
        $pdfUrl = $signature->getSignedDocumentUrl();

        // Only send SMS if PDF exists
        if (!$pdfUrl) {
            return;
        }

        $gymName = $orgUser->org->name ?? 'Wodworx';
        $message = __('gym.membership_agreement_signed', [
            'name' => $orgUser->fullName,
            'gym' => $gymName,
            'url' => $pdfUrl
        ]);

        try {
            $smsService = app(\App\Services\SmsService::class);
            $smsService->send(
                to: $orgUser->fullPhone,
                message: $message,
                orgId: $orgUser->org_id,
                orgUserId: $orgUser->id,
                options: [
                    'subject' => __('gym.signed_membership_agreement'),
                    'create_msg_item' => true,
                ]
            );
        } catch (\Exception $e) {
            // Log error but don't fail the signature process
            \Log::error('Failed to send signature completion SMS', [
                'org_user_id' => $orgUser->id,
                'signature_uuid' => $signature->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send email notification with signed PDF download link
     */
    private function sendSignatureCompletionEmail($model, $signature): void
    {
        // Only send email for OrgUser models
        if (!($model instanceof \App\Models\OrgUser)) {
            return;
        }

        $orgUser = $model;

        // Check if user has an email address
        if (!$orgUser->email) {
            return;
        }

        // Get the signed document URL
        $pdfUrl = $signature->getSignedDocumentUrl();

        // Only send email if PDF exists
        if (!$pdfUrl) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($orgUser->email)->send(
                new \App\Mail\SignedAgreementMail($orgUser, $signature, $pdfUrl)
            );
        } catch (\Exception $e) {
            // Log error but don't fail the signature process
            \Log::error('Failed to send signature completion email', [
                'org_user_id' => $orgUser->id,
                'signature_uuid' => $signature->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
