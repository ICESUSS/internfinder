<?php
include 'config.php';
$table = 'tb_company_detail';
$result = $conn->query("DESCRIBE $table");
echo "Structure of $table:\n";
while($row = $result->fetch_assoc()) {
    print_r($row);
}

$table2 = 'tb_internship';
$result2 = $conn->query("DESCRIBE $table2");
echo "\nStructure of $table2:\n";
while($row = $result2->fetch_assoc()) {
    print_r($row);
}
?>
