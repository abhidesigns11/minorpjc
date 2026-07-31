<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Customer | InvoiceFlex</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/customer.css">
</head>
<body>
    <div class="container mt-50">
        <div class="card">
            <h3>Add New Customer</h3>
            <form action="customer.php" method="POST">
                <div class="form-grid">
                    <div class="field full"><label>Company/Customer Name</label><input type="text" name="cust_name" required></div>
                    <div class="field"><label>GSTIN</label><input type="text" name="cust_gst"></div>
                    <div class="field"><label>PAN No.</label><input type="text" name="cust_pan"></div>
                    <div class="field"><label>State Code</label><input type="text" name="cust_state" value="24"></div>
                    <div class="field full"><label>Address</label><textarea name="cust_addr" rows="3"></textarea></div>
                </div>
                <div class="form-btns">
                    <a href="customer.php" class="btn-sec">Cancel</a>
                    <button type="submit" class="btn-pri">Save Customer</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>