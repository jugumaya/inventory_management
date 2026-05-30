<?php
include 'includes/config.php';
include 'includes/dbConnection.php'; // Include db connection directly
include 'includes/functions.php';
session_start(); // Start session for messages

$lossData = null;
$errors = []; // Initialize errors array

// Check if the loss ID is provided in the URL for fetching data (GET request)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $loss_id = sanitize($_GET['id']);

    // Fetch the existing loss data from the loststock table
    $sql_fetch = "SELECT loss_id, date_time, facility, stage, product_type, quantity_lost, loss_reason FROM loststock WHERE loss_id = ?";

    if ($stmt_fetch = $conn->prepare($sql_fetch)) {
        $stmt_fetch->bind_param("i", $loss_id);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();

        if ($result_fetch->num_rows === 1) {
            $lossData = $result_fetch->fetch_assoc();
            // Separate date and time for form display
            list($lossData['lossDate'], $lossData['lossTime']) = explode(' ', $lossData['date_time']);
        } else {
            $_SESSION['error_message'] = "Loss entry not found.";
            header("Location: dashboard-1.php"); // Redirect if ID is invalid or not found
            exit();
        }

        $stmt_fetch->close();
    } else {
        $_SESSION['error_message'] = "Database error fetching record: " . $conn->error;
        header("Location: dashboard-1.php");
        exit();
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission for updating the loss entry
    $loss_id = sanitize($_POST['id']); // Get the ID from the hidden input
    $facility = sanitize($_POST['facility']);
    $stage = sanitize($_POST['stage']);
    $productType = sanitize($_POST['productType']);
    // Cast to float and validate
    $quantityLost = filter_var($_POST['quantityLost'], FILTER_VALIDATE_FLOAT);
    $lossReason = sanitize($_POST['lossReason']);
    $lossDate = sanitize($_POST['lossDate']);
    $lossTime = sanitize($_POST['lossTime']);
    

     // Combine date and time for the database
    $dateTime = $lossDate . ' ' . $lossTime;

    // Validate required fields
    if (empty($loss_id) || !is_numeric($loss_id)) {
         $errors[] = "Invalid loss entry ID.";
    }
    if (empty($facility)) {
        $errors[] = "Facility is required.";
    }
    if (empty($stage)) {
        $errors[] = "Stage is required.";
    }
    if (empty($productType)) {
        $errors[] = "Product Type is required.";
    }
    if ($quantityLost === false || $quantityLost <= 0) {
        $errors[] = "Quantity lost must be a positive number.";
    }
    if (empty($lossReason)) {
        $errors[] = "Loss Reason is required.";
    }
    if (empty($lossDate)) {
        $errors[] = "Loss Date is required.";
    }
    if (empty($lossTime)) {
        $errors[] = "Loss Time is required.";
    }


    // If no errors, update the loss data in the database
    if (empty($errors)) {
        // Prepare the SQL query to update the loststock table
        $sql_update = "UPDATE loststock SET date_time = ?, facility = ?, stage = ?, product_type = ?, quantity_lost = ?, loss_reason = ? WHERE loss_id = ?";

        if ($stmt_update = $conn->prepare($sql_update)) {
            $stmt_update->bind_param("ssssssi", $dateTime, $facility, $stage, $productType, $quantityLost, $lossReason,$loss_id);
            if ($stmt_update->execute()) {
                if ($stmt_update->affected_rows > 0) {
                    $_SESSION['success_message'] = "Loss entry updated successfully.";
                } else {
                    $_SESSION['info_message'] = "No changes were made to the loss entry.";
                }
                header("Location: dashboard-1.php"); // Redirect back to the dashboard
                exit();
            } else {
                $errors[] = "Error updating loss entry: " . $stmt_update->error;
                 // Fall through to display the form with errors and submitted data
            }

            $stmt_update->close();
        } else {
             $errors[] = "Database error preparing update statement: " . $conn->error;
              // Fall through to display the form with errors
        }
    }

    // If there are errors or update failed, re-populate the form with the submitted data
    // Fetch the data again to ensure consistency, or use submitted POST data
     // Using POST data to repopulate the form on error
    $lossData = [
        'loss_id' => $loss_id,
        'facility' => $facility,
        'stage' => $stage,
        'product_type' => $productType,
        'quantity_lost' => $_POST['quantityLost'], // Use original string input
        'loss_reason' => $lossReason,
        'lossDate' => $lossDate,
        'lossTime' => $lossTime,
        'date_time' => $dateTime // Keep the combined date_time if needed elsewhere
    ];


} else {
    // Redirect if not a GET request with ID or a POST request
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: dashboard-1.php");
    exit();
}

