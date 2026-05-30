<?php
include 'includes/config.php';
include 'includes/dbConnection.php';
include 'includes/functions.php';
session_start();

$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$info_message = $_SESSION['info_message'] ?? '';
unset($_SESSION['info_message']);
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

$startDate = $_GET['start-date'] ?? '';
$endDate = $_GET['end-date'] ?? '';
$productType = $_GET['product-type'] ?? 'All';
$searchTerm = $_GET['search-term'] ?? '';

$sqlAggregate = "SELECT product_type, SUM(quantity) AS total_quantity FROM orders";

$where_clauses = [];
$params = [];
$param_types = "";

if (!empty($startDate)) {
    $where_clauses[] = "date_time >= ?";
    $params[] = $startDate . " 00:00:00";
    $param_types .= "s";
}
if (!empty($endDate)) {
    $where_clauses[] = "date_time <= ?";
    $params[] = $endDate . " 23:59:59";
    $param_types .= "s";
}
if ($productType != 'All') {
    $where_clauses[] = "product_type = ?";
    $params[] = $productType;
    $param_types .= "s";
}
if (!empty($searchTerm)) {
    $where_clauses[] = "(product_type LIKE ? OR customer_name LIKE ?)";
    $likeTerm = '%' . $searchTerm . '%';
    $params[] = $likeTerm;
    $params[] = $likeTerm;
    $param_types .= "ss";
}

if (count($where_clauses) > 0) {
    $sqlAggregate .= " WHERE " . implode(" AND ", $where_clauses);
}

$sqlAggregate .= " GROUP BY product_type";

$orderDataAggregate = [];
if ($stmtAggregate = $conn->prepare($sqlAggregate)) {
    if (count($params) > 0) {
        $stmtAggregate->bind_param($param_types, ...$params);
    }
    $stmtAggregate->execute();
    $resultAggregate = $stmtAggregate->get_result();
    while ($row = $resultAggregate->fetch_assoc()) {
        $orderDataAggregate[$row['product_type']] = $row['total_quantity'];
    }
    $stmtAggregate->close();
} else {
    $error_message = "Error fetching aggregated data: " . $conn->error;
}

$chartLabels = array_keys($orderDataAggregate);
$chartData = array_values($orderDataAggregate);
$chartColors = [
    'Apparel' => '#0d6efd',
    'Electronics' => '#fd7e14',
    'Home Goods' => '#e83e8c',
    'Books' => '#f8d7da',
    'Jewelry' => '#adb5bd',
];
$chartBackgroundColor = [];
foreach($chartLabels as $label) {
    $chartBackgroundColor[] = $chartColors[$label] ?? '#6c757d';
}

$sqlTable = "SELECT order_id, date_time, customer_name, product_type, quantity FROM orders";

$where_clauses_table = [];
$params_table = [];
$param_types_table = "";

if (!empty($startDate)) {
    $where_clauses_table[] = "date_time >= ?";
    $params_table[] = $startDate . " 00:00:00";
    $param_types_table .= "s";
}
if (!empty($endDate)) {
    $where_clauses_table[] = "date_time <= ?";
    $params_table[] = $endDate . " 23:59:59";
    $param_types_table .= "s";
}
if ($productType != 'All') {
    $where_clauses_table[] = "product_type = ?";
    $params_table[] = $productType;
    $param_types_table .= "s";
}
if (!empty($searchTerm)) {
    $where_clauses_table[] = "(product_type LIKE ? OR customer_name LIKE ?)";
    $likeTermTable = '%' . $searchTerm . '%';
    $params_table[] = $likeTermTable;
    $params_table[] = $likeTermTable;
    $param_types_table .= "ss";
}

if (count($where_clauses_table) > 0) {
    $sqlTable .= " WHERE " . implode(" AND ", $where_clauses_table);
}
$sqlTable .= " ORDER BY date_time DESC";

