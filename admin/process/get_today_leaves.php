<?php
include('../conn/conn.php');
header('Content-Type: application/json');

$sql = "SELECT 
            CONCAT(p.prefixname, e.fname, ' ', e.lname) as fullname,
            lt.leavetypename as leavetype
        FROM leaves l
        JOIN employees e ON l.employeesid = e.id
        JOIN prefix p ON e.prefix = p.prefixid
        JOIN leavetype lt ON l.leavetype = lt.leavetypeid
        WHERE DATE(l.leavestart) <= CURDATE() 
        AND DATE(l.leaveend) >= CURDATE()
       AND l.leavestatus = 'อนุมัติ' 
        ORDER BY e.fname ASC";

$result = $conn->query($sql);
$leaves = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $leaves[] = $row;
    }
}

echo json_encode($leaves);
$conn->close();
?>