<?php
// Data Logic
$inv_no = $_POST['inv_no'];
$inv_date = date("d.m.Y", strtotime($_POST['inv_date']));
$rcv_name = strtoupper($_POST['rcv_name']);
$rcv_addr = $_POST['rcv_addr'];
$prod_name = strtoupper($_POST['prod_name']);
$qty = (float)$_POST['qty'];
$rate = (float)$_POST['rate'];
$total = $qty * $rate;
$gst_val = ($total * (float)$_POST['gst_rate']) / 100;
$half_gst = $gst_val / 2;
$grand_total = $total + $gst_val;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Anuyog Engineering - Tax Invoice</title>
    <link rel="stylesheet" href="assets/css/invoice-preview.css">
</head>
<body>
    <div class="print-tools">
        <button onclick="window.print()">Print This Invoice</button>
        <a href="invoice-create.php">Edit Data</a>
    </div>

    <div class="page-a4">
        <!-- HEADER -->
        <div class="header-top">
            <div class="brand-section">
                <div class="ae-logo">A</div>
                <div class="company-name">ANUYOG ENGINEERING</div>
            </div>
            <div class="copy-types">
                <div class="copy-box">Original For recipient</div>
                <div class="copy-box">Duplicate For Supplier</div>
                <div class="copy-box">Triplicate For Supplier</div>
            </div>
        </div>

        <div class="address-line">
            Address: Plot No. 925, Opp. Swaraj Eng. Compound B/H Aarti Ind., 4th Phase, G.I.D.C., Vapi - 396 195<br>
            (M): +91 8141015605, +91 7046778251, Email: anuyog.engineering@gmail.com
        </div>

        <!-- MAIN DATA TABLE -->
        <table class="tax-invoice-table">
            <tr>
                <td colspan="4" class="bold">GSTIN: 24IVTPP6141P1ZJ</td>
                <td colspan="2" class="text-center bold bg-gray">Tax Invoice</td>
            </tr>
            <tr>
                <td width="20%">Invoice No.</td><td width="30%"><?php echo $inv_no; ?></td>
                <td width="20%">PO No.</td><td><?php echo $_POST['po_no']; ?></td>
            </tr>
            <tr>
                <td>Invoice Date.</td><td><?php echo $inv_date; ?></td>
                <td>PO Date.</td><td><?php echo $_POST['po_date']; ?></td>
            </tr>
            <tr>
                <td>State</td><td>Gujarat</td>
                <td>Vehicle Number</td><td><?php echo $_POST['veh_no']; ?></td>
            </tr>
            <tr>
                <td>State Code</td><td><?php echo $_POST['state_code']; ?></td>
                <td>E-WAY Bill No.</td><td>-</td>
            </tr>

            <!-- RECEIVER SECTION -->
            <tr class="bg-light-gold">
                <td colspan="4" class="bold">Details of Receiver / billed to:</td>
                <td colspan="2" class="bold text-right">PAN No : <?php echo $_POST['rcv_pan']; ?></td>
            </tr>
            <tr>
                <td colspan="6" class="receiver-details">
                    Name: <strong><?php echo $rcv_name; ?></strong><br>
                    Address: <?php echo $rcv_addr; ?><br>
                    GSTIN: <?php echo $_POST['rcv_gst']; ?><br>
                    State: Gujarat
                </td>
            </tr>

            <!-- PRODUCT TABLE HEADER -->
            <tr class="gold-header text-center">
                <td width="5%">Sr. No</td>
                <td width="50%">Name of Product</td>
                <td width="10%">HSN</td>
                <td width="10%">Qty.</td>
                <td width="10%">Rate</td>
                <td width="15%">Total</td>
            </tr>
            
            <!-- PRODUCT ROW -->
            <tr class="product-row">
                <td class="text-center">1}</td>
                <td><?php echo $prod_name; ?></td>
                <td class="text-center"><?php echo $_POST['hsn']; ?></td>
                <td class="text-center"><?php echo $qty; ?></td>
                <td class="text-right"><?php echo number_format($rate, 2); ?></td>
                <td class="text-right"><?php echo number_format($total, 2); ?></td>
            </tr>

            <!-- BLANK SPACE -->
            <tr style="height: 300px;">
                <td class="border-v"></td><td class="border-v"></td><td class="border-v"></td><td class="border-v"></td><td class="border-v"></td><td class="border-v"></td>
            </tr>

            <!-- BANK & TOTALS -->
            <tr>
                <td colspan="4" class="no-border-bottom"><strong>Company Bank Details</strong></td>
                <td class="bold">Total Amount</td>
                <td class="text-right bold"><?php echo number_format($total, 2); ?></td>
            </tr>
            <tr>
                <td colspan="4" class="no-border-v no-border-bottom">Bank Name : PUNJAB NATIONAL BANK</td>
                <td>Add : CGST</td>
                <td class="text-right"><?php echo number_format($half_gst, 2); ?></td>
            </tr>
            <tr>
                <td colspan="4" class="no-border-v no-border-bottom">A/c No. : 0008302100001876</td>
                <td>Add : SGST</td>
                <td class="text-right"><?php echo number_format($half_gst, 2); ?></td>
            </tr>
            <tr>
                <td colspan="4" class="no-border-v">Branch & IFSC Code : SILVASSA ROAD & PUNB0000830</td>
                <td>Add : IGST</td>
                <td class="text-right">-</td>
            </tr>
            <tr class="bg-gray">
                <td colspan="4">Total Invoice Amount in Words : <span id="words"></span></td>
                <td class="bold">Grand Amount</td>
                <td class="text-right bold">₹<?php echo number_format($grand_total, 2); ?></td>
            </tr>
        </table>

        <!-- SIGNATURE FOOTER -->
        <div class="footer-sig">
            <div class="terms">
                <strong>Terms & Conditions :</strong><br>
                1. 50% advance balance payment against delivery...<br>
                2. 18% GST extra<br>
                3. Transport extra
            </div>
            <div class="stamp-box">Stamp</div>
            <div class="signature">
                For, Anuyog Engineering<br><br><br><br>
                Authorised Signatory
            </div>
        </div>
    </div>
</body>
</html>