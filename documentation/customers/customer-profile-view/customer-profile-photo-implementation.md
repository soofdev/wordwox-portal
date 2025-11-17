# Profile Photo Implementation - wodworx-foh

## Overview

This document details the implementation of profile photo capture functionality in the wodworx-foh project. The system provides both file upload and webcam capture capabilities for member profile photos, maintaining compatibility with the existing wodworx ecosystem.

## 🏗️ Architecture

### System Design
The implementation follows the proven dual-photo architecture from the legacy Yii2 and Laravel Filament systems:

- **`photoFilePath`**: Public profile images for community display and mobile API
- **`portraitFilePath`**: Internal administrative photos for staff management and security

### Components Overview
```
┌─────────────────────────────────────────────────────────────┐
│                    wodworx-foh Application                   │
│                                                             │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │  UI Components  │    │      Business Logic             │ │
│  │                 │    │                                 │ │
│  │  MemberProfile  │◄───┤  OrgUserPhotoService           │ │
│  │  ActiveMembers  │    │  - uploadProfileImage()        │ │
│  │  CreationFlow   │    │  - uploadPortraitImage()       │ │
│  │  PhotoCapture   │    │  - clearProfileImage()         │ │
│  └─────────────────┘    └─────────────────────────────────┘ │
│             │                            │                 │
│             ▼                            ▼                 │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │                 Data Layer                              │ │
│  │  OrgUser Model + Photo Accessors + S3 Storage         │ │
│  └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    AWS S3 Storage                           │
│  u/p/{filename} - Profile and portrait images              │
└─────────────────────────────────────────────────────────────┘
```

## 📁 File Structure

### Core Files Created/Modified

```
app/
├── Services/
│   └── OrgUserPhotoService.php          # Photo management service (unified with wodworx-core)
├── Livewire/
│   └── ProfileImageManager.php          # Main photo management component
├── Models/
│   └── OrgUser.php                      # Updated with photo fields & accessors
resources/
├── views/
│   ├── components/
│   │   └── profile-image.blade.php      # Reusable profile image component
│   ├── livewire/
│   │   ├── profile-image-manager.blade.php     # Main photo management interface
│   │   ├── member-profile.blade.php     # Updated with photo display
│   │   └── active-members-list.blade.php       # Updated with profile photos
│   └── member/
│       └── creation-success.blade.php   # Updated with photo capture
```

## 🔧 Implementation Details

### 1. OrgUserPhotoService

**Location**: `app/Services/OrgUserPhotoService.php`

This service is **based on** the one in wodworx-core but **adapted for FOH** which doesn't use Filament. The key difference is that it uses Laravel session flash messages instead of Filament notifications.

**Key Methods**:
- `uploadProfileImage(OrgUser $orgUser, UploadedFile $image): bool`
- `uploadPortraitImage(OrgUser $orgUser, UploadedFile $image): bool`
- `clearProfileImage(OrgUser $orgUser): bool`
- `clearPortraitImage(OrgUser $orgUser): bool`

**Features**:
- S3 storage with public visibility
- Comprehensive error handling and logging
- Laravel session flash messages for user feedback
- Automatic cleanup of old files when replacing

### 2. OrgUser Model Updates

**Location**: `app/Models/OrgUser.php`

**Added Fields to `$fillable`**:
```php
'photoFileName',      // Legacy compatibility
'photoFilePath',      // Active: Public profile images
'portraitFileName',   // Legacy compatibility  
'portraitFilePath',   // Active: Administrative photos
```

**New Accessor Methods**:
- `getProfileImageUrlAttribute()`: Returns S3 URL for profile image
- `getPortraitImageUrlAttribute()`: Returns S3 URL for portrait image
- `getProfileImageOrAvatarAttribute()`: Returns image URL or generated avatar fallback
- `getInitialsAttribute()`: Returns user initials for avatar generation

### 3. ProfileImageManager

**Location**: `app/Livewire/ProfileImageManager.php`

**Features**:
- **Integrated Modal Interface**: Click profile image to open management modal
- **File Upload**: Traditional file selection from device  
- **Webcam Capture**: Real-time camera access with JavaScript
- **Photo Management**: View, upload, capture, and clear photos in one interface
- **Validation**: File size (1MB), type (JPEG, PNG, GIF), and MIME type checking
- **Error Handling**: Comprehensive error management with user notifications

**Props**:
- `$member`: OrgUser instance
- `$photoType`: 'profile' or 'portrait' (defaults to 'profile')
- `$size`: Display size for the profile image (defaults to '2xl')

**Key Methods**:
- `openModal()`: Opens the photo management modal
- `uploadPhoto()`: Handles file upload
- `uploadFromWebcam($imageData)`: Processes webcam-captured images  
- `clearPhoto()`: Removes existing photos
- `showUpload()`: Shows the file upload interface
- `showWebcam()`: Shows the webcam capture interface

### 4. ProfileImage Component

**Location**: `resources/views/components/profile-image.blade.php`

**Reusable Blade component** for consistent photo display across the application.

**Props**:
- `src`: Image URL (optional)
- `name`: User name for fallback initials
- `size`: Size variant (xs, sm, md, lg, xl, 2xl, 3xl, 4xl)
- `clickable`: Enable modal preview (optional)
- `class`: Additional CSS classes (optional)

**Features**:
- Automatic fallback to generated avatars with initials
- Multiple size variants
- Optional modal preview for larger images
- Responsive design with proper loading states

## 🔗 Integration Points

