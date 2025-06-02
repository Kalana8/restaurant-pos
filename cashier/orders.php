<?php
require_once '../includes/auth.php';
requireCashier();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_order':
                $items = $_POST['items'] ?? [];
                $quantities = $_POST['quantities'] ?? [];
                $notes = $_POST['notes'] ?? [];
                
                if (empty($items)) {
                    $error = 'Please select at least one item';
                } else {
                    // Generate order number
                    $order_number = 'ORD' . date('YmdHis');
                    
                    // Calculate total
                    $total = 0;
                    $sql = "SELECT id, price FROM menu_items WHERE id IN (" . implode(',', array_map('intval', $items)) . ")";
                    $result = mysqli_query($conn, $sql);
                    $prices = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        $prices[$row['id']] = $row['price'];
                    }
                    
                    foreach ($items as $index => $item_id) {
                        $total += $prices[$item_id] * $quantities[$index];
                    }
                    
                    // Create order
                    $sql = "INSERT INTO orders (order_number, total_amount, created_by) VALUES (?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "sdi", $order_number, $total, $_SESSION['user_id']);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $order_id = mysqli_insert_id($conn);
                        
                        // Add order items
                        $sql = "INSERT INTO order_items (order_id, menu_item_id, quantity, price, notes) VALUES (?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        
                        foreach ($items as $index => $item_id) {
                            $quantity = $quantities[$index];
                            $price = $prices[$item_id];
                            $note = $notes[$index] ?? '';
                            
                            mysqli_stmt_bind_param($stmt, "iiids", $order_id, $item_id, $quantity, $price, $note);
                            mysqli_stmt_execute($stmt);
                        }
                        
                        $message = 'Order created successfully';
                    } else {
                        $error = 'Error creating order: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                }
                break;
                
            case 'update_payment':
                $order_id = $_POST['order_id'] ?? 0;
                $payment_status = $_POST['payment_status'] ?? '';
                
                if ($order_id > 0 && !empty($payment_status)) {
                    $sql = "UPDATE orders SET payment_status = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "si", $payment_status, $order_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $message = 'Payment status updated successfully';
                    } else {
                        $error = 'Error updating payment status: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                }
                break;
        }
    }
}

// Get all menu items
$sql = "SELECT * FROM menu_items WHERE status = 'active' ORDER BY category, name";
$menu_items = mysqli_query($conn, $sql);

// Get recent orders
$sql = "SELECT o.*, u.username as cashier_name 
        FROM orders o 
        LEFT JOIN users u ON o.created_by = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 10";
$recent_orders = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Restaurant POS</title>
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
                    <li class="nav-item">
                        <a class="nav-link" href="payments.php">Payments</a>
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
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">New Order</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="orderForm">
                            <input type="hidden" name="action" value="create_order">
                            
                            <div class="mb-3">
                                <label class="form-label">Menu Items</label>
                                <div id="orderItems">
                                    <div class="row mb-2">
                                        <div class="col-md-5">
                                            <select class="form-select" name="items[]" required>
                                                <option value="">Select Item</option>
                                                <?php while ($item = mysqli_fetch_assoc($menu_items)): ?>
                                                    <option value="<?php echo $item['id']; ?>" 
                                                            data-price="<?php echo $item['price']; ?>"
                                                            data-category="<?php echo htmlspecialchars($item['category']); ?>">
                                                        <?php echo htmlspecialchars($item['name']); ?> - $<?php echo number_format($item['price'], 2); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" class="form-control" name="quantities[]" value="1" min="1" required>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" name="notes[]" placeholder="Special instructions">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm remove-item">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" id="addItem">
                                    <i class="bi bi-plus-lg"></i> Add Item
                                </button>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Total: $<span id="orderTotal">0.00</span></label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Create Order</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                            <div class="order-card mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo $order['order_number']; ?></h6>
                                        <p class="mb-1">$<?php echo number_format($order['total_amount'], 2); ?></p>
                                        <small class="text-muted">
                                            By <?php echo htmlspecialchars($order['cashier_name']); ?><br>
                                            <?php echo date('M d, H:i', strtotime($order['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <span class="order-status status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                        <br>
                                        <span class="order-status status-<?php echo $order['payment_status']; ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if ($order['payment_status'] === 'pending'): ?>
                                    <form method="POST" class="mt-2">
                                        <input type="hidden" name="action" value="update_payment">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="payment_status" value="paid">
                                        <button type="submit" class="btn btn-success btn-sm">Mark as Paid</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add new item row
        document.getElementById('addItem').addEventListener('click', function() {
            const template = document.querySelector('#orderItems .row').cloneNode(true);
            template.querySelector('select').value = '';
            template.querySelector('input[name="quantities[]"]').value = '1';
            template.querySelector('input[name="notes[]"]').value = '';
            document.getElementById('orderItems').appendChild(template);
            updateTotal();
        });

        // Remove item row
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item')) {
                if (document.querySelectorAll('#orderItems .row').length > 1) {
                    e.target.closest('.row').remove();
                    updateTotal();
                }
            }
        });

        // Update total when items change
        document.addEventListener('change', function(e) {
            if (e.target.matches('select[name="items[]"]') || e.target.matches('input[name="quantities[]"]')) {
                updateTotal();
            }
        });

        function updateTotal() {
            let total = 0;
            document.querySelectorAll('#orderItems .row').forEach(row => {
                const select = row.querySelector('select');
                const quantity = row.querySelector('input[name="quantities[]"]').value;
                if (select.value) {
                    const price = parseFloat(select.options[select.selectedIndex].dataset.price);
                    total += price * quantity;
                }
            });
            document.getElementById('orderTotal').textContent = total.toFixed(2);
        }
    </script>
</body>
</html> 