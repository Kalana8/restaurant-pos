<?php
require_once '../includes/auth.php';
requireAdmin();

// Get date range from request
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get sales statistics
$stats = [
    'total_sales' => 0,
    'total_orders' => 0,
    'average_order' => 0,
    'top_items' => []
];

// Total sales
$sql = "SELECT SUM(total_amount) as total 
        FROM orders 
        WHERE payment_status = 'paid' 
        AND DATE(created_at) BETWEEN ? AND ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
    $stats['total_sales'] = $row['total'] ?? 0;
}

// Total orders
$sql = "SELECT COUNT(*) as count 
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
    $stats['total_orders'] = $row['count'];
}

// Average order value
if ($stats['total_orders'] > 0) {
    $stats['average_order'] = $stats['total_sales'] / $stats['total_orders'];
}

// Top selling items
$sql = "SELECT m.name, SUM(oi.quantity) as total_quantity, SUM(oi.quantity * oi.price) as total_revenue
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.payment_status = 'paid'
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY m.id
        ORDER BY total_quantity DESC
        LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $stats['top_items'][] = $row;
}

// Get daily sales data for chart
$sql = "SELECT DATE(created_at) as date, SUM(total_amount) as total
        FROM orders
        WHERE payment_status = 'paid'
        AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$daily_sales = [];
while ($row = mysqli_fetch_assoc($result)) {
    $daily_sales[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Restaurant POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Restaurant POS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">Reports</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../includes/auth.php?logout=1">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Sales Reports</h2>
            <form class="row g-3">
                <div class="col-auto">
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-auto">
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </form>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Sales</h5>
                        <h2 class="card-text">$<?php echo number_format($stats['total_sales'], 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <h2 class="card-text"><?php echo $stats['total_orders']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Average Order Value</h5>
                        <h2 class="card-text">$<?php echo number_format($stats['average_order'], 2); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Daily Sales</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top Selling Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Qty</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['top_items'] as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo $item['total_quantity']; ?></td>
                                            <td>$<?php echo number_format($item['total_revenue'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize sales chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_sales, 'date')); ?>,
                datasets: [{
                    label: 'Daily Sales',
                    data: <?php echo json_encode(array_column($daily_sales, 'total')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 