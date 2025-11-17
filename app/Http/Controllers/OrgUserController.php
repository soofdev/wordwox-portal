<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrgUser;
use Illuminate\Support\Facades\Auth;

class OrgUserController extends Controller
{
    public function select()
    {
        $user = Auth::user();

        // Only show organizations where the user has FOH access
        $orgUsers = OrgUser::where('orgUser.user_id', $user->id)
            ->where('isFohUser', true)  // Only FOH-enabled orgUsers
            ->withoutGlobalScopes()
            ->with(['org'])
            ->join('org', 'orgUser.org_id', '=', 'org.id')
            ->orderBy('org.name')
            ->select('orgUser.*')
            ->get();

        return view('org-user.select', compact('orgUsers'));
    }

    public function set($id)
    {
        $user = Auth::user();

        // Verify the user has FOH access to this organization
        $orgUser = OrgUser::where('id', $id)
            ->where('user_id', $user->id)
            ->where('isFohUser', true)  // Must have FOH access
            ->withoutGlobalScopes()
            ->first();

        if (!$orgUser) {
            abort(403, __('gym.foh_access_denied'));
        }

        // Set the user.orgUser_id to the given id
        $user->orgUser_id = $id;
        $user->save();

        // Optional: Log the action
        // $this->logService->orgUserSelect($user->id, $id, 'User switched to orgUser #' . $id);

        return redirect()->route('dashboard');
    }
}
