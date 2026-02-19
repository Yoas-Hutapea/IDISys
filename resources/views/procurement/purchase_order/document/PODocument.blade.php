@php
    $header = $header ?? [];
    $details = $details ?? [];
    $vendor = $vendor ?? [];
    $company = $company ?? [];
    $logoBase64 = $logoBase64 ?? null;
    $qrCodeDataUri = $qrCodeDataUri ?? null;
    $terbilang = $terbilang ?? '-';

    $formatNumber = function ($value) {
        if ($value === null || $value === '') {
            return '0';
        }
        if (!is_numeric($value)) {
            return (string) $value;
        }
        return number_format((float) $value, 0, '.', ',');
    };

    $formatHeaderDate = function ($value, string $format = 'd-F-Y') {
        if (!$value) {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return null;
        }
    };
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width" />
    <title>Purchase Order - {{ $header['PurchOrderID'] ?? 'PO' }}</title>
    <style>
        /* Font & ukuran mengikuti referensi COMPAS: Courier New 14px untuk isi, 18px/20px untuk judul PO */
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 12px 16px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: black;
            line-height: 1.35;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }
        table table {
            table-layout: auto;
        }

        td, th {
            padding: 0;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            min-width: 0;
        }

        thead { display: table-header-group; }
        tfoot { display: table-row-group; }
        tr { page-break-inside: avoid; }

        /* Utility */
        .no-border { border: none !important; }
        .no-border-keep-b { border-left: none !important; border-right: none !important; border-top: none !important; border-bottom: 1px solid #333 !important; }
        .no-b-r { border-right: none !important; }
        .b-t { border-top: 1px solid #333 !important; }
        .b-b { border-bottom: 1px solid #333 !important; }
        .b-l { border-left: 1px solid #333 !important; }
        .b-r { border-right: 1px solid #333 !important; }
        .b-all { border: 1px solid #333 !important; }

        .txt-l { text-align: left; }
        .txt-c { text-align: center; }
        .txt-r { text-align: right; }
        .txt-bold { font-weight: bold; }
        .txt-underline { text-decoration: underline; }
        .valign-mid { vertical-align: middle; }
        .valign-top { vertical-align: top; }

        .pad { padding: 6px 8px !important; }
        .pad-sm { padding: 4px 6px !important; }

        /* Document sections */
        .po-header {
            margin-bottom: 8px;
        }
        .po-header-row {
            width: 100%;
            border: 0;
            border-collapse: collapse;
        }
        .po-header-row td {
            vertical-align: middle;
            padding: 0 4px;
        }
        .po-header-row .po-header-left {
            width: 25%;
            text-align: left;
        }
        .po-header-row .po-header-center {
            width: 50%;
            text-align: center;
        }
        .po-header-row .po-header-right {
            width: 25%;
        }
        .po-logo img {
            max-width: 140px;
            max-height: 70px;
            object-fit: contain;
            display: block;
        }
        .po-title {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 0 0 2px 0;
        }
        .po-number {
            font-family: 'Courier New', Courier, monospace;
            font-size: 14pt;
            font-weight: normal;
            margin: 0;
        }
        .po-meta {
            font-family: 'Courier New', Courier, monospace;
            text-align: left;
            margin-top: 6px;
            margin-bottom: 10px;
            font-size: 10pt;
        }
        .po-meta strong { font-weight: bold; }

        .po-info-table {
            width: 100%;
            margin-bottom: 4px;
            table-layout: fixed;
        }
        /* Baris definisi kolom: dipakai DomPDF untuk lebar kolom 1-6; disembunyikan di tampilan */
        .po-info-table tr.po-info-col-def td {
            padding: 0 !important;
            border: none !important;
            height: 0 !important;
            max-height: 0 !important;
            overflow: hidden;
        }
        .po-info-table td {
            padding: 4px 6px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 8pt;
            vertical-align: middle;
        }
        .po-info-table .po-to-label { font-size: 10pt; font-weight: normal; }
        .po-info-table .po-company-name { font-size: 10pt; font-weight: bold; }
        .po-info-table .po-company-address { font-size: 8pt; }
        /* Lebar kolom label / : / value; label Vendor/dll 8pt no bold */
        .po-info-table .label {
            font-size: 8pt;
            font-weight: normal;
            white-space: nowrap;
            text-align: left;
        }
        .po-info-table .label-w {
            width: 12%;
        }
        .po-info-table .label-w-r {
            width: 16%;
        }
        .po-info-table .po-colon {
            width: 2%;
            min-width: 14px;
            text-align: center;
            padding-left: 2px;
            padding-right: 2px;
        }
        .po-info-table .value {
            text-align: left;
            word-break: break-word;
        }
        .po-info-table .value-w {
            width: 38%;
        }
        .po-info-table .value-w-r {
            width: 32%;
        }

        .po-section-title {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            font-size: 10pt;
            padding: 10px 0 6px 0;
        }

        .po-items-table {
            width: 100%;
            margin: 8px 0;
        }
        .po-items-table th {
            padding: 4px 6px;
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            font-size: 9pt;
            border-bottom: 1px solid #333;
            background: #f5f5f5;
        }
        .po-items-table td {
            padding: 4px 6px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 8pt;
            border-bottom: 1px solid #ddd;
        }
        .po-items-table .col-no { width: 4%; text-align: center; }
        .po-items-table .col-desc { width: 38%; }
        .po-items-table .col-qty { width: 8%; text-align: right; }
        .po-items-table .col-uom { width: 8%; text-align: left; }
        .po-items-table .col-price { width: 15%; text-align: right; }
        .po-items-table .col-total { width: 15%; text-align: right; }

        .po-notes-block {
            margin: 12px 0;
            padding: 8px 0;
        }
        .po-notes-block .notes-label {
            white-space: nowrap;
            min-width: 52px;
            width: 7%;
            vertical-align: top;
            font-size: 8pt;
            font-weight: bold;
        }
        .po-notes-block .grand-total-label { font-size: 9pt; font-weight: bold; }
        .po-notes-block .grand-total-value { font-size: 11pt; font-weight: normal; }
        .po-notes-block .term-label { font-size: 9pt; font-weight: bold; text-decoration: underline; }
        .po-notes-block .terbilang-value { text-transform: uppercase; font-size: 9pt; }
        .po-notes-block .note-num {
            white-space: nowrap;
            min-width: 18px;
            width: 2%;
        }
        .po-notes-block .note-item {
            margin-bottom: 4px;
        }
        .po-grand-total {
            font-weight: bold;
            padding: 8px 0 4px 0;
            border-top: 1px solid #333;
            margin-top: 4px;
        }
        .po-terbilang, .po-top {
            margin-top: 6px;
            padding: 4px 0;
        }
        .po-terbilang .label, .po-top .label {
            font-weight: bold;
            text-decoration: underline;
        }

        .po-footer-table {
            width: 100%;
            margin-top: 12px;
        }
        .po-footer-table td {
            padding: 6px 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            vertical-align: middle;
        }
        .po-npwp-label { font-size: 8pt; font-weight: bold; }
        .po-npwp-box {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7pt;
            font-weight: normal;
            border: 1px solid #333;
            padding: 8px;
            min-height: 80px;
        }
        .po-npwp-box .po-npwp-company { font-size: 8pt; font-weight: bold; }
        .po-npwp-box .po-npwp-address { font-size: 7pt; font-weight: normal; }
        .po-npwp-box .po-npwp-number { font-size: 8pt; font-weight: bold; }
        .po-qr-box {
            text-align: center;
        }
        .po-qr-box img {
            width: 120px;
            height: 120px;
            margin-top: 10px;
        }
        .po-disclaimer {
            font-family: 'Courier New', Courier, monospace;
            font-style: italic;
            font-size: 10px;
            color: black;
            margin-top: 4px;
        }

        /* Print */
        @media print {
            body {
                padding: 8px 12px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .po-items-table th {
                background: #e8e8e8 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            tr { page-break-inside: avoid; }
            .po-footer-table { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    {{-- Header: Logo (kiri), Title + PO Number (tengah), satu baris middle align. Place/Date di bawah. --}}
    <div class="po-header">
        <table class="po-header-row" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="po-header-left">
                    @if (!empty($logoBase64))
                        <div class="po-logo">
                            <img src="{{ $logoBase64 }}" alt="Logo" />
                        </div>
                    @endif
                </td>
                <td class="po-header-center">
                    <div class="po-title">PURCHASE ORDER</div>
                    <div class="po-number">{{ $header['PurchOrderID'] ?? '-' }}</div>
                </td>
                <td class="po-header-right"></td>
            </tr>
        </table>
        @php
            $approvedDateDisplay = $formatHeaderDate($header['POApprovedDate'] ?? null, 'd-F-Y') ?? now()->format('d-F-Y');
        @endphp
        </br>
        <div class="po-meta"><strong>Place/Date :</strong> Jakarta, {{ $approvedDateDisplay }}</div>
    </div>

    {{-- Baris pertama: definisi lebar kolom 1-6 untuk DomPDF (label kiri 10%, : 2%, value kiri 38%, label kanan 16%, : 2%, value kanan 32%). Ubah width di sini agar PDF ikut. --}}
    <table class="po-info-table" border="0" cellspacing="0" cellpadding="0">
        <tr class="po-info-col-def" style="font-size:0; line-height:0; height:0; max-height:0;">
            <td style="width:12%; padding:0; border:none; font-size:0; line-height:0;"></td>
            <td style="width:2%; padding:0; border:none; font-size:0; line-height:0;"></td>
            <td style="width:34%; padding:0; border:none; font-size:0; line-height:0;"></td>
            <td style="width:14%; padding:0; border:none; font-size:0; line-height:0;"></td>
            <td style="width:2%; padding:0; border:none; font-size:0; line-height:0;"></td>
            <td style="width:34%; padding:0; border:none; font-size:0; line-height:0;"></td>
        </tr>
        <tr>
            <td class="label-w b-t b-l pad-sm po-to-label">TO</td>
            <td class="po-colon b-t pad-sm txt-c">:</td>
            <td class="value-w b-t b-r pad-sm"></td>
            <td class="b-t b-l b-r pad-sm po-company-name" colspan="3">{{ $company['CompanyName'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label label-w b-l pad-sm">Vendor Code</td>
            <td class="po-colon pad-sm txt-c">:</td>
            <td class="value-w b-r pad-sm value">{{ $vendor['VendorID'] ?? '-' }}</td>
            <td class="b-l b-r pad-sm value po-company-address" rowspan="2" colspan="3">
                {{ $company['CompanyAddress'] ?? '' }}<br/>
                {{ $company['CompanyAddress1'] ?? '' }}<br/>
                {{ $company['CompanyAddress2'] ?? '' }}
            </td>
        </tr>
        <tr>
            <td class="label label-w b-l pad-sm">Vendor Name</td>
            <td class="po-colon pad-sm txt-c">:</td>
            <td class="value-w b-r pad-sm value">{{ $vendor['VendorName'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label label-w b-l pad-sm">Address</td>
            <td class="po-colon pad-sm txt-c">:</td>
            <td class="value-w b-r pad-sm value">{{ $vendor['VendorAddress'] ?? '-' }}</td>
            <td class="label label-w-r b-l no-b-r pad-sm">Phone/Fax</td>
            <td class="po-colon no-border pad-sm txt-c">:</td>
            <td class="value-w-r b-r pad-sm value">{{ $company['PhoneNumber'] ?? '-' }} / {{ $company['Fax'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label label-w b-l pad-sm">Contract No.</td>
            <td class="po-colon pad-sm txt-c">:</td>
            <td class="value-w b-r pad-sm value">{{ $header['ContractNumber'] ?? '-' }}</td>
            <td class="label label-w-r b-l no-b-r pad-sm">PO Creator</td>
            <td class="po-colon no-border pad-sm txt-c">:</td>
            <td class="value-w-r b-r pad-sm value">{{ $header['POCreator'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label label-w b-l pad-sm">Attn</td>
            <td class="po-colon pad-sm txt-c">:</td>
            <td class="value-w b-r pad-sm value">{{ $vendor['ContactPerson'] ?? '-' }}</td>
            <td class="label label-w-r b-l no-b-r pad-sm">Email</td>
            <td class="po-colon no-border pad-sm txt-c">:</td>
            <td class="value-w-r b-r pad-sm value">{{ $header['POCreatorEmail'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label label-w b-l pad-sm">Phone/Mobile</td>
            <td class="po-colon pad-sm txt-c">:</td>
            <td class="value-w b-r pad-sm value">{{ $vendor['PhoneNumber'] ?? '-' }} / {{ $vendor['ContactPersonPhoneNumber'] ?? '-' }}</td>
            <td class="label label-w-r b-l no-b-r pad-sm">Requestor</td>
            <td class="po-colon no-border pad-sm txt-c">:</td>
            <td class="value-w-r b-r pad-sm value">{{ $header['RequestorPR'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label label-w b-l pad-sm">Fax</td>
            <td class="po-colon pad-sm txt-c">:</td>
            <td class="value-w b-r pad-sm value">{{ $vendor['FaxNumber'] ?? '-' }}</td>
            <td class="label label-w-r b-l no-b-r pad-sm">PR Number</td>
            <td class="po-colon no-border pad-sm txt-c">:</td>
            <td class="value-w-r b-r pad-sm value">{{ $header['PRNumber'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label label-w b-l pad-sm">Email</td>
            <td class="po-colon pad-sm txt-c">:</td>
            <td class="value-w b-r pad-sm value">{{ $vendor['EmailCorrespondence'] ?? '-' }}</td>
            <td class="label label-w-r b-l no-b-r pad-sm">Validity Date</td>
            <td class="po-colon no-border pad-sm txt-c">:</td>
            <td class="value-w-r b-r pad-sm value">{{ $header['StrValidityDate'] ?? $header['StrDateLock'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="b-l b-r pad-sm" colspan="3"></td>
            <td class="label label-w-r b-l no-b-r pad-sm">Periode</td>
            <td class="po-colon no-border pad-sm txt-c">:</td>
            <td class="value-w-r b-r pad-sm value">@if(!empty($header['ContractPeriod'])){{ $header['ContractPeriod'] }}@elseif(!empty($header['StrStartDate']) || !empty($header['StrEndDate'])){{ ($header['StrStartDate'] ?? '-') . ' s/d ' . ($header['StrEndDate'] ?? '-') }}@else-@endif</td>
        </tr>
        <tr>
            <td class="b-l b-r pad-sm" colspan="3"></td>
            <td class="label label-w-r b-l no-b-r pad-sm">SO Number</td>
            <td class="po-colon no-border pad-sm txt-c">:</td>
            <td class="value-w-r b-r pad-sm value">{{ $header['SONumber'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="b-l b-r b-b pad-sm" colspan="3"></td>
            <td class="label label-w-r b-l no-b-r b-b pad-sm">Delivery<br/>Address</td>
            <td class="po-colon no-border-keep-b pad-sm txt-c">:</td>
            <td class="value-w-r b-r b-b pad-sm value">{{ $company['DeliveryAddress'] ?? '-' }}</td>
        </tr>
    </table>

    {{-- Subject --}}
    <div class="po-section-title">SUBJECT: {{ $header['PurchOrderName'] ?? '-' }}</div>

    {{-- Items table --}}
    <table class="po-items-table" border="0" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th class="col-no">No.</th>
                <th class="col-desc">Item Description</th>
                <th class="col-qty">Qty</th>
                <th class="col-uom">UoM</th>
                <th class="col-price">Unit Price</th>
                <th class="col-total">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $counter = 1; @endphp
            @foreach ($details as $item)
                <tr>
                    <td class="col-no txt-c">{{ $counter }}</td>
                    <td class="col-desc">{{ $item['SubItemName'] ?? '-' }}</td>
                    <td class="col-qty txt-r">{{ $formatNumber($item['Quantity'] ?? 0) }}</td>
                    <td class="col-uom">{{ $item['ItemUnit'] ?? '-' }}</td>
                    <td class="col-price txt-r">{{ $formatNumber($item['Price'] ?? 0) }}</td>
                    <td class="col-total txt-r">{{ $formatNumber($item['TotalAmount'] ?? 0) }}</td>
                </tr>
                @php $counter++; @endphp
            @endforeach
        </tbody>
    </table>

    {{-- Notes, Grand Total, Term of Payment, Terbilang --}}
    <div class="po-notes-block">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="pad-sm txt-bold notes-label">Note*:</td>
                <td class="pad-sm txt-c note-num">1.</td>
                <td class="pad-sm" style="width: 36%;">Total Harga Di Atas Belum Termasuk Pajak Pertambahan Nilai</td>
                <td style="width: 10%;"></td>
                <td class="pad-sm b-t grand-total-label" style="width: 15%;">Grand Total*</td>
                <td class="pad-sm b-t" style="width: 2%;">:</td>
                <td class="pad-sm txt-r b-t grand-total-value" style="width: 20%;">{{ $formatNumber($header['TotalAmountPO'] ?? 0) }}</td>
            </tr>
            <tr>
                <td class="pad-sm"></td>
                <td class="pad-sm txt-c note-num">2.</td>
                <td class="pad-sm">Invoice Harus Melampirkan Salinan NPWP</td>
                <td colspan="4"></td>
            </tr>
            <tr>
                <td class="pad-sm term-label" colspan="3">Term of Payment :</td>
                <td></td>
                <td class="pad-sm term-label" colspan="3">Says/Terbilang :</td>
            </tr>
            <tr>
                <td class="pad-sm" colspan="3">{{ $header['TOPRemarks'] ?? '-' }}</td>
                <td></td>
                <td class="pad-sm terbilang-value" colspan="3">{{ $terbilang ?? '-' }}</td>
            </tr>
        </table>
    </div>

    {{-- Footer: NPWP + QR + Disclaimer --}}
    <table class="po-footer-table" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width: 45%;">
                <div class="po-npwp-label pad-sm b-b">NPWP Address</div>
                <div class="po-npwp-box">
                    <span class="po-npwp-company">{{ $company['CompanyName'] ?? '' }}</span><br/><br/>
                    <span class="po-npwp-address">{{ $company['NPWPAddress'] ?? '' }}<br/>
                    {{ $company['NPWPAddress1'] ?? '' }}<br/>
                    {{ $company['NPWPAddress2'] ?? '' }}</span><br/><br/>
                    <span class="po-npwp-number">NPWP : {{ $company['NPWP'] ?? '-' }}</span>
                </div>
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 20%;" class="po-qr-box">
                @if (!empty($qrCodeDataUri))
                    <img src="{{ $qrCodeDataUri }}" alt="QR Code" />
                @else
                    <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" />
                @endif
            </td>
            <td style="width: 25%;" class="pad">
                <span class="po-disclaimer">*Purchase Order ini dicetak secara elektronik sehingga tidak memerlukan tanda tangan</span>
            </td>
        </tr>
    </table>
</body>
</html>
