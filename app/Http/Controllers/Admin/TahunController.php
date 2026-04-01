<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tahun;
use Illuminate\Http\Request;

class TahunController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $items = Tahun::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('tahun', 'like', "%{$q}%");
            })
            ->orderByDesc('tahun')
            ->paginate(10)
            ->appends(['q' => $q]);

        return view('admin.tahuns.index', compact('items', 'q'));
    }

    public function create()
    {
        return view('admin.tahuns.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tahun' => ['required', 'digits:4', 'integer', 'min:1901', 'max:2155', 'unique:tahun,tahun'],
        ]);

        Tahun::create($validated);

        return redirect()->route('tahun.index')->with('success', 'Tahun berhasil ditambahkan.');
    }

    public function edit(Tahun $tahun)
    {
        return view('admin.tahuns.edit', compact('tahun'));
    }

    public function update(Request $request, Tahun $tahun)
    {
        $validated = $request->validate([
            'tahun' => ['required', 'digits:4', 'integer', 'min:1901', 'max:2155', 'unique:tahun,tahun,' . $tahun->id],
        ]);

        $tahun->update($validated);

        return redirect()->route('tahun.index')->with('success', 'Tahun berhasil diupdate.');
    }

    public function destroy(Tahun $tahun)
    {
        try {
            $tahun->delete();
            return back()->with('success', 'Tahun berhasil dihapus.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '23000') {
                return back()->with('failed', 'Tahun ini tidak dapat dihapus karena sedang digunakan (berelasi) dengan data lainnya.');
            }
            return back()->with('failed', 'Gagal menghapus tahun: ' . $e->getMessage());
        }
    }
}
