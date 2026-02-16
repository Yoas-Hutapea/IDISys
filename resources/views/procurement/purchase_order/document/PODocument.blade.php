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

<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width" />
    <title>Purchase Order</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }
        /* DomPDF: nested tables must not use display:block or min/max width fails */
        table table {
            table-layout: auto;
        }

        table, td {
            padding: 0px;
        }

        th {
            padding-bottom: 0px;
            padding-top: 0px;
        }

        p {
            padding: 2px;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
        }

        td, th {
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            hyphens: auto;
            overflow: visible;
            vertical-align: top;
            min-width: 0;
        }
        /* Pastikan konten panjang terlihat dan wrap, bukan terpotong */
        td span, td div, th span, th div {
            overflow: visible;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .bnone {
            border-style: none;
            border-top: 0px;
            border-left: 0px;
            border-right: 0px;
            border-bottom: 0px;
            border-color: white;
            border-collapse: collapse;
            border-spacing: 0;
        }

        .bBotBold {
            border-bottom-style: solid;
            border-top: 0px;
            border-left: 0px;
            border-right: 0px;
            border-bottom-width: 2px;
            border-bottom-color: black;
        }

        .bTopBold {
            border-top-style: solid;
            border-bottom: 0px;
            border-left: 0px;
            border-right: 0px;
            border-top-width: 2px;
            border-top-color: black;
        }

        .bLeftBold {
            border-left-style: solid;
            border-bottom: 0px;
            border-top: 0px;
            border-right: 0px;
            border-left-width: 2px;
            border-left-color: black;
        }

        .bLeftTopBold {
            border-left-style: solid;
            border-top-style: solid;
            border-bottom: 0px;
            border-right: 0px;
            border-left-width: 2px;
            border-left-color: black;
            border-top-width: 2px;
            border-top-color: black;
        }

        .bLeftBotBold {
            border-left-style: solid;
            border-bottom-style: solid;
            border-top: 0px;
            border-right: 0px;
            border-left-width: 2px;
            border-left-color: black;
            border-bottom-width: 2px;
            border-bottom-color: black;
        }

        .bLeftRightBold {
            border-left-style: solid;
            border-right-style: solid;
            border-bottom: 0px;
            border-top: 0px;
            border-left-width: 2px;
            border-left-color: black;
            border-right-width: 2px;
            border-right-color: black;
        }

        .bLeftRightBotBold {
            border-right-style: solid;
            border-left-style: solid;
            border-bottom-style: solid;
            border-right-width: 2px;
            border-right-color: black;
            border-left-width: 2px;
            border-left-color: black;
            border-bottom-width: 2px;
            border-bottom-color: black;
            border-top: 0px;
        }

        .bRightTopBold {
            border-right-style: solid;
            border-top-style: solid;
            border-bottom: 0px;
            border-left: 0px;
            border-right-width: 2px;
            border-right-color: black;
            border-top-width: 2px;
            border-top-color: black;
        }

        .bRightBold {
            border-right-style: solid;
            border-bottom: 0px;
            border-top: 0px;
            border-left: 0px;
            border-right-width: 2px;
            border-right-color: black;
        }

        .bAllBold {
            border-right-style: solid;
            border-left-style: solid;
            border-bottom-style: solid;
            border-top-style: solid;
            border-right-width: 2px;
            border-right-color: black;
            border-left-width: 2px;
            border-left-color: black;
            border-bottom-width: 2px;
            border-bottom-color: black;
            border-top-width: 2px;
            border-top-color: black;
        }

        .txtCenterMid {
            text-align: center;
            vertical-align: middle;
            font-family: 'Courier New';
            color: black;
            font-size: 14px;
        }

        .txtCenterBold {
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            font-family: 'Courier New';
            color: black;
            font-size: 14px;
        }

        .txtLeftBold {
            text-align: left;
            vertical-align: middle;
            font-weight: bold;
            font-family: 'Courier New';
            color: black;
            font-size: 14px;
        }

        .txtRightBold {
            text-align: right;
            vertical-align: middle;
            font-weight: bold;
            font-family: 'Courier New';
            color: black;
            font-size: 14px;
        }

        .txtCenterTop {
            text-align: center;
            vertical-align: top;
            font-family: 'Courier New';
            color: black;
            font-size: 14px;
        }

        .txtCenterTopBold {
            text-align: center;
            vertical-align: top;
            font-family: 'Courier New';
            color: black;
            font-weight: bold;
            font-size: 14px;
        }

        .txtRightMid {
            text-align: right;
            vertical-align: middle;
            font-family: 'Courier New';
            font-size: 14px;
        }

        .txtRightTop {
            text-align: right;
            vertical-align: top;
            font-family: 'Courier New';
            color: black;
            font-size: 14px;
        }

        .txtLeftMid {
            text-align: left;
            vertical-align: middle;
            font-family: 'Courier New';
            color: black;
            font-size: 14px;
        }

        .txtLeftTop {
            text-align: left;
            vertical-align: top;
            font-family: 'Courier New';
            color: black;
            font-size: 14px;
        }

        .txtLeftTopBold {
            text-align: left;
            vertical-align: top;
            font-family: 'Courier New';
            font-weight: bold;
            color: black;
            font-size: 14px;
        }

        .txtLeftTopBoldUnderline {
            text-align: left;
            vertical-align: top;
            font-family: 'Courier New';
            text-decoration: underline;
            font-weight: bold;
            color: black;
            font-size: 14px;
        }
        /* Kurangi tinggi blok vendor/company dan hindari konten terpotong */
        .cell-vendor-company {
            padding: 4px 6px !important;
        }
        .cell-vendor-company .wrap-text {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }
    </style>
</head>
<body>
    <div class="row">
        <div class="col-md-12">
            <table class="table" style="border-style:none">
                <thead style="border-style:none">
                    <tr class="bnone">
                        <td class="bnone" colspan="10">
                            <span>
                                @if (!empty($logoBase64))
                                    <img width="120" height="60" src="{{ $logoBase64 }}" />
                                @endif
                            </span>
                        </td>
                    </tr>
                    <tr class="txtCenterMid bnone" style="margin-top:0px; margin-bottom:0px; padding-top:0px;padding-bottom:0px">
                        <th class="bnone" colspan="10" style="font-weight: bold; font-size: 18px; text-decoration: underline; margin-top: 0px; margin-bottom: 0px; padding-top: 0px; padding-bottom: 0px; text-align: center; border-top: 0px; border-bottom: 0px">PURCHASE ORDER</th>
                    </tr>
                    <tr class="txtCenterMid bnone" style="margin-top:0px; margin-bottom:0px; padding-top:0px;padding-bottom:0px">
                        <td class="bnone" colspan="10" style="font-weight:bold; font-size: 20px; margin-top: 0px; margin-bottom: 0px; padding-top: 0px; padding-bottom: 0px; text-align: center; border-top: 0px; border-bottom: 0px">{{ $header['PurchOrderID'] ?? '-' }}</td>
                    </tr>
                    <tr class="bnone" style="border-top: 0px; border-bottom: 0px">
                        @php
                            $approvedDateDisplay = $formatHeaderDate($header['POApprovedDate'] ?? null, 'd-F-Y') ?? now()->format('d-F-Y');
                        @endphp
                        <td class="bnone" colspan="10" style="text-align: left; border-top: 0px; border-bottom: 0px"><span class="txtLeftBold">Place/Date    :    </span><span class="txtLeftMid">Jakarta, {{ $approvedDateDisplay }}</span></td>
                    </tr>
                    <tr class="bnone" style="border-top: 0px; border-bottom: 0px">
                        <td style="border-top: 0px; border-bottom: 0px" class="bBotBold" width="5%"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bBotBold" width="7%"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bBotBold" width="1%"></td>
                        <td class="bBotBold" width="28%" style="min-width: 230px; border-top: 0px; border-bottom: 0px"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bBotBold" width="2%"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bnone" width="1%"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bBotBold" width="20%"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bBotBold" width="1%"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bBotBold" width="17%"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bBotBold" width="18%"></td>
                    </tr>
                    <tr style="border-style: none; border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px">
                        <td class="txtLeftTop bLeftBold" style="border-top:2px; border-top-style:solid; border-bottom:0px">TO </td>
                        <td style="border-top: 2px; border-bottom: 0px; border-top-style: solid;" class="bnone"></td>
                        <td style="border-top: 2px; border-bottom: 0px; border-top-style:solid;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 2px; border-bottom: 0px; border-top-style:solid;" class="bnone"></td>
                        <td style="border-top: 2px; border-bottom: 0px; border-top-style:solid;" class="bRightBold"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bnone"></td>
                        <td style="border-top: 2px; border-top-style: solid; border-right: 2px; border-right-style: solid; border-bottom: 0px; " class="txtLeftTopBold bLeftRightBold" colspan="4">{{ $company['CompanyName'] ?? '-' }}</td>
                    </tr>
                    <tr class="bLeftRightBold" style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">Vendor Code</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">{{ $vendor['VendorID'] ?? '-' }}</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="4" rowspan="2"><span>{{ $company['CompanyAddress'] ?? '' }} <br /> {{ $company['CompanyAddress1'] ?? '' }} <br /> {{ $company['CompanyAddress2'] ?? '' }}</span></td>
                    </tr>
                    <tr class="bLeftRightBold" style="border-top: 0px; border-bottom: 0px">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">Vendor Name</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bRightBold" colspan="2">{{ $vendor['VendorName'] ?? '-' }}</td>
                    </tr>
                    <tr class="bLeftRightBold" style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">Address</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">{{ $vendor['VendorAddress'] ?? '-' }}</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 2px; border-bottom-style:solid; padding: 4px 6px; white-space: nowrap;" class="txtLeftTop bBotBold">Phone/Fax</td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style: solid; padding: 4px 6px;" class="txtCenterTop bBotBold">:</td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 2px; border-bottom-style: solid; padding: 4px 6px;" class="txtLeftTop bBotBold" colspan="2">{{ $company['PhoneNumber'] ?? '-' }} / {{ $company['Fax'] ?? '-' }}</td>
                    </tr>
                    <tr style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px" class="bLeftRightBold">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bnone" colspan="2"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bnone"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 0px; padding: 4px 6px; white-space: nowrap;" class="txtLeftTop bnone">PO Creator</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">{{ $header['POCreator'] ?? '-' }}</td>
                    </tr>
                    <tr class="bLeftRightBold" style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2"> Contract No.</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">{{ $header['ContractNumber'] ?? '-' }}</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 2px; border-bottom-style: solid; padding: 4px 6px; white-space: nowrap;" class="txtLeftTop bBotBold">Email</td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style: solid; padding: 4px 6px;" class="txtCenterTop bBotBold">:</td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 2px; border-bottom-style: solid; padding: 4px 6px;" class="txtLeftTop bBotBold" colspan="2">{{ $header['POCreatorEmail'] ?? '-' }}</td>
                    </tr>
                    <tr class="bLeftRightBold" style="border-right: 2px; border-right-style: solid;">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">Attn</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">{{ $vendor['ContactPerson'] ?? '-' }}</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 0px; padding: 4px 6px; white-space: nowrap;" class="txtLeftTop bnone">Requestor</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">{{ $header['RequestorPR'] ?? '-' }}</td>
                    </tr>
                    <tr style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px" class="bLeftRightBold">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">Phone/Mobile</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">{{ $vendor['PhoneNumber'] ?? '-' }} / {{ $vendor['ContactPersonPhoneNumber'] ?? '-' }}</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 0px; padding: 4px 6px; white-space: nowrap;" class="txtLeftTop bnone">PR Number</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">{{ $header['PRNumber'] ?? '-' }}</td>
                    </tr>

                    <tr style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px" class="bLeftRightBold">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">Fax</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">{{ $vendor['FaxNumber'] ?? '-' }}</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bBotBold"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bBotBold"></td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bBotBold" colspan="2"></td>
                    </tr>
                    <tr style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px" class="bLeftRightBold">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bBotBold" colspan="2">Email</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bBotBold">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bBotBold" colspan="2">{{ $vendor['EmailCorrespondence'] ?? '-' }}</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bRightBold"></td>
                        <td style="border-top: 2px; border-top-style:solid; border-left: 2px; border-left-style: solid; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone">Delivery Address</td>
                        <td style="border-top: 2px; border-top-style:solid; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 2px; border-top-style:solid; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone wrap-text" colspan="2">{{ $company['CompanyAddress'] ?? '' }}<br />{{ $company['CompanyAddress1'] ?? '' }}<br />{{ $company['CompanyAddress2'] ?? '' }}</td>
                    </tr>
                    <tr class="bLeftRightBold" style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px">
                        <td class="bRightBold" style="border-top: 0px; border-bottom: 0px; padding: 4px 6px; border-left: none;" colspan="2"></td>
                        <td class="bnone" style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;"></td>
                        <td class="bRightBold" style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" colspan="2"></td>
                        <td class="bLeftRightBold" style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;"></td>
                        <td class="txtLeftTop bnone" style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 0px; padding: 4px 6px; white-space: nowrap;">Validity Date</td>
                        <td class="txtCenterTop bnone" style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;">:</td>
                        <td class="txtLeftTop bnone" style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 4px 6px;" colspan="2">{{ $header['StrValidityDate'] ?? '-' }}</td>
                    </tr>
                    <tr style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px" class="bLeftRightBold">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px; border-left: none;" class="bRightBold" colspan="2"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bnone"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bRightBold" colspan="2"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 0px; padding: 4px 6px; white-space: nowrap;" class="txtLeftTop bnone">Periode</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">{{ $header['ContractPeriod'] ?? '-' }}</td>
                    </tr>
                    <tr style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px" class="bLeftRightBold">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px; border-left: none;" class="bnone" colspan="2"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bnone"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bnone" colspan="2"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px; white-space: nowrap;" class="txtLeftTop bLeftBold">SO Number</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 4px 6px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 4px 6px;" class="txtLeftTop bnone" colspan="2">{{ $header['SONumber'] ?? '-' }}</td>
                    </tr>
                    <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                        <td style="border-top: 2px; border-top-style:solid; border-bottom: 0px" class="bTopBold"></td>
                        <td style="border-top: 2px; border-top-style:solid; border-bottom: 0px" class="bTopBold"></td>
                        <td style="border-top: 2px; border-top-style:solid; border-bottom: 0px" class="bTopBold"></td>
                        <td style="border-top: 2px; border-top-style:solid; border-bottom: 0px" class="bTopBold"></td>
                        <td style="border-top: 2px; border-top-style:solid; border-bottom: 0px" class="bTopBold"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bnone"></td>
                        <td style="border-top: 2px; border-top-style:solid; border-bottom: 0px" class="bTopBold"></td>
                        <td style="border-top: 2px; border-top-style:solid; border-bottom: 0px" class="bTopBold"></td>
                        <td style="border-top: 2px; border-top-style:solid; border-bottom: 0px" class="bTopBold"></td>
                        <td style="border-top: 2px; border-top-style:solid; border-bottom: 0px" class="bTopBold"></td>
                    </tr>

                    <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 12px 0 8px 0;" class="bnone" colspan="10"></td>
                    </tr>
                    <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                        <th class="bnone txtLeftTopBold" style="font-family: Arial; font-weight: 100; border-top: 0px; border-bottom: 0px; white-space: nowrap;">SUBJECT: </th>
                        <th class="bnone txtLeftTopBold" colspan="9" style="font-family: Arial; font-weight: 100; border-top: 0px; border-bottom: 0px; white-space: nowrap;">{{ $header['PurchOrderName'] ?? '-' }}</th>
                    </tr>
                    <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 0;" class="bnone" colspan="10"></td>
                    </tr>
                </thead>
                <tbody style="border-top: 0px; border-bottom: 0px" class="txtCenterMid bnone">
                    <tr class="bBotBold" style="border-top: 0px; border-bottom: 0px">
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid" class="txtCenterBold bBotBold">No.</td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid" class="txtLeftBold bBotBold" colspan="3">Item Description</td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid; white-space: nowrap;" class="txtRightBold bBotBold">Qty</td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid" class="txtCenterBold bBotBold"></td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid; white-space: nowrap;" class="txtLeftBold bBotBold"> UoM</td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid; white-space: nowrap;" class="txtRightBold bBotBold" colspan="2">Unit Price (IDR)</td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid" class="txtRightBold bBotBold">Total (IDR)</td>
                    </tr>
                    @php $counter = 1; @endphp
                    @foreach ($details as $item)
                        <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                            <td style="border-top: 0px; border-bottom: 0px;" class="txtCenterTop bnone">{{ $counter }}</td>
                            <td style="border-top: 0px; border-bottom: 0px; word-break: break-all; vertical-align: top;" class="txtLeftTop bnone" colspan="3">{{ $item['SubItemName'] ?? '-' }}</td>
                            <td style="border-top: 0px; border-bottom: 0px;" class="txtRightTop bnone">{{ $formatNumber($item['Quantity'] ?? 0) }}</td>
                            <td style="border-top: 0px; border-bottom: 0px;" class="txtCenterTop bnone"></td>
                            <td style="border-top: 0px; border-bottom: 0px;" class="txtLeftTop bnone">{{ $item['ItemUnit'] ?? '-' }}</td>
                            <td style="border-top: 0px; border-bottom: 0px;" class="txtCenterTop bnone"></td>
                            <td style="border-top: 0px; border-bottom: 0px;" class="txtRightTop bnone">{{ $formatNumber($item['Price'] ?? 0) }}</td>
                            <td style="border-top: 0px; border-bottom: 0px;" class="txtRightTop bnone">{{ $formatNumber($item['TotalAmount'] ?? 0) }}</td>
                        </tr>
                        @php $counter++; @endphp
                    @endforeach
                    <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 12px 0 0 0;" class="bnone" colspan="10"></td>
                    </tr>
                    <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                        <td style="border-top: 0px; border-bottom: 0px" class="bnone" colspan="10">
                            <table style="border-top: 0px; border-bottom: 0px; width: 100%;" class="table bnone">
                                <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px; white-space: nowrap;" class="txtLeftTopBold bnone">Note*:</td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="txtCenterTopBold bnone">1.</td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="txtLeftTopBold bnone" colspan="3"><span>Total Harga Di Atas Belum <br />Termasuk Pajak Pertambahan Nilai</span></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone"></td>
                                    <td style="border-top: 2px; border-top-style: solid; border-bottom: 0px;" class="txtLeftTopBold bnone">Grand Total*</td>
                                    <td style="border-top: 2px; border-top-style: solid; border-bottom: 0px;" class="txtLeftTopBold bnone">:</td>
                                    <td style="border-top: 2px; border-top-style: solid; border-bottom: 0px;" class="txtRightTop bnone" colspan="2">{{ $formatNumber($header['TotalAmountPO'] ?? 0) }}</td>
                                </tr>
                                <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="txtLeftTop bnone"></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="txtCenterTopBold bnone">2.</td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="txtLeftTopBold bnone" colspan="3"><span>Invoice Harus Melampirkan <br />Salinan NPWP</span></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone"></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone"></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone"></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone"></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone"></td>
                                </tr>
                                <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                                    <td style="border-top: 0px; border-bottom: 0px; text-decoration:underline" class="txtLeftTopBoldUnderline bnone" colspan="5">Term of Payment :</td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bnone"></td>
                                    <td style="border-top: 0px; border-bottom: 0px; text-decoration:underline" class="txtLeftTopBoldUnderline bnone" colspan="4">Says/Terbilang :</td>
                                </tr>
                                <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="txtLeftTop bnone" colspan="5">{{ $header['TOPRemarks'] ?? '-' }}</td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone"></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="txtLeftTop bnone" colspan="4">{{ $terbilang ?? '-' }}</td>
                                </tr>
                                <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                                    <td style="border-top: 0px; border-bottom: 0px" class="bnone" width="5%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bnone" width="7%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bnone" width="1%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bnone" width="28%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bnone" width="9%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bnone" width="1%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bTopBold" width="16%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bTopBold" width="1%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bTopBold" width="15%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bTopBold" width="18%"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                        <td style="border-top: 0px; border-bottom: 0px" class="bnone" colspan="10">
                            <table style="border-top: 0px; border-bottom: 0px; width: 100%;" class="table bnone">
                                <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                                    <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid; padding: 6px 4px;" class="txtLeftTopBold bBotBold" colspan="5">NPWP Address</td>
                                    <td style="border-top: 0px; border-bottom: 2px; padding: 6px 4px;" class="bnone" colspan="5"></td>
                                </tr>
                                <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                                    <td style="border-top: 0px; border-bottom: 0px" class="bLeftTopBold" width="5%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bTopBold" width="7%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bTopBold" width="1%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bTopBold" width="28%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bRightBold" width="9%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone" width="1%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone" width="16%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone" width="1%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone" width="15%"></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone" width="18%"></td>
                                </tr>
                                <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                                    <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 6px 4px;" class="txtLeftTopBold bLeftRightBold" colspan="5"><span>{{ $company['CompanyName'] ?? '' }}<br /><br />{{ $company['NPWPAddress'] ?? '' }}<br />{{ $company['NPWPAddress1'] ?? '' }}<br />{{ $company['NPWPAddress2'] ?? '' }} <br /><br />NPWP : {{ $company['NPWP'] ?? '-' }}</span></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone"></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="txtLeftTop bnone" colspan="1">
                                        @if (!empty($qrCodeDataUri))
                                            <span><img style="width:100px; height:100px;" src="{{ $qrCodeDataUri }}" alt="QR Code"/></span>
                                        @else
                                            <span><img style="width:100px; height:100px;" src="{{ asset('assets/img/logo.png') }}" alt="QR Code"/></span>
                                        @endif
                                    </td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="txtLeftMid bnone" colspan="3"><span><i>*Purchase Order ini dicetak secara elektronik sehingga tidak memerlukan tanda tangan</i></span></td>
                                </tr>
                                <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                                    <td style="border-top: 0px; border-bottom: 2px; border-bottom-style: solid;" class="bnone bLeftRightBotBold" colspan="5"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bnone"></td>
                                    <td style="border-top: 0px; border-bottom: 0px" class="bnone" colspan="4"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
