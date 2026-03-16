<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tahun;
use Illuminate\Http\Request;

class DeadlineController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');

        $items = Tahun::query()
            ->when($q, fn($query) => $query->where('tahun', 'like', "%{$q}%"))
            ->orderByDesc('tahun')
            ->paginate(10)
            ->withQueryString();

        return view('admin.deadlines.index', compact('items','q'));
    }

    public function update(Request $request, Tahun $tahun)
    {
        $validated = $request->validate([
            'deadline_submit' => ['nullable','date'],
        ]);

        $tahun->deadline_submit = $validated['deadline_submit'] ?? null;
        $tahun->save();

        return back()->with('success', 'Deadline berhasil disimpan.');
    }

    // ✅ TAMBAHAN: Hapus deadline khusus
    public function destroy(Tahun $tahun)
    {
        $tahun->deadline_submit = null;
        $tahun->save();

        return back()->with('success', 'Deadline berhasil dihapus.');
    }
}
