@extends('layouts.app')

@section('title', 'Purchases')

@section('content')
<div class="top">
    <div>
        <h1>Supplier & Purchasing</h1>
        <div class="muted">
            Manage supplier records and accepted supplier deliveries
        </div>
    </div>
    <div class="who">Owner</div>
</div>

<div class="tabs">
    <a href="{{ route('suppliers.index') }}">Suppliers</a>
    <a class="active" href="{{ route('purchases.index') }}">Purchases</a>
</div>

<div class="toolbar">
    <a class="btn primary" href="{{ route('purchases.create') }}">
        + Record Purchase
    </a>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Date / Time</th>
                    <th>Supplier</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Recorded By</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                    <tr>
                        <td><strong>PUR-{{ str_pad($purchase->purchase_id, 4, '0', STR_PAD_LEFT) }}</strong></td>
                        <td>{{ $purchase->purchase_date->format('m/d/Y g:i A') }}</td>
                        <td>{{ $purchase->supplier?->supplier_name ?? '—' }}</td>
                        <td>{{ $purchase->items->count() }}</td>
                        <td>₱{{ number_format((float) $purchase->total_amount, 2) }}</td>
                        <td>{{ $purchase->user?->username ?? '—' }}</td>
                        <td>{{ $purchase->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted" style="text-align:center;padding:30px;">No purchase records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
