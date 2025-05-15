<?php
header('Content-Type: application/json');
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['name'])) {
    $id = intval($_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);

    // Check if prefix exists
    $check_sql = "SELECT * FROM prefix WHERE prefixid = $id";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        // Check for duplicate prefix name
        $duplicate_sql = "SELECT * FROM prefix WHERE prefixname = '$name' AND prefixid != $id";
        $duplicate_result = mysqli_query($conn, $duplicate_sql);
        
        if (mysqli_num_rows($duplicate_result) > 0) {
            echo json_encode(['status' => 'error', 'message' => 'คำนำหน้านี้มีอยู่แล้วในระบบ']);
        } else {
            // Update prefix
            $update_sql = "UPDATE prefix SET prefixname = '$name' WHERE prefixid = $id";
            
            if (mysqli_query($conn, $update_sql)) {
                echo json_encode(['status' => 'success', 'message' => 'อัพเดทข้อมูลสำเร็จ']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
            }
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบคำนำหน้านี้ในระบบ']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง']);
}

mysqli_close($conn);
?>