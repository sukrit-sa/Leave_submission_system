<?php
// Prevent any error output before JSON
error_reporting(0);

include('../conn/conn.php');

try {
    // ตรวจสอบว่ามีการส่ง date parameter มาหรือไม่
    if (!isset($_GET['date'])) {
        throw new Exception("Date parameter is required");
    }

    $date = $_GET['date'];
    
    // เพิ่ม error reporting
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    // คำสั่ง SQL ควรใช้ prepared statement
    $stmt = $conn->prepare("SELECT CONCAT(p.prefixname, e.fname, ' ', e.lname) as fullname, lt.leavetypename as leavetype 
                           FROM leaves l 
                           JOIN employees e ON l.employeesid = e.id
                           JOIN prefix p ON e.prefix = p.prefixid
                           JOIN leavetype lt ON l.leavetype = lt.leavetypeid 
                           WHERE DATE(l.leavestart) <= ? 
                           AND DATE(l.leaveend) >= ?
                           AND l.leavestatus = 'อนุมัติ'");
    $stmt->bind_param("ss", $date, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $leaves = array();
    while ($row = $result->fetch_assoc()) {
        $leaves[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($leaves);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
