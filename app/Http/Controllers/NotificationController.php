<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Taxonomy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('notifications.index', ['notifications' => $request->user()->notifications()->paginate(20),
            'preferences' => $request->user()->notification_preferences ?? [],
            'taxonomies' => Taxonomy::where('is_active', true)->orderBy('name')->get()->groupBy('kind')]);
    }

    public function read(Request $request, string $id)
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return back();
    }

    public function preferences(Request $request)
    {
        $request->merge(['email' => $request->boolean('email'), 'watch' => $request->boolean('watch'),
            'categories' => $request->input('categories', []), 'regions' => $request->input('regions', [])]);
        $data = $request->validate(['email' => 'boolean', 'watch' => 'boolean',
            'min_priority' => ['required', Rule::in(array_keys(Issue::PRIORITIES))],
            'categories' => 'array', 'regions' => 'array',
            'categories.*' => ['integer', Rule::exists('taxonomies', 'id')->where('kind', 'category')],
            'regions.*' => ['integer', Rule::exists('taxonomies', 'id')->where('kind', 'region')]]);
        $request->user()->forceFill(['notification_preferences' => $data])->save();

        return back()->with('status', 'Preferensi notifikasi disimpan.');
    }
}
