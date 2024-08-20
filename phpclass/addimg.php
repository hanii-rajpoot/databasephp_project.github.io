<?php
// Include the database connection file
include 'connect.php';

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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a real image
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check !== false) {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Image path to be saved in the database
            $image_path = "http://localhost/phpclass/" . $target_file;
            $title = $_POST['title'];
            $description = $_POST['description'];
            $price = $_POST['price'];

            // Insert data into the database
            $stmt = $conn->prepare("INSERT INTO product (image, title, description, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $image_path, $title, $description, $price);

            if ($stmt->execute()) {
                echo '<div class="message"><p>New product added successfully</p></div>';
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

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        .container h2 {
            margin-top: 0;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-group button {
            width: 100%;
            padding: 10px;
            background-color: #5cb85c;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #4cae4c;
        }
        .message {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Add Product</h2>
    <form action="addimg.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="image">Image</label>
            <input type="file" id="image" name="image" required>
        </div>
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" required></textarea>
        </div>
        <div class="form-group">
            <label for="price">Price</label>
            <input type="number" step="0.01" id="price" name="price" required>
        </div>
        <div class="form-group">
            <button type="submit">Add Product</button>
        </div>
    </form>
</div>

</body>
</html>
