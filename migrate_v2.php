<?php
include 'config.php';

echo "Updating tb_internship table...\n";

// Check if com_email_sent exists
$result = $conn->query("SHOW COLUMNS FROM tb_internship LIKE 'com_email_sent'");
if ($result->num_rows == 0) {
    if ($conn->query("ALTER TABLE tb_internship ADD COLUMN com_email_sent TINYINT(1) DEFAULT 0 AFTER status")) {
        echo "Successfully added com_email_sent column to tb_internship.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "com_email_sent column already exists in tb_internship.\n";
}

// Also ensure tb_company.com_email exists (just in case from previous fail)
$result = $conn->query("SHOW COLUMNS FROM tb_company LIKE 'com_email'");
if ($result->num_rows == 0) {
    if ($conn->query("ALTER TABLE tb_company ADD COLUMN com_email VARCHAR(255) AFTER com_tel")) {
        echo "Successfully added com_email column to tb_company.\n";
    }
}
?>
