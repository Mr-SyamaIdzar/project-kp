@extends('layouts.admin')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
  <div>
    <div class="font-bold text-lg md:text-xl">Data User</div>
    <div class="text-(--muted) text-xs md:text-sm mt-0.5">Kelola user role Admin, OPD &amp; BPS</div>
  </div>
  <a href="{{ route('users.create') }}" class="inline-flex items-center gap-2 px-3 md:px-4 py-2 bg-(--brand) text-white rounded-xl hover:opacity-90 font-medium transition-opacity text-xs md:text-sm shrink-0">
    <i class="bi bi-person-plus"></i> Tambah User
  </a>
</div>

<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-4 mb-6">
  <form method="GET" action="{{ route('users.index') }}" class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
    <div class="flex-1 w-full">
      <div class="relative flex items-center w-full">
        <span class="absolute left-4 text-(--muted)"><i class="bi bi-search"></i></span>
        <input type="text" name="q" value="{{ $q ?? request('q') }}"
               class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) placeholder-(--muted) rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all text-xs md:text-sm"
               placeholder="Cari username / nama / role (admin, opd, bps)...">
      </div>
      <p class="text-(--muted) text-[10px] mt-1 ml-1">Contoh: <b>opd</b>, <b>bps</b>, <b>admin1</b>, atau nama OPD.</p>
    </div>
    <div class="flex gap-2 shrink-0">
      <button class="px-3 py-2 bg-transparent border border-(--border-strong) text-(--text) rounded-xl hover:bg-white/5 transition-colors flex items-center gap-2 text-xs md:text-sm" type="submit">
        <i class="bi bi-search"></i> Cari
      </button>
      @if(!empty($q))
        <a href="{{ route('users.index') }}" class="px-3 py-2 bg-transparent border border-orange-500/50 text-orange-500 rounded-xl hover:bg-orange-500/10 transition-colors flex items-center gap-2 text-xs md:text-sm">
          <i class="bi bi-x-circle"></i> Reset
        </a>
      @endif
    </div>
  </form>
</div>

<div class="overflow-x-auto bg-(--panel) border border-(--border-strong) rounded-2xl">
  <table class="w-full text-(--text) border-collapse min-w-[640px]">
    <thead>
      <tr class="border-b border-(--border-strong) bg-black/5 text-left text-xs md:text-sm font-semibold text-(--muted)">
        <th class="p-4 w-16">No</th>
        <th class="p-4 w-48">Username</th>
        <th class="p-4">Nama OPD</th>
        <th class="p-4 w-32">Role</th>
        <th class="p-4 w-52 text-center">Aksi</th>
      </tr>
    </thead>

    <tbody class="divide-y divide-(--border-strong) text-xs md:text-sm">
      @forelse($users as $u)
        <tr class="hover:bg-black/5 transition-colors">
          <td class="p-4">{{ ($users->currentPage()-1)*$users->perPage() + $loop->iteration }}</td>
          <td class="p-4 font-semibold text-(--text)">{{ $u->username }}</td>
          <td class="p-4">{{ $u->nama }}</td>

          <td class="p-4">
            @if($u->role === 'opd')
              <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-blue-500/20 border border-blue-500/40 text-blue-600 dark:text-blue-300">
                OPD
              </span>
            @elseif($u->role === 'bps')
              <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-emerald-500/20 border border-emerald-500/40 text-emerald-600 dark:text-emerald-300">
                BPS
              </span>
            @else
              <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-slate-500 text-white border border-slate-600">
                ADMIN
              </span>
            @endif
          </td>

          <td class="p-4 text-center">
            <div class="flex items-center justify-center gap-2">
              <a href="{{ route('users.edit', $u->id) }}"
                 class="px-3 py-1.5 bg-transparent border border-cyan-500/50 text-cyan-500 hover:bg-cyan-500 hover:text-white rounded-xl transition-colors inline-flex items-center gap-2 text-xs md:text-sm">
                <i class="bi bi-pencil-square"></i> Edit
              </a>

              <form action="{{ route('users.destroy', $u->id) }}"
                    method="POST"
                    class="form-delete-user inline-block"
                    data-username="{{ $u->username }}"
                    @if($u->id === auth()->id()) data-disabled="1" @endif>
                @csrf
                @method('DELETE')

                <button type="submit"
                        class="px-2 md:px-3 py-1 md:py-1.5 bg-transparent border border-red-500/50 text-red-500 hover:bg-red-500 hover:text-white rounded-xl transition-colors inline-flex items-center gap-2 text-xs md:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        {{ $u->id === auth()->id() ? 'disabled' : '' }}>
                  <i class="bi bi-trash"></i> Hapus
                </button>
              </form>
            </div>

            @if($u->id === auth()->id())
              <div class="text-[10px] md:text-xs text-amber-500 mt-2 flex items-center justify-center gap-1">
                <i class="bi bi-shield-lock-fill"></i> Akun sedang login
              </div>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="p-8 text-center text-(--muted)">
            @if(!empty($q))
              Tidak ada hasil untuk pencarian: <b>{{ $q }}</b>
            @else
              Belum ada data user.
            @endif
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
  <div class="text-(--muted) text-xs md:text-sm">
    @if($users->total() > 0)
      Menampilkan {{ $users->firstItem() }}–{{ $users->lastItem() }} dari {{ $users->total() }} data
      @if(!empty($q))
        <span class="ml-1">(filter: <b>{{ $q }}</b>)</span>
      @endif
    @else
      Menampilkan 0 data
    @endif
  </div>

  <div class="pagination-wrap">
    {{ $users->onEachSide(1)->links() }}
  </div>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.form-delete-user').forEach(form => {
      form.addEventListener('submit', (e) => {
        const disabled = form.getAttribute('data-disabled') === '1';
        if(disabled){
          e.preventDefault();
          showToast('Tidak bisa menghapus akun yang sedang login.', 'warning');
          return;
        }

        const username = form.getAttribute('data-username') || 'user';
        showToast(`Konfirmasi: hapus user "${username}"?`, 'warning');

        const ok = confirm(`Yakin hapus user "${username}"?`);
        if(!ok) e.preventDefault();
      });
    });
  });
</script>
@endpush
@endsection
