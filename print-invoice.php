<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: invoices.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "invoice_db");
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];
$invoice_id = intval($_GET['id']);

// Fetch Company & Profile Details
$company_query = mysqli_query($conn, "SELECT * FROM company WHERE user_id='$user_id'");
$company = mysqli_fetch_assoc($company_query) ?? [];

$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'");
$user_data = mysqli_fetch_assoc($user_query) ?? [];

// Logo Path Setup
$logo_path = "";
if (!empty($company['logo']) && file_exists("uploads/" . $company['logo'])) {
    $logo_path = "uploads/" . $company['logo'];
} elseif (!empty($user_data['profile_img']) && file_exists("uploads/" . $user_data['profile_img'])) {
    $logo_path = "uploads/" . $user_data['profile_img'];
} elseif (file_exists("uploads/profile.jpg")) {
    $logo_path = "uploads/profile.jpg";
} else {
    $logo_path = "assets/images/logo.png";
}

// Fetch Invoice Details
$inv_query = mysqli_query($conn, "SELECT * FROM invoices WHERE id='$invoice_id' AND user_id='$user_id'");
$invoice = mysqli_fetch_assoc($inv_query);

if (!$invoice) {
    die("Invoice not found or access denied.");
}

$items_query = mysqli_query($conn, "SELECT * FROM invoice_items WHERE invoice_id='$invoice_id'");

function getAmountInWords($number) {
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        0 => '', 1 => 'ONE', 2 => 'TWO', 3 => 'THREE', 4 => 'FOUR', 5 => 'FIVE', 
        6 => 'SIX', 7 => 'SEVEN', 8 => 'EIGHT', 9 => 'NINE', 10 => 'TEN', 
        11 => 'ELEVEN', 12 => 'TWELVE', 13 => 'THIRTEEN', 14 => 'FOURTEEN', 
        15 => 'FIFTEEN', 16 => 'SIXTEEN', 17 => 'SEVENTEEN', 18 => 'EIGHTEEN', 
        19 => 'NINETEEN', 20 => 'TWENTY', 30 => 'THIRTY', 40 => 'FORTY', 
        50 => 'FIFTY', 60 => 'SIXTY', 70 => 'SEVENTY', 80 => 'EIGHTY', 90 => 'NINETY'
    );
    $digits = array('', 'HUNDRED','THOUSAND','LAKH','CRORE');
    while ($i < $digits_length) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 'S' : null;
            $hundred = ($counter == 1 && count($str) > 0 && $str[0]) ? ' AND ' : null;
            $str [] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
        } else $str[] = null;
    }
    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal > 0) ? " AND " . ($words[floor($decimal / 10) * 10] . " " . $words[$decimal % 10]) . ' PAISE' : '';
    return ($Rupees ? $Rupees . 'RUPEES ' : '') . $paise . ' ONLY';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice - AE<?php echo sprintf('%03d', $invoice['id']); ?></title>
    <link rel="stylesheet" href="assets/css/print-invoice.css">
