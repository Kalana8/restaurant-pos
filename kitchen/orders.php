<?php
require_once '../includes/auth.php';
requireKitchen();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $order_item_id = $_POST['order_item_id'] ?? 0;
                $status = $_POST['status'] ?? '';
                
                if ($order_item_id > 0 && !empty($status)) {
                    $sql = "UPDATE order_items SET status = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "si", $status, $order_item_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        // Check if all items in the order are ready
                        $sql = "SELECT o.id, 
                               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as total_items,
                               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id AND status = 'ready') as ready_items
                               FROM orders o
                               WHERE o.id = (SELECT order_id FROM order_items WHERE id = ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "i", $order_item_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if ($row = mysqli_fetch_assoc($result)) {
                            if ($row['total_items'] == $row['ready_items']) {
                                // Update order status to ready
                                $sql = "UPDATE orders SET status = 'ready' WHERE id = ?";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "i", $row['id']);
                                mysqli_stmt_execute($stmt);
                            }
                        }
                        
                        $message = 'Order item status updated successfully';
                    } else {
                        $error = 'Error updating order item status: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                }
                break;
        }
    }
}

// Get pending orders
$sql = "SELECT o.*, u.username as cashier_name,
        GROUP_CONCAT(
            CONCAT(oi.id, ':', m.name, ':', oi.quantity, ':', oi.notes, ':', oi.status)
            SEPARATOR '|'
        ) as items
        FROM orders o
        LEFT JOIN users u ON o.created_by = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE o.status IN ('pending', 'preparing')
        GROUP BY o.id
        ORDER BY o.created_at ASC";
$pending_orders = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Orders - Restaurant POS</title>
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
                        <a class="nav-link active" href="orders.php">Orders</a>
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
        <h2 class="mb-4">Kitchen Orders</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <?php while ($order = mysqli_fetch_assoc($pending_orders)): ?>
                <div class="col-md-6 mb-4">
                    <div class="card kitchen-order">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><?php echo $order['order_number']; ?></h5>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <small class="text-muted">
                                    By <?php echo htmlspecialchars($order['cashier_name']); ?><br>
                                    <?php echo date('M d, H:i', strtotime($order['created_at'])); ?>
                                </small>
                            </p>
                            
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th>Notes</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $items = explode('|', $order['items']);
                                        foreach ($items as $item) {
                                            list($id, $name, $quantity, $notes, $status) = explode(':', $item);
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($name); ?></td>
                                                <td><?php echo $quantity; ?></td>
                                                <td><?php echo htmlspecialchars($notes); ?></td>
                                                <td>
                                                    <span class="order-status status-<?php echo $status; ?>">
                                                        <?php echo ucfirst($status); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($status !== 'ready'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="order_item_id" value="<?php echo $id; ?>">
                                                            <input type="hidden" name="status" value="ready">
                                                            <button type="submit" class="btn btn-success btn-sm">
                                                                <i class="bi bi-check-lg"></i> Ready
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh the page every 30 seconds
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html> 