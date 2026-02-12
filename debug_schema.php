<?php
include 'config.php';
$res = $conn->query("DESCRIBE tb_company");
if (!$res) {
    echo "Error: " . $conn->error;
} else {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}
?>
