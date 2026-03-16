<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $domains = Domain::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('kode', 'like', "%{$q}%")
                        ->orWhere('nama_domain', 'like', "%{$q}%")
                        ->orWhere('nama_aspek', 'like', "%{$q}%")
                        ->orWhere('nama_indikator', 'like', "%{$q}%");
                });
            })
            ->orderBy('kode')
            ->latest()
            ->paginate(10)
            ->appends(['q' => $q]);

        return view('admin.domains.index', compact('domains', 'q'));
    }

    public function create()
    {
        return view('admin.domains.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode'          => ['required','integer','min:1','max:999999'],
            'nama_domain'   => ['required','string','max:250'],
            'nama_aspek'    => ['required','string','max:250'],
            'nama_indikator'=> ['required','string','max:250'],
        ]);

        Domain::create($validated);

        return redirect()->route('domains.index')->with('success', 'Domain berhasil ditambahkan.');
    }

    public function edit(Domain $domain)
    {
        return view('admin.domains.edit', compact('domain'));
    }

    public function update(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'kode'          => ['required','integer','min:1','max:999999'],
            'nama_domain'   => ['required','string','max:250'],
            'nama_aspek'    => ['required','string','max:250'],
            'nama_indikator'=> ['required','string','max:250'],
        ]);

        $domain->update($validated);

        return redirect()->route('domains.index')->with('success', 'Domain berhasil diupdate.');
    }

    public function destroy(Domain $domain)
    {
        $domain->delete();
        return back()->with('success', 'Domain berhasil dihapus.');
    }
}
