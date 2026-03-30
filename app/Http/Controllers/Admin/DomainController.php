<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Indikator;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $domains = Indikator::query()
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

        Indikator::create($validated);

        return redirect()->route('domains.index')->with('success', 'Domain berhasil ditambahkan.');
    }

    public function edit(Indikator $domain)
    {
        return view('admin.domains.edit', compact('domain'));
    }

    public function update(Request $request, Indikator $domain)
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

    public function destroy(Indikator $domain)
    {
        try {
            $domain->delete();
            return back()->with('success', 'Domain berhasil dihapus.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '23000') {
                return back()->with('failed', 'Domain/Indikator ini tidak dapat dihapus karena sedang digunakan (berelasi) dengan data lainnya.');
            }
            return back()->with('failed', 'Gagal menghapus domain: ' . $e->getMessage());
        }
    }
}