$tableData = [];
if ($stmtTable = $conn->prepare($sqlTable)) {
    if (count($params_table) > 0) {
        $stmtTable->bind_param($param_types_table, ...$params_table);
    }
    $stmtTable->execute();
    $resultTable = $stmtTable->get_result();
    if ($resultTable->num_rows > 0) {
        while($row = $resultTable->fetch_assoc()) {
            $tableData[] = $row;
        }
    }
    $stmtTable->close();
} else {
    $error_message = "Error fetching table data: " . $conn->error;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Dashboard - <?= SITE_NAME ?></title>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="9" y1="21" x2="9" y2="9"></line>
                    </svg>
                    <span class="nav-item-dashboard">Dashboard</span>
                </a>
                <a href="../All/analytics.php" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span class="nav-item-name">Analytics</span>
                </a>
                <a href="../All/add_stock.php" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                    <span class="nav-item-name">Stock Entry</span>
                </a>
                <a href="../coldstorage/dashboard-template.php" class="nav-item">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
        <path d="M12 2C10.89 2 10 2.89 10 4V16.44C8.85 16.72 8 17.97 8 19.3C8 21.03 9.97 23 12 23C14.03 23 16 21.03 16 19.3C16 17.97 15.15 16.72 14 16.44V4C14 2.89 13.11 2 12 2ZM12 20C11.45 20 11 19.55 11 19C11 18.45 11.45 18 12 18C12.55 18 13 18.45 13 19C13 19.55 12.55 20 12 20ZM14 7H10V4C10 3.45 10.45 3 11 3C11.55 3 12 3.45 12 4V7H14V5C14 4.45 13.55 4 13 4C12.45 4 12 4.45 12 5V7Z"></path>
    </svg>
    <span class="nav-item-name">Cold Storage</span>
</a>
<a href="..\lossaduitor\dashboard-1.php" class="nav-item active">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
        <path d="M10.29 3.86L3.86 10.29a2 2 0 0 0 0 2.83l6.43 6.43a2 2 0 0 0 2.83 0l6.43-6.43a2 2 0 0 0 0-2.83L13.12 3.86a2 2 0 0 0-2.83 0z" />
        <line x1="12" y1="8" x2="12" y2="12" />
        <line x1="12" y1="16" x2="12.01" y2="16" />
    </svg>
    <span class="nav-item-name">Loss Auditor</span>
</a>



                <a href="../productseller/productseller.php" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M16 12H8" />
                        <path d="M12 8v8" />
                    </svg>
                    <span class="nav-item-name">Productseller</span>
                </a>
                 <a href="../productseller/orders-dashboard.php" class="nav-item active">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
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
            

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1 class="page-title">Orders Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <div class="profile-button" id="profileButton">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <div class="dropdown-menu" id="profileDropdown">
                            <a href="profile.php">Profile</a>
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="container-fluid">
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($info_message): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($info_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="filter-controls p-3 mb-4 rounded shadow-sm">
                    <h5 class="mb-3">Filter Order Entries</h5>
                    <form id="filterForm" action="orders-dashboard.php" method="GET">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="start-date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start-date" name="start-date" value="<?= htmlspecialchars($startDate) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="end-date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end-date" name="end-date" value="<?= htmlspecialchars($endDate) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search-term-filter" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search-term-filter" name="search-term" placeholder="Search product or customer..." value="<?= htmlspecialchars($searchTerm) ?>">
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="orders-dashboard.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card card-custom">
                            <div class="card-body">
                                <h5 class="card-title">Orders by Product Type</h5>
                                <canvas id="ordersChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-custom h-100">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center">
                                <h5 class="card-title text-center">Quick Actions</h5>
                                <a href="addorder.php" class="btn btn-success my-2 w-75">
                                    <i class="bi bi-plus-circle"></i> Add New Order
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-custom mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Order Entries</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Customer Name</th>
                                        <th>Product Type</th>
                                        <th>Quantity</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($tableData)): ?>
                                        <?php foreach ($tableData as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['date_time']) ?></td>
                                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                                <td><?= htmlspecialchars($row['product_type']) ?></td>
                                                <td><?= htmlspecialchars($row['quantity']) ?></td>
                                                <td class="text-center">
                                                    <a href="editorder.php?id=<?= htmlspecialchars($row['order_id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                    <a href="deleteorder.php?id=<?= htmlspecialchars($row['order_id']) ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this order entry?');"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No orders found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartCtx = document.getElementById('ordersChart');
            const chartLabels = <?= json_encode($chartLabels) ?>;
            const chartData = <?= json_encode($chartData) ?>;
            const chartBackgroundColor = <?= json_encode($chartBackgroundColor) ?>;
            
            new Chart(chartCtx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Total Order Quantity',
                        data: chartData,
                        backgroundColor: chartBackgroundColor,
                        borderColor: chartBackgroundColor.map(color => color.replace(')', ', 0.8)')),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Quantity'
                            }
                        },
                        x: {
                             title: {
                                display: true,
                                text: 'Product Type'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y + ' units';
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
