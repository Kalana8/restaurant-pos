<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/auth.php';
requireAdmin();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'] ?? '';
                $description = $_POST['description'] ?? '';
                $price = $_POST['price'] ?? 0;
                $category = $_POST['category'] ?? '';
                $status = $_POST['status'] ?? 'active';
                
                if (empty($name) || empty($price) || empty($category)) {
                    $error = 'Name, price, and category are required';
                } else {
                    $sql = "INSERT INTO menu_items (name, description, price, category, status) VALUES (?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssdss", $name, $description, $price, $category, $status);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $message = 'Menu item added successfully';
                    } else {
                        $error = 'Error adding menu item: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                }
                break;
                
            case 'edit':
                $id = $_POST['id'] ?? 0;
                $name = $_POST['name'] ?? '';
                $description = $_POST['description'] ?? '';
                $price = $_POST['price'] ?? 0;
                $category = $_POST['category'] ?? '';
                $status = $_POST['status'] ?? 'active';
                
                if (empty($name) || empty($price) || empty($category)) {
                    $error = 'Name, price, and category are required';
                } else {
                    $sql = "UPDATE menu_items SET name = ?, description = ?, price = ?, category = ?, status = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssdssi", $name, $description, $price, $category, $status, $id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $message = 'Menu item updated successfully';
                    } else {
                        $error = 'Error updating menu item: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? 0;
                if ($id > 0) {
                    $sql = "DELETE FROM menu_items WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "i", $id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $message = 'Menu item deleted successfully';
                    } else {
                        $error = 'Error deleting menu item: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                }
                break;
        }
    }
}

// Get all menu items
$sql = "SELECT * FROM menu_items ORDER BY category, name";
$menu_items = mysqli_query($conn, $sql);

// Get unique categories
$sql = "SELECT DISTINCT category FROM menu_items ORDER BY category";
$categories = mysqli_query($conn, $sql);
$category_list = [];
while ($row = mysqli_fetch_assoc($categories)) {
    $category_list[] = $row['category'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Restaurant POS</title>
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="menu.php">Menu</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Menu Management</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                <i class="bi bi-plus-lg"></i> Add New Item
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = mysqli_fetch_assoc($menu_items)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-item" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editItemModal"
                                                data-id="<?php echo $item['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($item['description']); ?>"
                                                data-price="<?php echo $item['price']; ?>"
                                                data-category="<?php echo htmlspecialchars($item['category']); ?>"
                                                data-status="<?php echo $item['status']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category" list="categories" required>
                            <datalist id="categories">
                                <?php foreach ($category_list as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="edit_category" name="category" list="categories" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit button clicks
        document.querySelectorAll('.edit-item').forEach(button => {
            button.addEventListener('click', function() {
                const data = this.dataset;
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_name').value = data.name;
                document.getElementById('edit_description').value = data.description;
                document.getElementById('edit_price').value = data.price;
                document.getElementById('edit_category').value = data.category;
                document.getElementById('edit_status').value = data.status;
            });
        });
    </script>
</body>
</html> 