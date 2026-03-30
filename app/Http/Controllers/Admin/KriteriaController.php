<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Indikator;
use App\Models\Kriteria;
use Illuminate\Http\Request;

class KriteriaController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $kriterias = Kriteria::query()
            ->with('domain')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('tingkat', 'like', "%{$q}%")
                        ->orWhere('kriteria', 'like', "%{$q}%")
                        ->orWhereHas('domain', function ($d) use ($q) {
                            $d->where('nama_domain', 'like', "%{$q}%")
                              ->orWhere('nama_indikator', 'like', "%{$q}%");
                        });
                });
            })
            ->orderBy('domain_id')
            ->orderBy('tingkat')
            ->orderBy('id')
            ->paginate(10)
            ->appends(['q' => $q]);

        return view('admin.kriterias.index', compact('kriterias', 'q'));
    }

    public function create()
    {
        // dropdown indikator dari domains
        $domains = Indikator::orderBy('kode')->get();
        return view('admin.kriterias.create', compact('domains'));
    }

    // multi-insert: items[]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required','array','min:1'],
            'items.*.domain_id' => ['required','integer','exists:domains,id'],
            'items.*.tingkat'   => ['required','integer','min:1','max:100'],
            'items.*.kriteria'  => ['required','string','max:500'],
        ]);

        foreach ($validated['items'] as $i => $row) {

            // 🔒 CEK DUPLIKASI DOMAIN + TINGKAT
            $exists = Kriteria::where('domain_id', $row['domain_id'])
                ->where('tingkat', $row['tingkat'])
                ->exists();

            if ($exists) {
                return back()
                    ->withErrors([
                        "items.$i.tingkat" => "Domain dan tingkat tersebut sudah ada."
                    ])
                    ->withInput();
            }

            Kriteria::create($row);
        }

        return redirect()
            ->route('kriterias.index')
            ->with('success', 'Kriteria berhasil ditambahkan.');
    }


    public function edit(Kriteria $kriteria)
    {
        $domains = Indikator::orderBy('kode')->get();
        return view('admin.kriterias.edit', compact('kriteria', 'domains'));
    }

    public function update(Request $request, Kriteria $kriteria)
    {
        $validated = $request->validate([
            'domain_id' => ['required','integer','exists:domains,id'],
            'tingkat'   => ['required','integer','min:1','max:100'],
            'kriteria'  => ['required','string','max:500'],
        ]);

        $exists = Kriteria::where('domain_id', $validated['domain_id'])
            ->where('tingkat', $validated['tingkat'])
            ->where('id', '!=', $kriteria->id)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'tingkat' => 'Domain dan tingkat tersebut sudah ada.'
                ])
                ->withInput();
        }

        $kriteria->update($validated);

        return redirect()
            ->route('kriterias.index')
            ->with('success', 'Kriteria berhasil diupdate.');
    }


    public function destroy(Kriteria $kriteria)
    {
        try {
            $kriteria->delete();
            return back()->with('success', 'Kriteria berhasil dihapus.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '23000') {
                return back()->with('failed', 'Kriteria ini tidak dapat dihapus karena sedang digunakan (berelasi) dengan data lainnya.');
            }
            return back()->with('failed', 'Gagal menghapus kriteria: ' . $e->getMessage());
        }
    }
}
