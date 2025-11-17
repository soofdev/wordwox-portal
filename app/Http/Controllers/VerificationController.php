<?php

namespace App\Http\Controllers;

use App\Models\OrgUser;
use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

/**
 * VerificationController handles the verification flow for OrgUser accounts
 * Supports both email and SMS verification with account linking
 */
class VerificationController extends Controller
{
    protected VerificationService $verificationService;

    public function __construct(VerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * Main verification entry point - handles token validation and routing
     * 
     * @param Request $request
     * @param string $token
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function verify(Request $request, string $token)
    {
        // Step 1: Show landing page with loading state
        if (!$request->has('process')) {
            return $this->showLandingPage($token);
        }

        // Process the token
        $result = $this->verificationService->validateToken($token);
        
        if (!$result['valid']) {
            return view('verification.error', [
                'error' => $result['error'],
                'title' => 'Verification Error'
            ]);
        }

        $orgUser = $result['orgUser'];
        $verificationType = $result['verificationType'];

        // Check if already verified
        if ($this->verificationService->isAlreadyVerified($orgUser)) {
            return view('verification.already-verified', [
                'org' => $orgUser->org,
                'orgUser' => $orgUser,
                'user' => $orgUser->user,
                'memberships' => $this->verificationService->getUserMemberships($orgUser->user)
            ]);
        }

        // Check for existing user
        $existingUser = $this->verificationService->checkExistingUser($orgUser, $verificationType);

        if ($existingUser) {
            // Step 2B: Account linking path (will implement next)
            return view('verification.link-account', [
                'org' => $orgUser->org,
                'orgUser' => $orgUser,
                'existingUser' => $existingUser,
                'token' => $token,
                'memberships' => $this->verificationService->getUserMemberships($existingUser)
            ]);
        }

        // Step 2A: New user path
        return view('verification.new-user', [
            'org' => $orgUser->org,
            'orgUser' => $orgUser,
            'token' => $token,
            'verificationType' => $verificationType
        ]);
    }

    /**
     * Show the landing page with gym branding and loading state
     * 
     * @param string $token
     * @return \Illuminate\View\View
     */
    protected function showLandingPage(string $token)
    {
        // Quick token validation to get org info
        $result = $this->verificationService->validateToken($token);
        
        if (!$result['valid']) {
            return view('verification.error', [
                'error' => $result['error'],
                'title' => 'Invalid Link'
            ]);
        }

        return view('verification.landing', [
            'org' => $result['orgUser']->org,
            'token' => $token
        ]);
    }

    /**
     * Handle new user account creation
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed'
        ], [
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $result = $this->verificationService->validateToken($request->token);
        
        if (!$result['valid']) {
            return back()->withErrors(['token' => 'Invalid or expired token']);
        }

        $orgUser = $result['orgUser'];
        $verificationType = $result['verificationType'];

        try {
            // Prepare user data based on verification type
            $userData = [
                'password' => Hash::make($request->password),
                'fullName' => $orgUser->fullName,
            ];

            // Add only the verified contact information
            if ($verificationType === 'email') {
                $userData['email'] = $orgUser->email;
            } elseif ($verificationType === 'sms') {
                $userData['phoneNumber'] = $orgUser->phoneNumber;
                $userData['phoneCountry'] = $orgUser->phoneCountry;
            } elseif ($verificationType === 'both') {
                // For 'both', include both (this case might not occur with current token system)
                $userData['email'] = $orgUser->email;
                $userData['phoneNumber'] = $orgUser->phoneNumber;
                $userData['phoneCountry'] = $orgUser->phoneCountry;
            }

            // Create user account
            $user = $this->verificationService->createUserAccount($userData, $orgUser, $verificationType);

            // Redirect to success page with OrgUser UUID in path
            return redirect()->route('orguser.verification.success', [
                'uuid' => $orgUser->uuid
            ])->with('success', 'Account created successfully!');

        } catch (\Exception $e) {
            return back()->withErrors(['general' => 'An error occurred while creating your account. Please try again.']);
        }
    }

    /**
     * Handle account linking for existing users
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function linkAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string'
            // Removed 'action' validation - only linking is supported
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $result = $this->verificationService->validateToken($request->token);
        
        if (!$result['valid']) {
            return back()->withErrors(['token' => 'Invalid or expired token']);
        }

        $orgUser = $result['orgUser'];
        $verificationType = $result['verificationType'];

        // Only account linking is supported - no create new option
        $existingUser = $this->verificationService->checkExistingUser($orgUser, $verificationType);
        
        if (!$existingUser) {
            return back()->withErrors(['general' => 'Existing user not found.']);
        }

        try {
            $this->verificationService->linkExistingUser($existingUser, $orgUser);

            return redirect()->route('orguser.verification.success', [
                'uuid' => $orgUser->uuid
            ])->with('success', 'Account linked successfully!');

        } catch (\Exception $e) {
            return back()->withErrors(['general' => 'An error occurred while linking your account. Please try again.']);
        }
    }

    /**
     * Show success page after verification
     * 
     * @param Request $request
     * @param string $uuid OrgUser UUID
     * @return \Illuminate\View\View
     */
    public function success(Request $request, string $uuid)
    {
        // Find OrgUser by UUID
        $orgUser = OrgUser::where('uuid', $uuid)
                         ->with(['org', 'user'])
                         ->first();

        if (!$orgUser) {
            return view('verification.error', [
                'error' => 'Invalid verification data. Please try the verification process again.',
                'title' => 'Verification Error'
            ]);
        }

        // Verify the OrgUser is actually verified (has user_id)
        if (!$orgUser->user_id || !$orgUser->user) {
            return view('verification.error', [
                'error' => 'Account verification incomplete. Please try the verification process again.',
                'title' => 'Verification Incomplete'
            ]);
        }

        return view('verification.success', [
            'org' => $orgUser->org,
            'orgUser' => $orgUser,
            'user' => $orgUser->user,
            'memberships' => $this->verificationService->getUserMemberships($orgUser->user),
            'verificationType' => 'email' // Default since token is cleared
        ]);
    }
}
