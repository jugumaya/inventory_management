<?php
include 'includes/config.php';
include 'includes/dbConnection.php';
include 'includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    // Validate and sanitize input
    $type = sanitize($_POST['type']);
    $batch = sanitize($_POST['batch']);
    $quantity = (float) $_POST['quantity'];
    $supplier = sanitize($_POST['supplier']);
    $cost = (float) $_POST['cost'];
    $processingDate = sanitize($_POST['processingDate']);
    $expirationDate = sanitize($_POST['expirationDate']);
    $location = sanitize($_POST['location']);
    
    // Calculate the total cost
    $totalCost = $quantity * $cost;

    // Basic validation
    $errors = [];
    if (empty($type)) $errors[] = "Meat type is required.";
    if (empty($batch)) $errors[] = "Batch number is required.";
    if ($quantity <= 0) $errors[] = "Quantity must be greater than zero.";
    if (empty($supplier)) $errors[] = "Supplier is required.";
    if ($cost <= 0) $errors[] = "Cost must be greater than zero.";
    if (empty($processingDate)) $errors[] = "Processing date is required.";
    if (empty($expirationDate)) $errors[] = "Expiration date is required.";
    
    if (empty($errors)) {
        // Updated SQL query to include total_cost
        $sql = "INSERT INTO inventory1 (meat_type, batch_number, quantity, supplier, cost, total_cost, processing_date, expiration_date, location) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // Updated bind_param to include the new 'totalCost' field (d for double)
        $stmt->bind_param("ssdssssss", $type, $batch, $quantity, $supplier, $cost, $totalCost, $processingDate, $expirationDate, $location);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "New stock added successfully!";
            header("Location: add_stock.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error adding stock: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = implode("<br>", $errors);
    }
}

// 2. UPDATE Operation: Handle form submission for editing stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    // Validate and sanitize input
    $id = (int) $_POST['stock_id'];
    $type = sanitize($_POST['type']);
    $batch = sanitize($_POST['batch']);
    $quantity = (float) $_POST['quantity'];
    $supplier = sanitize($_POST['supplier']);
    $cost = (float) $_POST['cost'];
    $processingDate = sanitize($_POST['processingDate']);
    $expirationDate = sanitize($_POST['expirationDate']);
    $location = sanitize($_POST['location']);
    
    // Calculate the total cost for the update
    $totalCost = $quantity * $cost;

    $errors = [];
    if ($id <= 0) $errors[] = "Invalid stock ID.";
    if (empty($type)) $errors[] = "Meat type is required.";
    if (empty($batch)) $errors[] = "Batch number is required.";
    if ($quantity <= 0) $errors[] = "Quantity must be greater than zero.";
    if (empty($supplier)) $errors[] = "Supplier is required.";
    if ($cost <= 0) $errors[] = "Cost must be greater than zero.";
    if (empty($processingDate)) $errors[] = "Processing date is required.";
    if (empty($expirationDate)) $errors[] = "Expiration date is required.";

    if (empty($errors)) {
        // Updated SQL query to include total_cost
        $sql = "UPDATE inventory1 SET meat_type=?, batch_number=?, quantity=?, supplier=?, cost=?, total_cost=?, processing_date=?, expiration_date=?, location=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        // Updated bind_param to include the new 'totalCost' field (d for double)
        $stmt->bind_param("ssdssssssi", $type, $batch, $quantity, $supplier, $cost, $totalCost, $processingDate, $expirationDate, $location, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Stock updated successfully!";
            header("Location: add_stock.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error updating stock: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = implode("<br>", $errors);
    }
}

// 3. DELETE Operation: Handle form submission for deleting stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_stock'])) {
    $id = (int) $_POST['stock_id'];

    if ($id > 0) {
        $sql = "DELETE FROM inventory1 WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Stock deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting stock: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Invalid stock ID.";
    }
    header("Location: add_stock.php");
    exit();
}

// 4. READ Operation: Fetch all stock data to display in a table
$stock_data = [];
$sql = "SELECT * FROM inventory1"; 
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $stock_data[] = $row;
    }
}
$conn->close();

