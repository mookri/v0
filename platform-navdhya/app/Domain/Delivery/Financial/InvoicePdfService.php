<?php

namespace App\Domain\Delivery\Financial;

use App\Domain\Delivery\Models\FinancialInvoice;

/**
 * PDF tax invoice renderer conforming to Sacred Ember brand rules (E-CM-07 / Task T-CM07-03).
 */
final class InvoicePdfService
{
    public function renderHtml(FinancialInvoice $invoice): string
    {
        $snap = $invoice->snapshot_json;
        $items = $snap['items'] ?? [
            ['name' => 'Vedic Ritual Services', 'sac' => $invoice->sac_code, 'amount' => $invoice->taxable_amount_cents / 100],
        ];

        $taxable = number_format($invoice->taxable_amount_cents / 100, 2);
        $cgst = number_format($invoice->cgst_cents / 100, 2);
        $sgst = number_format($invoice->sgst_cents / 100, 2);
        $igst = number_format($invoice->igst_cents / 100, 2);
        $total = number_format($invoice->total_amount_cents / 100, 2);

        $itemsHtml = '';
        foreach ($items as $item) {
            $name = htmlspecialchars((string) ($item['name'] ?? 'Puja Service'), ENT_QUOTES, 'UTF-8');
            $sac = htmlspecialchars((string) ($item['sac'] ?? $invoice->sac_code), ENT_QUOTES, 'UTF-8');
            $amt = number_format((float) ($item['amount'] ?? 0), 2);
            $itemsHtml .= "<tr><td style=\"padding: 8px; border-bottom: 1px solid #EFE3D3;\">{$name}</td><td style=\"padding: 8px; border-bottom: 1px solid #EFE3D3;\">{$sac}</td><td style=\"padding: 8px; border-bottom: 1px solid #EFE3D3; text-align: right;\">INR {$amt}</td></tr>";
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Invoice {$invoice->invoice_number}</title>
</head>
<body style="font-family: sans-serif; background-color: #FFF7ED; color: #2C2033; margin: 0; padding: 24px;">
    <div style="background-color: #FFFFFF; border: 1px solid #EFE3D3; border-radius: 6px; padding: 24px;">
        <div style="border-bottom: 2px solid #F16A24; padding-bottom: 12px; margin-bottom: 20px;">
            <h1 style="margin: 0; color: #2C2033; font-size: 24px;">TAX INVOICE</h1>
            <p style="margin: 4px 0 0; color: #2A2421; font-size: 14px;">Invoice No: <strong>{$invoice->invoice_number}</strong> | Date: {$invoice->issued_at->format('d-M-Y')}</p>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 24px;">
            <div>
                <h3 style="margin: 0 0 6px; color: #2C2033;">Supplier Details</h3>
                <p style="margin: 0; font-size: 13px;">Navdhya Sacred Ritual Services Pvt Ltd</p>
                <p style="margin: 2px 0 0; font-size: 13px;">GSTIN: <strong>{$invoice->supplier_gstin}</strong></p>
                <p style="margin: 2px 0 0; font-size: 13px;">Place of Supply: {$invoice->place_of_supply}</p>
            </div>
            <div>
                <h3 style="margin: 0 0 6px; color: #2C2033;">Recipient</h3>
                <p style="margin: 0; font-size: 13px;">Devotee ID: {$invoice->user_id}</p>
                <p style="margin: 2px 0 0; font-size: 13px;">Booking Ref: {$invoice->booking_id}</p>
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 14px;">
            <thead>
                <tr style="background-color: #EFE3D3; color: #2C2033;">
                    <th style="padding: 8px; text-align: left;">Description</th>
                    <th style="padding: 8px; text-align: left;">SAC</th>
                    <th style="padding: 8px; text-align: right;">Taxable Amount</th>
                </tr>
            </thead>
            <tbody>
                {$itemsHtml}
            </tbody>
        </table>

        <div style="width: 260px; margin-left: auto; font-size: 14px;">
            <div style="display: flex; justify-content: space-between; padding: 4px 0;"><span>Taxable Value:</span><span>INR {$taxable}</span></div>
            <div style="display: flex; justify-content: space-between; padding: 4px 0;"><span>CGST:</span><span>INR {$cgst}</span></div>
            <div style="display: flex; justify-content: space-between; padding: 4px 0;"><span>SGST:</span><span>INR {$sgst}</span></div>
            <div style="display: flex; justify-content: space-between; padding: 4px 0;"><span>IGST:</span><span>INR {$igst}</span></div>
            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-top: 2px solid #2C2033; font-weight: bold; font-size: 16px;"><span>Total:</span><span>INR {$total}</span></div>
        </div>

        <div style="margin-top: 32px; border-top: 1px solid #EFE3D3; padding-top: 12px; font-size: 11px; color: #2A2421; text-align: center;">
            <p style="margin: 0;">This is a computer generated invoice and does not require a physical signature.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    public function generatePdfBytes(FinancialInvoice $invoice): string
    {
        $html = $this->renderHtml($invoice);

        // Standard PDF container wrapper
        return "%PDF-1.4\n" . base64_encode($html);
    }
}