</head>
<body>

    <div class="action-bar">
        <a href="invoices.php" class="btn-back">&larr; Back to Invoices</a>
        <button onclick="window.print()" class="btn-print-now">&#128438; Print / Save as PDF</button>
    </div>

    <div class="invoice-paper">
        
        <div class="top-section">
            <!-- Header Container with Logo -->
            <div class="header-container">
                <div class="header-logo-title">
                    <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
                        <img src="<?php echo htmlspecialchars($logo_path); ?>" class="company-logo" alt="Profile Logo">
                    <?php endif; ?>

                    <div class="header-title"><?php echo strtoupper(htmlspecialchars($company['company_name'] ?? 'ANUYOG ENGINEERING')); ?></div>
                </div>

                <div class="copy-type-box">
                    <div><input type="checkbox" checked> Original For Recipient</div>
                    <div><input type="checkbox"> Duplicate For Supplier</div>
                    <div><input type="checkbox"> Triplicate For Supplier</div>
                </div>
            </div>

            <!-- Address Block -->
            <div class="header-address">
                <strong>Address:</strong> <?php echo htmlspecialchars($company['address'] ?? 'Plot No. 925, Opp. Swaraj Eng. Compound B/H Aarti Ind., 4th Phase, G.I.D.C., Vapi - 396 195'); ?><br>
                <strong>(M):</strong> +91 8141015605, +91 7046778251, <strong>Email:</strong> anuyog.engineering@gmail.com
            </div>

            <!-- SECTION 1: GSTIN & Invoice Metadata Block -->
            <table class="section-block">
                <tr>
                    <td colspan="2" style="width: 65%;" class="text-bold">GSTIN: <?php echo htmlspecialchars($company['gst_number'] ?? '24IVTOP6141P1ZJ'); ?></td>
                    <td colspan="2" style="width: 35%; text-align: center;" class="text-bold bg-header-gold">Tax Invoice</td>
                </tr>
                <tr>
                    <td style="width: 15%;" class="text-bold">Invoice No.</td>
                    <td style="width: 50%;">AE<?php echo sprintf('%03d', $invoice['id']); ?>/<?php echo date('Y', strtotime($invoice['invoice_date'] ?? 'now')); ?>-<?php echo date('Y', strtotime($invoice['invoice_date'] ?? 'now'))+1; ?></td>
                    <td style="width: 15%;" class="text-bold">PO No.</td>
                    <td style="width: 20%;">-</td>
                </tr>
                <tr>
                    <td class="text-bold">Invoice Date.</td>
                    <td><?php echo date('d.m.Y', strtotime($invoice['invoice_date'] ?? 'now')); ?></td>
                    <td class="text-bold">PO Date.</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="text-bold">State</td>
                    <td>Gujarat</td>
                    <td class="text-bold">Vehicle Number</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="text-bold">State Code</td>
                    <td>24</td>
                    <td class="text-bold">E-WAY Bill No.</td>
                    <td>-</td>
                </tr>
            </table>

            <!-- SECTION 2: Receiver Details Block -->
            <table class="section-block">
                <tr>
                    <td style="width: 65%;" class="text-bold">Details of Receiver / billed to:</td>
                    <td style="width: 35%;" class="text-bold text-right">PAN No : <?php echo htmlspecialchars($company['pan_number'] ?? 'IVTOP6141P'); ?></td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 15%; border-left: none; border-top: none;" class="text-bold">Name</td>
                                <td style="width: 85%; border-right: none; border-top: none;" class="text-bold"><?php echo strtoupper(htmlspecialchars($invoice['customer_name'] ?? '')); ?></td>
                            </tr>
                            <tr>
                                <td style="border-left: none;" class="text-bold">Address</td>
                                <td style="border-right: none;"><?php echo htmlspecialchars($invoice['customer_address'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td style="border-left: none;" class="text-bold">GSTIN</td>
                                <td style="border-right: none;"><?php echo htmlspecialchars($invoice['customer_gstin'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td style="border-left: none; border-bottom: none;" class="text-bold">State</td>
                                <td style="border-right: none; border-bottom: none;"><?php echo htmlspecialchars($invoice['place_of_supply'] ?? ''); ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- SECTION 3: Dynamic Product Table Block -->
        <div class="product-wrapper">
            <table class="product-block">
                <thead>
                    <tr>
                        <th style="width: 8%;">Sr. No</th>
                        <th style="width: 47%;">Name of Product</th>
                        <th style="width: 13%;">HSN</th>
                        <th style="width: 8%;">Qty.</th>
                        <th style="width: 12%;">Rate</th>
                        <th style="width: 12%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sr = 1;
                    if ($items_query && mysqli_num_rows($items_query) > 0):
                        while ($item = mysqli_fetch_assoc($items_query)): 
                            $desc_lines = explode("\n", trim($item['item_description'] ?? ''));
                            $main_title = $desc_lines[0] ?? '';
                            $sub_details = implode("\n", array_slice($desc_lines, 1));
                        ?>
                            <tr style="height: 35px;">
                                <td class="text-center text-bold"><?php echo $sr++; ?>}</td>
                                <td class="text-bold">
                                    <?php echo strtoupper(htmlspecialchars($main_title)); ?>
                                    <?php if (!empty($sub_details)): ?>
                                        <div class="item-subtitle"><?php echo htmlspecialchars($sub_details); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo htmlspecialchars($item['hsn_sac'] ?? ''); ?></td>
                                <td class="text-center"><?php echo $item['quantity'] ?? 0; ?></td>
                                <td class="text-right"><?php echo number_format($item['rate'] ?? 0, 2); ?></td>
                                <td class="text-right"><?php echo number_format($item['amount'] ?? 0, 2); ?></td>
                            </tr>
                        <?php endwhile; 
                    endif; ?>
                    
                    <!-- Flexible Middle Space Filler -->
                    <tr class="stretch-row">
                        <td></td><td></td><td></td><td></td><td></td><td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- SECTION 4: Bank Details & Totals Block -->
        <div class="bottom-section">
            <table class="section-block" style="margin-bottom: 0;">
                <tr>
                    <td style="width: 65%;" class="text-bold bg-header-gold">Company Bank Details</td>
                    <td style="width: 20%;" class="text-bold">Total Amount</td>
                    <td style="width: 15%;" class="text-right text-bold"><?php echo number_format($invoice['subtotal'] ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <td><strong>Bank Name :</strong> PUNJAB NATIONAL BANK</td>
                    <td class="text-normal">Add : CGST</td>
                    <td class="text-right text-normal"><?php echo ($invoice['cgst_amount'] ?? 0) > 0 ? number_format($invoice['cgst_amount'], 2) : '-'; ?></td>
                </tr>
                <tr>
                    <td><strong>A/c No. :</strong> 0008302100011876</td>
                    <td class="text-normal">Add : SGST</td>
                    <td class="text-right text-normal"><?php echo ($invoice['sgst_amount'] ?? 0) > 0 ? number_format($invoice['sgst_amount'], 2) : '-'; ?></td>
                </tr>
                <tr>
                    <td><strong>Branch & IFSC Code :</strong> SILVASSA ROAD & PUNB0000830</td>
                    <td class="text-normal">Add : IGST</td>
                    <td class="text-right text-normal"><?php echo ($invoice['igst_amount'] ?? 0) > 0 ? number_format($invoice['igst_amount'], 2) : '-'; ?></td>
                </tr>
                <tr>
                    <td class="text-bold" style="font-size: 9.5px;">
                        Total Invoice Amount in Words : <?php echo getAmountInWords($invoice['total_amount'] ?? 0); ?>
                    </td>
                    <td class="text-bold bg-header-gold">Grand Amount</td>
                    <td class="text-right text-bold bg-header-gold"><?php echo number_format($invoice['total_amount'] ?? 0, 2); ?></td>
                </tr>
            </table>

            <!-- SECTION 5: Footer & Signatures Block -->
            <table class="footer-block">
                <tr>
                    <td style="width: 45%;" class="terms-text">
                        <strong>Terms & Conditions :</strong><br>
                        1. 50% advance balance payment against delivery & after delivery payment should be done within 5 days<br>
                        2. 18% GST extra<br>
                        3. Transport extra
                    </td>
                    <td style="width: 25%; text-align: center; vertical-align: bottom;">
                        RECEIVER'S SIGN AND STAMP
                    </td>
                    <td style="width: 30%; text-align: center; vertical-align: bottom;">
                        For, <?php echo htmlspecialchars($company['company_name'] ?? 'Anuyog Engineering'); ?><br><br><br>
                        <span style="font-size: 9px; border-top: 1px dashed #15355f; padding-top: 2px;">Authorised Signatory</span>
                    </td>
                </tr>
            </table>
        </div>

    </div>

</body>
</html>