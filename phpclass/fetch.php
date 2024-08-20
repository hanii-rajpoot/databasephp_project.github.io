<?php
// Include the database connection file
include 'connect.php';

// Fetch products from the database
$sql = "SELECT * FROM product";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .container {
    width: 80%;
    max-width: 1200px;
    margin: 20px auto;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
}

.card {
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    overflow: hidden;
    width: calc(33.333% - 20px); /* 33.333% width for 3 cards in a row */
    box-sizing: border-box;
}

.card img {
    width: 100%;
    height: auto;
}

.card-content {
    padding: 15px;
}

.card-content h3 {
    margin: 0;
    font-size: 18px;
    color: #333;
}

.card-content p {
    margin: 10px 0;
    color: #666;
}

.card-content .price {
    font-size: 16px;
    color: #e74c3c;
    font-weight: bold;
}

@media (max-width: 768px) {
    .card {
        width: calc(50% - 20px); /* 50% width for 2 cards in a row on smaller screens */
    }
}

@media (max-width: 480px) {
    .card {
        width: calc(100% - 20px); /* 100% width for 1 card in a row on very small screens */
    }
}

    </style>
</head>
<body>

<div class="container">
    <?php
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo '<div class="card">';
            echo '<img src="' . $row["image"] . '" alt="' . htmlspecialchars($row["title"]) . '">';
            echo '<div class="card-content">';
            echo '<h3>' . htmlspecialchars($row["title"]) . '</h3>';
            echo '<p>' . htmlspecialchars($row["description"]) . '</p>';
            echo '<p class="price">$' . number_format($row["price"], 2) . '</p>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<p>No products found.</p>';
    }
    $conn->close();
    ?>
</div>

</body>
</html>