// Check for success or error messages from session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/styles-dashboard2.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="logo.png" alt="IN" width="28" height="28">
                    <span class="brand-name">Inventory</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="../All/dashboard.php" class="nav-item ">
                    <svg xmlns="http://www.w3.00/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="9" y1="21" x2="9" y2="9"></line>
                    </svg>
                    <span class="nav-item-dashboard">Dashboard</span>
                </a>
                <a href="../All/analytics.php" class="nav-item">
                    <svg xmlns="http://www.w3.00/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span class="nav-item-name">Analytics</span>
                </a>
                <a href="../All/add_stock.php" class="nav-item">
                    <svg xmlns="http://www.w3.00/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                    <span class="nav-item-name">Stock Entry</span>
                </a>
                <a href="../coldstorage/dashboard-template.php" class="nav-item">
    <svg xmlns="http://www.w3.00/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
        <path d="M12 2C10.89 2 10 2.89 10 4V16.44C8.85 16.72 8 17.97 8 19.3C8 21.03 9.97 23 12 23C14.03 23 16 21.03 16 19.3C16 17.97 15.15 16.72 14 16.44V4C14 2.89 13.11 2 12 2ZM12 20C11.45 20 11 19.55 11 19C11 18.45 11.45 18 12 18C12.55 18 13 18.45 13 19C13 19.55 12.55 20 12 20ZM14 7H10V4C10 3.45 10.45 3 11 3C11.55 3 12 3.45 12 4V7H14V5C14 4.45 13.55 4 13 4C12.45 4 12 4.45 12 5V7Z"></path>
    </svg>
    <span class="nav-item-name">Cold Storage</span>
</a>
<a href="..\lossaduitor\dashboard-1.php" class="nav-item active">
    <svg xmlns="http://www.w3.00/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
        <path d="M10.29 3.86L3.86 10.29a2 2 0 0 0 0 2.83l6.43 6.43a2 2 0 0 0 2.83 0l6.43-6.43a2 2 0 0 0 0-2.83L13.12 3.86a2 2 0 0 0-2.83 0z" />
        <line x1="12" y1="8" x2="12" y2="12" />
        <line x1="12" y1="16" x2="12.01" y2="16" />
    </svg>
    <span class="nav-item-name">Loss Auditor</span>
</a>
<a href="../productseller/productseller.php" class="nav-item">
    <svg xmlns="http://www.w3.00/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
        <circle cx="12" cy="12" r="10" />
        <path d="M16 12H8" />
        <path d="M12 8v8" />
    </svg>
    <span class="nav-item-name">Productseller</span>
</a>
<a href="../productseller/orders-dashboard.php" class="nav-item active">
    <svg xmlns="http://www.w3.00/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
        <polyline points="14 2 14 8 20 8" />
        <line x1="16" y1="13" x2="8" y2="13" />
        <line x1="16" y1="17" x2="8" y2="17" />
        <line x1="10" y1="9" x2="10" y2="9" />
    </svg>
    <span class="nav-item-name">Orders</span>
