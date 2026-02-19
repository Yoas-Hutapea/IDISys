<?php

namespace App\Modules\Procurement\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\POReleasedNotificationMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class PurchaseOrderController extends Controller
{
    public function downloadDocument(Request $request)
    {
        $paramEncrypted = (string) $request->query('paramEncrypted', '');
        if (trim($paramEncrypted) === '') {
            return response('Parameter is required', 400);
        }

        $decoded = base64_decode($paramEncrypted, true);
        if ($decoded === false || $decoded === '') {
            return response('Invalid parameter encoding', 400);
        }

        $filter = json_decode($decoded, true);
        if (!is_array($filter)) {
            return response('Invalid filter parameters', 400);
        }

        $id = (int) ($filter['ID'] ?? 0);
        $poNumber = trim((string) ($filter['PurchOrderID'] ?? ''));

        if ($id === 0 && $poNumber === '') {
            return response('Invalid filter parameters', 400);
        }

        $poQuery = DB::table('trxPROPurchaseOrder')->where('IsActive', true);
        if ($id > 0) {
            $poQuery->where('ID', $id);
        } else {
            $poQuery->where('PurchaseOrderNumber', $poNumber);
        }

        $po = $poQuery->first();
        if (!$po) {
            return response('Purchase Order not found', 404);
        }

        $poNumber = trim((string) ($po->PurchaseOrderNumber ?? ''));

        // Jika format=pdf atau download=1: return PDF dengan Content-Disposition attachment agar langsung terdownload
        if ($request->query('format') === 'pdf' || $request->query('download') === '1') {
            $pdfContent = $this->generatePODocumentPdfContent($poNumber);
            if ($pdfContent === null || $pdfContent === '') {
                return response('Failed to generate PDF', 500);
            }
            $filename = 'PO-' . preg_replace('/[^a-zA-Z0-9\-_]/', '_', $poNumber) . '.pdf';
            return response($pdfContent, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length'      => (string) strlen($pdfContent),
            ]);
        }

