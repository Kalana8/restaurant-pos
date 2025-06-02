<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/auth.php';
requireCashier();

$message = '';
$error = '';

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_payment') {
        $order_id = $_POST['order_id'] ?? 0;
        $amount = $_POST['amount'] ?? 0;
        $payment_method = $_POST['payment_method'] ?? '';
        $reference_number = $_POST['reference_number'] ?? '';
        
        if (empty($order_id) || empty($amount) || empty($payment_method)) {
            $error = 'Please fill in all required fields';
        } else {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Insert payment record
                $sql = "INSERT INTO payments (order_id, amount, payment_method, reference_number, created_at) 
                        VALUES (?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "idss", $order_id, $amount, $payment_method, $reference_number);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Error recording payment: ' . mysqli_error($conn));
                }
                
                // Update order status
                $sql = "UPDATE orders SET status = 'paid' WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $order_id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Error updating order status: ' . mysqli_error($conn));
                }
                
                mysqli_commit($conn);
                $message = 'Payment recorded successfully';
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = $e->getMessage();
            }
        }
    }
}

// Get pending orders
$sql = "SELECT o.*, 
        (SELECT SUM(quantity * price) FROM order_items oi WHERE oi.order_id = o.id) as total_amount,
        (SELECT SUM(amount) FROM payments p WHERE p.order_id = o.id) as paid_amount
        FROM orders o 
        WHERE o.status = 'ready' 
        ORDER BY o.created_at DESC";
$orders = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Restaurant POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .payment-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .payment-card:hover {
            transform: translateY(-5px);
        }
        .payment-method-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .order-card {
            border-left: 4px solid #0d6efd;
        }
        .order-card.paid {
            border-left-color: #198754;
        }
        .amount-display {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0d6efd;
        }
        .payment-steps {
            position: relative;
            padding: 20px 0;
        }
        .payment-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }
        .step {
            position: relative;
            z-index: 2;
            background: white;
            padding: 0 15px;
        }
        .step.active {
            color: #0d6efd;
        }
        .step.completed {
            color: #198754;
        }
        .payment-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .payment-method-select {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .payment-method-select:hover {
            border-color: #0d6efd;
        }
        .payment-method-select.selected {
            border-color: #0d6efd;
            background: #e7f1ff;
        }
        .payment-method-select input[type="radio"] {
            display: none;
        }
        .payment-method-select.selected input[type="radio"] {
            display: inline-block;
        }
        @media (max-width: 768px) {
            .payment-steps {
                flex-direction: column;
                align-items: flex-start;
            }
            .payment-steps::before {
                display: none;
            }
            .step {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-shop me-2"></i>Restaurant POS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-cart me-1"></i>Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="payments.php">
                            <i class="bi bi-cash me-1"></i>Payments
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../includes/auth.php?logout=1">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="bi bi-cash-stack me-2"></i>Payment Management
            </h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card order-card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1">Order #<?php echo $order['id']; ?></h5>
                                    <p class="text-muted mb-0">
                                        <i class="bi bi-table me-1"></i>Table <?php echo htmlspecialchars($order['table_number']); ?>
                                    </p>
                                </div>
                                <span class="badge bg-<?php echo $order['status'] === 'ready' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            
                            <div class="payment-summary mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Total Amount:</span>
                                    <span class="amount-display">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                                <?php if ($order['paid_amount']): ?>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span>Paid Amount:</span>
                                        <span class="text-success">$<?php echo number_format($order['paid_amount'], 2); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <button type="button" class="btn btn-primary w-100" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#paymentModal"
                                    data-order-id="<?php echo $order['id']; ?>"
                                    data-total="<?php echo $order['total_amount']; ?>">
                                <i class="bi bi-cash me-1"></i>Process Payment
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-cash-stack me-2"></i>Process Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_payment">
                        <input type="hidden" name="order_id" id="payment_order_id">
                        
                        <div class="payment-steps d-flex justify-content-between mb-4">
                            <div class="step active">
                                <i class="bi bi-1-circle-fill me-1"></i>Select Method
                            </div>
                            <div class="step">
                                <i class="bi bi-2-circle me-1"></i>Enter Details
                            </div>
                            <div class="step">
                                <i class="bi bi-3-circle me-1"></i>Confirm
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="payment-summary">
                                    <h6 class="mb-3">Order Summary</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total Amount:</span>
                                        <span class="amount-display" id="payment_total"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="payment-summary">
                                    <h6 class="mb-3">Payment Details</h6>
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Payment Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h6 class="mb-3">Select Payment Method</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="payment-method-select w-100">
                                    <input type="radio" name="payment_method" value="cash" required>
                                    <div class="text-center">
                                        <i class="bi bi-cash-stack payment-method-icon"></i>
                                        <div>Cash</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="payment-method-select w-100">
                                    <input type="radio" name="payment_method" value="credit_card" required>
                                    <div class="text-center">
                                        <i class="bi bi-credit-card payment-method-icon"></i>
                                        <div>Credit Card</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="payment-method-select w-100">
                                    <input type="radio" name="payment_method" value="debit_card" required>
                                    <div class="text-center">
                                        <i class="bi bi-credit-card-2-front payment-method-icon"></i>
                                        <div>Debit Card</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="payment-method-select w-100">
                                    <input type="radio" name="payment_method" value="mobile_payment" required>
                                    <div class="text-center">
                                        <i class="bi bi-phone payment-method-icon"></i>
                                        <div>Mobile Payment</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3 mt-4" id="referenceNumberGroup" style="display: none;">
                            <label for="reference_number" class="form-label">Reference Number</label>
                            <input type="text" class="form-control" id="reference_number" name="reference_number">
                            <small class="text-muted">Required for credit/debit card and mobile payments</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Complete Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle payment modal
        const paymentModal = document.getElementById('paymentModal');
        if (paymentModal) {
            paymentModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const orderId = button.getAttribute('data-order-id');
                const total = button.getAttribute('data-total');
                
                document.getElementById('payment_order_id').value = orderId;
                document.getElementById('payment_total').textContent = '$' + parseFloat(total).toFixed(2);
                document.getElementById('amount').value = total;
            });
        }

        // Handle payment method selection
        document.querySelectorAll('.payment-method-select').forEach(select => {
            select.addEventListener('click', function() {
                // Remove selected class from all methods
                document.querySelectorAll('.payment-method-select').forEach(s => {
                    s.classList.remove('selected');
                });
                // Add selected class to clicked method
                this.classList.add('selected');
                // Check the radio button
                this.querySelector('input[type="radio"]').checked = true;
                
                // Show/hide reference number field
                const method = this.querySelector('input[type="radio"]').value;
                const referenceGroup = document.getElementById('referenceNumberGroup');
                if (method === 'credit_card' || method === 'debit_card' || method === 'mobile_payment') {
                    referenceGroup.style.display = 'block';
                    document.getElementById('reference_number').required = true;
                } else {
                    referenceGroup.style.display = 'none';
                    document.getElementById('reference_number').required = false;
                }
            });
        });

        // Validate payment amount
        document.getElementById('amount').addEventListener('input', function() {
            const total = parseFloat(document.getElementById('payment_total').textContent.replace('$', ''));
            const amount = parseFloat(this.value);
            
            if (amount < total) {
                this.setCustomValidity('Payment amount cannot be less than total amount');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html> 