</a>
            </nav>
        </aside>
                    
        <div class="main-content">
            <div class="container-fluid p-4">
                <h1 class="mb-4">Stock Management</h1>

                <!-- Success and Error Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Form to Add New Stock -->
                <div class="card p-4 shadow-sm mb-5">
                    <h2 class="card-title mb-4">Add New Stock</h2>
                    <form id="add-stock-form" action="add_stock.php" method="POST">
                        <input type="hidden" name="add_stock" value="1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">Meat Type</label>
        
                                <select class="form-select" id="type" name="type" required>     
                                <option value="">Select Product Type</option>
                                <option value="Beef" <?= (($_POST['productType'] ?? '') === 'Beef') ? 'selected' : '' ?>>Beef</option>
                                <option value="Pork" <?= (($_POST['productType'] ?? '') === 'Pork') ? 'selected' : '' ?>>Pork</option>
                                <option value="Poultry"<?= (($_POST['productType']??'')=== 'Poultry')?'selected' : '' ?>>Poultry</option>
                                <option value="Lamb" <?= (($_POST['productType'] ?? '') === 'Lamb') ? 'selected' : '' ?>>Lamb</option>
                               </select>
                            </div>
                            <div class="col-md-6">
                                <label for="batch" class="form-label">Batch Number</label>
                                <input type="text" class="form-control" id="batch" name="batch" required>
                            </div>
                            <div class="col-md-6">
                                <label for="quantity" class="form-label">Quantity (kg/lbs)</label>
                                <input type="number" step="0.01" class="form-control" id="quantity" name="quantity" required>
                            </div>
                            <div class="col-md-6">
                                <label for="supplier" class="form-label">Supplier</label>
                                <input type="text" class="form-control" id="supplier" name="supplier" required>
                                

                            </div>
                            <div class="col-md-6">
                                <label for="cost" class="form-label">Cost ($)</label>
                                <input type="number" step="0.01" class="form-control" id="cost" name="cost" required>
                            </div>
                            <div class="col-md-6">
                                <label for="totalCost" class="form-label">Total Cost ($)</label>
                                <input type="text" class="form-control" id="totalCost" readonly>
                            </div>

                            <div class="col-md-6">
                                <label for="processingDate" class="form-label">Processing Date</label>
                                <input type="date" class="form-control" id="processingDate" name="processingDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="expirationDate" class="form-label">Expiration Date</label>
                                <input type="date" class="form-control" id="expirationDate" name="expirationDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="location" class="form-label">Storage Location</label>
                                <select class="form-select" id="location" name="location" required>
                                    <option value="">Select Storage Location</option>
                                    <option value="Cold Storage A">Cold Storage A</option>
                                    <option value="Cold Storage B">Cold Storage B</option>
                                    <option value="Cold Storage C">Cold Storage C</option>
                                    <option value="Freezer 1">Freezer 1</option>
                                    <option value="Freezer 2">Freezer 2</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-modern-add">Add Stock</button>
                        </div>
                    </form>
                </div>

                <!-- Table to Display Existing Stock (READ Operation) -->
                <div class="card p-4 shadow-sm">
                    <h2 class="card-title mb-4">Current Stock Inventory</h2>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Meat Type</th>
                                    <th>Batch #</th>
                                    <th>Quantity</th>
                                    <th>Supplier</th>
                                    <th>Cost</th>
                                    <th>Total Cost</th> <!-- Added new header -->
                                    <th>Processing Date</th>
                                    <th>Expiration Date</th>
                                    <th>Location</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stock_data)): ?>
                                    <?php foreach ($stock_data as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['id']) ?></td>
                                            <td><?= htmlspecialchars($item['meat_type']) ?></td>
                                            <td><?= htmlspecialchars($item['batch_number']) ?></td>
                                            <td><?= htmlspecialchars($item['quantity']) ?></td>
                                            <td><?= htmlspecialchars($item['supplier']) ?></td>
                                            <td><?= htmlspecialchars($item['cost']) ?></td>
                                            <td><?= htmlspecialchars($item['total_cost']) ?></td> <!-- Added new data cell -->
                                            <td><?= htmlspecialchars($item['processing_date']) ?></td>
                                            <td><?= htmlspecialchars($item['expiration_date']) ?></td>
                                            <td><?= htmlspecialchars($item['location']) ?></td>
                                            <td>
                                                <!-- Edit button opens modal with data -->
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editStockModal"
                                                    data-id="<?= htmlspecialchars($item['id']) ?>"
                                                    data-type="<?= htmlspecialchars($item['meat_type']) ?>"
                                                    data-batch="<?= htmlspecialchars($item['batch_number']) ?>"
                                                    data-quantity="<?= htmlspecialchars($item['quantity']) ?>"
                                                    data-supplier="<?= htmlspecialchars($item['supplier']) ?>"
                                                    data-cost="<?= htmlspecialchars($item['cost']) ?>"
                                                    data-totalcost="<?= htmlspecialchars($item['total_cost']) ?>"
                                                    data-procdate="<?= htmlspecialchars($item['processing_date']) ?>"
                                                    data-expdate="<?= htmlspecialchars($item['expiration_date']) ?>"
                                                    data-location="<?= htmlspecialchars($item['location']) ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <!-- Delete button -->
                                                <form action="add_stock.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="delete_stock" value="1">
                                                    <input type="hidden" name="stock_id" value="<?= htmlspecialchars($item['id']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this stock item?');">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="11" class="text-center">No stock found. Please add a new entry.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Stock Modal (UPDATE Operation) -->
    <div class="modal fade" id="editStockModal" tabindex="-1" aria-labelledby="editStockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStockModalLabel">Edit Stock Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="form-control">
                    <form id="edit-stock-form" action="add_stock.php" method="POST">
                        <input type="hidden" name="update_stock" value="1">
                        <input type="hidden" id="edit-id" name="stock_id">
                        <div class="mb-3">
                            <label for="edit-type" class="form-label">Meat Type</label>
                            <select class="form-select" id="edit-type" name="type" required>     
                                <option value="">Select Product Type</option>
                                <option value="Beef">Beef</option>
                                <option value="Pork">Pork</option>
                                <option value="Poultry">Poultry</option>
                                <option value="Lamb">Lamb</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-batch" class="form-label">Batch Number</label>
                            <input type="text" class="form-control" id="edit-batch" name="batch" placeholder="e.g., A123-B456" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-quantity" class="form-label">Quantity (kg/lbs)</label>
                            <input type="number" step="0.01" class="form-control" id="edit-quantity" name="quantity" placeholder="e.g., 50.00" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-supplier" class="form-label">Supplier</label>
                            <input type="text" class="form-control" id="edit-supplier" name="supplier" placeholder="" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-cost" class="form-label">Cost per unit ($)</label>
                            <input type="number" step="0.01" class="form-control" id="edit-cost" name="cost" placeholder="e.g., 12.50" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-totalCost" class="form-label">Total Cost ($)</label>
                            <input type="text" class="form-control" id="edit-totalCost" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit-processingDate" class="form-label">Processing Date</label>
                            <input type="date" class="form-control" id="edit-processingDate" name="processingDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-expirationDate" class="form-label">Expiration Date</label>
                            <input type="date" class="form-control" id="edit-expirationDate" name="expirationDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-location" class="form-label">Storage Location</label>
                            <select class="form-select" id="edit-location" name="location" required>
                                <option value="">Select Storage Location</option>
                                <option value="Cold Storage A">Cold Storage A</option>
                                <option value="Cold Storage B">Cold Storage B</option>
                                <option value="Cold Storage C">Cold Storage C</option>
                                <option value="Freezer 1">Freezer 1</option>
                                <option value="Freezer 2">Freezer 2</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to calculate and display total cost for the Add form
            function calculateAddTotalCost() {
                var quantityInput = document.getElementById('quantity');
                var costInput = document.getElementById('cost');
                var totalCostInput = document.getElementById('totalCost');
                if (quantityInput && costInput && totalCostInput) {
                    var quantity = parseFloat(quantityInput.value) || 0;
                    var cost = parseFloat(costInput.value) || 0;
                    var total = quantity * cost;
                    totalCostInput.value = total.toFixed(2);
                }
            }

            var quantityInput = document.getElementById('quantity');
            var costInput = document.getElementById('cost');
            if (quantityInput && costInput) {
                // Attach event listeners to ensure the calculation is triggered reliably
                quantityInput.addEventListener('input', calculateAddTotalCost);
                quantityInput.addEventListener('keyup', calculateAddTotalCost);
                quantityInput.addEventListener('change', calculateAddTotalCost);
                costInput.addEventListener('input', calculateAddTotalCost);
                costInput.addEventListener('keyup', calculateAddTotalCost);
                costInput.addEventListener('change', calculateAddTotalCost);
            }

            // Function to calculate and display total cost for the Edit form
            function calculateEditTotalCost() {
                var quantityInput = document.getElementById('edit-quantity');
                var costInput = document.getElementById('edit-cost');
                var totalCostInput = document.getElementById('edit-totalCost');
                if (quantityInput && costInput && totalCostInput) {
                    var quantity = parseFloat(quantityInput.value) || 0;
                    var cost = parseFloat(costInput.value) || 0;
                    var total = quantity * cost;
                    totalCostInput.value = total.toFixed(2);
                }
            }
            
            // Add event listeners for the edit form inputs
            var editQuantityInput = document.getElementById('edit-quantity');
            var editCostInput = document.getElementById('edit-cost');
            if (editQuantityInput && editCostInput) {
                // Attach event listeners to ensure the calculation is triggered reliably
                editQuantityInput.addEventListener('input', calculateEditTotalCost);
                editQuantityInput.addEventListener('keyup', calculateEditTotalCost);
                editQuantityInput.addEventListener('change', calculateEditTotalCost);
                editCostInput.addEventListener('input', calculateEditTotalCost);
                editCostInput.addEventListener('keyup', calculateEditTotalCost);
                editCostInput.addEventListener('change', calculateEditTotalCost);
            }


            // Populate the edit modal with data from the table
            var editStockModal = document.getElementById('editStockModal');
            if (editStockModal) {
                editStockModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    var id = button.getAttribute('data-id');
                    var type = button.getAttribute('data-type');
                    var batch = button.getAttribute('data-batch');
                    var quantity = button.getAttribute('data-quantity');
                    var supplier = button.getAttribute('data-supplier');
                    var cost = button.getAttribute('data-cost');
                    var totalCost = button.getAttribute('data-totalcost');
                    var procDate = button.getAttribute('data-procdate');
                    var expDate = button.getAttribute('data-expdate');
                    var location = button.getAttribute('data-location');
                    
                    var modalForm = document.getElementById('edit-stock-form');
                    modalForm.querySelector('#edit-id').value = id;
                    modalForm.querySelector('#edit-type').value = type;
                    modalForm.querySelector('#edit-batch').value = batch;
                    modalForm.querySelector('#edit-quantity').value = quantity;
                    modalForm.querySelector('#edit-supplier').value = supplier;
                    modalForm.querySelector('#edit-cost').value = cost;
                    modalForm.querySelector('#edit-totalCost').value = totalCost; // Set the total cost value from the database
                    modalForm.querySelector('#edit-processingDate').value = procDate;
                    modalForm.querySelector('#edit-expirationDate').value = expDate;
                    modalForm.querySelector('#edit-location').value = location;
                });
            }
        });
    </script>
    <script src="js/dashboard.js"></script>
</body>
</html>
