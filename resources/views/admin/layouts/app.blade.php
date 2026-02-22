<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($title ?? 'Administration') . ' - EcomAdmin' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Fraunces:opsz,wght@9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>
<body>
<main class="shell">
    <aside class="sidebar">
        <div>
            <h1 class="brand">EcomAdmin</h1>
            <p class="brand-sub">Back-office générique</p>
        </div>

        <nav class="menu">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <iconify-icon icon="heroicons:squares-2x2-solid" class="nav-icon"></iconify-icon>
                Tableau de bord
            </a>
            <a href="{{ route('admin.orders') }}" class="{{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                <iconify-icon icon="heroicons:shopping-bag-solid" class="nav-icon"></iconify-icon>
                Commandes
            </a>
            <a href="{{ route('admin.categories') }}" class="{{ request()->routeIs('admin.categories*') ? 'active' : '' }}">
                <iconify-icon icon="heroicons:folder-solid" class="nav-icon"></iconify-icon>
                Catégories
            </a>
            <a href="{{ route('admin.products') }}" class="{{ request()->routeIs('admin.products*') ? 'active' : '' }}">
                <iconify-icon icon="heroicons:cube-solid" class="nav-icon"></iconify-icon>
                Produits
            </a>
            <a href="{{ route('admin.settings') }}" class="{{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <iconify-icon icon="heroicons:cog-6-tooth-solid" class="nav-icon"></iconify-icon>
                Paramètres
            </a>
            <a href="{{ route('admin.audit') }}" class="{{ request()->routeIs('admin.audit') ? 'active' : '' }}">
                <iconify-icon icon="heroicons:clipboard-document-list-solid" class="nav-icon"></iconify-icon>
                Audit
            </a>
        </nav>

        <div class="side-bottom">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout">
                    <iconify-icon icon="heroicons:arrow-left-on-rectangle-solid"></iconify-icon>
                    Déconnexion
                </button>
            </form>
        </div>
    </aside>

    <section class="content">
        <header class="header">
            <h2 class="title">{{ $title ?? 'Administration' }}</h2>
            @if (!empty($subtitle))
                <p class="subtitle">{{ $subtitle }}</p>
            @endif
        </header>

        @if (session('success'))
            <div class="alert-success">
                <iconify-icon icon="heroicons:check-circle-solid"></iconify-icon>
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-error">
                <iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon>
                {{ $errors->first() }}
            </div>
        @endif

        @yield('content')
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@2.1.0/dist/iconify-icon.min.js"></script>
<script src="{{ asset('js/admin.js') }}" defer></script>
@stack('scripts')
</body>
</html>
