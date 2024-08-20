<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mytech2";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start(); // Start the session

// Handle admin signup
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    $admin_username = $_POST['username'];
    $admin_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM admin WHERE username = ?");
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "Username already taken.";
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $admin_username, $admin_password);

        if ($stmt->execute()) {
            echo "Signup successful! You can now <a href='#' onclick='showLoginForm()'>login</a>.";
        } else {
            echo "Error: " . $stmt->error;
        }
    }
    $stmt->close();
}

// Handle admin login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $admin_username = $_POST['username'];
    $admin_password = $_POST['password'];

    $stmt = $conn->prepare("SELECT password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();

    if (password_verify($admin_password, $hashed_password)) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $admin_username;
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Invalid login credentials.";
    }
    $stmt->close();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?"); // Redirect to the login/signup page
    exit();
}

// Create the product table if it doesn't already exist
$createTableSql = "CREATE TABLE IF NOT EXISTS product (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image VARCHAR(255),
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL
)";
if ($conn->query($createTableSql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

// Handle form submission for adding products
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['title']) && isset($_FILES['image'])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a real image**
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check !== false) {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Image path to be saved in the database**
            $image_path = "http://localhost/phpclass/" . $target_file;
            $title = $_POST['title'];
            $description = $_POST['description'];
            $price = $_POST['price'];

            // Insert data into the database**
            $stmt = $conn->prepare("INSERT INTO product (image, title, description, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $image_path, $title, $description, $price);

            if ($stmt->execute()) {
                echo '<div class="message"><p>Product added successfully.</p></div>';
            } else {
                echo '<div class="message"><p>Error: ' . $stmt->error . '</p></div>';
            }

            $stmt->close();
        } else {
            echo '<div class="message"><p>Sorry, there was an error uploading your file.</p></div>';
        }
    } else {
        echo '<div class="message"><p>File is not an image.</p></div>';
    }
}

// Handle deletion of products
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    $deleteSql = "DELETE FROM product WHERE id=?";
    $stmt = $conn->prepare($deleteSql);
    $stmt->bind_param("i", $deleteId);

    if ($stmt->execute()) {
        // echo '<div class="message"><p></p></div>';
    } else {
        echo '<div class="message"><p>Error: ' . $stmt->error . '</p></div>';
    }

    $stmt->close();
}

// Handle editing of products**
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_id']) && isset($_POST['edit_title'])) {
    $edit_id = $_POST['edit_id'];
    $edit_title = $_POST['edit_title'];
    $edit_description = $_POST['edit_description'];
    $edit_price = $_POST['edit_price'];

    $image_path = null;
    if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["edit_image"]["name"]);
        if (move_uploaded_file($_FILES["edit_image"]["tmp_name"], $target_file)) {
            $image_path = "http://localhost/phpclass/" . $target_file;
        } else {
            echo '<div class="message"><p>Error uploading the file.</p></div>';
        }
    }

    if ($image_path) {
        $update_sql = "UPDATE product SET title=?, description=?, price=?, image=? WHERE id=?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssi", $edit_title, $edit_description, $edit_price, $image_path, $edit_id);
    } else {
        $update_sql = "UPDATE product SET title=?, description=?, price=? WHERE id=?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssi", $edit_title, $edit_description, $edit_price, $edit_id);
    }

    if ($stmt->execute()) {
        // echo '<div class="message"><p>Product updated successfully.</p></div>';
    } else {
        echo '<div class="message"><p>Error: ' . $stmt->error . '</p></div>';
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
         body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
            background: url('portfolio3.jpg') no-repeat center center fixed; /* Replace with your image path */
            background-size: cover;
            color: white;
            
        }
        .sidebar {
            width: 250px;
            height: 100%;
            background-color: rgba(52, 58, 64, 0.9); /* Slightly transparent background */
            padding-top: 20px;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            color: white;
        }
        .sidebar a {
            padding: 15px;
            text-align: left;
            display: block;
            color: white;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #007bff;
            color: white;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
            overflow-y: auto;
            
        }
        .container-fluid {
            padding: 15px;
        }
        .card {
            margin-bottom: 20px;
        }
        h2 {
            margin-bottom: 20px;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #eaf4e0;
        }
        h2 {
    margin-bottom: 33px;
    text-align: center;

}
.form-group {
    display: grid;
    background-color: #0d1a1a9e;
    border-radius: 23px;
}
form {
    margin: 44px 133px 50px 172px;
    text-align: center;
    font-size: 18px;
    font-size: 18px;
}
textarea {
    overflow: auto;
    resize: vertical;
    border-radius: 7px;
}
button, input {
    overflow: visible;
    border-radius: 29px;
}
.table {
    width: 100%;
    margin-bottom: 1rem;
    color: white;
}
div#login-form {
    margin-top: 135px;
}

    </style>
