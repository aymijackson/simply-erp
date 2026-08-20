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
