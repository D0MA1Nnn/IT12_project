<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report — {{ $isPurchaseReport ? 'Purchases List' : 'Sales List' }}</title>
    @include('sales.report-details-styles')
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 28px; font-family: Arial, sans-serif; color: #0f172a; background: #f1f5f9; }
        .report-print-document { max-width: 1000px; margin: auto; padding: 30px; background: #fff; }
        .report-print-heading { border-bottom: 2px solid #0f172a; padding-bottom: 18px; margin-bottom: 20px; }
        .report-print-heading h1 { font-size: 24px; margin: 8px 0; }
        .report-print-heading p { font-size: 12px; margin: 6px 0; overflow-wrap: anywhere; }
        .report-print-summary { display: flex; gap: 24px; flex-wrap: wrap; margin-bottom: 26px; font-size: 13px; }
        .report-print-summary span { display: block; margin-bottom: 5px; }
        .report-print-record { padding: 24px 0; border-top: 1px solid #cbd5e1; }
        @page { size: A4; margin: 14mm; }
        @media print {
            body { background: #fff; padding: 0; }
            .report-print-document { max-width: none; padding: 0; }
            .report-print-heading, .report-print-summary { break-inside: avoid; }
        }
    </style>
</head>
<body data-report-print-document>
    <main class="report-print-document">
        <header class="report-print-heading">
            <strong>SENADOR COCO — Lumber &amp; Construction Supplies</strong>
            <h1>Report — {{ $isPurchaseReport ? 'Purchases List' : 'Sales List' }}</h1>
            <p>Generated {{ now()->format('M d, Y g:i A') }} · Prepared by {{ auth()->user()->username }}</p>
            <p>Date range: {{ $filters['from'] ?? 'the beginning' }} to {{ $filters['to'] ?? 'today' }}</p>
            @if(!empty($filters['q']))
                <p>Search: {{ $filters['q'] }}</p>
            @endif
            <p>Full details for all matching completed {{ $isPurchaseReport ? 'purchases' : 'sales' }}.</p>
        </header>
        <div class="report-print-summary">
            <div><span>{{ $isPurchaseReport ? 'Completed Purchases' : 'Completed Sales' }}</span><strong>{{ $totalRecords }}</strong></div>
            <div><span>{{ $isPurchaseReport ? 'Total Purchase Amount' : 'Total Sales Amount' }}</span><strong>₱{{ number_format($totalAmount, 2) }}</strong></div>
            <div><span>{{ $isPurchaseReport ? 'Average Purchase' : 'Average Sale' }}</span><strong>₱{{ number_format($averageAmount, 2) }}</strong></div>
            @unless($isPurchaseReport)
                <div><span>Walk-in</span><strong>{{ $walkInSales }}</strong></div>
                <div><span>Delivery</span><strong>{{ $deliverySales }}</strong></div>
            @endunless
        </div>
        @forelse($records as $record)
            <section class="report-print-record">
                @include('sales.report-details', ['record' => $record, 'isPurchaseReport' => $isPurchaseReport])
            </section>
        @empty
            <p>No {{ $isPurchaseReport ? 'purchases' : 'sales' }} found.</p>
        @endforelse
    </main>
</body>
</html>
