@auth
    @if (in_array(auth()->user()->role, ['admin', 'petugas_lapangan'], true))
        <a @class(['nav-item', 'active' => request()->routeIs('operational.dashboard')]) href="{{ route('operational.dashboard') }}">
            <i class="fa-solid fa-gauge-high w-5 text-center"></i>
            Dashboard {{ auth()->user()->role === 'admin' ? 'Admin' : 'Petugas' }}
        </a>
    @endif
@endauth
