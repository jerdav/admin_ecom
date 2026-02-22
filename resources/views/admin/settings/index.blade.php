@extends('admin.layouts.app', [
    'title' => 'Parametres',
    'subtitle' => 'Configuration de la boutique et activation des modules.',
])

@section('content')
    @php
        $tabs = [
            'boutique' => 'Boutique',
            'metier' => 'Metier',
            'modules' => 'Modules',
            'transporteurs' => 'Transporteurs',
        ];
    @endphp

    <nav class="settings-tabs">
        @foreach($tabs as $tabCode => $tabLabel)
            <a href="{{ route('admin.settings', ['tab' => $tabCode]) }}" class="settings-tab {{ $activeTab === $tabCode ? 'active' : '' }}">
                {{ $tabLabel }}
            </a>
        @endforeach
    </nav>

    @if($activeTab === 'boutique')
        <form method="POST" action="{{ route('admin.settings.update', ['tab' => 'boutique']) }}" class="panel form-grid form-grid-gap-12">
            @csrf
            <input type="hidden" name="section" value="boutique">

            <h3 class="section-title">Boutique</h3>
            <div class="form-grid form-grid-2">
                <label>
                    <span class="field-label">Nom boutique</span>
                    <input type="text" name="shop_name" value="{{ old('shop_name', $form['shop_name']) }}" required class="field-input">
                </label>
                <label>
                    <span class="field-label">Devise (ISO)</span>
                    <input type="text" name="shop_currency" value="{{ old('shop_currency', $form['shop_currency']) }}" maxlength="3" required class="field-input">
                </label>
            </div>
            <div>
                <button type="submit" class="logout logout-inline">Enregistrer</button>
            </div>
        </form>
    @endif

    @if($activeTab === 'metier')
        <form method="POST" action="{{ route('admin.settings.update', ['tab' => 'metier']) }}" class="panel form-grid form-grid-gap-12">
            @csrf
            <input type="hidden" name="section" value="metier">

            <h3 class="section-title">Reglages metier</h3>
            <div class="form-grid form-grid-2">
                <label>
                    <span class="field-label">Taux TVA par defaut (%)</span>
                    <input type="number" min="0" max="100" name="tax_default_rate" value="{{ old('tax_default_rate', $form['tax_default_rate']) }}" required class="field-input">
                </label>

                <label>
                    <span class="field-label">Role utilisateur par defaut</span>
                    <select name="users_default_role" class="field-select">
                        <option value="customer" {{ old('users_default_role', $form['users_default_role']) === 'customer' ? 'selected' : '' }}>Client</option>
                        <option value="admin" {{ old('users_default_role', $form['users_default_role']) === 'admin' ? 'selected' : '' }}>Administrateur</option>
                    </select>
                </label>
            </div>

            <div class="form-grid form-grid-3-eq">
                <label class="checkbox-inline">
                    <input type="hidden" name="orders_auto_confirm" value="0">
                    <input type="checkbox" name="orders_auto_confirm" value="1" {{ old('orders_auto_confirm', $form['orders_auto_confirm']) ? 'checked' : '' }}>
                    Confirmation automatique commande
                </label>
                <label class="checkbox-inline">
                    <input type="hidden" name="mail_order_notifications" value="0">
                    <input type="checkbox" name="mail_order_notifications" value="1" {{ old('mail_order_notifications', $form['mail_order_notifications']) ? 'checked' : '' }}>
                    Emails de commande
                </label>
                <label class="checkbox-inline">
                    <input type="hidden" name="users_allow_secondary_users" value="0">
                    <input type="checkbox" name="users_allow_secondary_users" value="1" {{ old('users_allow_secondary_users', $form['users_allow_secondary_users']) ? 'checked' : '' }}>
                    Autoriser plusieurs administrateurs
                </label>
            </div>

            <div>
                <button type="submit" class="logout logout-inline">Enregistrer</button>
            </div>
        </form>
    @endif

    @if($activeTab === 'modules')
        <form method="POST" action="{{ route('admin.settings.update', ['tab' => 'modules']) }}" class="panel form-grid form-grid-gap-12">
            @csrf
            <input type="hidden" name="section" value="modules">

            <h3 class="section-title">Modules activables</h3>
            <div class="form-grid form-grid-2">
                @foreach($flags as $flag)
                    <label class="module-flag">
                        <span>
                            <span class="field-strong">{{ $flag['label'] }}</span>
                            <span class="field-mono">{{ $flag['code'] }}</span>
                        </span>
                        <span>
                            <input type="hidden" name="feature_flags[{{ $flag['code'] }}]" value="0">
                            <input type="checkbox" name="feature_flags[{{ $flag['code'] }}]" value="1" {{ old('feature_flags.'.$flag['code'], $flag['enabled']) ? 'checked' : '' }}>
                        </span>
                    </label>
                @endforeach
            </div>

            <div>
                <button type="submit" class="logout logout-inline">Enregistrer</button>
            </div>
        </form>
    @endif

    @if($activeTab === 'transporteurs')
        <section class="panel">
            <h3>Transporteurs</h3>
            <p class="provider-help">
                Mode d'emploi: modifiez les champs d'une ligne puis cliquez sur <strong>Maj</strong> pour enregistrer ce transporteur.
                Le bouton <strong>Suppr</strong> supprime la ligne. Le tarif et le seuil sont en euros.
            </p>

            <div class="provider-list">
                @forelse($shippingProviders as $provider)
                    <div class="form-grid-provider-row">
                        <form method="POST" action="{{ route('admin.shipping-providers.update', ['provider' => $provider, 'tab' => 'transporteurs']) }}">
                            @csrf
                            <div class="form-grid form-grid-provider">
                                <label>
                                    <span class="field-label-sm">Nom</span>
                                    <input type="text" name="name" value="{{ $provider->name }}" required class="field-input-sm">
                                    <span class="field-spacer">.</span>
                                </label>
                                <label>
                                    <span class="field-label-sm">Code</span>
                                    <input type="text" name="code" value="{{ $provider->code }}" required class="field-input-sm">
                                    <span class="field-hint">Ex: colissimo, mondial_relay</span>
                                </label>
                                <label>
                                    <span class="field-label-sm">Tarif fixe (€)</span>
                                    <input type="number" min="0" step="0.01" name="flat_rate_eur" value="{{ number_format($provider->flat_rate_cents / 100, 2, '.', '') }}" required class="field-input-sm">
                                    <span class="field-spacer">.</span>
                                </label>
                                <label>
                                    <span class="field-label-sm">Seuil livraison gratuite (€)</span>
                                    <input type="number" min="0" step="0.01" name="free_shipping_threshold_eur" value="{{ $provider->free_shipping_threshold_cents !== null ? number_format($provider->free_shipping_threshold_cents / 100, 2, '.', '') : '' }}" placeholder="ex: 100.00" class="field-input-sm">
                                    <span class="field-spacer">.</span>
                                </label>
                                <div>
                                    <span class="field-label-sm text-transparent">Etat</span>
                                    <label class="checkbox-inline-sm min-h-40">
                                        <input type="hidden" name="enabled" value="0">
                                        <input type="checkbox" name="enabled" value="1" {{ $provider->enabled ? 'checked' : '' }}>
                                        Actif
                                    </label>
                                    <span class="field-spacer">.</span>
                                </div>
                                <div>
                                    <span class="field-label-sm text-transparent">Action</span>
                                    <button type="submit" class="logout logout-inline-sm min-h-40">Maj</button>
                                    <span class="field-spacer">.</span>
                                </div>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.shipping-providers.destroy', ['provider' => $provider, 'tab' => 'transporteurs']) }}" class="provider-delete-form">
                            @csrf
                            <span class="field-label-sm text-transparent">Action</span>
                            <button type="submit" class="logout logout-inline-sm min-h-40 logout-danger">Suppr</button>
                            <span class="field-spacer">.</span>
                        </form>
                    </div>
                @empty
                    <p class="text-muted-md">Aucun transporteur configure.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('admin.shipping-providers.store', ['tab' => 'transporteurs']) }}" class="provider-card">
                @csrf
                <p class="provider-add-title">Ajouter un transporteur</p>
                <div class="form-grid form-grid-provider-new">
                    <label>
                        <span class="field-label-sm">Nom</span>
                        <input type="text" name="name" required class="field-input-sm">
                        <span class="field-spacer">.</span>
                    </label>
                    <label>
                        <span class="field-label-sm">Code</span>
                        <input type="text" name="code" required class="field-input-sm">
                        <span class="field-hint">Ex: colissimo, mondial_relay</span>
                    </label>
                    <label>
                        <span class="field-label-sm">Tarif fixe (€)</span>
                        <input type="number" min="0" step="0.01" name="flat_rate_eur" value="0.00" required class="field-input-sm">
                        <span class="field-spacer">.</span>
                    </label>
                    <label>
                        <span class="field-label-sm">Seuil livraison gratuite (€)</span>
                        <input type="number" min="0" step="0.01" name="free_shipping_threshold_eur" placeholder="ex: 100.00" class="field-input-sm">
                        <span class="field-spacer">.</span>
                    </label>
                    <div>
                        <span class="field-label-sm text-transparent">Etat</span>
                        <label class="checkbox-inline-sm min-h-40">
                            <input type="hidden" name="enabled" value="0">
                            <input type="checkbox" name="enabled" value="1" checked>
                            Actif
                        </label>
                        <span class="field-spacer">.</span>
                    </div>
                </div>
                <button type="submit" class="logout logout-inline-sm mt-10">Ajouter le transporteur</button>
            </form>
        </section>
    @endif
@endsection
