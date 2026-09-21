@extends('layouts.app')

@section('content')
<div class="import-log-page">
    <div class="import-log-header">
        <h1>API-importer</h1>
        <p>En rad per kund och körning.</p>
    </div>

    @if (session('status'))
        <div class="import-log-message import-log-message-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="import-log-message import-log-message-error">{{ session('error') }}</div>
    @endif
    <div id="import-loading-message" class="import-log-message import-log-message-loading" hidden aria-live="polite">
        API-anropet körs. Data hämtas, vänta tills importen är klar.
    </div>

    <section class="import-log-actions" aria-labelledby="import-actions-title">
        <div>
            <h2 id="import-actions-title">Hämta senaste data</h2>
            <p>Starta en ny import från API:et för en butik eller alla butiker.</p>
        </div>
        <div class="import-log-action-forms">
            <form method="POST" action="{{ route('import-logs.import-tenant') }}" class="import-request-form">
                @csrf
                <label for="import-tenant-id">
                    Butik
                    <select name="tenant_id" id="import-tenant-id" required>
                        <option value="">-- Välj en butik --</option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}">{{ $tenant->name }} ({{ $tenant->store_code }})</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit">Hämta vald butik</button>
            </form>

            <form method="POST" action="{{ route('import-logs.import-all') }}" class="import-request-form">
                @csrf
                <button type="submit">Hämta alla butiker</button>
            </form>
        </div>
    </section>

    <section class="import-log-new-tenant" aria-labelledby="new-tenant-title">
        <h2 id="new-tenant-title">Importera ny kund</h2>
        <p>Verifiera API-endpointen innan kunden sparas.</p>
        <form method="POST" action="{{ route('import-logs.import-new-tenant') }}" class="import-log-new-tenant-form import-request-form">
            @csrf
            <label for="tenant-endpoint">
                API-endpoint
                <input type="text" id="tenant-endpoint" name="tenant_endpoint" value="{{ old('tenant_endpoint') }}" required maxlength="255">
            </label>
            <label for="store-code">
                Butikskod
                <input type="text" id="store-code" name="store_code" value="{{ old('store_code') }}" required maxlength="255">
            </label>
            <label for="tenant-name">
                Kundnamn
                <input type="text" id="tenant-name" name="name" value="{{ old('name') }}" maxlength="255">
            </label>
            <button type="submit">Importera ny kund</button>
        </form>
        @error('tenant_endpoint') <div class="import-log-field-error">{{ $message }}</div> @enderror
        @error('store_code') <div class="import-log-field-error">{{ $message }}</div> @enderror
        @error('name') <div class="import-log-field-error">{{ $message }}</div> @enderror
    </section>

    <form method="GET" action="{{ route('import-logs.index') }}" class="import-log-filters">
        <label for="tenant">
            Kundnamn
            <input type="search" id="tenant" name="tenant" value="{{ $filters['tenant'] }}" placeholder="Sök kundnamn">
        </label>
        <label for="from">
            Från
            <input type="datetime-local" id="from" name="from" value="{{ $filters['from'] }}">
        </label>
        <label for="to">
            Till
            <input type="datetime-local" id="to" name="to" value="{{ $filters['to'] }}">
        </label>
        <button type="submit">Sök</button>
    </form>

    <div class="import-log-table-wrap">
        <table class="import-log-table">
            <thead>
                <tr>
                    <th>Kund</th>
                    <th>Tidpunkt</th>
                    <th>Importerade poster</th>
                    <th>Status</th>
                    <th>Fel</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->tenant->name }}</td>
                        <td>{{ $log->started_at->format('Y-m-d H:i:s') }}</td>
                        <td>{{ number_format($log->imported_count, 0, ',', ' ') }}</td>
                        <td class="status-{{ $log->status }}">
                            @if ($log->status === 'success')
                                <div class="success-label">Klar</div>
                            @else
                                <div class="failed-label">Misslyckad</div>
                            @endif                    
                        </td>
                        <td>{{ $log->error }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Inga importer matchar sökningen.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="import-log-pagination">
        {{ $logs->links() }}
    </div>
</div>
@endsection