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
            margin: 0;
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
        }

        .vendor-section {
            width: 35% !important;
        }

        .company-section {
            width: 50% !important;
        }

        .bnone {
            border-style: none !important;
            border-top: 0px !important;
            border-left: 0px !important;
            border-right: 0px !important;
            border-bottom: 0px !important;
            border-color: white;
        }

        .bBotBold {
            border-bottom-style: solid !important;
            border-top: 0px !important;
            border-left: 0px !important;
            border-right: 0px !important;
            border-bottom-width: 2px !important;
            border-bottom-color: black !important;
        }

        .bTopBold {
            border-top-style: solid !important;
            border-bottom: 0px !important;
            border-left: 0px !important;
            border-right: 0px !important;
            border-top-width: 2px !important;
            border-top-color: black !important;
        }

        .bLeftBold {
            border-left-style: solid !important;
            border-bottom: 0px !important;
            border-top: 0px !important;
            border-right: 0px !important;
            border-left-width: 2px !important;
            border-left-color: black !important;
        }

        .bLeftTopBold {
            border-left-style: solid !important;
            border-top-style: solid !important;
            border-bottom: 0px !important;
            border-right: 0px !important;
            border-left-width: 2px !important;
            border-left-color: black !important;
            border-top-width: 2px !important;
            border-top-color: black !important;
        }

        .bLeftBotBold {
            border-left-style: solid !important;
            border-bottom-style: solid !important;
            border-top: 0px !important;
            border-right: 0px !important;
            border-left-width: 2px !important;
            border-left-color: black !important;
            border-bottom-width: 2px !important;
            border-bottom-color: black !important;
        }

        .bLeftRightBold {
            border-left-style: solid !important;
            border-right-style: solid !important;
            border-bottom: 0px !important;
            border-top: 0px !important;
            border-left-width: 2px !important;
            border-left-color: black !important;
            border-right-width: 2px !important;
            border-right-color: black !important;
        }

        .bLeftRightBotBold {
            border-right-style: solid !important;
            border-left-style: solid !important;
            border-bottom-style: solid !important;
            border-right-width: 2px !important;
            border-right-color: black !important;
            border-left-width: 2px !important;
            border-left-color: black !important;
            border-bottom-width: 2px !important;
            border-bottom-color: black !important;
            border-top: 0px !important;
        }

        .bRightTopBold {
            border-right-style: solid !important;
            border-top-style: solid !important;
            border-bottom: 0px !important;
            border-left: 0px !important;
            border-right-width: 2px !important;
            border-right-color: black !important;
            border-top-width: 2px !important;
            border-top-color: black !important;
        }

        .bRightBold {
            border-right-style: solid !important;
            border-bottom: 0px !important;
            border-top: 0px !important;
            border-left: 0px !important;
            border-right-width: 2px !important;
            border-right-color: black !important;
        }

        .bAllBold {
            border-right-style: solid !important;
            border-left-style: solid !important;
            border-bottom-style: solid !important;
            border-top-style: solid !important;
            border-right-width: 2px !important;
            border-right-color: black !important;
            border-left-width: 2px !important;
            border-left-color: black !important;
            border-bottom-width: 2px !important;
            border-bottom-color: black !important;
            border-top-width: 2px !important;
            border-top-color: black !important;
        }

        .txtCenterMid {
            text-align: center;
            vertical-align: middle;
            font-family: 'Courier New';
            color: black;
            font-size: 11px;
        }

        .txtCenterBold {
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            font-family: 'Courier New';
            color: black;
            font-size: 11px;
        }

        .txtLeftBold {
            text-align: left;
            vertical-align: middle;
            font-weight: bold;
            font-family: 'Courier New';
            color: black;
            font-size: 11px;
        }

        .txtRightBold {
            text-align: right;
            vertical-align: middle;
            font-weight: bold;
            font-family: 'Courier New';
            color: black;
            font-size: 11px;
        }

        .txtCenterTop {
            text-align: center;
            vertical-align: top;
            font-family: 'Courier New';
            color: black;
            font-size: 11px;
        }

        .txtCenterTopBold {
            text-align: center;
            vertical-align: top;
            font-family: 'Courier New';
            color: black;
            font-weight: bold;
            font-size: 11px;
        }

        .txtRightMid {
            text-align: right;
            vertical-align: middle;
            font-family: 'Courier New';
            font-size: 11px;
        }

        .txtRightTop {
            text-align: right;
            vertical-align: top;
            font-family: 'Courier New';
            color: black;
            font-size: 11px;
        }

        .txtLeftMid {
            text-align: left;
            vertical-align: middle;
            font-family: 'Courier New';
            color: black;
            font-size: 11px;
        }

        .txtLeftTop {
            text-align: left;
            vertical-align: top;
            font-family: 'Courier New';
            color: black;
            font-size: 11px;
        }

        .txtLeftTopBold {
            text-align: left;
            vertical-align: top;
            font-family: 'Courier New';
            font-weight: bold;
            color: black;
            font-size: 11px;
        }

        .txtLeftTopBoldUnderline {
            text-align: left;
            vertical-align: top;
            font-family: 'Courier New';
            text-decoration: underline;
            font-weight: bold;
            color: black;
            font-size: 11px;
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
                        <th class="bnone" colspan="10" style="font-weight: bold; font-size: 14px; text-decoration: underline; margin-top: 0px; margin-bottom: 0px; padding-top: 0px; padding-bottom: 0px; text-align: center; border-top: 0px; border-bottom: 0px">PURCHASE ORDER</th>
                    </tr>
                    <tr class="txtCenterMid bnone" style="margin-top:0px; margin-bottom:0px; padding-top:0px;padding-bottom:0px">
                        <td class="bnone" colspan="10" style="font-weight:bold; font-size: 15px; margin-top: 0px; margin-bottom: 0px; padding-top: 0px; padding-bottom: 0px; text-align: center; border-top: 0px; border-bottom: 0px">{{ $header['PurchOrderID'] ?? '-' }}</td>
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
                        <td class="bBotBold vendor-section" style="border-top: 0px; border-bottom: 0px;"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bBotBold" width="0%"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bnone" width="1%"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bBotBold" width="1%"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bBotBold" width="1%"></td>
                        <td class="bBotBold company-section" style="border-top: 0px; border-bottom: 0px;"></td>
                        <td style="border-top: 0px; border-bottom: 0px" class="bBotBold" width="1%"></td>
                    </tr>
                    <tr style="border-style: none; border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px">
                        <td class="txtLeftTop bLeftBold" style="border-top:2px; border-top-style:solid; border-bottom:0px; padding: 8px 4px;">TO</td>
                        <td style="border-top: 2px; border-bottom: 0px; border-top-style: solid; padding: 8px 4px;" class="bnone"></td>
                        <td style="border-top: 2px; border-bottom: 0px; border-top-style:solid; padding: 8px 4px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 2px; border-bottom: 0px; border-top-style:solid; padding: 8px 4px;" class="bnone"></td>
                        <td style="border-top: 2px; border-bottom: 0px; border-top-style:solid; padding: 8px 4px;" class="bRightBold"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="bnone"></td>
                        <td style="border-top: 2px; border-top-style: solid; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTopBold bLeftRightBold" colspan="4">{{ $company['CompanyName'] ?? '-' }}</td>
                    </tr>
                    <tr class="bLeftRightBold" style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">Vendor Code</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">{{ $vendor['VendorID'] ?? '-' }}</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="4" rowspan="2"><span>{{ $company['CompanyAddress1'] ?? '' }} <br /> {{ $company['CompanyAddress2'] ?? '' }} <br /> {{ $company['CompanyAddress3'] ?? '' }}</span></td>
                    </tr>
                    <tr class="bLeftRightBold" style="border-top: 0px; border-bottom: 0px">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">Vendor Name</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bRightBold" colspan="2">{{ $vendor['VendorName'] ?? '-' }}</td>
                    </tr>
                    <tr class="bLeftRightBold" style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">Address</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">{{ $vendor['VendorAddress'] ?? '-' }}</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 2px; border-bottom-style:solid; padding: 8px 4px; white-space: nowrap;" class="txtLeftTop bBotBold">Phone/Fax</td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style: solid; padding: 8px 4px;" class="txtCenterTop bBotBold">:</td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 2px; border-bottom-style: solid; padding: 8px 4px;" class="txtLeftTop bBotBold" colspan="2">{{ $company['PhoneNumber'] ?? '-' }} / {{ $company['Fax'] ?? '-' }}</td>
                    </tr>
                    <tr style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px" class="bLeftRightBold">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="bnone" colspan="2"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 0px; padding: 8px 4px; white-space: nowrap;" class="txtLeftTop bnone">PO Creator</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">{{ $header['POCreator'] ?? '-' }}</td>
                    </tr>
                    <tr class="bLeftRightBold" style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2"> Contract No.</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">{{ $header['ContractNumber'] ?? '-' }}</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 2px; border-bottom-style: solid; padding: 8px 4px; white-space: nowrap;" class="txtLeftTop bBotBold">Email</td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style: solid; padding: 8px 4px;" class="txtCenterTop bBotBold">:</td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 2px; border-bottom-style: solid; padding: 8px 4px;" class="txtLeftTop bBotBold" colspan="2">{{ $header['POCreatorEmail'] ?? '-' }}</td>
                    </tr>
                    <tr class="bLeftRightBold" style="border-right: 2px; border-right-style: solid;">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">Attn</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">{{ $vendor['ContactPerson'] ?? '-' }}</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 0px; padding: 8px 4px; white-space: nowrap;" class="txtLeftTop bnone">Requestor</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">{{ $header['RequestorPR'] ?? '-' }}</td>
                    </tr>
                    <tr style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px" class="bLeftRightBold">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">Phone/Mobile</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">{{ $vendor['PhoneNumber'] ?? '-' }} / {{ $vendor['ContactPersonPhoneNumber'] ?? '-' }}</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 0px; padding: 8px 4px; white-space: nowrap;" class="txtLeftTop bnone">PR Number</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">{{ $header['PRNumber'] ?? '-' }}</td>
                    </tr>

                    <tr style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px" class="bLeftRightBold">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">Fax</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">{{ $vendor['FaxNumber'] ?? '-' }}</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bBotBold"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bBotBold"></td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bBotBold" colspan="2"></td>
                    </tr>
                    <tr style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px" class="bLeftRightBold">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bLeftBotBold" rowspan="4" colspan="2">Email</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bBotBold" rowspan="4">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bBotBold" rowspan="4" colspan="2">{{ $vendor['EmailCorrespondence'] ?? '-' }}</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="bLeftRightBold"></td>
                        <td style="border-top: 2px; border-top-style:solid; border-left: 2px; border-left-style: solid; border-bottom: 0px; padding: 8px 4px; white-space: nowrap;" class="txtLeftTop bnone">Delivery Address</td>
                        <td style="border-top: 2px; border-top-style:solid; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 2px; border-top-style:solid; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">site</td>
                    </tr>
                    <tr class="bLeftRightBold" style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px">
                        <td class="bLeftRightBold" style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;"></td>
                        <td class="txtLeftTop bnone" style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 0px; padding: 8px 4px; white-space: nowrap;">Validity Date</td>
                        <td class="txtCenterTop bnone" style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;">:</td>
                        <td class="txtLeftTop bnone" style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 8px 4px;" colspan="2">{{ $header['StrValidityDate'] ?? '-' }}</td>
                    </tr>
                    <tr style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px" class="bLeftRightBold">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="bLeftRightBold"></td>
                        <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-bottom: 0px; padding: 8px 4px; white-space: nowrap;" class="txtLeftTop bnone">Periode</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">{{ $header['ContractPeriod'] ?? '-' }}</td>
                    </tr>
                    <tr style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px" class="bLeftRightBold">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="bnone"></td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px; white-space: nowrap;" class="txtLeftTop bLeftBold">SO Number</td>
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone">:</td>
                        <td style="border-top: 0px; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 8px 4px;" class="txtLeftTop bnone" colspan="2">{{ $header['SONumber'] ?? '-' }}</td>
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
                        <th class="bnone txtLeftTopBold" style="font-family: Arial; font-weight: 100; border-top: 0px; border-bottom: 0px; white-space: nowrap; padding: 4px 0;">SUBJECT:</th>
                        <th class="bnone txtLeftTopBold" colspan="9" style="font-family: Arial; font-weight: 100; border-top: 0px; border-bottom: 0px; padding: 4px 0;">{{ $header['PurchOrderName'] ?? '-' }}</th>
                    </tr>
                    <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 8px 0;" class="bnone" colspan="10"></td>
                    </tr>
                </thead>
                <tbody style="border-top: 0px; border-bottom: 0px" class="txtCenterMid bnone">
                    <tr class="bBotBold" style="border-top: 0px; border-bottom: 0px">
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid; padding: 8px 4px;" class="txtCenterBold bBotBold" width="5%">No.</td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid; padding: 8px 4px;" class="txtLeftBold bBotBold" colspan="3" width="40%">Item Description</td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid; padding: 8px 4px; white-space: nowrap;" class="txtRightBold bBotBold" width="10%">Qty</td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid; padding: 8px 4px;" class="txtCenterBold bBotBold" width="2%"></td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid; padding: 8px 4px;" class="txtLeftBold bBotBold" width="8%">UoM</td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid; padding: 8px 4px;" class="txtRightBold bBotBold" colspan="2" width="20%">Unit Price (IDR)</td>
                        <td style="border-top: 0px; border-bottom: 2px; border-bottom-style:solid; padding: 8px 4px;" class="txtRightBold bBotBold" width="15%">Total (IDR)</td>
                    </tr>
                    @php $counter = 1; @endphp
                    @foreach ($details as $item)
                        <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                            <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px; vertical-align: middle;" class="txtCenterMid bnone">{{ $counter }}</td>
                            <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px; word-break: break-word; overflow-wrap: break-word; vertical-align: top;" class="txtLeftTop bnone" colspan="3">{{ $item['SubItemName'] ?? '-' }}</td>
                            <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px; white-space: nowrap; vertical-align: middle; text-align: right;" class="txtRightMid bnone">{{ $formatNumber($item['Quantity'] ?? 0) }}</td>
                            <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone"></td>
                            <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px; vertical-align: middle;" class="txtLeftMid bnone">{{ $item['ItemUnit'] ?? '-' }}</td>
                            <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px;" class="txtCenterTop bnone"></td>
                            <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px; white-space: nowrap; vertical-align: middle; text-align: right;" class="txtRightMid bnone">{{ $formatNumber($item['Price'] ?? 0) }}</td>
                            <td style="border-top: 0px; border-bottom: 0px; padding: 8px 4px; white-space: nowrap; vertical-align: middle; text-align: right;" class="txtRightMid bnone">{{ $formatNumber($item['TotalAmount'] ?? 0) }}</td>
                        </tr>
                        @php $counter++; @endphp
                    @endforeach
                    <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                        <td style="border-top: 0px; border-bottom: 0px; padding: 12px 0 0 0;" class="bnone" colspan="10"></td>
                    </tr>
                    <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                        <td style="border-top: 0px; border-bottom: 0px" class="bnone" colspan="10">
                            <table style="border-top: 0px; border-bottom: 0px; display: block; width: 100%;" class="table bnone">
                                <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px; white-space: nowrap;" class="txtLeftTopBold bnone">Note*:</td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="txtCenterTopBold bnone">1.</td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="txtLeftTopBold bnone" colspan="3"><span>Total Harga Di Atas Belum <br />Termasuk Pajak Pertambahan Nilai</span></td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone"></td>
                                    <td style="border-top: 2px; border-top-style: solid; border-bottom: 0px; padding: 6px 4px; white-space: nowrap;" class="txtLeftTopBold bTopBold">Grand Total*:</td>
                                    <td style="border-top: 2px; border-top-style: solid; border-bottom: 0px; padding: 6px 4px; white-space: nowrap; text-align: right;" class="txtRightMid bTopBold" colspan="3">{{ $formatNumber($header['TotalAmountPO'] ?? 0) }}</td>
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
                                    <td style="border-top: 0px; border-bottom: 0px; text-decoration:underline; padding: 6px 4px;" class="txtLeftTopBoldUnderline bnone" colspan="5">Term of Payment :</td>
                                    <td style="border-top: 0px; border-bottom: 0px; padding: 6px 4px;" class="bnone"></td>
                                    <td style="border-top: 0px; border-bottom: 0px; text-decoration:underline; padding: 6px 4px;" class="txtLeftTopBoldUnderline bnone" colspan="4">Says/Terbilang :</td>
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
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr style="border-top: 0px; border-bottom: 0px" class="bnone">
                        <td style="border-top: 0px; border-bottom: 0px" class="bnone" colspan="10">
                            <table style="border-top: 0px; border-bottom: 0px; display:block" class="table bnone">
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
                                    <td style="border-top: 0px; border-left: 2px; border-left-style: solid; border-right: 2px; border-right-style: solid; border-bottom: 0px; padding: 6px 4px;" class="txtLeftTopBold bLeftRightBold" colspan="5"><span>{{ $company['CompanyName'] ?? '' }}<br /><br />{{ $company['CompanyAddress1'] ?? '' }}<br />{{ $company['CompanyAddress2'] ?? '' }}<br />{{ $company['CompanyAddress3'] ?? '' }} <br /><br />NPWP : {{ $company['NPWP'] ?? '-' }}</span></td>
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
