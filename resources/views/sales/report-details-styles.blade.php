<style>
.report-transaction { color: #0f172a; font-size: 13px; }
.report-transaction h3 { margin: 0 0 18px; font-size: 18px; }
.report-details-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin: 0 0 22px; }
.report-details-meta dt { color: #64748b; font-size: 12px; font-weight: 700; margin-bottom: 5px; }
.report-details-meta dd { margin: 0; overflow-wrap: anywhere; white-space: pre-wrap; }
.report-details-address { grid-column: 1 / -1; }
.report-details-items { overflow-x: auto; }
.report-items-table { width: 100%; border-collapse: collapse; text-align: left; }
.report-items-table th, .report-items-table td { padding: 11px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; overflow-wrap: anywhere; }
.report-items-table th { background: #eef2f8; font-size: 11px; }
.report-details-totals { margin: 20px 0 0 auto; width: min(100%, 360px); }
.report-details-totals div { display: flex; justify-content: space-between; gap: 16px; margin-top: 10px; }
.report-details-totals dd { margin: 0; font-weight: 700; }
.report-modal-overlay { display: none; position: fixed; inset: 0; z-index: 10000; align-items: center; justify-content: center; padding: 24px; background: rgba(15, 23, 42, .5); }
.report-modal-overlay.show { display: flex; }
.report-modal { width: min(850px, 100%); max-height: 90vh; display: flex; flex-direction: column; background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(15, 23, 42, .2); }
.report-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 22px 24px; border-bottom: 1px solid #edf1f6; }
.report-modal-header h2 { font-size: 23px; margin: 0; }
.report-modal-body { overflow-y: auto; padding: 24px; }
.report-modal-footer { padding: 16px 24px; border-top: 1px solid #edf1f6; display: flex; justify-content: flex-end; }
@media (max-width: 600px) {
    .report-modal-overlay { padding: 12px; }
    .report-modal-header, .report-modal-body, .report-modal-footer { padding: 16px; }
    .report-details-meta { grid-template-columns: 1fr; }
}
@media print {
    .report-items-table thead { display: table-header-group; }
    .report-items-table tr, .report-details-meta div, .report-details-totals { break-inside: avoid; }
    .report-details-items { overflow: visible; }
    .report-transaction h3 { break-after: avoid; }
    .report-modal-overlay { display: none !important; }
}
</style>
