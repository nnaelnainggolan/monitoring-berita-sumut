<?php

namespace App\Http\Controllers;

use App\Models\Taxonomy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaxonomyController extends Controller
{
    public function index()
    {
        return view('admin.taxonomies', ['items' => Taxonomy::orderBy('kind')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(Taxonomy::KINDS))],
            'name' => ['required', 'string', 'max:150', Rule::unique('taxonomies')->where('kind', $request->input('kind'))],
        ]);
        Taxonomy::create($data);
        return back()->with('status', 'Data klasifikasi ditambahkan.');
    }

    public function update(Request $request, Taxonomy $taxonomy)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('taxonomies')->where('kind', $taxonomy->kind)->ignore($taxonomy->id)],
            'is_active' => 'required|boolean',
        ]);
        $taxonomy->update($data);
        return back()->with('status', 'Data klasifikasi diperbarui.');
    }
}