        $data = $this->buildPODocumentViewData($po, $paramEncrypted);
        return view('procurement.purchase_order.document.PODocument', $data);
    }

    /**
     * Fetch QR code image from URL and return as data URI so it can be embedded in PDF without DomPDF loading external URLs.
     */
    private function fetchQrCodeAsDataUri(string $url): ?string
    {
        try {
            $response = Http::timeout(5)->withOptions(['verify' => false])->get($url);
            if ($response->successful()) {
                $imageContent = $response->body();
                if ($imageContent !== '' && strlen($imageContent) < 500000) {
                    return 'data:image/png;base64,' . base64_encode($imageContent);
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return null;
    }

    /**
     * Build view data for PO document (HTML view or PDF). Used by downloadDocument and PDF generation.
     *
     * @param  object  $po  Row from trxPROPurchaseOrder
     * @param  string|null  $paramEncrypted  Optional; if null, built from PO number for PDF
     * @param  bool  $forPdfAttachment  When true, skip external QR URL so DomPDF can generate without network
     * @return array<string, mixed>
     */
    private function buildPODocumentViewData(object $po, ?string $paramEncrypted = null, bool $forPdfAttachment = false): array
    {
        $poNumber = $po->PurchaseOrderNumber ?? '';
        if ($paramEncrypted === null) {
            $paramEncrypted = base64_encode(json_encode(['PurchOrderID' => $poNumber]));
        }

        $itemsQuery = DB::table('trxPROPurchaseOrderItem')
            ->where('trxPROPurchaseOrderNumber', $poNumber);

        if (Schema::hasColumn('trxPROPurchaseOrderItem', 'IsActive')) {
            $itemsQuery->where('IsActive', true);
        }

        $items = $itemsQuery->orderBy('ID')->get();

        $details = $items->map(function ($item) {
            return [
                'SubItemName' => $item->ItemName ?? '-',
                'Quantity' => $item->ItemQty ?? 0,
                'ItemUnit' => $item->ItemUnit ?? '-',
                'Price' => $item->UnitPrice ?? 0,
                'TotalAmount' => $item->Amount ?? 0,
            ];
        })->values();

        $totalAmount = $po->PurchaseOrderAmount ?? null;
        if ($totalAmount === null || $totalAmount === '') {
            $totalAmount = $details->sum(fn ($row) => (float) ($row['TotalAmount'] ?? 0));
        }

        $assignVendor = null;
        if (Schema::hasTable('trxPROPurchaseOrderAssignVendor')) {
            $assignVendor = DB::table('trxPROPurchaseOrderAssignVendor')
                ->where('trxPROPurchaseOrderNumber', $poNumber)
                ->where('IsActive', true)
                ->first();
        }

        $topRemarks = null;
        if ($assignVendor && !empty($assignVendor->SeqTOP) && Schema::hasTable('mstFINInvoiceTOP')) {
            $topQuery = DB::table('mstFINInvoiceTOP')->where('IsActive', true);
            if (Schema::hasColumn('mstFINInvoiceTOP', 'TOPCount')) {
                $topQuery->where('TOPCount', $assignVendor->SeqTOP);
            } else {
                $topQuery->where('ID', $assignVendor->SeqTOP);
            }
            $topRow = $topQuery->first();
            if ($topRow) {
                if (isset($topRow->TOPRemarks)) {
                    $topRemarks = $topRow->TOPRemarks;
                } elseif (isset($topRow->TOPDescription)) {
                    $topRemarks = $topRow->TOPDescription;
                }
            }
        }

        $vendor = $this->getVendorInfoForDocument($po);
        $company = $this->getCompanyInfoForDocument($po);

        $creatorInfo = $this->getEmployeeInfo($po->CreatedBy ?? null);
        $authorInfo = $this->getEmployeeInfo($po->PurchaseOrderAuthor ?? null);
        $requestorInfo = $this->getEmployeeInfo($po->PurchaseRequestRequestor ?? null);

        $poApprovedDate = null;
        if ((int) ($po->mstApprovalStatusID ?? 0) === 11 && Schema::hasTable('logPROPurchaseOrder')) {
            $prNumber = trim((string) ($po->trxPROPurchaseRequestNumber ?? ''));
            if ($prNumber !== '') {
                $logQuery = DB::table('logPROPurchaseOrder')
                    ->where('trxPROPurchaseRequestNumber', $prNumber)
                    ->where('mstApprovalStatusID', '11');
                if (Schema::hasColumn('logPROPurchaseOrder', 'IsActive')) {
                    $logQuery->where('IsActive', true);
                }
                $logRow = $logQuery->orderByDesc('CreatedDate')->first();
                if ($logRow && isset($logRow->CreatedDate)) {
                    $poApprovedDate = $logRow->CreatedDate;
                }
            }
        }

        $additionalRow = null;
        $soNumber = null;
        if (Schema::hasTable('trxPROPurchaseRequestAdditional')) {
            $prNumber = trim((string) ($po->trxPROPurchaseRequestNumber ?? ''));
            if ($prNumber !== '') {
                $additionalQuery = DB::table('trxPROPurchaseRequestAdditional')
                    ->where('trxPROPurchaseRequestNumber', $prNumber);
                if (Schema::hasColumn('trxPROPurchaseRequestAdditional', 'IsActive')) {
                    $additionalQuery->where('IsActive', true);
                }
                $additionalRow = $additionalQuery->first();
                if ($additionalRow && isset($additionalRow->Sonumb) && trim((string) $additionalRow->Sonumb) !== '') {
                    $soNumber = $additionalRow->Sonumb;
                }
            }
        }

        if (!$soNumber && Schema::hasTable('trxFINInvoice')) {
            $invoiceRow = DB::table('trxFINInvoice')
                ->where('trxPROPurchaseOrderNumber', $poNumber)
                ->first();
            if ($invoiceRow && isset($invoiceRow->SONumber)) {
                $soNumber = $invoiceRow->SONumber;
            }
        }

        // Periode: dari trxPROPurchaseRequestAdditional.StartPeriod s/d EndPeriod bila ada; bila tidak dari PO/assignVendor
        $periodStart = $additionalRow ? ($additionalRow->StartPeriod ?? $po->StartPeriod ?? null) : ($po->StartPeriod ?? null);
        $periodEnd = $additionalRow ? ($additionalRow->EndPeriod ?? $po->EndPeriod ?? null) : ($po->EndPeriod ?? null);
        $contractPeriodForDoc = $additionalRow ? null : ($assignVendor->ContractPeriod ?? null);

        $validityDate = null;
        if (!empty($po->EndPeriod)) {
            $validityDate = $this->safeAddYears($po->EndPeriod, 1);
        } elseif (!empty($po->PurchaseOrderDate)) {
            $validityDate = $this->safeAddYears($po->PurchaseOrderDate, 1);
        }

        $dateLock = $po->EndPeriod ?? null;

        $header = [
            'ID' => $po->ID ?? null,
            'PurchOrderID' => $po->PurchaseOrderNumber ?? null,
            'PurchOrderName' => $po->PurchaseOrderName ?? null,
            'PODate' => $po->PurchaseOrderDate ?? null,
            'POApprovedDate' => $poApprovedDate,
            'POAuthor' => $authorInfo['name'] ?? ($po->PurchaseOrderAuthor ?? null),
            'POCreator' => $creatorInfo['name'] ?? ($po->CreatedBy ?? null),
            'POCreatorEmail' => $creatorInfo['email'] ?? null,
            'PRNumber' => $po->trxPROPurchaseRequestNumber ?? null,
            'PRRequestor' => $po->PurchaseRequestRequestor ?? null,
            'RequestorPR' => $requestorInfo['name'] ?? ($po->PurchaseRequestRequestor ?? null),
            'ContractNumber' => $assignVendor->ContractNumber ?? null,
            'TotalAmountPO' => $totalAmount,
            'TOPRemarks' => $topRemarks ?? ($assignVendor->DescriptionVendor ?? null),
            'StartPeriod' => $periodStart,
            'EndPeriod' => $periodEnd,
            'StrStartDate' => $this->safeFormatDate($periodStart, 'd-M-Y'),
            'StrEndDate' => $this->safeFormatDate($periodEnd, 'd-M-Y'),
            'StrValidityDate' => $this->safeFormatDate($validityDate, 'd-M-Y'),
            'StrDateLock' => $this->safeFormatDate($dateLock, 'd-M-Y'),
            'ContractPeriod' => $contractPeriodForDoc,
            'SONumber' => $soNumber,
        ];

        // Delivery Address: dari trxPROPurchaseRequestAdditional.SiteName hanya jika PR punya Sonumb di additional; else company address
        $deliveryAddress = null;
        if ($additionalRow && trim((string) ($additionalRow->Sonumb ?? '')) !== '') {
            $deliveryAddress = trim((string) ($additionalRow->SiteName ?? ''));
        }
        if ($deliveryAddress === null || $deliveryAddress === '') {
            $deliveryAddress = trim(implode(' ', array_filter([
                $company['CompanyAddress'] ?? '',
                $company['CompanyAddress1'] ?? '',
                $company['CompanyAddress2'] ?? '',
            ])));
            $deliveryAddress = $deliveryAddress !== '' ? $deliveryAddress : '-';
        }
        $company['DeliveryAddress'] = $deliveryAddress;

        $logoBase64 = null;
        $logoPath = public_path('assets/img/logo.png');
        if (is_file($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $terbilang = null;
        if (is_numeric($totalAmount)) {
            $terbilang = $this->getTerbilang((float) $totalAmount);
        }

        $downloadUrl = url('/Procurement/PurchaseOrder/Document/DownloadDocument') . '?paramEncrypted=' . urlencode($paramEncrypted);
        $qrUrl = 'https://quickchart.io/qr?text=' . urlencode($downloadUrl) . '&size=200';
        if ($forPdfAttachment) {
            // Fetch QR image in PHP and pass as base64 so DomPDF can embed it without loading external URL
            $qrCodeDataUri = $this->fetchQrCodeAsDataUri($qrUrl) ?? ($logoBase64 ?? null);
        } else {
            $qrCodeDataUri = $qrUrl;
        }

        return [
            'header' => $header,
            'details' => $details,
            'vendor' => $vendor,
            'company' => $company,
            'logoBase64' => $logoBase64,
            'qrCodeDataUri' => $qrCodeDataUri,
            'terbilang' => $terbilang,
        ];
    }

    public function show(string $poNumber)
    {
        $decodedNumber = urldecode($poNumber);

        $po = DB::table('trxPROPurchaseOrder')
            ->where('PurchaseOrderNumber', $decodedNumber)
            ->where('IsActive', true)
            ->first();

        if (!$po) {
            return response()->json(['message' => 'Purchase Order not found'], 404);
        }

        $approvalStatus = null;
        if (!empty($po->mstApprovalStatusID)) {
            $approvalStatus = DB::table('mstApprovalStatus')
                ->where('ID', $po->mstApprovalStatusID)
                ->where('IsActive', true)
                ->value('ApprovalStatus');
        }

        $vendorType = null;
        $coreBusiness = null;
        $subCoreBusiness = null;
        $purchaseSubTypeName = null;
        $contractNumber = null;
        $contractPeriod = null;
        $topDescription = null;
        $descriptionVendor = null;

        if (Schema::hasTable('trxPROPurchaseOrderAssignVendor')) {
            $assignVendor = DB::table('trxPROPurchaseOrderAssignVendor')
                ->where('trxPROPurchaseOrderNumber', $decodedNumber)
                ->where('IsActive', true)
                ->first();

            if ($assignVendor) {
                $vendorType = $assignVendor->VendorType ?? null;
                $contractNumber = $assignVendor->ContractNumber ?? null;
                $contractPeriod = $assignVendor->ContractPeriod ?? null;
                $descriptionVendor = $assignVendor->DescriptionVendor ?? null;

                if (!empty($assignVendor->SeqTOP) && Schema::hasTable('mstFINInvoiceTOP')) {
                    $topDescription = DB::table('mstFINInvoiceTOP')
                        ->where('ID', $assignVendor->SeqTOP)
                        ->where('IsActive', true)
                        ->value('TOPDescription');
                }

                $assignVendorCoreBusinessId = $assignVendor->mstPROCoreBusinessID ?? $assignVendor->CoreBusinessID ?? null;
                $assignVendorSubCoreBusinessId = $assignVendor->mstPROCoreBusinessSubCoreID ?? $assignVendor->SubCoreBusinessID ?? null;

                if (!empty($assignVendorCoreBusinessId) && Schema::hasTable('mstPROCoreBusiness')) {
                    $coreBusiness = DB::table('mstPROCoreBusiness')
                        ->where('ID', $assignVendorCoreBusinessId)
                        ->value('CoreBusiness');
                }

                if (!empty($assignVendorSubCoreBusinessId) && Schema::hasTable('mstPROCoreBusinessSubCore')) {
                    $subCoreBusiness = DB::table('mstPROCoreBusinessSubCore')
                        ->where('ID', $assignVendorSubCoreBusinessId)
                        ->value('SubCoreBusiness');
                }
            }
        }

        $purchaseSubTypeId = null;
        if (Schema::hasColumn('trxPROPurchaseOrder', 'mstPROPurchaseSubTypeID')) {
            $purchaseSubTypeId = $po->mstPROPurchaseSubTypeID ?? null;
        }
        if (!$purchaseSubTypeId) {
            $purchaseSubTypeId = $po->PurchaseSubType ?? null;
        }

        if (!empty($purchaseSubTypeId) && Schema::hasTable('mstPROPurchaseSubType')) {
            $purchaseSubTypeName = DB::table('mstPROPurchaseSubType')
                ->where('ID', $purchaseSubTypeId)
                ->value('PurchaseRequestSubType');
        }

        $poCoreBusinessId = null;
        $poSubCoreBusinessId = null;
        if (Schema::hasColumn('trxPROPurchaseOrder', 'mstPROCoreBusinessID')) {
            $poCoreBusinessId = $po->mstPROCoreBusinessID ?? null;
        }
        if (Schema::hasColumn('trxPROPurchaseOrder', 'mstPROCoreBusinessSubCoreID')) {
            $poSubCoreBusinessId = $po->mstPROCoreBusinessSubCoreID ?? null;
        }

        if (!$coreBusiness && !empty($poCoreBusinessId) && Schema::hasTable('mstPROCoreBusiness')) {
            $coreBusiness = DB::table('mstPROCoreBusiness')
                ->where('ID', $poCoreBusinessId)
                ->value('CoreBusiness');
        }

        if (!$subCoreBusiness && !empty($poSubCoreBusinessId) && Schema::hasTable('mstPROCoreBusinessSubCore')) {
            $subCoreBusiness = DB::table('mstPROCoreBusinessSubCore')
                ->where('ID', $poSubCoreBusinessId)
                ->value('SubCoreBusiness');
        }

        $data = [
            'id' => $po->ID ?? null,
            'purchOrderID' => $po->PurchaseOrderNumber ?? null,
            'PurchOrderID' => $po->PurchaseOrderNumber ?? null,
            'purchOrderName' => $po->PurchaseOrderName ?? null,
            'PurchOrderName' => $po->PurchaseOrderName ?? null,
            'purchType' => $po->PurchaseType ?? null,
            'PurchType' => $po->PurchaseType ?? null,
            'purchSubType' => $purchaseSubTypeName ?? $po->PurchaseSubType ?? null,
            'PurchSubType' => $purchaseSubTypeName ?? $po->PurchaseSubType ?? null,
            'poAmount' => $po->PurchaseOrderAmount ?? 0,
            'POAmount' => $po->PurchaseOrderAmount ?? 0,
            'poDate' => $po->PurchaseOrderDate ?? null,
            'PODate' => $po->PurchaseOrderDate ?? null,
            'poAuthor' => $po->PurchaseOrderAuthor ?? null,
            'POAuthor' => $po->PurchaseOrderAuthor ?? null,
            'prNumber' => $po->trxPROPurchaseRequestNumber ?? null,
            'PRNumber' => $po->trxPROPurchaseRequestNumber ?? null,
            'prRequestor' => $po->PurchaseRequestRequestor ?? null,
            'PRRequestor' => $po->PurchaseRequestRequestor ?? null,
            'prDate' => $po->PurchaseRequestDate ?? null,
            'PRDate' => $po->PurchaseRequestDate ?? null,
            'mstVendorVendorName' => $po->mstVendorVendorName ?? null,
            'mstVendorVendorID' => $po->mstVendorVendorID ?? null,
            'MstVendorVendorID' => $po->mstVendorVendorID ?? null,
            'companyName' => $po->CompanyName ?? null,
            'mstApprovalStatusID' => $po->mstApprovalStatusID ?? null,
            'approvalStatus' => $approvalStatus,
            'vendorType' => $vendorType,
            'VendorType' => $vendorType,
            'coreBusiness' => $coreBusiness,
            'CoreBusiness' => $coreBusiness,
            'subCoreBusiness' => $subCoreBusiness,
            'SubCoreBusiness' => $subCoreBusiness,
            'contractNumber' => $contractNumber,
            'ContractNumber' => $contractNumber,
            'contractPeriod' => $contractPeriod,
            'ContractPeriod' => $contractPeriod,
            'top' => $assignVendor ? ($assignVendor->SeqTOP ?? null) : null,
            'TOP' => $assignVendor ? ($assignVendor->SeqTOP ?? null) : null,
            'topDescription' => $topDescription,
            'TOPDescription' => $topDescription,
            'descriptionVendor' => $descriptionVendor,
            'DescriptionVendor' => $descriptionVendor,
        ];

        return response()->json($data);
    }

    public function items(string $poNumber)
    {
        $decodedNumber = urldecode($poNumber);

        $itemsQuery = DB::table('trxPROPurchaseOrderItem')
            ->where('trxPROPurchaseOrderNumber', $decodedNumber);

        if (Schema::hasColumn('trxPROPurchaseOrderItem', 'IsActive')) {
            $itemsQuery->where('IsActive', true);
        }

        $items = $itemsQuery
            ->orderBy('ID')
            ->get()
            ->map(function ($item) {
                return [
                    'ID' => $item->ID ?? null,
                    'trxPROPurchaseOrderNumber' => $item->trxPROPurchaseOrderNumber ?? null,
                    'mstPROPurchaseItemInventoryItemID' => $item->mstPROPurchaseItemInventoryItemID ?? null,
                    'ItemName' => $item->ItemName ?? null,
                    'ItemDescription' => $item->ItemDescription ?? null,
                    'ItemUnit' => $item->ItemUnit ?? null,
                    'ItemQty' => $item->ItemQty ?? null,
                    'CurrencyCode' => $item->CurrencyCode ?? null,
                    'UnitPrice' => $item->UnitPrice ?? null,
                    'Amount' => $item->Amount ?? null,
                    'CreatedDate' => $item->CreatedDate ?? null,
                ];
            })
            ->values();

        return response()->json($items);
    }

    public function approvalHistory(string $poNumber)
    {
        $decodedNumber = urldecode($poNumber);

        if (!Schema::hasTable('logPROPurchaseOrder')) {
            return response()->json([]);
        }

        $po = DB::table('trxPROPurchaseOrder')
            ->where('PurchaseOrderNumber', $decodedNumber)
            ->first();

        $prNumber = $po?->trxPROPurchaseRequestNumber;
        if (!$prNumber) {
            return response()->json([]);
        }

        $historyQuery = DB::table('logPROPurchaseOrder')
            ->where('trxPROPurchaseRequestNumber', $prNumber);

        if (Schema::hasColumn('logPROPurchaseOrder', 'IsActive')) {
            $historyQuery->where('IsActive', true);
        }

        $history = $historyQuery
            ->orderBy('CreatedDate')
            ->get();

        if ($history->isEmpty()) {
            return response()->json([]);
        }

        $employeeIds = $history
            ->pluck('mstEmployeeID')
            ->filter(fn ($id) => !empty($id))
            ->unique()
            ->values();

        $employeeNames = [];
        if (!$employeeIds->isEmpty() && Schema::hasTable('mstEmployee')) {
            $employees = DB::table('mstEmployee')
                ->whereIn('Employ_Id', $employeeIds)
                ->orWhereIn('Employ_Id_TBGSYS', $employeeIds)
                ->get();

            foreach ($employees as $emp) {
                if (!empty($emp->Employ_Id)) {
                    $employeeNames[$emp->Employ_Id] = $emp->name ?? $emp->Employ_Id;
                }
                if (!empty($emp->Employ_Id_TBGSYS) && !isset($employeeNames[$emp->Employ_Id_TBGSYS])) {
                    $employeeNames[$emp->Employ_Id_TBGSYS] = $emp->name ?? $emp->Employ_Id_TBGSYS;
                }
            }
        }

        $data = $history->map(function ($row) use ($employeeNames) {
            $employeeId = $row->mstEmployeeID ?? null;
            return [
                'mstEmployeeID' => $employeeId,
                'employeeName' => $employeeId && isset($employeeNames[$employeeId]) ? $employeeNames[$employeeId] : $employeeId,
                'positionName' => $row->mstEmployeePositionName ?? null,
                'activity' => $row->Activity ?? null,
                'decision' => $row->Decision ?? null,
                'remark' => $row->Remark ?? null,
                'createdDate' => $row->CreatedDate ?? null,
            ];
        })->values();

        return response()->json($data);
    }

    public function cancelPeriodAmortizations(string $poNumber)
    {
        $decodedNumber = urldecode($poNumber);

        if (!Schema::hasTable('trxPROPurchaseOrderAmortization')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'periodOfPayment' => [],
                    'termOfPayment' => [],
                ],
            ]);
        }

        $poColumn = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'trxPROPurchaseOrderNumber')
            ? 'trxPROPurchaseOrderNumber'
            : (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'PurchaseOrderNumber') ? 'PurchaseOrderNumber' : null);

        if (!$poColumn) {
            return response()->json([
                'success' => true,
                'data' => [
                    'periodOfPayment' => [],
                    'termOfPayment' => [],
                ],
            ]);
        }

        $rowsQuery = DB::table('trxPROPurchaseOrderAmortization')
            ->where($poColumn, $decodedNumber);

        if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'PeriodNumber')) {
            $rowsQuery->orderBy('PeriodNumber');
        }

        $rows = $rowsQuery->get();

        $mapRow = function ($row) {
            return [
                'id' => $row->ID ?? null,
                'periodNumber' => $row->PeriodNumber ?? $row->periodNumber ?? null,
                'startDate' => $row->StartDate ?? $row->startDate ?? null,
                'endDate' => $row->EndDate ?? $row->endDate ?? null,
                'termValue' => $row->TermValue ?? $row->termValue ?? null,
                'invoiceAmount' => $row->InvoiceAmount ?? $row->invoiceAmount ?? null,
                'status' => $row->Status ?? $row->status ?? null,
                'isCanceled' => isset($row->IsCanceled) ? (bool) $row->IsCanceled : (bool) ($row->isCanceled ?? false),
                'canceledDate' => $row->CanceledDate ?? $row->canceledDate ?? null,
                'canceledBy' => $row->CanceledBy ?? $row->canceledBy ?? null,
                'cancelReason' => $row->CancelReason ?? $row->cancelReason ?? null,
                'invoiceNumber' => $row->InvoiceNumber ?? $row->invoiceNumber ?? null,
            ];
        };

        $periodOfPayment = [];
        $termOfPayment = [];

        foreach ($rows as $row) {
            $type = $row->AmortizationType ?? $row->amortizationType ?? '';
            $normalizedType = strtolower((string) $type);
            if ($normalizedType === 'period') {
                $periodOfPayment[] = $mapRow($row);
            } elseif ($normalizedType === 'term') {
                $termOfPayment[] = $mapRow($row);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'periodOfPayment' => $periodOfPayment,
                'termOfPayment' => $termOfPayment,
            ],
        ]);
    }

    public function cancelPeriodSubmit(Request $request)
    {
        $purchOrderId = (string) $request->input('purchOrderID');
        $termPositions = $request->input('termPositions', []);
        $periodNumbers = $request->input('periodNumbers', []);
        $cancelReason = $request->input('cancelReason');

        if ($purchOrderId === '') {
            return response()->json(['message' => 'Purchase Order ID is required'], 422);
        }

        if ((empty($termPositions) || !is_array($termPositions)) && (empty($periodNumbers) || !is_array($periodNumbers))) {
            return response()->json(['message' => 'At least one term position or period number must be specified'], 422);
        }

        if (!Schema::hasTable('trxPROPurchaseOrderAmortization')) {
            return response()->json(['message' => 'Amortization table not found'], 500);
        }

        $poColumn = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'trxPROPurchaseOrderNumber')
            ? 'trxPROPurchaseOrderNumber'
            : (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'PurchaseOrderNumber') ? 'PurchaseOrderNumber' : null);

        $periodColumn = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'PeriodNumber')
            ? 'PeriodNumber'
            : (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'Period') ? 'Period' : null);

        if (!$poColumn || !$periodColumn) {
            return response()->json(['message' => 'Amortization columns not found'], 500);
        }

        $now = now();
        $employeeId = $this->getCurrentEmployeeId();

        $updatePayload = [];
        if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'IsCanceled')) {
            $updatePayload['IsCanceled'] = true;
        }
        if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'CanceledDate')) {
            $updatePayload['CanceledDate'] = $now;
        }
        if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'CanceledBy')) {
            $updatePayload['CanceledBy'] = $employeeId;
        }
        if ($cancelReason !== null && Schema::hasColumn('trxPROPurchaseOrderAmortization', 'CancelReason')) {
            $updatePayload['CancelReason'] = $cancelReason;
        }
        if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'Status')) {
            $updatePayload['Status'] = 'Canceled';
        } elseif (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'status')) {
            $updatePayload['status'] = 'Canceled';
        }
        if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'UpdatedDate')) {
            $updatePayload['UpdatedDate'] = $now;
        }
        if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'UpdatedBy')) {
            $updatePayload['UpdatedBy'] = $employeeId;
        }

        $applyCancel = function (string $type, array $periods) use ($purchOrderId, $poColumn, $periodColumn, $updatePayload) {
            if (empty($periods)) {
                return;
            }

            $query = DB::table('trxPROPurchaseOrderAmortization')
                ->where($poColumn, $purchOrderId)
                ->whereIn($periodColumn, $periods);

            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'AmortizationType')) {
                $query->where('AmortizationType', $type);
            }

            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'IsCanceled')) {
                $query->where(function ($q) {
                    $q->whereNull('IsCanceled')->orWhere('IsCanceled', false);
                });
            }

            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'InvoiceNumber')) {
                $query->where(function ($q) {
                    $q->whereNull('InvoiceNumber')->orWhere('InvoiceNumber', '');
                });
            }

            if (!empty($updatePayload)) {
                $query->update($updatePayload);
            }
        };

        $applyAutoCancel = function (string $type, int $maxPeriod, string $autoReason) use ($purchOrderId, $poColumn, $periodColumn, $updatePayload) {
            if ($maxPeriod <= 0) {
                return;
            }

            $query = DB::table('trxPROPurchaseOrderAmortization')
                ->where($poColumn, $purchOrderId)
                ->where($periodColumn, '>', $maxPeriod);

            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'AmortizationType')) {
                $query->where('AmortizationType', $type);
            }

            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'IsCanceled')) {
                $query->where(function ($q) {
                    $q->whereNull('IsCanceled')->orWhere('IsCanceled', false);
                });
            }

            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'InvoiceNumber')) {
                $query->where(function ($q) {
                    $q->whereNull('InvoiceNumber')->orWhere('InvoiceNumber', '');
                });
            }

            if (!empty($updatePayload)) {
                $payload = $updatePayload;
                if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'CancelReason')) {
                    $payload['CancelReason'] = $autoReason;
                }
                $query->update($payload);
            }
        };

        DB::beginTransaction();
        try {
            if (is_array($termPositions) && !empty($termPositions)) {
                $applyCancel('Term', $termPositions);
                $maxTerm = max(array_map('intval', $termPositions));
                $applyAutoCancel('Term', $maxTerm, "Auto-canceled because period {$maxTerm} was canceled");
            }

            if (is_array($periodNumbers) && !empty($periodNumbers)) {
                $applyCancel('Period', $periodNumbers);
                $maxPeriod = max(array_map('intval', $periodNumbers));
                $applyAutoCancel('Period', $maxPeriod, "Auto-canceled because period {$maxPeriod} was canceled");
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Period/Termin canceled successfully',
            'purchOrderID' => $purchOrderId,
            'canceledTerms' => $termPositions ?? [],
            'canceledPeriods' => $periodNumbers ?? [],
        ]);
    }

    public function confirmSubmit(Request $request)
    {
        $poNumber = trim((string) $request->input('poNumber', ''));
        $remarks = trim((string) $request->input('remarks', ''));

        if ($poNumber === '' || $remarks === '') {
            return response()->json(['message' => 'PO Number and remarks are required'], 422);
        }

        $decision = trim((string) $request->input('decision', 'Confirm')) ?: 'Confirm';
        $updatedItems = $request->input('updatedItems', []);
        $deletedItemIds = $request->input('deletedItemIds', []);

        $employeeId = $this->getCurrentEmployeeId();
        $positionName = $this->getPositionName();
        $now = now();

        return DB::transaction(function () use (
            $poNumber,
            $remarks,
            $decision,
            $updatedItems,
            $deletedItemIds,
            $employeeId,
            $positionName,
            $now
        ) {
            $po = DB::table('trxPROPurchaseOrder')
                ->where('PurchaseOrderNumber', $poNumber)
                ->first();

            if (!$po) {
                return response()->json(['message' => 'Purchase Order not found'], 404);
            }

            $prNumber = trim((string) ($po->trxPROPurchaseRequestNumber ?? ''));
            if ($prNumber === '') {
                return response()->json(['message' => 'Purchase Request number not found for PO'], 422);
            }

            $itemsQuery = DB::table('trxPROPurchaseOrderItem')
                ->where('trxPROPurchaseOrderNumber', $poNumber);

            $items = $itemsQuery->get();
            $itemsById = [];
            foreach ($items as $item) {
                if (isset($item->ID)) {
                    $itemsById[$item->ID] = $item;
                }
            }

            if (is_array($updatedItems)) {
                foreach ($updatedItems as $itemDto) {
                    $itemId = $itemDto['ID'] ?? $itemDto['id'] ?? null;
                    if (!$itemId || !isset($itemsById[$itemId])) {
                        continue;
                    }

                    $updatePayload = [
                        'ItemDescription' => (string) ($itemDto['ItemDescription'] ?? ''),
                        'ItemUnit' => (string) ($itemDto['ItemUnit'] ?? ''),
                        'ItemQty' => $itemDto['ItemQty'] ?? $itemsById[$itemId]->ItemQty ?? 0,
                        'UnitPrice' => $itemDto['UnitPrice'] ?? $itemsById[$itemId]->UnitPrice ?? 0,
                        'Amount' => $itemDto['Amount'] ?? $itemsById[$itemId]->Amount ?? 0,
                        'UpdatedBy' => $employeeId,
                        'UpdatedDate' => $now,
                    ];

                    DB::table('trxPROPurchaseOrderItem')
                        ->where('ID', $itemId)
                        ->update($updatePayload);

                    $itemsById[$itemId]->ItemDescription = $updatePayload['ItemDescription'];
                    $itemsById[$itemId]->ItemUnit = $updatePayload['ItemUnit'];
                    $itemsById[$itemId]->ItemQty = $updatePayload['ItemQty'];
                    $itemsById[$itemId]->UnitPrice = $updatePayload['UnitPrice'];
                    $itemsById[$itemId]->Amount = $updatePayload['Amount'];
                }
            }

            $deletedIds = array_filter(array_map('intval', is_array($deletedItemIds) ? $deletedItemIds : []));
            $hasIsActiveColumn = Schema::hasColumn('trxPROPurchaseOrderItem', 'IsActive');
            if ($hasIsActiveColumn && !empty($deletedIds)) {
                DB::table('trxPROPurchaseOrderItem')
                    ->whereIn('ID', $deletedIds)
                    ->update([
                        'IsActive' => false,
                        'UpdatedBy' => $employeeId,
                        'UpdatedDate' => $now,
                    ]);

                foreach ($deletedIds as $deletedId) {
                    if (isset($itemsById[$deletedId])) {
                        $itemsById[$deletedId]->IsActive = false;
                    }
                }
            }

            $activeItems = array_filter($itemsById, function ($item) use ($hasIsActiveColumn, $deletedIds) {
                $itemId = $item->ID ?? null;
                if (in_array($itemId, $deletedIds, true)) {
                    return false;
                }
                if (!$hasIsActiveColumn) {
                    return true;
                }
                $isActive = $item->IsActive ?? null;
                return (int) $isActive === 1;
            });

            $totalAmount = null;
            if (!empty($activeItems)) {
                $totalAmount = collect($activeItems)
                    ->pluck('Amount')
                    ->filter(fn ($amount) => $amount !== null)
                    ->sum();
            }

            DB::table('trxPROPurchaseOrder')
                ->where('PurchaseOrderNumber', $poNumber)
                ->update([
                    'mstApprovalStatusID' => 9,
                    'PurchaseOrderAmount' => $totalAmount,
                    'UpdatedBy' => $employeeId,
                    'UpdatedDate' => $now,
                ]);

            if (Schema::hasTable('logPROPurchaseOrder')) {
                $logData = [
                    'mstEmployeeID' => $employeeId,
                    'mstEmployeePositionName' => $positionName,
                    'trxPROPurchaseRequestNumber' => $prNumber,
                    'Activity' => 'Confirm Purchase Order',
                    'mstApprovalStatusID' => '9',
                    'Remark' => $remarks,
                    'Decision' => $decision,
                ];

                if (Schema::hasColumn('logPROPurchaseOrder', 'CreatedBy')) {
                    $logData['CreatedBy'] = $employeeId;
                }
                if (Schema::hasColumn('logPROPurchaseOrder', 'CreatedDate')) {
                    $logData['CreatedDate'] = $now;
                }
                if (Schema::hasColumn('logPROPurchaseOrder', 'IsActive')) {
                    $logData['IsActive'] = true;
                }

                DB::table('logPROPurchaseOrder')->insert($logData);
            }

            return response()->json([
                'poNumber' => $poNumber,
                'success' => true,
                'message' => 'Purchase Order confirmed successfully',
                'confirmedDate' => $now,
            ]);
        });
    }

    public function confirmBulk(Request $request)
    {
        $poNumbers = $request->input('PoNumbers', []);
        $remarks = trim((string) $request->input('Remarks', ''));
        $decision = trim((string) $request->input('Decision', 'Confirm')) ?: 'Confirm';

        if (!is_array($poNumbers) || empty($poNumbers)) {
            return response()->json(['message' => 'At least one PO Number is required'], 422);
        }
        if ($remarks === '') {
            return response()->json(['message' => 'Remarks is required for bulk confirmation'], 422);
        }

        $employeeId = $this->getCurrentEmployeeId();
        $positionName = $this->getPositionName();
        $now = now();

        $successCount = 0;
        $failed = [];
        $hasIsActiveColumn = Schema::hasColumn('trxPROPurchaseOrderItem', 'IsActive');

        foreach ($poNumbers as $poNumberRaw) {
            $poNumber = trim((string) $poNumberRaw);
            if ($poNumber === '') {
                continue;
            }

            try {
                DB::transaction(function () use (
                    $poNumber,
                    $remarks,
                    $decision,
                    $employeeId,
                    $positionName,
                    $now,
                    $hasIsActiveColumn
                ) {
                    $po = DB::table('trxPROPurchaseOrder')
                        ->where('PurchaseOrderNumber', $poNumber)
                        ->first();

                    if (!$po) {
                        throw new \RuntimeException('Purchase Order not found');
                    }

                    $prNumber = trim((string) ($po->trxPROPurchaseRequestNumber ?? ''));
                    if ($prNumber === '') {
                        throw new \RuntimeException('Purchase Request number not found for PO');
                    }

                    $amountQuery = DB::table('trxPROPurchaseOrderItem')
                        ->where('trxPROPurchaseOrderNumber', $poNumber);
                    if ($hasIsActiveColumn) {
                        $amountQuery->where('IsActive', true);
                    }
                    $amountQuery->whereNotNull('Amount');
                    $totalAmount = $amountQuery->sum('Amount');
                    $totalAmount = $totalAmount === 0.0 ? null : $totalAmount;

                    DB::table('trxPROPurchaseOrder')
                        ->where('PurchaseOrderNumber', $poNumber)
                        ->update([
                            'mstApprovalStatusID' => 9,
                            'PurchaseOrderAmount' => $totalAmount,
                            'UpdatedBy' => $employeeId,
                            'UpdatedDate' => $now,
                        ]);

                    if (Schema::hasTable('logPROPurchaseOrder')) {
                        $logData = [
                            'mstEmployeeID' => $employeeId,
                            'mstEmployeePositionName' => $positionName,
                            'trxPROPurchaseRequestNumber' => $prNumber,
                            'Activity' => 'Confirm Purchase Order',
                            'mstApprovalStatusID' => '9',
                            'Remark' => $remarks,
                            'Decision' => $decision,
                        ];

                        if (Schema::hasColumn('logPROPurchaseOrder', 'CreatedBy')) {
                            $logData['CreatedBy'] = $employeeId;
                        }
                        if (Schema::hasColumn('logPROPurchaseOrder', 'CreatedDate')) {
                            $logData['CreatedDate'] = $now;
                        }
                        if (Schema::hasColumn('logPROPurchaseOrder', 'IsActive')) {
                            $logData['IsActive'] = true;
                        }

                        DB::table('logPROPurchaseOrder')->insert($logData);
                    }
                });

                $successCount++;
            } catch (\Throwable $e) {
                $failed[] = $poNumber;
            }
        }

        if (!empty($failed)) {
            return response()->json([
                'message' => 'Bulk confirmation completed with failures',
                'successCount' => $successCount,
                'failedCount' => count($failed),
                'failedPONumbers' => $failed,
            ], 400);
        }

        return response()->json([
            'poNumber' => 'Bulk Operation',
            'success' => true,
            'message' => "Successfully confirmed {$successCount} Purchase Order(s).",
            'confirmedDate' => $now,
        ]);
    }

    public function updateBulkyPriceSubmit(Request $request)
    {
        $items = $request->input('items', []);
        if (!is_array($items) || empty($items)) {
            return response()->json(['message' => 'Items list cannot be empty'], 422);
        }

        $employeeId = $this->getCurrentEmployeeId();
        $now = now();

        return DB::transaction(function () use ($items, $employeeId, $now) {
            $itemIds = collect($items)
                ->map(fn ($item) => $item['Id'] ?? $item['ID'] ?? $item['id'] ?? null)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values();

            if ($itemIds->isEmpty()) {
                return response()->json(['message' => 'Items list cannot be empty'], 422);
            }

            $itemsQuery = DB::table('trxPROPurchaseOrderItem')
                ->whereIn('ID', $itemIds);

            if (Schema::hasColumn('trxPROPurchaseOrderItem', 'IsActive')) {
                $itemsQuery->where('IsActive', true);
            }

            $itemsFromDb = $itemsQuery->get();
            if ($itemsFromDb->isEmpty()) {
                return response()->json(['message' => 'No valid Purchase Order items found to update'], 422);
            }

            $itemsById = $itemsFromDb->keyBy('ID');
            $affectedPONumbers = $itemsFromDb
                ->pluck('trxPROPurchaseOrderNumber')
                ->filter(fn ($value) => $value !== null && trim($value) !== '')
                ->unique()
                ->values();

            foreach ($items as $itemDto) {
                $itemId = $itemDto['Id'] ?? $itemDto['ID'] ?? $itemDto['id'] ?? null;
                if (!$itemId || !$itemsById->has((int) $itemId)) {
                    continue;
                }

                DB::table('trxPROPurchaseOrderItem')
                    ->where('ID', (int) $itemId)
                    ->update([
                        'UnitPrice' => $itemDto['UnitPrice'] ?? 0,
                        'Amount' => $itemDto['Amount'] ?? 0,
                        'UpdatedBy' => $employeeId,
                        'UpdatedDate' => $now,
                    ]);
            }

            foreach ($affectedPONumbers as $poNumber) {
                $amountQuery = DB::table('trxPROPurchaseOrderItem')
                    ->where('trxPROPurchaseOrderNumber', $poNumber);
                if (Schema::hasColumn('trxPROPurchaseOrderItem', 'IsActive')) {
                    $amountQuery->where('IsActive', true);
                }
                $amountQuery->whereNotNull('Amount');
                $totalAmount = $amountQuery->sum('Amount');
                $totalAmount = $totalAmount === 0.0 ? null : $totalAmount;

                DB::table('trxPROPurchaseOrder')
                    ->where('PurchaseOrderNumber', $poNumber)
                    ->update([
                        'PurchaseOrderAmount' => $totalAmount,
                        'UpdatedBy' => $employeeId,
                        'UpdatedDate' => $now,
                    ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Unit prices updated successfully',
            ]);
        });
    }

    public function approvalSubmit(Request $request, string $poNumber)
    {
        $decodedNumber = urldecode($poNumber);
        $decision = trim((string) $request->input('Decision', 'Approve'));
        $remark = trim((string) $request->input('Remark', ''));
        $activity = trim((string) $request->input('Activity', ''));

        return DB::transaction(function () use ($decodedNumber, $decision, $remark, $activity) {
            $po = DB::table('trxPROPurchaseOrder')
                ->where('PurchaseOrderNumber', $decodedNumber)
                ->first();

            if (!$po) {
                return response()->json(['message' => 'Purchase Order not found'], 404);
            }

            if (!in_array((int) ($po->mstApprovalStatusID ?? 0), [9, 10], true)) {
                return response()->json(['message' => 'Purchase Order status is not eligible for approval'], 422);
            }

            $prNumber = trim((string) ($po->trxPROPurchaseRequestNumber ?? ''));
            if ($prNumber === '') {
                return response()->json(['message' => 'Purchase Request number not found for PO'], 422);
            }

            $decisionLower = strtolower($decision);
            if ($decisionLower === 'reject') {
                $nextStatusId = 12;
            } else {
                $nextStatusId = ((int) $po->mstApprovalStatusID === 9) ? 10 : 11;
            }

            $employeeId = $this->getCurrentEmployeeId();
            $positionName = $this->getPositionName();
            $now = now();

            DB::table('trxPROPurchaseOrder')
                ->where('PurchaseOrderNumber', $decodedNumber)
                ->update([
                    'mstApprovalStatusID' => $nextStatusId,
                    'UpdatedBy' => $employeeId,
                    'UpdatedDate' => $now,
                ]);

            if (Schema::hasTable('logPROPurchaseOrder')) {
                $logData = [
                    'mstEmployeeID' => $employeeId,
                    'mstEmployeePositionName' => $positionName,
                    'trxPROPurchaseRequestNumber' => $prNumber,
                    'Activity' => $activity !== '' ? $activity : ($nextStatusId === 12 ? 'Reject Purchase Order' : 'Approve Purchase Order'),
                    'mstApprovalStatusID' => (string) $nextStatusId,
                    'Remark' => $remark,
                    'Decision' => $decision !== '' ? $decision : ($nextStatusId === 12 ? 'Reject' : 'Approve'),
                ];

                if (Schema::hasColumn('logPROPurchaseOrder', 'CreatedBy')) {
                    $logData['CreatedBy'] = $employeeId;
                }
                if (Schema::hasColumn('logPROPurchaseOrder', 'CreatedDate')) {
                    $logData['CreatedDate'] = $now;
                }
                if (Schema::hasColumn('logPROPurchaseOrder', 'IsActive')) {
                    $logData['IsActive'] = true;
                }

                DB::table('logPROPurchaseOrder')->insert($logData);
            }

            if ($nextStatusId === 11) {
                $decodedNumberForEmail = $decodedNumber;
                $remarkForEmail = $remark;
                DB::afterCommit(function () use ($decodedNumberForEmail, $remarkForEmail) {
                    try {
                        $this->sendPOReleasedEmail($decodedNumberForEmail, $remarkForEmail);
                    } catch (\Throwable $e) {
                        // Log but do not fail the approval response
                    }
                });
            }

            return response()->json([
                'trxPROPurchaseOrderNumber' => $decodedNumber,
                'mstApprovalStatusID' => $nextStatusId,
                'Remark' => $remark,
                'CreatedDate' => $now,
            ]);
        });
    }

    public function approvalBulk(Request $request)
    {
        $poNumbers = $request->input('poNumbers', []);
        if (!is_array($poNumbers) || empty($poNumbers)) {
            return response()->json(['message' => 'At least one Purchase Order Number is required'], 422);
        }

        $decision = trim((string) $request->input('Decision', 'Approve'));
        $remark = trim((string) $request->input('Remark', ''));
        $activity = trim((string) $request->input('Activity', ''));

        $employeeId = $this->getCurrentEmployeeId();
        $positionName = $this->getPositionName();
        $now = now();

        $successCount = 0;
        $failed = [];

        foreach ($poNumbers as $rawNumber) {
            $poNumber = trim((string) $rawNumber);
            if ($poNumber === '') {
                continue;
            }

            try {
                DB::transaction(function () use ($poNumber, $decision, $remark, $activity, $employeeId, $positionName, $now) {
                    $po = DB::table('trxPROPurchaseOrder')
                        ->where('PurchaseOrderNumber', $poNumber)
                        ->first();

                    if (!$po) {
                        throw new \RuntimeException('Purchase Order not found');
                    }

                    if (!in_array((int) ($po->mstApprovalStatusID ?? 0), [9, 10], true)) {
                        throw new \RuntimeException('Purchase Order status is not eligible for approval');
                    }

                    $prNumber = trim((string) ($po->trxPROPurchaseRequestNumber ?? ''));
                    if ($prNumber === '') {
                        throw new \RuntimeException('Purchase Request number not found for PO');
                    }

                    $decisionLower = strtolower($decision);
                    if ($decisionLower === 'reject') {
                        $nextStatusId = 12;
                    } else {
                        $nextStatusId = ((int) $po->mstApprovalStatusID === 9) ? 10 : 11;
                    }

                    DB::table('trxPROPurchaseOrder')
                        ->where('PurchaseOrderNumber', $poNumber)
                        ->update([
                            'mstApprovalStatusID' => $nextStatusId,
                            'UpdatedBy' => $employeeId,
                            'UpdatedDate' => $now,
                        ]);

                    if (Schema::hasTable('logPROPurchaseOrder')) {
                        $logData = [
                            'mstEmployeeID' => $employeeId,
                            'mstEmployeePositionName' => $positionName,
                            'trxPROPurchaseRequestNumber' => $prNumber,
                            'Activity' => $activity !== '' ? $activity : ($nextStatusId === 12 ? 'Reject Purchase Order' : 'Approve Purchase Order'),
                            'mstApprovalStatusID' => (string) $nextStatusId,
                            'Remark' => $remark,
                            'Decision' => $decision !== '' ? $decision : ($nextStatusId === 12 ? 'Reject' : 'Approve'),
                        ];

                        if (Schema::hasColumn('logPROPurchaseOrder', 'CreatedBy')) {
                            $logData['CreatedBy'] = $employeeId;
                        }
                        if (Schema::hasColumn('logPROPurchaseOrder', 'CreatedDate')) {
                            $logData['CreatedDate'] = $now;
                        }
                        if (Schema::hasColumn('logPROPurchaseOrder', 'IsActive')) {
                            $logData['IsActive'] = true;
                        }

                        DB::table('logPROPurchaseOrder')->insert($logData);
                    }

                    if ($nextStatusId === 11) {
                        $poNumberForEmail = $poNumber;
                        $remarkForEmail = $remark;
                        DB::afterCommit(function () use ($poNumberForEmail, $remarkForEmail) {
                            try {
                                $this->sendPOReleasedEmail($poNumberForEmail, $remarkForEmail);
                            } catch (\Throwable $e) {
                                // ignore
                            }
                        });
                    }
                });

                $successCount++;
            } catch (\Throwable $e) {
                $failed[] = $poNumber;
            }
        }

        return response()->json([
            'totalCount' => count($poNumbers),
            'successCount' => $successCount,
            'failedCount' => count($failed),
            'failedPONumbers' => $failed,
            'message' => "Successfully processed {$successCount} out of " . count($poNumbers) . " Purchase Order(s)",
        ]);
    }

    public function purchaseOrderItemsGrid(Request $request)
    {
        $draw = (int) $request->input('draw', 0);
        $startInput = $request->input('start');
        if ($startInput === null) {
            $startInput = $request->input('Start');
        }
        $lengthInput = $request->input('length');
        if ($lengthInput === null) {
            $lengthInput = $request->input('Length');
        }
        $start = (int) ($startInput ?? 0);
        $length = (int) ($lengthInput ?? 10);

        $baseQuery = $this->basePurchaseOrderItemsQuery($request);
        $recordsTotal = (clone $baseQuery)->count('item.ID');

        $filteredQuery = $this->applyPurchaseOrderItemsFilters($baseQuery, $request);
        $recordsFiltered = (clone $filteredQuery)->count('item.ID');

        $order = $request->input('order.0', []);
        $columns = $request->input('columns', []);
        $orderColumnIndex = $order['column'] ?? null;
        $orderDir = $order['dir'] ?? 'asc';

        if ($orderColumnIndex !== null && isset($columns[$orderColumnIndex]['data'])) {
            $columnKey = $columns[$orderColumnIndex]['data'];
            $orderColumn = $this->mapPurchaseOrderItemsOrderColumn($columnKey);
            if ($orderColumn) {
                $filteredQuery->orderBy($orderColumn, $orderDir === 'desc' ? 'desc' : 'asc');
            }
        } else {
            $filteredQuery->orderBy('item.ID', 'asc');
        }

        $rows = $filteredQuery
            ->skip($start)
            ->take($length > 0 ? $length : $recordsFiltered)
            ->get();

        $data = $rows->map(function ($row) {
            return [
                'id' => $row->id ?? null,
                'trxPROPurchaseOrderNumber' => $row->trxPROPurchaseOrderNumber ?? null,
                'mstPROPurchaseItemInventoryItemID' => $row->mstPROPurchaseItemInventoryItemID ?? null,
                'itemName' => $row->itemName ?? null,
                'itemDescription' => $row->itemDescription ?? null,
                'itemUnit' => $row->itemUnit ?? null,
                'itemQty' => $row->itemQty ?? null,
                'currencyCode' => $row->currencyCode ?? null,
                'unitPrice' => $row->unitPrice ?? null,
                'amount' => $row->amount ?? null,
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function grid(Request $request)
    {
        $draw = (int) $request->input('draw', 0);
        $startInput = $request->input('start');
        if ($startInput === null) {
            $startInput = $request->input('Start');
        }
        $lengthInput = $request->input('length');
        if ($lengthInput === null) {
            $lengthInput = $request->input('Length');
        }
        $start = (int) ($startInput ?? 0);
        $length = (int) ($lengthInput ?? 10);

        $baseQuery = $this->applyStatusFilters($this->baseQuery(), $request);
        $recordsTotal = (clone $baseQuery)->count('po.ID');

        $filteredQuery = $this->applyFilters($baseQuery, $request);
        $recordsFiltered = (clone $filteredQuery)->count('po.ID');

        $order = $request->input('order.0', []);
        $columns = $request->input('columns', []);
        $orderColumnIndex = $order['column'] ?? null;
        $orderDir = $order['dir'] ?? 'desc';

        if ($orderColumnIndex !== null && isset($columns[$orderColumnIndex]['data'])) {
            $columnKey = $columns[$orderColumnIndex]['data'];
            $orderColumn = $this->mapOrderColumn($columnKey);
            if ($orderColumn) {
                $filteredQuery->orderBy($orderColumn, $orderDir === 'asc' ? 'asc' : 'desc');
            }
        } else {
            $filteredQuery->orderBy('po.PurchaseOrderDate', 'desc');
        }

        $rows = $filteredQuery
            ->skip($start)
            ->take($length > 0 ? $length : $recordsFiltered)
            ->get();

        $data = $rows->map(fn ($row) => $this->mapRow($row))->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function baseQuery()
    {
        return DB::table('trxPROPurchaseOrder as po')
            ->leftJoin('mstPROPurchaseSubType as subType', 'po.PurchaseSubType', '=', 'subType.ID')
            ->leftJoin('mstApprovalStatus as status', 'po.mstApprovalStatusID', '=', 'status.ID')
            ->where('po.IsActive', true)
            ->select([
                'po.ID as id',
                'po.PurchaseOrderNumber as purchOrderID',
                'po.PurchaseOrderName as purchOrderName',
                'po.PurchaseType as purchType',
                'po.PurchaseSubType as purchSubType',
                'subType.PurchaseRequestSubType as purchaseSubTypeName',
                'po.PurchaseOrderAmount as poAmount',
                'po.PurchaseOrderDate as poDate',
                'po.PurchaseOrderAuthor as poAuthor',
                'po.trxPROPurchaseRequestNumber as prNumber',
                'po.PurchaseRequestRequestor as prRequestor',
                'po.mstVendorVendorName as mstVendorVendorName',
                'po.CompanyName as companyName',
                'po.mstApprovalStatusID as mstApprovalStatusID',
                'status.ApprovalStatus as approvalStatus',
            ]);
    }

    private function applyStatusFilters($query, Request $request)
    {
        $statusIds = $request->input('mstApprovalStatusIDs', []);
        $statusIds = is_array($statusIds) ? $statusIds : [];
        $singleStatusId = $request->input('mstApprovalStatusID');

        if ($singleStatusId !== null && $singleStatusId !== '') {
            $query->where('po.mstApprovalStatusID', (int) $singleStatusId);
        }

        if (!empty($statusIds)) {
            $allowedStatuses = $this->filterAllowedStatuses($statusIds);
            if (empty($allowedStatuses)) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereIn('po.mstApprovalStatusID', $allowedStatuses);

            if (in_array(8, $allowedStatuses, true) || in_array(12, $allowedStatuses, true)) {
                $userIds = $this->getCurrentUserIdentifiers();
                if (empty($userIds)) {
                    return $query->whereRaw('1 = 0');
                }
                $query->whereIn('po.CreatedBy', $userIds);
            }
        }

        return $query;
    }

    private function applyFilters($query, Request $request)
    {
        $poNumber = $request->input('poNumber');
        $prNumber = $request->input('prNumber');
        $purchType = $request->input('purchType');
        $purchSubType = $request->input('purchSubType');
        $purchName = $request->input('purchName');
        $company = $request->input('company');
        $vendorName = $request->input('vendorName');
        $poStartDate = $request->input('poStartDate');
        $poEndDate = $request->input('poEndDate');

        if ($poNumber && trim($poNumber) !== '') {
            $query->where('po.PurchaseOrderNumber', trim($poNumber));
        }
        if ($prNumber && trim($prNumber) !== '') {
            $query->where('po.trxPROPurchaseRequestNumber', trim($prNumber));
        }
        if ($purchType && trim($purchType) !== '') {
            $query->where('po.PurchaseType', trim($purchType));
        }
        if ($purchSubType && trim($purchSubType) !== '') {
            $query->where('po.PurchaseSubType', trim($purchSubType));
        }
        if ($purchName && trim($purchName) !== '') {
            $query->where('po.PurchaseOrderName', 'like', '%' . trim($purchName) . '%');
        }
        if ($company && trim($company) !== '') {
            $query->where('po.CompanyName', trim($company));
        }
        if ($vendorName && trim($vendorName) !== '') {
            $query->where('po.mstVendorVendorName', 'like', '%' . trim($vendorName) . '%');
        }
        if ($poStartDate) {
            $query->whereDate('po.PurchaseOrderDate', '>=', $poStartDate);
        }
        if ($poEndDate) {
            $query->whereDate('po.PurchaseOrderDate', '<=', $poEndDate);
        }

        return $query;
    }

    private function mapRow($row): array
    {
        $subType = $row->purchaseSubTypeName ?? $row->purchSubType ?? null;

        return [
            'id' => $row->id ?? null,
            'purchOrderID' => $row->purchOrderID ?? null,
            'purchOrderName' => $row->purchOrderName ?? null,
            'purchType' => $row->purchType ?? null,
            'purchSubType' => $subType,
            'approvalStatus' => $row->approvalStatus ?? null,
            'mstApprovalStatusID' => $row->mstApprovalStatusID ?? null,
            'poAmount' => $row->poAmount ?? 0,
            'poDate' => $row->poDate ?? null,
            'prNumber' => $row->prNumber ?? null,
            'mstVendorVendorName' => $row->mstVendorVendorName ?? null,
            'companyName' => $row->companyName ?? null,
            'poAuthor' => $row->poAuthor ?? null,
            'prRequestor' => $row->prRequestor ?? null,
        ];
    }

    private function mapOrderColumn(?string $columnKey): ?string
    {
        return match ($columnKey) {
            'purchOrderID' => 'po.PurchaseOrderNumber',
            'purchOrderName' => 'po.PurchaseOrderName',
            'purchType' => 'po.PurchaseType',
            'purchSubType' => 'po.PurchaseSubType',
            'approvalStatus' => 'status.ApprovalStatus',
            'poAmount' => 'po.PurchaseOrderAmount',
            'poDate' => 'po.PurchaseOrderDate',
            'prNumber' => 'po.trxPROPurchaseRequestNumber',
            'mstVendorVendorName' => 'po.mstVendorVendorName',
            'companyName' => 'po.CompanyName',
            'poAuthor' => 'po.PurchaseOrderAuthor',
            'prRequestor' => 'po.PurchaseRequestRequestor',
            default => null,
        };
    }

    private function basePurchaseOrderItemsQuery(Request $request)
    {
        $query = DB::table('trxPROPurchaseOrderItem as item')
            ->leftJoin('trxPROPurchaseOrder as po', 'item.trxPROPurchaseOrderNumber', '=', 'po.PurchaseOrderNumber')
            ->select([
                'item.ID as id',
                'item.trxPROPurchaseOrderNumber as trxPROPurchaseOrderNumber',
                'item.mstPROPurchaseItemInventoryItemID as mstPROPurchaseItemInventoryItemID',
                'item.ItemName as itemName',
                'item.ItemDescription as itemDescription',
                'item.ItemUnit as itemUnit',
                'item.ItemQty as itemQty',
                'item.CurrencyCode as currencyCode',
                'item.UnitPrice as unitPrice',
                'item.Amount as amount',
                'po.mstApprovalStatusID as mstApprovalStatusID',
            ]);

        if (Schema::hasColumn('trxPROPurchaseOrderItem', 'IsActive')) {
            $query->where('item.IsActive', true);
        }

        $statusIds = $request->input('mstApprovalStatusIDs', []);
        $statusIds = is_array($statusIds) ? $statusIds : [];
        if (!empty($statusIds)) {
            $query->whereIn('po.mstApprovalStatusID', array_map('intval', $statusIds));
        }

        return $query;
    }

    private function applyPurchaseOrderItemsFilters($query, Request $request)
    {
        $poNumber = $request->input('poNumber');
        $poNumberFilter = $request->input('poNumberFilter');
        $itemIdFilter = $request->input('itemIDFilter');
        $descriptionFilter = $request->input('descriptionFilter');

        if ($poNumber && trim($poNumber) !== '') {
            $query->where('item.trxPROPurchaseOrderNumber', trim($poNumber));
        }
        if ($poNumberFilter && trim($poNumberFilter) !== '') {
            $query->where('item.trxPROPurchaseOrderNumber', trim($poNumberFilter));
        }
        if ($itemIdFilter && trim($itemIdFilter) !== '') {
            $query->where('item.mstPROPurchaseItemInventoryItemID', trim($itemIdFilter));
        }
        if ($descriptionFilter && trim($descriptionFilter) !== '') {
            $query->where('item.ItemDescription', 'like', '%' . trim($descriptionFilter) . '%');
        }

        return $query;
    }

    private function mapPurchaseOrderItemsOrderColumn(?string $columnKey): ?string
    {
        return match ($columnKey) {
            'id' => 'item.ID',
            'trxPROPurchaseOrderNumber' => 'item.trxPROPurchaseOrderNumber',
            'mstPROPurchaseItemInventoryItemID' => 'item.mstPROPurchaseItemInventoryItemID',
            'itemName' => 'item.ItemName',
            'itemDescription' => 'item.ItemDescription',
            'itemUnit' => 'item.ItemUnit',
            'itemQty' => 'item.ItemQty',
            'currencyCode' => 'item.CurrencyCode',
            'unitPrice' => 'item.UnitPrice',
            'amount' => 'item.Amount',
            default => null,
        };
    }

    private function filterAllowedStatuses(array $statusIds): array
    {
        $positionName = $this->getPositionName();

        $isAccountPayableManager = $positionName !== '' &&
            strcasecmp($positionName, 'Account Payable, Treasury & Revenue Assurance Manager') === 0;
        $isFinanceDivisionHead = $positionName !== '' &&
            strcasecmp($positionName, 'Finance & Treasury Division Head') === 0;

        $statusIds = array_map('intval', $statusIds);
        $statusIds = array_values(array_unique($statusIds));

        $allowed = [];
        if ($isAccountPayableManager) {
            foreach ($statusIds as $statusId) {
                if ($statusId === 10) {
                    continue;
                }
                if ($statusId === 9 || ($statusId !== 9 && $statusId !== 10)) {
                    $allowed[] = $statusId;
                }
            }
        } elseif ($isFinanceDivisionHead) {
            foreach ($statusIds as $statusId) {
                if ($statusId === 9) {
                    continue;
                }
                if ($statusId === 10 || ($statusId !== 9 && $statusId !== 10)) {
                    $allowed[] = $statusId;
                }
            }
        } else {
            foreach ($statusIds as $statusId) {
                if ($statusId === 9 || $statusId === 10) {
                    continue;
                }
                $allowed[] = $statusId;
            }
        }

        return $allowed;
    }

    private function getPositionName(): string
    {
        $employee = session('employee');
        $positionName = $employee?->PositionName ?? $employee?->JobTitleName ?? '';
        $positionName = trim((string) $positionName);
        if ($positionName === '') {
            return '';
        }

        return preg_replace('/\s+/', ' ', $positionName) ?? '';
    }

    private function getCurrentEmployeeId(): string
    {
        $employee = session('employee');
        if ($employee && !empty($employee->Employ_Id)) {
            return $employee->Employ_Id;
        }

        return (string) optional(Auth::user())->Username;
    }

    private function getCurrentUserIdentifiers(): array
    {
        $employee = session('employee');
        $ids = [];

        if ($employee) {
            if (!empty($employee->Employ_Id)) {
                $ids[] = $employee->Employ_Id;
            }
            if (!empty($employee->Employ_Id_TBGSYS)) {
                $ids[] = $employee->Employ_Id_TBGSYS;
            }
        }

        $username = (string) optional(Auth::user())->Username;
        if ($username !== '') {
            $ids[] = $username;
        }

        return array_values(array_unique(array_filter($ids, fn ($id) => $id !== '')));
    }

    private function safeFormatDate($value, string $format): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function safeAddYears($value, int $years)
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->addYears($years);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getEmployeeInfo(?string $employeeId): array
    {
        if (!$employeeId || !Schema::hasTable('mstEmployee')) {
            return [];
        }

        $employee = DB::table('mstEmployee')
            ->where('Employ_Id', $employeeId)
            ->orWhere('Employ_Id_TBGSYS', $employeeId)
            ->first();

        if (!$employee) {
            return [];
        }

        $name = null;
        if (isset($employee->name)) {
            $name = $employee->name;
        } elseif (isset($employee->Name)) {
            $name = $employee->Name;
        }

        $email = null;
        if (isset($employee->Email)) {
            $email = $employee->Email;
        } elseif (isset($employee->email)) {
            $email = $employee->email;
        }

        return [
            'name' => $name,
            'email' => $email,
        ];
    }

    /**
     * Resolve PO Type to display name (mstPROPurchaseType.PurchaseRequestType).
     */
    private function resolvePOTypeName(object $po): string
    {
        $typeId = null;
        if (Schema::hasColumn('trxPROPurchaseOrder', 'mstPROPurchaseTypeID') && !empty($po->mstPROPurchaseTypeID)) {
            $typeId = $po->mstPROPurchaseTypeID;
        } elseif (isset($po->PurchaseType) && is_numeric($po->PurchaseType)) {
            $typeId = (int) $po->PurchaseType;
        }
        if ($typeId !== null && Schema::hasTable('mstPROPurchaseType')) {
            $name = DB::table('mstPROPurchaseType')
                ->where('ID', $typeId)
                ->value('PurchaseRequestType');
            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }
        $raw = trim((string) ($po->PurchaseType ?? ''));
        return $raw !== '' ? $raw : 'General';
    }

    /**
     * Resolve PO Sub Type to display name (mstPROPurchaseSubType.PurchaseRequestSubType).
     */
    private function resolvePOSubTypeName(object $po): string
    {
        $subTypeId = null;
        if (Schema::hasColumn('trxPROPurchaseOrder', 'mstPROPurchaseSubTypeID') && !empty($po->mstPROPurchaseSubTypeID)) {
            $subTypeId = $po->mstPROPurchaseSubTypeID;
        } elseif (isset($po->PurchaseSubType) && is_numeric($po->PurchaseSubType)) {
            $subTypeId = (int) $po->PurchaseSubType;
        }
        if ($subTypeId !== null && Schema::hasTable('mstPROPurchaseSubType')) {
            $name = DB::table('mstPROPurchaseSubType')
                ->where('ID', $subTypeId)
                ->value('PurchaseRequestSubType');
            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }
        $raw = trim((string) ($po->PurchaseSubType ?? ''));
        return $raw !== '' ? $raw : 'General';
    }

    /**
     * Get requestor email for a PO from trxPROPurchaseRequest.Requestor -> mstEmployee.Email
     */
    private function getRequestorEmailForPO(object $po): ?string
    {
        $prNumber = trim((string) ($po->trxPROPurchaseRequestNumber ?? ''));
        if ($prNumber === '' || !Schema::hasTable('trxPROPurchaseRequest')) {
            return null;
        }
        $pr = DB::table('trxPROPurchaseRequest')
            ->where('PurchaseRequestNumber', $prNumber)
            ->first();
        if (!$pr || empty($pr->Requestor)) {
            return null;
        }
        $info = $this->getEmployeeInfo($pr->Requestor);
        return $info['email'] ?? null;
    }

    /**
     * Get vendor contact person email (CPEmail) from mstVendor for CC.
     */
    private function getVendorCPEmailForPO(object $po): ?string
    {
        $vendorId = trim((string) ($po->mstVendorVendorID ?? ''));
        if ($vendorId === '' || !Schema::hasTable('mstVendor') || !Schema::hasColumn('mstVendor', 'CPEmail')) {
            return null;
        }
        $email = DB::table('mstVendor')
            ->where('VendorID', $vendorId)
            ->value('CPEmail');
        return $email ? trim((string) $email) : null;
    }

    /**
     * Generate PO document as PDF raw content for email attachment (same as DownloadDocument view).
     * First tries with forPdfAttachment=true (no external QR); fallback with QR URL if needed.
     */
    private function generatePODocumentPdfContent(string $poNumber): ?string
    {
        $po = DB::table('trxPROPurchaseOrder')
            ->where('PurchaseOrderNumber', $poNumber)
            ->where('IsActive', true)
            ->first();
        if (!$po) {
            return null;
        }
        foreach ([true, false] as $forPdfAttachment) {
            try {
                $data = $this->buildPODocumentViewData($po, null, $forPdfAttachment);
                $pdf = Pdf::loadView('procurement.purchase_order.document.PODocument', $data);
                $output = $pdf->output();
                if ($output !== null && $output !== '') {
                    return $output;
                }
            } catch (\Throwable $e) {
                if ($forPdfAttachment) {
                    try {
                        \Illuminate\Support\Facades\Log::warning('PO PDF generation (forPdfAttachment) failed: ' . $e->getMessage(), ['poNumber' => $poNumber]);
                    } catch (\Throwable $logIgnore) {
                    }
                }
                continue;
            }
        }
        return null;
    }

    /**
     * True when running on localhost/127.0.0.1 so we can redirect test emails and avoid spamming real addresses.
     */
    private function isLocalEnvironment(): bool
    {
        $appUrl = strtolower((string) (config('app.url') ?? ''));
        if (str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1')) {
            return true;
        }
        try {
            $host = strtolower((string) (request()->getHost() ?? ''));
            return $host === 'localhost' || $host === '127.0.0.1';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Email address to use for PO Release notification when testing (localhost or MAIL_TEST_TO).
     * Prefer env MAIL_TEST_TO; fallback to yoas.hutapea@ideanet.net.id when on local.
     */
    private function getTestNotificationEmail(): ?string
    {
        $testTo = config('mail.test_to');
        if (is_string($testTo) && trim($testTo) !== '') {
            return trim($testTo);
        }
        if ($this->isLocalEnvironment()) {
            return 'yoas.hutapea@ideanet.net.id';
        }
        return null;
    }

    /**
     * Send PO Released notification email to Requestor with CC to vendor when Approval PO 2 completes (Fully Approved).
     * On localhost/127.0.0.1 all emails are sent to yoas.hutapea@ideanet.net.id for testing.
     */
    private function sendPOReleasedEmail(string $poNumber, string $approvalRemarks): void
    {
        $po = DB::table('trxPROPurchaseOrder')
            ->where('PurchaseOrderNumber', $poNumber)
            ->where('IsActive', true)
            ->first();
        if (!$po) {
            return;
        }

        $testTo = $this->getTestNotificationEmail();
        $requestorEmail = $this->getRequestorEmailForPO($po);
        if (empty($requestorEmail) && empty($testTo)) {
            return;
        }

        $approvalStatusName = 'Fully Approved';

        $purchaseSubTypeName = $this->resolvePOSubTypeName($po);
        $poTypeName = $this->resolvePOTypeName($po);
        $amount = $po->PurchaseOrderAmount ?? 0;
        $amountFormatted = is_numeric($amount) ? number_format((float) $amount, 0, '.', ',') : (string) $amount;
        $remarks = $approvalRemarks !== '' ? $approvalRemarks : '——';

        $pdfContent = $this->generatePODocumentPdfContent($poNumber);

        $mailable = new POReleasedNotificationMail(
            poNumber: $poNumber,
            poType: $poTypeName,
            poSubType: $purchaseSubTypeName,
            mitraName: trim((string) ($po->mstVendorVendorName ?? '')),
            amountFormatted: $amountFormatted,
            status: $approvalStatusName,
            remarks: $remarks,
            pdfContent: $pdfContent
        );

        if ($testTo !== null) {
            Mail::to($testTo)->send($mailable);
            return;
        }

        $mailer = Mail::to($requestorEmail);
        $vendorCc = $this->getVendorCPEmailForPO($po);
        if (!empty($vendorCc)) {
            $mailer->cc($vendorCc);
        }
        $mailer->send($mailable);
    }

    private function getVendorInfoForDocument(object $po): array
    {
        $vendor = null;
        if (Schema::hasTable('mstVendor')) {
            $vendorId = trim((string) ($po->mstVendorVendorID ?? ''));
            if ($vendorId !== '' && Schema::hasColumn('mstVendor', 'VendorID')) {
                $vendorQuery = DB::table('mstVendor')->where('VendorID', $vendorId);
                if (Schema::hasColumn('mstVendor', 'IsActive')) {
                    $vendorQuery->where('IsActive', true);
                }
                $vendor = $vendorQuery->first();
            }
        }

        return [
            'VendorID' => $vendor->VendorID ?? ($po->mstVendorVendorID ?? '-'),
            'VendorName' => $vendor->VendorName ?? ($po->mstVendorVendorName ?? '-'),
            'VendorAddress' => $vendor->NPWPAddress
                ?? $vendor->RecipientAddress
                ?? '-',
            'ContactPerson' => $vendor->CP ?? '-',
            'PhoneNumber' => $vendor->Telephone ?? '-',
            'ContactPersonPhoneNumber' => $vendor->CPPhone ?? '-',
            'FaxNumber' => $vendor->Fax ?? '-',
            'EmailCorrespondence' => $vendor->EmailCorresspondence ?? '-',
        ];
    }

    private function getCompanyInfoForDocument(object $po): array
    {
        $company = null;
        if (Schema::hasTable('mstCompany')) {
            $companyId = trim((string) ($po->CompanyID ?? ''));

            if ($companyId !== '' && Schema::hasColumn('mstCompany', 'CompanyID')) {
                $company = DB::table('mstCompany')
                    ->where('CompanyID', $companyId)
                    ->first();
            } elseif ($companyId !== '' && is_numeric($companyId)) {
                $company = DB::table('mstCompany')
                    ->where('ID', (int) $companyId)
                    ->first();
            }
        }

        $companyName = $po->CompanyName ?? '-';
        $companyAddress = null;
        $companyAddress1 = null;
        $companyAddress2 = null;
        $phoneNumber = null;
        $fax = null;
        $npwp = null;
        $npwpAddress = null;
        $npwpAddress1 = null;
        $npwpAddress2 = null;

        if ($company !== null) {
            $companyName = $company->Company ?? $companyName;
            $companyAddress = $company->CompanyAddress ?? null;
            $companyAddress1 = $company->CompanyAddress1 ?? null;
            $companyAddress2 = $company->CompanyAddress2 ?? null;
            $phoneNumber = $company->TelpNumber ?? null;
            $fax = $company->Fax ?? null;
            $npwp = $company->NPWP ?? null;
            $npwpAddress = $company->NPWPAddress ?? null;
            $npwpAddress1 = $company->NPWPAddress1 ?? null;
            $npwpAddress2 = $company->NPWPAddress2 ?? null;
        }

        return [
            'CompanyName' => $companyName,
            'CompanyAddress' => $companyAddress,
            'CompanyAddress1' => $companyAddress1,
            'CompanyAddress2' => $companyAddress2,
            'PhoneNumber' => $phoneNumber,
            'Fax' => $fax,
            'NPWP' => $npwp,
            'NPWPAddress' => $npwpAddress,
            'NPWPAddress1' => $npwpAddress1,
            'NPWPAddress2' => $npwpAddress2,
        ];
    }

    private function getTerbilang(float $number): string
    {
        if ($number == 0.0) {
            return 'Nol Rupiah';
        }

        $integerPart = (int) floor($number);
        $result = $this->terbilangBeforeComma((string) $integerPart);

        $decimalPart = $number - floor($number);
        if ($decimalPart > 0) {
            $decimalStr = number_format($decimalPart, 2, '.', '');
            $decimalDigits = explode('.', $decimalStr)[1] ?? '';
            $result .= ' ' . $this->terbilangAfterComma($decimalDigits);
        }

        return trim($result) . ' Rupiah';
    }

    private function terbilangBeforeComma(string $numberStr): string
    {
        $numberStr = ltrim($numberStr, '0');
        if ($numberStr === '') {
            return '';
        }

        $number = (int) $numberStr;
        $bilangan = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        if ($number < 12) {
            return $bilangan[$number];
        }
        if ($number < 20) {
            return $bilangan[$number % 10] . ' Belas';
        }
        if ($number < 100) {
            $puluh = intdiv($number, 10);
            $sisa = $number % 10;
            return $sisa === 0
                ? $bilangan[$puluh] . ' Puluh'
                : $bilangan[$puluh] . ' Puluh ' . $bilangan[$sisa];
        }
        if ($number < 200) {
            $sisa = $number % 100;
            return $sisa === 0 ? 'Seratus' : 'Seratus ' . $this->terbilangBeforeComma((string) $sisa);
        }
        if ($number < 1000) {
            $ratus = intdiv($number, 100);
            $sisa = $number % 100;
            return $sisa === 0
                ? $bilangan[$ratus] . ' Ratus'
                : $bilangan[$ratus] . ' Ratus ' . $this->terbilangBeforeComma((string) $sisa);
        }
        if ($number < 2000) {
            $sisa = $number % 1000;
            return $sisa === 0 ? 'Seribu' : 'Seribu ' . $this->terbilangBeforeComma((string) $sisa);
        }
        if ($number < 1000000) {
            $ribu = intdiv($number, 1000);
            $sisa = $number % 1000;
            return $sisa === 0
                ? $this->terbilangBeforeComma((string) $ribu) . ' Ribu'
                : $this->terbilangBeforeComma((string) $ribu) . ' Ribu ' . $this->terbilangBeforeComma((string) $sisa);
        }
        if ($number < 1000000000) {
            $juta = intdiv($number, 1000000);
            $sisa = $number % 1000000;
            return $sisa === 0
                ? $this->terbilangBeforeComma((string) $juta) . ' Juta'
                : $this->terbilangBeforeComma((string) $juta) . ' Juta ' . $this->terbilangBeforeComma((string) $sisa);
        }
        if ($number < 1000000000000) {
            $milyar = intdiv($number, 1000000000);
            $sisa = $number % 1000000000;
            return $sisa === 0
                ? $this->terbilangBeforeComma((string) $milyar) . ' Milyar'
                : $this->terbilangBeforeComma((string) $milyar) . ' Milyar ' . $this->terbilangBeforeComma((string) $sisa);
        }

        $triliun = intdiv($number, 1000000000000);
        $sisa = $number % 1000000000000;
        return $sisa === 0
            ? $this->terbilangBeforeComma((string) $triliun) . ' Triliun'
            : $this->terbilangBeforeComma((string) $triliun) . ' Triliun ' . $this->terbilangBeforeComma((string) $sisa);
    }

    private function terbilangAfterComma(string $decimalStr): string
    {
        if ($decimalStr === '' || $decimalStr === '00') {
            return '';
        }

        $number = (int) $decimalStr;
        if ($number === 0) {
            return '';
        }

        return $this->terbilangBeforeComma((string) $number) . ' Sen';
    }
}
