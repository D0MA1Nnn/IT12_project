<article class="report-transaction">
    <h3>{{ $isPurchaseReport ? 'PUR-' : 'SALE-' }}{{ str_pad($record->getKey(), 4, '0', STR_PAD_LEFT) }}</h3>
    <dl class="report-details-meta">
        <div><dt>Date / Time</dt><dd>{{ ($isPurchaseReport ? $record->purchase_date : $record->sale_date)->format('m/d/Y g:i A') }}</dd></div>
        <div><dt>Recorded By</dt><dd>{{ $record->user?->username ?? '—' }}</dd></div>
        <div><dt>Status</dt><dd>{{ $record->status }}</dd></div>
        @if($isPurchaseReport)
            <div><dt>Supplier</dt><dd>{{ $record->supplier?->supplier_name ?? '—' }}</dd></div>
            <div><dt>Contact Person</dt><dd>{{ $record->supplier?->contact_person ?? '—' }}</dd></div>
            <div><dt>Phone</dt><dd>{{ $record->supplier?->contact_number ?? '—' }}</dd></div>
            <div><dt>Email</dt><dd>{{ $record->supplier?->email ?? '—' }}</dd></div>
            <div class="report-details-address"><dt>Supplier Address</dt><dd>{{ $record->supplier?->address ?? '—' }}</dd></div>
        @else
            <div><dt>Payment Option</dt><dd>{{ $record->payment_method === 'COD' ? 'Cash on Delivery (COD)' : 'Pay now' }}</dd></div>
            <div><dt>Delivery</dt><dd>{{ $record->delivery_required ? 'Required' : 'Walk-in' }}</dd></div>
            @if($record->delivery_required)
                <div><dt>Delivery Status</dt><dd>{{ $record->delivery_status }}</dd></div>
                <div><dt>Customer Name</dt><dd>{{ $record->customer_name ?? '—' }}</dd></div>
                <div><dt>Contact Number</dt><dd>{{ $record->customer_contact_number ?? '—' }}</dd></div>
                <div class="report-details-address"><dt>Delivery Address</dt><dd>{{ $record->delivery_address ?? '—' }}</dd></div>
            @endif
        @endif
    </dl>
    <div class="report-details-items">
        <table class="report-items-table">
            <thead><tr><th>Product</th><th>Unit</th><th>Quantity</th><th>{{ $isPurchaseReport ? 'Purchase Cost / Unit' : 'Selling Price / Unit' }}</th><th>Subtotal</th></tr></thead>
            <tbody>
                @forelse($record->items as $item)
                    <tr>
                        <td>{{ $item->productUnit?->product?->product_name ?? 'Product' }}</td>
                        <td>{{ $isPurchaseReport ? ($item->productUnit?->unit?->unit_name ?? 'Unit') : $item->sellingUnitName() }}</td>
                        <td>{{ rtrim(rtrim(number_format($isPurchaseReport ? (float) $item->quantity : $item->sellingQuantity(), 8, '.', ''), '0'), '.') }}</td>
                        <td>₱{{ number_format($isPurchaseReport ? (float) $item->unit_cost : $item->sellingUnitPrice(), 2) }}</td>
                        <td>₱{{ number_format((float) $item->subtotal, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No items found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <dl class="report-details-totals">
        @unless($isPurchaseReport)
            @if($record->delivery_required)
                <div><dt>Delivery Fee</dt><dd>₱{{ number_format((float) $record->delivery_fee, 2) }}</dd></div>
            @endif
        @endunless
        <div><dt>{{ $isPurchaseReport ? 'Purchase Total' : 'Sale Total' }}</dt><dd>₱{{ number_format((float) $record->total_amount, 2) }}</dd></div>
        @unless($isPurchaseReport)
            @if($record->payment_status === 'PAID')
                <div><dt>Payment Received</dt><dd>₱{{ number_format($record->receivedPayment(), 2) }}</dd></div>
                <div><dt>Change</dt><dd>₱{{ number_format($record->paymentChange(), 2) }}</dd></div>
            @else
                <div><dt>Amount Due{{ $record->delivery_status === 'PENDING' ? ' on Delivery' : '' }}</dt><dd>₱{{ number_format($record->amountDue(), 2) }}</dd></div>
            @endif
        @endunless
    </dl>
</article>
