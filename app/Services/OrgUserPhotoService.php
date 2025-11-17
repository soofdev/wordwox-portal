<?php

namespace App\Services;

use App\Models\OrgUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class OrgUserPhotoService
{
    /**
     * Clear the profile image for an OrgUser
     *
     * @param OrgUser $orgUser
     * @return bool
     */
    public function clearProfileImage(OrgUser $orgUser): bool
    {
        // Delete the file from S3 storage
        if (!empty($orgUser->photoFilePath)) {
            Storage::disk('s3')->delete($orgUser->photoFilePath);
        }
        
        // Clear the file path in the database
        $orgUser->update([
            'photoFilePath' => null,
        ]);
        
        session()->flash('photo-success', 'Profile image cleared successfully');
            
        return true;
    }
    
    /**
     * Clear the portrait image for an OrgUser
     *
     * @param OrgUser $orgUser
     * @return bool
     */
    public function clearPortraitImage(OrgUser $orgUser): bool
    {
        // Delete the file from S3 storage
        if (!empty($orgUser->portraitFilePath)) {
            Storage::disk('s3')->delete($orgUser->portraitFilePath);
        }
        
        // Clear the file path in the database
        $orgUser->update([
            'portraitFilePath' => null,
        ]);
        
        session()->flash('photo-success', 'Portrait image cleared successfully');
            
        return true;
    }
    
    /**
     * Upload a profile image for an OrgUser
     *
     * @param OrgUser $orgUser
     * @param UploadedFile $image
     * @return bool
     */
    public function uploadProfileImage(OrgUser $orgUser, UploadedFile $image): bool
    {
        try {
            // Log the file information
            Log::info('Uploading profile image', [
                'filename' => $image->getClientOriginalName(),
                'size' => $image->getSize(),
                'mime' => $image->getMimeType(),
            ]);
            
            // Check if S3 disk is configured
            $diskConfig = config('filesystems.disks.s3');
            Log::info('S3 disk configuration', [
                'driver' => $diskConfig['driver'] ?? 'not set',
                'bucket' => $diskConfig['bucket'] ?? 'not set',
                'url' => $diskConfig['url'] ?? 'not set',
                'endpoint' => $diskConfig['endpoint'] ?? 'not set',
                'use_path_style_endpoint' => $diskConfig['use_path_style_endpoint'] ?? 'not set',
            ]);
            
            // Store the file in S3 with visibility
            $path = $image->storePublicly('u/p', 's3');
            
            Log::info('Profile image stored', [
                'path' => $path,
                'exists' => Storage::disk('s3')->exists($path),
                'url' => Storage::disk('s3')->url($path),
            ]);
            
            // Update the record with the new file path
            $orgUser->update([
                'photoFilePath' => $path,
            ]);
            
            session()->flash('photo-success', 'Profile image uploaded successfully');
                
            return true;
        } catch (\Exception $e) {
            Log::error('Error uploading profile image', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            session()->flash('photo-error', 'Error uploading profile image: ' . $e->getMessage());
                
            return false;
        }
    }
    
    /**
     * Upload a portrait image for an OrgUser
     *
     * @param OrgUser $orgUser
     * @param UploadedFile $image
     * @return bool
     */
    public function uploadPortraitImage(OrgUser $orgUser, UploadedFile $image): bool
    {
        try {
            // Log the file information
            Log::info('Uploading portrait image', [
                'filename' => $image->getClientOriginalName(),
                'size' => $image->getSize(),
                'mime' => $image->getMimeType(),
            ]);
            
            // Store the file in S3 with visibility
            $path = $image->storePublicly('u/p', 's3');
            
            Log::info('Portrait image stored', [
                'path' => $path,
                'exists' => Storage::disk('s3')->exists($path),
                'url' => Storage::disk('s3')->url($path),
            ]);
            
            // Update the record with the new file path
            $orgUser->update([
                'portraitFilePath' => $path,
            ]);
            
            session()->flash('photo-success', 'Portrait image uploaded successfully');
                
            return true;
        } catch (\Exception $e) {
            Log::error('Error uploading portrait image', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            session()->flash('photo-error', 'Error uploading portrait image: ' . $e->getMessage());
                
            return false;
        }
    }
}
