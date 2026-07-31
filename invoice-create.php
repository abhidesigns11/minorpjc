<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// XAMPP Connection
$conn = mysqli_connect("localhost", "root", "", "invoice_db");
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];
$error = "";

// Fetch Company Profile for Seller State Code
$company_query = mysqli_query($conn, "SELECT * FROM company WHERE user_id='$user_id'");
$company = mysqli_fetch_assoc($company_query);

// Fallback GST State Code from Company GSTIN (first 2 digits)
$seller_state_code = "24"; 
if (!empty($company['gst_number']) && strlen($company['gst_number']) >= 2) {
    $seller_state_code = substr($company['gst_number'], 0, 2);
}

// Fetch Customer List
$customers_query = mysqli_query($conn, "SELECT * FROM customers WHERE user_id='$user_id'");

// Save Invoice Handling
if (isset($_POST['save_invoice'])) {
    $customer_id    = intval($_POST['customer_id']);
    $invoice_date   = $_POST['invoice_date'];
    $due_date       = $_POST['due_date'];
    $subtotal       = floatval($_POST['subtotal']);
    $cgst_rate      = floatval($_POST['cgst_rate']);
    $cgst_amount    = floatval($_POST['cgst_amount']);
    $sgst_rate      = floatval($_POST['sgst_rate']);
    $sgst_amount    = floatval($_POST['sgst_amount']);
    $igst_rate      = floatval($_POST['igst_rate']);
    $igst_amount    = floatval($_POST['igst_amount']);
    $total_amount   = floatval($_POST['total_amount']);

    // Fetch details for selected customer
    $cust_data = mysqli_query($conn, "SELECT * FROM customers WHERE id='$customer_id'");
    $cust = mysqli_fetch_assoc($cust_data);

    if (!$cust) {
        $error = "Please select a valid customer.";
    } else {
        $cust_name    = mysqli_real_escape_string($conn, $cust['name']);
        $cust_gstin   = mysqli_real_escape_string($conn, $cust['gst_number']);
        $cust_address = mysqli_real_escape_string($conn, $cust['address']);
        $place_supply = mysqli_real_escape_string($conn, $cust['state']);

        // Insert Invoice Master
        $insert_inv = "INSERT INTO invoices (user_id, customer_id, customer_name, customer_gstin, customer_address, invoice_date, due_date, place_of_supply, subtotal, cgst_rate, cgst_amount, sgst_rate, sgst_amount, igst_rate, igst_amount, total_amount, status) 
                       VALUES ('$user_id', '$customer_id', '$cust_name', '$cust_gstin', '$cust_address', '$invoice_date', '$due_date', '$place_supply', '$subtotal', '$cgst_rate', '$cgst_amount', '$sgst_rate', '$sgst_amount', '$igst_rate', '$igst_amount', '$total_amount', 'Pending')";

        if (mysqli_query($conn, $insert_inv)) {
            $invoice_id = mysqli_insert_id($conn);

            // Insert Items
            $descriptions = $_POST['item_description'];
            $hsn_codes    = $_POST['hsn_sac'];
            $quantities   = $_POST['quantity'];
            $rates        = $_POST['rate'];
            $amounts      = $_POST['amount'];

            for ($i = 0; $i < count($descriptions); $i++) {
                $desc   = mysqli_real_escape_string($conn, $descriptions[$i]);
                $hsn    = mysqli_real_escape_string($conn, $hsn_codes[$i]);
                $qty    = intval($quantities[$i]);
                $rate   = floatval($rates[$i]);
                $amt    = floatval($amounts[$i]);

                if ($desc != "" && $qty > 0) {
                    mysqli_query($conn, "INSERT INTO invoice_items (invoice_id, item_description, hsn_sac, quantity, rate, amount) 
                                         VALUES ('$invoice_id', '$desc', '$hsn', '$qty', '$rate', '$amt')");
                }
            }

            header("Location: invoices.php");
            exit();
        } else {
            $error = "Failed to save invoice: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Invoice - Billing System</title>
    <!-- Linked to assets/css/invoice-create.css -->
    <link rel="stylesheet" href="assets/css/invoice-create.css">
</head>
<body>

    <!-- Drawer Navigation -->
    <input type="checkbox" id="menu-toggle">
    <label for="menu-toggle" class="overlay"></label>

    <div class="sidebar">
        <h3><?php echo htmlspecialchars($company['company_name'] ?? 'Company'); ?></h3>
        <a href="dashboard.php">Dashboard</a>
        <a href="customer.php">Customers</a>
        <a href="invoices.php" class="active">Monthly Invoices</a>
        <a href="profile.php">Company Profile</a>
        <a href="logout.php" class="logout-link">Logout</a>
    </div>

    <!-- Header -->
    <div class="navbar">
        <div class="nav-left">
            <label for="menu-toggle" class="hamburger-btn">&#9776;</label>
            <span class="brand-title">Create Tax Invoice</span>
        </div>
    </div>

    <!-- Main Form Container -->
    <div class="container">
        <div class="card">
            <h2>Create New Invoice</h2>

            <?php if ($error != ""): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="invoice-create.php" id="invoiceForm">
                
                <!-- Seller State Code (Hidden for tax calculation) -->
                <input type="hidden" id="sellerStateCode" value="<?php echo $seller_state_code; ?>">

                <div class="form-grid">
                    <div>
                        <label>Select Customer:</label>
                        <select name="customer_id" id="customerSelect" onchange="updateTaxCalculation()" required>
                            <option value="">-- Choose Customer --</option>
                            <?php while ($cust = mysqli_fetch_assoc($customers_query)): ?>
                                <option value="<?php echo $cust['id']; ?>" 
                                        data-statecode="<?php echo htmlspecialchars($cust['state_code']); ?>">
                                    <?php echo htmlspecialchars($cust['name']); ?> (State Code: <?php echo htmlspecialchars($cust['state_code']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label>Invoice Date:</label>
                        <input type="date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div>
                        <label>Payment Due Date:</label>
                        <input type="date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>" required>
                    </div>
                </div>

                <!-- Items Dynamic Table -->
                <h3>Bill Items</h3>
                <table class="items-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Description</th>
                            <th style="width: 15%;">HSN/SAC</th>
                            <th style="width: 12%;">Qty</th>
                            <th style="width: 15%;">Rate (₹)</th>
                            <th style="width: 15%;">Amount (₹)</th>
                            <th style="width: 3%;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr>
                            <td><input type="text" name="item_description[]" placeholder="Item/Service details" required></td>
                            <td><input type="text" name="hsn_sac[]" placeholder="8471"></td>
                            <td><input type="number" name="quantity[]" class="qty" value="1" min="1" oninput="calculateRow(this)" required></td>
                            <td><input type="number" step="0.01" name="rate[]" class="rate" value="0.00" oninput="calculateRow(this)" required></td>
                            <td><input type="number" step="0.01" name="amount[]" class="amt" value="0.00" readonly></td>
                            <td><button type="button" class="btn-remove" onclick="removeRow(this)">&times;</button></td>
                        </tr>
                    </tbody>
                </table>

                <button type="button" class="btn-add-item" onclick="addRow()">+ Add Line Item</button>

                <!-- Calculation Summary Section -->
                <div class="summary-box">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>₹<span id="displaySubtotal">0.00</span></span>
                    </div>

                    <div class="summary-row" id="cgstRow">
                        <span>CGST (9%): <span class="tax-badge">Intrastate</span></span>
                        <span>₹<span id="displayCGST">0.00</span></span>
                    </div>

                    <div class="summary-row" id="sgstRow">
                        <span>SGST (9%): <span class="tax-badge">Intrastate</span></span>
                        <span>₹<span id="displaySGST">0.00</span></span>
                    </div>

                    <div class="summary-row" id="igstRow" style="display: none;">
                        <span>IGST (18%): <span class="tax-badge">Interstate</span></span>
                        <span>₹<span id="displayIGST">0.00</span></span>
                    </div>

                    <div class="summary-row total">
                        <span>Grand Total:</span>
                        <span>₹<span id="displayTotal">0.00</span></span>
                    </div>

                    <!-- Hidden Fields for Database -->
                    <input type="hidden" name="subtotal" id="inputSubtotal" value="0.00">
                    <input type="hidden" name="cgst_rate" id="inputCGSTRate" value="9">
                    <input type="hidden" name="cgst_amount" id="inputCGST" value="0.00">
                    <input type="hidden" name="sgst_rate" id="inputSGSTRate" value="9">
                    <input type="hidden" name="sgst_amount" id="inputSGST" value="0.00">
                    <input type="hidden" name="igst_rate" id="inputIGSTRate" value="0">
                    <input type="hidden" name="igst_amount" id="inputIGST" value="0.00">
                    <input type="hidden" name="total_amount" id="inputTotal" value="0.00">
                </div>

                <button type="submit" name="save_invoice" class="btn-save">Save & Generate Invoice</button>
            </form>
        </div>
    </div>

    <!-- JavaScript for Dynamic Calculation -->
    <script>
        function calculateRow(element) {
            let row = element.closest('tr');
            let qty = parseFloat(row.querySelector('.qty').value) || 0;
            let rate = parseFloat(row.querySelector('.rate').value) || 0;
            let amount = qty * rate;
            
            row.querySelector('.amt').value = amount.toFixed(2);
            updateTaxCalculation();
        }

        function addRow() {
            let tbody = document.getElementById('itemsBody');
            let tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="text" name="item_description[]" placeholder="Item/Service details" required></td>
                <td><input type="text" name="hsn_sac[]" placeholder="8471"></td>
                <td><input type="number" name="quantity[]" class="qty" value="1" min="1" oninput="calculateRow(this)" required></td>
                <td><input type="number" step="0.01" name="rate[]" class="rate" value="0.00" oninput="calculateRow(this)" required></td>
                <td><input type="number" step="0.01" name="amount[]" class="amt" value="0.00" readonly></td>
                <td><button type="button" class="btn-remove" onclick="removeRow(this)">&times;</button></td>
            `;
            tbody.appendChild(tr);
        }

        function removeRow(btn) {
            let tbody = document.getElementById('itemsBody');
            if (tbody.rows.length > 1) {
                btn.closest('tr').remove();
                updateTaxCalculation();
            } else {
                alert("At least one item line is required.");
            }
        }

        function updateTaxCalculation() {
            let amounts = document.querySelectorAll('.amt');
            let subtotal = 0;

            amounts.forEach(amt => {
                subtotal += parseFloat(amt.value) || 0;
            });

            let sellerState = document.getElementById('sellerStateCode').value;
            let customerSelect = document.getElementById('customerSelect');
            let selectedOption = customerSelect.options[customerSelect.selectedIndex];
            let customerState = selectedOption ? selectedOption.getAttribute('data-statecode') : sellerState;

            let cgstAmt = 0, sgstAmt = 0, igstAmt = 0;

            if (!customerState || customerState === sellerState) {
                // Same State -> CGST (9%) + SGST (9%)
                document.getElementById('cgstRow').style.display = 'flex';
                document.getElementById('sgstRow').style.display = 'flex';
                document.getElementById('igstRow').style.display = 'none';

                cgstAmt = subtotal * 0.09;
                sgstAmt = subtotal * 0.09;

                document.getElementById('inputCGSTRate').value = "9";
                document.getElementById('inputSGSTRate').value = "9";
                document.getElementById('inputIGSTRate').value = "0";
            } else {
                // Different State -> IGST (18%)
                document.getElementById('cgstRow').style.display = 'none';
                document.getElementById('sgstRow').style.display = 'none';
                document.getElementById('igstRow').style.display = 'flex';

                igstAmt = subtotal * 0.18;

                document.getElementById('inputCGSTRate').value = "0";
                document.getElementById('inputSGSTRate').value = "0";
                document.getElementById('inputIGSTRate').value = "18";
            }

            let total = subtotal + cgstAmt + sgstAmt + igstAmt;

            // Display UI updates
            document.getElementById('displaySubtotal').innerText = subtotal.toFixed(2);
            document.getElementById('displayCGST').innerText = cgstAmt.toFixed(2);
            document.getElementById('displaySGST').innerText = sgstAmt.toFixed(2);
            document.getElementById('displayIGST').innerText = igstAmt.toFixed(2);
            document.getElementById('displayTotal').innerText = total.toFixed(2);

            // Set Form Hidden Inputs
            document.getElementById('inputSubtotal').value = subtotal.toFixed(2);
            document.getElementById('inputCGST').value = cgstAmt.toFixed(2);
            document.getElementById('inputSGST').value = sgstAmt.toFixed(2);
            document.getElementById('inputIGST').value = igstAmt.toFixed(2);
            document.getElementById('inputTotal').value = total.toFixed(2);
        }
    </script>
</body>
</html>