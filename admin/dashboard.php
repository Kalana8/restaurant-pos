<?php
require_once '../includes/auth.php';
requireAdmin();

// Get statistics
$stats = [
    'total_orders' => 0,
    'pending_orders' => 0,
    'total_revenue' => 0,
    'total_users' => 0
];

// Total orders
$sql = "SELECT COUNT(*) as count FROM orders";
$result = mysqli_query($conn, $sql);
if ($row = mysqli_fetch_assoc($result)) {
    $stats['total_orders'] = $row['count'];
}

// Pending orders
$sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
$result = mysqli_query($conn, $sql);
if ($row = mysqli_fetch_assoc($result)) {
    $stats['pending_orders'] = $row['count'];
}

// Total revenue
$sql = "SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'";
$result = mysqli_query($conn, $sql);
if ($row = mysqli_fetch_assoc($result)) {
    $stats['total_revenue'] = $row['total'] ?? 0;
}

// Total users
$sql = "SELECT COUNT(*) as count FROM users";
$result = mysqli_query($conn, $sql);
if ($row = mysqli_fetch_assoc($result)) {
    $stats['total_users'] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Restaurant POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Reports</a>
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
        <h2 class="mb-4">Dashboard</h2>
        
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <h2 class="card-text"><?php echo $stats['total_orders']; ?></h2>
                        <i class="bi bi-cart position-absolute top-50 end-0 me-3 opacity-50" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Pending Orders</h5>
                        <h2 class="card-text"><?php echo $stats['pending_orders']; ?></h2>
                        <i class="bi bi-clock position-absolute top-50 end-0 me-3 opacity-50" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Revenue</h5>
                        <h2 class="card-text">$<?php echo number_format($stats['total_revenue'], 2); ?></h2>
                        <i class="bi bi-currency-dollar position-absolute top-50 end-0 me-3 opacity-50" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <h2 class="card-text"><?php echo $stats['total_users']; ?></h2>
                        <i class="bi bi-people position-absolute top-50 end-0 me-3 opacity-50" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT * FROM orders ORDER BY created_at DESC LIMIT 5";
                                    $result = mysqli_query($conn, $sql);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>";
                                        echo "<td>{$row['order_number']}</td>";
                                        echo "<td>$" . number_format($row['total_amount'], 2) . "</td>";
                                        echo "<td><span class='order-status status-{$row['status']}'>{$row['status']}</span></td>";
                                        echo "<td>" . date('M d, H:i', strtotime($row['created_at'])) . "</td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <a href="users.php" class="btn btn-primary w-100">
                                    <i class="bi bi-person-plus"></i> Manage Users
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="menu.php" class="btn btn-success w-100">
                                    <i class="bi bi-list"></i> Manage Menu
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="reports.php" class="btn btn-info w-100">
                                    <i class="bi bi-graph-up"></i> View Reports
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="../cashier/orders.php" class="btn btn-warning w-100">
                                    <i class="bi bi-cart"></i> New Order
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 