### 1. Member Profile Page

**File**: `resources/views/livewire/member-profile.blade.php`

**Changes**:
- Replaced static profile image with `<livewire:profile-image-manager>` component
- Clicking the profile photo opens an integrated management modal
- Removed dedicated photo management card for streamlined UI

### 2. Active Members List

**File**: `resources/views/livewire/active-members-list.blade.php`

**Changes**:
- Replaced `flux:avatar` with `<x-profile-image>` component
- Shows actual profile photos or generated avatars
- Uses card-based grid layout (ID card design metaphor)
- Responsive design with 1-4 columns based on screen size

### 3. Member Creation Success Page

**File**: `resources/views/member/creation-success.blade.php`

**Changes**:
- Added `<livewire:profile-image-manager>` component in "Next Steps" area
- Click-to-manage interface for immediate photo capture after member creation
- Integrated seamlessly with existing workflow

## 🎨 User Experience

### Photo Management Flow

1. **Access Management**:
   - Click on any profile photo to open management modal
   - Large preview of current photo (or placeholder)
   - Three main action buttons: Upload, Take Photo, Remove

2. **Upload Method**:
   - Click "Upload Photo" button in modal
   - File selection interface appears
   - Select file from device with real-time validation
   - Automatic upload and display update

3. **Webcam Method**:
   - Click "Take Photo" button in modal
   - Webcam interface appears
   - Grant camera permissions
   - Live camera preview
   - Capture, review, retake options
   - Save captured photo

### Photo Display

1. **Profile Photos**: Shown in member profile header and active members card grid
2. **Fallback Avatars**: Generated with user initials when no photo exists  
3. **Click-to-Manage**: Click any profile photo to access management options
4. **Integrated Interface**: Upload, capture, and clear all in one modal
5. **Card Layout**: Large avatars prominently displayed in member cards (ID card design)

## 🔒 Security & Validation

### File Upload Security
- **Size Limit**: 1MB maximum
- **File Types**: JPEG, PNG, JPG, GIF only
- **MIME Type Validation**: Server-side content verification
- **S3 Storage**: Secure cloud storage with public read access

### Webcam Security
- **Permission-based**: Requires explicit user permission
- **Client-side Processing**: Image processing in browser
- **Secure Upload**: Base64 data converted to file server-side

## 📊 Performance Considerations

### Optimizations
- **Lazy Loading**: Images loaded on demand
- **S3 CDN**: Fast delivery via AWS infrastructure
- **Efficient Queries**: Minimal database impact
- **Fallback Caching**: Generated avatars cached via external service

### Storage
- **Path Structure**: `u/p/{filename}` (consistent with legacy systems)
- **File Naming**: Laravel automatic unique naming
- **Cleanup**: Automatic removal of old files when replacing

## 🧪 Testing Considerations

### Manual Testing Checklist

**File Upload**:
- [ ] Upload valid image files (JPEG, PNG, GIF)
- [ ] Test file size limits (reject >1MB)
- [ ] Test invalid file types (reject non-images)
- [ ] Verify S3 storage and URL generation
- [ ] Test photo replacement and cleanup

**Webcam Capture**:
- [ ] Camera permission request
- [ ] Live camera preview
- [ ] Photo capture and preview
- [ ] Retake functionality
- [ ] Upload and storage

**Display Integration**:
- [ ] Profile photos in member profile
- [ ] Photos in active members card grid (ID card layout)
- [ ] Fallback avatars when no photo
- [ ] Modal preview functionality

**Error Handling**:
- [ ] Network failures during upload
- [ ] Camera access denied
- [ ] Invalid file formats
- [ ] S3 storage errors

## 🔄 Compatibility

### Database Compatibility
- **Field Names**: Identical to wodworx-core and Yii2 legacy
- **Data Types**: VARCHAR(255) for file paths
- **Migration**: No migration needed (fields already exist)

### Service Compatibility
- **OrgUserPhotoService**: Exact copy from wodworx-core
- **Storage Paths**: Compatible with existing S3 structure
- **API Integration**: Ready for mobile API consumption

## 🚀 Deployment Notes

### Environment Variables
Ensure S3 configuration is properly set:
```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket-name.s3.amazonaws.com
```

### S3 Bucket Configuration
- **Public Read Access**: Required for image display
- **CORS Configuration**: Enable for direct uploads
- **Lifecycle Policies**: Consider for storage optimization

## 📈 Future Enhancements

### Phase 3 Potential Features
- **Image Cropping**: Client-side crop before upload
- **Multiple Sizes**: Automatic thumbnail generation
- **Bulk Operations**: Batch photo management
- **Photo History**: Version tracking and rollback
- **Advanced Validation**: Image quality checks
- **Progressive Upload**: Better upload experience

## 🔍 Troubleshooting

### Common Issues

**Photos Not Displaying**:
1. Check S3 configuration and credentials
2. Verify bucket permissions and CORS settings
3. Confirm file paths in database

**Upload Failures**:
1. Check file size and format
2. Verify S3 write permissions
3. Review Laravel logs for detailed errors

**Webcam Not Working**:
1. Ensure HTTPS connection (required for camera access)
2. Check browser permissions
3. Test on different browsers/devices

### Monitoring Points
- Upload success/failure rates
- S3 storage usage and costs
- Error frequency by type
- User engagement with photo features

---

This implementation provides a robust, user-friendly photo capture system that integrates seamlessly with the existing wodworx-foh workflow while maintaining compatibility across the entire wodworx ecosystem.
