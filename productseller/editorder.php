<?php
// Ensure sessions are started at the very beginning
session_start();

// Include necessary files
include 'includes/config.php';
include 'includes/dbConnection.php';
include 'includes/functions.php';

$orderData = null;
$errors = [];
$productsellerData = [];

// Fetch productseller data for the dropdown
if ($conn) {
    $sql_productseller = "SELECT product_type FROM productseller GROUP BY product_type";
    $result_productseller = $conn->query($sql_productseller);
    if ($result_productseller) {
        while ($row = $result_productseller->fetch_assoc()) {
            $productsellerData[] = $row;
        }
    } else {
        $_SESSION['error_message'] = "Database error fetching product types: " . $conn->error;
    }
}

// Handle GET request to fetch data for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $order_id = sanitize($_GET['id']);
    
    // Prepare SQL to prevent injection
    $sql_fetch = "SELECT order_id, date_time, customer_name, product_type, quantity FROM orders WHERE order_id = ?";
    if ($stmt_fetch = $conn->prepare($sql_fetch)) {
        $stmt_fetch->bind_param("i", $order_id);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        
        if ($result_fetch->num_rows === 1) {
            $orderData = $result_fetch->fetch_assoc();
        } else {
            $_SESSION['error_message'] = "Order entry not found.";
            header("Location: orders-dashboard.php");
            exit();
        }
        $stmt_fetch->close();
    } else {
        $_SESSION['error_message'] = "Database error fetching record: " . $conn->error;
        header("Location: orders-dashboard.php");
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle POST request to update the record
    $order_id = sanitize($_POST['id']);
    $customer_name = sanitize($_POST['customer_name']);
    $product_type = sanitize($_POST['productType']); // Changed from product_type to productType to match HTML
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_FLOAT);
    $dateTime = sanitize($_POST['date_time']);

    // Validation
    if (empty($order_id) || !is_numeric($order_id)) {
        $errors[] = "Invalid order entry ID.";
    }
    if (empty($customer_name)) {
        $errors[] = "Customer name is required.";
    }
    if (empty($product_type)) {
        $errors[] = "Product Type is required.";
    }
    if ($quantity === false || $quantity <= 0) {
        $errors[] = "Quantity must be a positive number.";
    }
    if (empty($dateTime)) {
        $errors[] = "Date & Time are required.";
    }

    if (empty($errors)) {
        // Prepare SQL for update
        $sql_update = "UPDATE orders SET date_time = ?, customer_name = ?, product_type = ?, quantity = ? WHERE order_id = ?";
        if ($stmt_update = $conn->prepare($sql_update)) {
            // Corrected bind_param string: sssdi
            $stmt_update->bind_param("sssdi", $dateTime, $customer_name, $product_type, $quantity, $order_id);
            if ($stmt_update->execute()) {
                if ($stmt_update->affected_rows > 0) {
                    $_SESSION['success_message'] = "Order entry updated successfully.";
                } else {
                    $_SESSION['info_message'] = "No changes were made to the order entry.";
                }
                header("Location: orders-dashboard.php");
                exit();
            } else {
                $errors[] = "Error updating order entry: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $errors[] = "Database error preparing update statement: " . $conn->error;
        }
    }

    // If there were errors, re-populate form fields with submitted data
    $orderData = [
        'order_id' => $order_id,
        'customer_name' => $customer_name,
        'product_type' => $product_type, // Changed to reflect new form field name
        'quantity' => $_POST['quantity'],
        'date_time' => $dateTime
    ];
} else {
    // Redirect for invalid requests
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: orders-dashboard.php");
    exit();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order Entry - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/styles3.css">
</head>
<body>
    <div class="app-container">
        <main class="main-content">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class=" card-custom p-4">
                            <h2 class="card-title text-center">Edit Order Entry</h2>
                            <?php if (!empty($errors)): ?>
                                <div class="a" role="alert">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <?php if ($orderData): ?>
                                <form action="editorder.php" method="POST">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($orderData['order_id']) ?>">
                                    <div class="mb-3">
                                        <label for="date_time" class="form-label text-white">Date & Time</label>
                                        <input type="datetime-local" class="form-select bg-dark text-white border-secondary" id="date_time" name="date_time" value="<?= htmlspecialchars($orderData['date_time']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="customer_name" class="form-label text-white">Customer Name</label>
                                        <input type="text" class="form-select bg-dark text-white border-secondary" id="customer_name" name="customer_name" value="<?= htmlspecialchars($orderData['customer_name']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="productType" class="form-label text-white">Meat Type <span class="text-danger">*</span></label>
                                        <select class="form-select bg-dark text-white border-secondary" id="productType" name="productType" required>
                                            <option value="">Select Meat Type</option>
                                            <option value="Beef" <?= ($orderData['product_type'] === 'Beef') ? 'selected' : '' ?>>Beef</option>
                                            <option value="Pork" <?= ($orderData['product_type'] === 'Pork') ? 'selected' : '' ?>>Pork</option>
                                            <option value="Poultry" <?= ($orderData['product_type'] === 'Poultry') ? 'selected' : '' ?>>Poultry</option>
                                            <option value="Lamb" <?= ($orderData['product_type'] === 'Lamb') ? 'selected' : '' ?>>Lamb</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label text-white">Quantity</label>
                                        <input type="number" step="0.01" class="form-select bg-dark text-white border-secondary" id="quantity" name="quantity" value="<?= htmlspecialchars($orderData['quantity']) ?>" required min="0.01">
                                    </div>
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-primary">Update</button>
                                        <a href="orders-dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                                    </div>
                                </form>
                            <?php else: ?>
                                <p class="">Order entry not found or invalid request.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>
