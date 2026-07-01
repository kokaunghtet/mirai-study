<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountRestoreController extends Controller
{
    /**
     * Cancel the scheduled account deletion.
     */
    public function restore(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isDeletionScheduled()) {
            return back()->with('error', 'No scheduled deletion found.');
        }

        $user->update(['deletion_scheduled_at' => null]);

        return back()->with('success', 'Account deletion has been cancelled successfully.');
    }
}
