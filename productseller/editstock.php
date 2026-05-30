<?php
include 'includes/config.php';
include 'includes/dbConnection.php';
include 'includes/functions.php';
session_start();

$productsellerData = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $productseller_id = sanitize($_GET['id']);

    $sql_fetch = "SELECT productseller_id, date_time, product_type, quantity, adjustment_reason FROM productseller WHERE productseller_id = ?";

    if ($stmt_fetch = $conn->prepare($sql_fetch)) {
        $stmt_fetch->bind_param("i", $productseller_id);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();

        if ($result_fetch->num_rows === 1) {
            $productsellerData = $result_fetch->fetch_assoc();
            list($productsellerData['productsellerDate'], $productsellerData['productsellerTime']) = explode(' ', $productsellerData['date_time']);
        } else {
            $_SESSION['error_message'] = "Productseller entry not found.";
            header("Location: productseller.php");
            exit();
        }

        $stmt_fetch->close();
    } else {
        $_SESSION['error_message'] = "Database error fetching record: " . $conn->error;
        header("Location: productseller.php");
        exit();
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productseller_id = sanitize($_POST['id']);
    $productType = sanitize($_POST['productType']);
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_FLOAT);
    $adjustmentReason = sanitize($_POST['adjustmentReason']);
    $productsellerDate = sanitize($_POST['productsellerDate']);
    $productsellerTime = sanitize($_POST['productsellerTime']);
    

    $dateTime = $productsellerDate . ' ' . $productsellerTime;

    if (empty($productseller_id) || !is_numeric($productseller_id)) {
         $errors[] = "Invalid productseller entry ID.";
    }
    if (empty($productType)) {
        $errors[] = "Product Type is required.";
    }
    if ($quantity === false || $quantity <= 0) {
        $errors[] = "Quantity must be a positive number.";
    }
    if (empty($adjustmentReason)) {
        $errors[] = "Reason is required.";
    }
    if (empty($productsellerDate)) {
        $errors[] = "Date is required.";
    }
    if (empty($productsellerTime)) {
        $errors[] = "Time is required.";
    }


    if (empty($errors)) {
        $sql_update = "UPDATE productseller SET date_time = ?, product_type = ?, quantity = ?, adjustment_reason = ? WHERE productseller_id = ?";

        if ($stmt_update = $conn->prepare($sql_update)) {
            $stmt_update->bind_param("ssdsi", $dateTime, $productType, $quantity, $adjustmentReason, $productseller_id);
            if ($stmt_update->execute()) {
                if ($stmt_update->affected_rows > 0) {
                    $_SESSION['success_message'] = "Productseller entry updated successfully.";
                } else {
                    $_SESSION['info_message'] = "No changes were made to the productseller entry.";
                }
                header("Location: productseller.php");
                exit();
            } else {
                $errors[] = "Error updating productseller entry: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
             $errors[] = "Database error preparing update statement: " . $conn->error;
        }
        $productsellerData = [ 
            'productseller_id' => $productseller_id,
            'product_type' => $productType, 
            'quantity' => $_POST['quantity'],
            'adjustment_reason' => $adjustmentReason,
            'productsellerDate' => $productsellerDate,
            'productsellerTime' => $productsellerTime,
            'date_time' => $dateTime
        ];
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: productseller.php");
    exit();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Productseller Entry - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="app-container">
        <main class="main-content">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="card card-custom p-4">
                            <h2 class="card-title text-center">Edit Productseller Entry</h2>
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <?php if ($productsellerData): ?>
                                <form action="editstock.php" method="POST">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($productsellerData['productseller_id']) ?>">
                        <div class="mb-3">
                            <label for="productType" class="form-label text-white">Meat Type <span class="text-danger">*</span></label>
                            <select class="form-select bg-dark text-white border-secondary" id="productType" name="productType" required>
                                <option value="">Select Meat Type</option>
                                <option value="Beef" <?= (isset($_POST['productType']) && $_POST['productType'] === 'Beef') ? 'selected' : '' ?>>Beef</option>
                                <option value="Pork" <?= (isset($_POST['productType']) && $_POST['productType'] === 'Pork') ? 'selected' : '' ?>>Pork</option>
                                <option value="Poultry" <?= (isset($_POST['productType']) && $_POST['productType'] === 'Poultry') ? 'selected' : '' ?>>Poultry</option>
                                <option value="Lamb" <?= (isset($_POST['productType']) && $_POST['productType'] === 'Lamb') ? 'selected' : '' ?>>Lamb</option>
                            </select>
                        </div>
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label text-white">Quantity</label>
                                        <input type="number" step="0.01" class="form-select bg-dark text-white border-secondary" id="quantity" name="quantity" value="<?= htmlspecialchars($productsellerData['quantity']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="adjustmentReason" class="form-label text-white">Adjustment Reason</label>
                                        <textarea class="form-select bg-dark text-white border-secondary" id="adjustmentReason" name="adjustmentReason" rows="3" required><?= htmlspecialchars($productsellerData['adjustment_reason']) ?></textarea>
                                    </div>
                                     <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="productsellerDate" class="form-label text-white">Date</label>
                                            <input type="date" class="form-select bg-dark text-white border-secondary" id="productsellerDate" name="productsellerDate" value="<?= htmlspecialchars($productsellerData['productsellerDate']) ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="productsellerTime" class="form-label">Time</label>
                                            <input type="time" class="form-select bg-dark text-white border-secondary" id="productsellerTime" name="productsellerTime" value="<?= htmlspecialchars($productsellerData['productsellerTime']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-primary">Update</button>
                                         <a href="productseller.php" class="btn btn-outline-secondary">Cancel</a>
                                    </div>
                                </form>
                            <?php else: ?>
                                <p class="text-white text-center">Productseller entry not found or invalid request.</p>
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