</head>
<body>
    <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true): ?>
        <div class="container">
            <div id="login-form">
                <h2>Admin Login</h2>
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="login">Login</button>
                    </div>
                    <div class="form-group">
                        <p>Don't have an account? <a href="#" onclick="showSignupForm()">Sign Up</a></p>
                    </div>
                </form>
            </div>

            <div id="signup-form" style="display: none;">
                <h2>Admin Signup</h2>
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="signup">Sign Up</button>
                    </div>
                    <div class="form-group">
                        <p>Already have an account? <a href="#" onclick="showLoginForm()">Login</a></p>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Sidebar -->
        <div class="sidebar">
            <a href="#" onclick="loadPage('dashboard')">Dashboard</a>
            <a href="#" onclick="loadPage('add-product')">Add Product</a>
            <a href="#" onclick="loadPage('manage-products')">Manage Products</a>
            <a href="?logout">Logout</a>
        </div>

        <!-- Page Content -->
        <div class="content">
            <div id="page-content" class="container-fluid" >
                <h2>Welcome to the Dashboard</h2>
                <p></p>
            </div>
        </div>

        <script>
            function loadPage(page) {
                var content = document.getElementById('page-content');
                if (page === 'dashboard') {
                    content.innerHTML = `
                        <h2>Dashboard</h2>
                        <p></p>
                    `;
                } else if (page === 'add-product') {
                    content.innerHTML = `
                    
                        <h2>Add Product</h2>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="title">Title</label>
                                <input type="text" id="title" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="4" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="price">Price</label>
                                <input type="text" id="price" name="price" required>
                            </div>
                            <div class="form-group">
                                <label for="image">Image</label>
                                <input type="file" id="image" name="image" required>
                            </div>
                            <div class="form-group">
                                <button type="submit">Add Product</button>
                            </div>
                        </form>
                    `;
                } else if (page === 'manage-products') {
                    <?php
                        // Fetch products from the database
                        $result = $conn->query("SELECT * FROM product");
                        $products = [];
                        while ($row = $result->fetch_assoc()) {
                            $products[] = $row;
                        }
                        echo "var products = " . json_encode($products) . ";";
                    ?>
                    var content = document.getElementById('page-content');
                    content.innerHTML = `
                        <h2>Manage Products</h2>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${products.map(product => `
                                    <tr>
                                        <td><img src="${product.image}" alt="${product.title}" style="width: 100px; height: auto;"></td>
                                        <td>${product.title}</td>
                                        <td>${product.description}</td>
                                        <td>${product.price}</td>
                                        <td>
                                            <a href="#" onclick="showEditForm(${product.id}, '${product.title}', '${product.description}', ${product.price}, '${product.image}')">Edit</a> |
                                            <a href="?delete_id=${product.id}" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                        <div id="edit-form" style="display: none;">
                            <h2>Edit Product</h2>
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" id="edit_id" name="edit_id">
                                <div class="form-group">
                                    <label for="edit_title">Title</label>
                                    <input type="text" id="edit_title" name="edit_title" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_description">Description</label>
                                    <textarea id="edit_description" name="edit_description" rows="4" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="edit_price">Price</label>
                                    <input type="text" id="edit_price" name="edit_price" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_image">Image</label>
                                    <input type="file" id="edit_image" name="edit_image">
                                </div>
                                <div class="form-group">
                                    <button type="submit">Update Product</button>
                                </div>
                            </form>
                        </div>
                    `;
                }
            }

            function showLoginForm() {
                document.getElementById('login-form').style.display = 'block';
                document.getElementById('signup-form').style.display = 'none';
            }

            function showSignupForm() {
                document.getElementById('login-form').style.display = 'none';
                document.getElementById('signup-form').style.display = 'block';
            }

            function showEditForm(id, title, description, price, image) {
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_title').value = title;
                document.getElementById('edit_description').value = description;
                document.getElementById('edit_price').value = price;
                document.getElementById('edit_image').value = "";
                document.getElementById('edit-form').style.display = 'block';
            }
        </script>
    <?php endif; ?>
</body>
</html>
