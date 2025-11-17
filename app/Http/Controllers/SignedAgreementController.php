<?php

namespace App\Http\Controllers;

use App\Models\OrgUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SignedAgreementController extends Controller
{
    /**
     * Download the signed agreement PDF for a member
     * 
     * @param OrgUser $member
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(OrgUser $member)
    {
        // Security check: Verify member belongs to current user's organization
        if ($member->org_id !== auth()->user()->orgUser->org_id) {
            abort(403, 'Access denied to this member\'s signed agreement.');
        }

        // Check if member has been signed
        if (!$member->hasBeenSigned() || !$member->signature) {
            abort(404, 'No signed agreement found for this member.');
        }

        // Check if PDF document exists
        if (!$member->signature->document_filename) {
            abort(404, 'Signed agreement PDF not available.');
        }

        // Get the signed document path
        $documentPath = $member->signature->getSignedDocumentPath();
        
        if (!$documentPath || !Storage::disk(config('sign-pad.disk_name'))->exists($documentPath)) {
            abort(404, 'Signed agreement file not found.');
        }

        // Generate a descriptive filename for download
        $orgName = $member->org->name ?? 'Organization';
        $memberName = $member->fullName;
        $signedDate = $member->signature->created_at->format('Y-m-d');
        
        // Sanitize filename components
        $orgName = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $orgName));
        $memberName = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $memberName));
        
        $downloadFilename = "{$orgName}-{$memberName}-Signed-Agreement-{$signedDate}.pdf";

        // Get file content and return as download
        $fileContent = Storage::disk(config('sign-pad.disk_name'))->get($documentPath);
        
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $downloadFilename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Preview the signed agreement PDF in browser (optional method)
     * 
     * @param OrgUser $member
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function preview(OrgUser $member)
    {
        // Security check: Verify member belongs to current user's organization
        if ($member->org_id !== auth()->user()->orgUser->org_id) {
            abort(403, 'Access denied to this member\'s signed agreement.');
        }

        // Check if member has been signed
        if (!$member->hasBeenSigned() || !$member->signature) {
            abort(404, 'No signed agreement found for this member.');
        }

        // Check if PDF document exists
        if (!$member->signature->document_filename) {
            abort(404, 'Signed agreement PDF not available.');
        }

        // Get the signed document path
        $documentPath = $member->signature->getSignedDocumentPath();
        
        if (!$documentPath || !Storage::disk(config('sign-pad.disk_name'))->exists($documentPath)) {
            abort(404, 'Signed agreement file not found.');
        }

        // Generate a descriptive filename for preview
        $orgName = $member->org->name ?? 'Organization';
        $memberName = $member->fullName;
        $signedDate = $member->signature->created_at->format('Y-m-d');
        
        // Sanitize filename components
        $orgName = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $orgName));
        $memberName = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $memberName));
        
        $previewFilename = "{$orgName}-{$memberName}-Signed-Agreement-{$signedDate}.pdf";

        // Get file content and return for inline preview
        $fileContent = Storage::disk(config('sign-pad.disk_name'))->get($documentPath);
        
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $previewFilename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
