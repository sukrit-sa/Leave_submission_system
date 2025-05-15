<?php
header('Content-Type: application/json');
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']); // Convert to integer for security

    // Check if employee exists and not already an admin
    $check_sql = "SELECT id FROM employees WHERE id = $id";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        // Check if already admin
        $admin_check_sql = "SELECT id FROM admin WHERE id = $id";
        $admin_check_result = mysqli_query($conn, $admin_check_sql);

        if (mysqli_num_rows($admin_check_result) === 0) {
            // Insert new admin
            $sql = "INSERT INTO admin (id) VALUES ($id)";

            if (mysqli_query($conn, $sql)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'พนักงานนี้เป็นผู้ดูแลระบบอยู่แล้ว']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบพนักงานในระบบ']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง']);
}

mysqli_close($conn);