// Close connection after processing or fetching data
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Loss Entry - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/styles3.css"> <style>
        /* Add or adjust styles for the edit page if needed */
         .btn-modern-update {
            position: relative;
            padding: 15px 30px;
            border: none;
            background: linear-gradient(to right, #007bff, #0056b3); /* Blue Gradient */
            color: #fff;
            font-size: 1.2em;
            font-weight: bold;
            border-radius: 5px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .btn-modern-update .btn-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
        }

        .btn-modern-update .btn-glow {
            position: absolute;
            top: var(--y, 0);
            left: var(--x, 0);
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            z-index: 1;
            transition: width 0.4s ease, height 0.4s ease;
        }

        .btn-modern-update:hover {
            transform: scale(1.05);
        }

        .btn-modern-update:hover .btn-glow {
            width: 300px;
            height: 300px;
        }
    </style>
</head>

<body class="bg-dark text-light">
    <div class="app-container">
        <?php // include 'includes/sidebar.php'; ?>
          <!-- Sidebar -->
          <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="logo.png" alt="IN" width="28" height="28">
                    <span class="brand-name">Inventory</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                
            </nav>
        </aside>
        <main class="main-content">
            <?php // include 'includes/header.php'; ?>
            <header class="header">
                <div class="search-container">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="Search inventory, batches..." class="search-input">
                </div>

                <h1 class="page-title">Edit Loss Entry</h1> <div class="profile-container">
                    <button id="profileButton" class="profile-button">
                        <div class="profile-avatar">JD</div>
                    </button>
                    <div id="profileDropdown" class="profile-dropdown">
                         <a href="#" class="dropdown-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            Settings
                        </a>
                        <a href="#" class="dropdown-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            </header>

            <div class="container py-4">
                 <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <?php
                        // Display errors from form submission
                        if (!empty($errors)) {
                            echo '<div class="alert alert-danger">';
                            echo '<ul class="mb-0">';
                            foreach ($errors as $error) {
                                echo '<li>' . htmlspecialchars($error) . '</li>';
                            }
                            echo '</ul>';
                            echo '</div>';
                        }

                        // Display success or info messages from update process
                        if (isset($_SESSION['success_message'])) {
                            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                            unset($_SESSION['success_message']);
                        }
                         if (isset($_SESSION['info_message'])) {
                            echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['info_message']) . '</div>';
                            unset($_SESSION['info_message']);
                        }
                        ?>

                        <div class="card bg-dark border-secondary mb-4">
                            <div class="card-header bg-dark ">
                                <ul class="nav nav-tabs card-header-tabs">
                                    
                                </ul>
                            </div>
                            <div class="card-body p-4">
                                <?php if ($lossData): ?>
                                     <form method="POST" action="editloss.php">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($lossData['loss_id']) ?>">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="facility" class="form-label text-white">Facility <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control bg-dark text-white border-secondary" id="facility" name="facility" value="<?= htmlspecialchars($lossData['facility']) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="stage" class="form-label text-white">Stage <span class="text-danger">*</span></label>
                                                <select class="form-select bg-dark text-white border-secondary" id="stage" name="stage" required>
                                                    <option value="">Select Stage</option>
                                                    <option value="Slaughter" <?= ($lossData['stage'] === 'Slaughter') ? 'selected' : '' ?>>Slaughter</option>
                                                    <option value="Processing" <?= ($lossData['stage'] === 'Processing') ? 'selected' : '' ?>>Processing</option>
                                                    <option value="Storage" <?= ($lossData['stage'] === 'Storage') ? 'selected' : '' ?>>Storage</option>
                                                    <option value="Handling" <?= ($lossData['stage'] === 'Handling') ? 'selected' : '' ?>>Handling</option>
                                                    <option value="Transport" <?= ($lossData['stage'] === 'Transport') ? 'selected' : '' ?>>Transport</option>
                                                    <option value="Rejected" <?= ($lossData['stage'] === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                                                    <option value="Spoiled" <?= ($lossData['stage'] === 'Spoiled') ? 'selected' : '' ?>>Spoiled</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="productType" class="form-label text-white">MeatType <span class="text-danger">*</span></label>
                                                <select class="form-select bg-dark text-white border-secondary" id="productType" name="productType" required>
                                                    <option value="">Select Meat Type</option>
                                                    <option value="Beef" <?= ($lossData['product_type'] === 'Beef') ? 'selected' : '' ?>>Beef</option>
                                                    <option value="Pork" <?= ($lossData['product_type'] === 'Pork') ? 'selected' : '' ?>>Pork</option>
                                                    <option value="Poultry" <?= ($lossData['product_type'] === 'Poultry') ? 'selected' : '' ?>>Poultry</option>
                                                    <option value="Lamb" <?= ($lossData['product_type'] === 'Lamb') ? 'selected' : '' ?>>Lamb</option>
                                                </select>

                                            </div>
                                            <div class="col-md-6">
                                                <label for="quantityLost" class="form-label text-white">Quantity Lost (kg) <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control bg-dark text-white border-secondary" id="quantityLost" name="quantityLost" step="0.01" min="0.01" value="<?= htmlspecialchars($lossData['quantity_lost']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="lossReason" class="form-label text-white">Loss Reason <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control bg-dark text-white border-secondary" id="lossReason" name="lossReason" value="<?= htmlspecialchars($lossData['loss_reason']) ?>" required>
                                            </div>
                                        </div>
                                         <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="lossDate" class="form-label text-white">Loss Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control bg-dark text-white border-secondary" id="lossDate" name="lossDate" value="<?= htmlspecialchars($lossData['lossDate'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="lossTime" class="form-label text-white">Loss Time <span class="text-danger">*</span></label>
                                                 <input type="time" class="form-control bg-dark text-white border-secondary" id="lossTime" name="lossTime" value="<?= htmlspecialchars($lossData['lossTime'] ?? '') ?>" required>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn-modern-update">
                                                 <div class="btn-content">
                                                    <i class="bi bi-save-fill me-2"></i>
                                                    <span>Update Loss</span>
                                                </div>
                                                <div class="btn-glow"></div>
                                            </button>
                                             <a href="dashboard-1.php" class="btn btn-outline-secondary">Cancel</a>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <p class="text-white text-center">Loss entry not found or invalid request.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
     <script>
        // Keep your existing JavaScript for button glow
        document.addEventListener('DOMContentLoaded', function() {
            const updateButton = document.querySelector('.btn-modern-update');
            if (updateButton) {
                updateButton.addEventListener('mousemove', function(e) {
                    const rect = this.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    this.style.setProperty('--x', x + 'px');
                    this.style.setProperty('--y', y + 'px');
                });
            }
        });
     </script>
    <script src="js/dashboard.js"></script>

</body>
</html>