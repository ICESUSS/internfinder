<?php
include 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS tb_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    std_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table tb_notifications created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
