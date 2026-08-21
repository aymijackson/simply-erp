<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;
use DataTables;

class AdminController extends Controller
{
    public function profile()
    {
        $user = auth()->user();

        return view('profile.index', compact('user'));
    }

    /**
     * Temporary diagnostic: shows the logged-in user's own roles and their
     * procurement.purchase_orders.* permissions as plain JSON, so this can
     * be checked by just visiting the URL in a browser instead of running
     * a terminal command. Remove once the permissions mismatch it's meant
     * to diagnose is resolved.
     */
    public function debugPermissions()
    {
        $u = auth()->user();

        $perms = collect(['view', 'edit', 'approve', 'cancel', 'delete', 'issue', 'pdf', 'close', 'create'])
            ->mapWithKeys(fn ($p) => ["procurement.purchase_orders.{$p}" => $u->can("procurement.purchase_orders.{$p}")]);

        return response()->json([
            'user_id' => $u->id,
            'email' => $u->email,
            'roles' => $u->getRoleNames(),
            'procurement.purchase_orders.*' => $perms,
        ]);
    }

    /**
     * Partial update of the current user's own appearance preference.
     * Only touches whichever field(s) are present in the request, so the
     * topbar quick-toggle can send just theme_mode without clobbering a
     * previously-chosen accent/sidebar style.
     */
    public function updateTheme(Request $request)
    {
        $data = $request->validate([
            'theme_mode'    => ['sometimes', 'nullable', 'in:light,dark'],
            'theme_accent'  => ['sometimes', 'nullable', 'in:indigo,emerald,sky,rose,amber,slate'],
            'theme_sidebar' => ['sometimes', 'nullable', 'in:dark,light'],
        ]);

        auth()->user()->update($data);

        return response()->json(['message' => 'Appearance updated.']);
    }
}
