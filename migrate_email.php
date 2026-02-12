<?php
include 'config.php';

$output = "Columns in tb_company:\n";
$result = $conn->query("SHOW COLUMNS FROM tb_company");
while($row = $result->fetch_assoc()) {
    $output .= "- " . $row['Field'] . "\n";
}

// Check if com_email exists specifically
$result = $conn->query("SHOW COLUMNS FROM tb_company LIKE 'com_email'");
if ($result->num_rows == 0) {
    $output .= "\ncom_email MISSING. Trying to add...\n";
    if ($conn->query("ALTER TABLE tb_company ADD COLUMN com_email VARCHAR(255) AFTER com_tel")) {
        $output .= "Successfully added com_email column.\n";
    } else {
        $output .= "Error adding column: " . $conn->error . "\n";
    }
} else {
    $output .= "\ncom_email already exists.\n";
}

file_put_contents('debug_schema.log', $output);
echo "Debug info saved to debug_schema.log\n";
?>
