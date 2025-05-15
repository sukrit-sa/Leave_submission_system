<?php
header('Content-Type: application/json; charset=utf-8');
include("../sql/conn.php");

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // ตรวจสอบว่ามีพนักงานอยู่จริงหรือไม่
    $check_sql = "SELECT id FROM employees WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่พบข้อมูลพนักงานที่ต้องการลบ'
        ]);
        exit;
    }

    // เริ่ม transaction เพื่อให้แน่ใจว่าการลบทั้งหมดสำเร็จหรือยกเลิกพร้อมกัน
    $conn->begin_transaction();

    try {
        // ลบข้อมูลในตาราง leaves ที่อ้างอิง employees.id
        $sql_leaves = "DELETE FROM leaves WHERE employeesid = ?";
        $stmt_leaves = $conn->prepare($sql_leaves);
        $stmt_leaves->bind_param("i", $id);
        $stmt_leaves->execute();
        $stmt_leaves->close();

        // ลบข้อมูลในตาราง leaveday ที่อ้างอิง employees.id
        $sql_leaveday = "DELETE FROM leaveday WHERE empid = ?";
        $stmt_leaveday = $conn->prepare($sql_leaveday);
        $stmt_leaveday->bind_param("i", $id);
        $stmt_leaveday->execute();
        $stmt_leaveday->close();

        // ลบไฟล์ที่เกี่ยวข้อง (ถ้ามี)
        $sql_files = "SELECT file FROM leaves WHERE employeesid = ?";
        $stmt_files = $conn->prepare($sql_files);
        $stmt_files->bind_param("i", $id);
        $stmt_files->execute();
        $result_files = $stmt_files->get_result();

        while ($row = $result_files->fetch_assoc()) {
            if ($row['file']) {
                $file_path = "../uploads/leaves/" . $row['file'];
                if (file_exists($file_path)) {
                    unlink($file_path); // ลบไฟล์
                }
            }
        }
        $stmt_files->close();

        // ลบข้อมูลในตาราง employees
        $sql = "DELETE FROM employees WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception("เกิดข้อผิดพลาดในการลบข้อมูลพนักงาน: " . $stmt->error);
        }

        if ($stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode([
                'status' => 'success',
                'message' => 'ลบข้อมูลพนักงานและข้อมูลที่เกี่ยวข้องเรียบร้อยแล้ว'
            ]);
        } else {
            throw new Exception("ไม่สามารถลบข้อมูลพนักงานได้");
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }

    $stmt->close();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่ได้ระบุรหัสพนักงาน'
    ]);
}

$conn->close();
?>