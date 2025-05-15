<?php
session_start();
ob_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

include('../conn/conn.php');

date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json');

$response = ['type' => 'error', 'message' => 'คำขอไม่ถูกต้อง'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("วิธีการร้องขอไม่ถูกต้อง: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode($response);
    exit;
}

if (!isset($_POST['action'])) {
    error_log("ไม่มีการระบุการกระทำในข้อมูล POST");
    echo json_encode($response);
    exit;
}

$action = $_POST['action'];

if ($conn->connect_error) {
    error_log("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(['type' => 'error', 'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว']);
    exit;
}

if ($action === 'delegate') {
    $empid = isset($_POST['empid']) ? intval($_POST['empid']) : 0;
    $subdepartid = isset($_POST['subdepartid']) ? intval($_POST['subdepartid']) : 0;

    error_log("การมอบอำนาจ: empid=$empid, subdepartid=$subdepartid");

    if ($empid <= 0 || $subdepartid <= 0) {
        $response['message'] = 'กรุณาเลือกพนักงานและหน่วยงานย่อย';
        error_log("ข้อมูลไม่ถูกต้อง: empid=$empid, subdepartid=$subdepartid");
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();

    $sql_check_subdepart = "SELECT subdepartid FROM subdepart WHERE subdepartid = ?";
    $stmt_check_subdepart = $conn->prepare($sql_check_subdepart);
    $stmt_check_subdepart->bind_param('i', $subdepartid);
    $stmt_check_subdepart->execute();
    $result_check_subdepart = $stmt_check_subdepart->get_result();
    if ($result_check_subdepart->num_rows == 0) {
        $conn->rollback();
        $response['message'] = "หน่วยงานย่อย ID $subdepartid ไม่มีอยู่ในระบบ";
        error_log("หน่วยงานย่อย ID $subdepartid ไม่มีอยู่ในระบบ");
        echo json_encode($response);
        exit;
    }
    $stmt_check_subdepart->close();

    $sql_check_employee = "SELECT e.id FROM employees e
                           JOIN position p ON e.position = p.positionid
                           JOIN role r ON p.roleid = r.roleid
                           WHERE e.id = ? AND r.rolename IN ('รองผู้อำนวยการ', 'หัวหน้างาน')";
    $stmt_check_employee = $conn->prepare($sql_check_employee);
    $stmt_check_employee->bind_param('i', $empid);
    $stmt_check_employee->execute();
    $result_check_employee = $stmt_check_employee->get_result();
    if ($result_check_employee->num_rows == 0) {
        $conn->rollback();
        $response['message'] = "พนักงาน ID $empid ไม่มีบทบาทรองผู้อำนวยการหรือหัวหน้างาน";
        error_log("พนักงาน ID $empid ไม่มีบทบาทรองผู้อำนวยการหรือหัวหน้างาน");
        echo json_encode($response);
        exit;
    }
    $stmt_check_employee->close();

    $sql_check = "SELECT delegation_id FROM delegation WHERE empid = ? AND subdepartid = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param('ii', $empid, $subdepartid);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $conn->rollback();
        $response['message'] = "พนักงาน ID $empid ได้รับมอบอำนาจในหน่วยงานย่อย ID $subdepartid อยู่แล้ว";
        error_log("พนักงาน ID $empid ได้รับมอบอำนาจในหน่วยงานย่อย ID $subdepartid อยู่แล้ว");
        echo json_encode($response);
        exit;
    }
    $stmt_check->close();

    $sql_insert = "INSERT INTO delegation (empid, subdepartid) VALUES (?, ?)";
    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param('ii', $empid, $subdepartid);
    $stmt->execute();
    $response = [
        'type' => 'success',
        'message' => "มอบอำนาจสำเร็จสำหรับพนักงาน ID $empid ในหน่วยงานย่อย ID $subdepartid"
    ];
    error_log("มอบอำนาจอนุมัติการลาให้ empid $empid สำหรับ subdepartid $subdepartid");
    $stmt->close();
    $conn->commit();
} elseif ($action === 'revoke') {
    $delegation_id = isset($_POST['delegation_id']) ? intval($_POST['delegation_id']) : 0;

    error_log("การยกเลิกอำนาจ: delegation_id=$delegation_id");

    if ($delegation_id <= 0) {
        $response['message'] = 'กรุณาระบุ Delegation ID';
        error_log("delegation_id ไม่ถูกต้อง: $delegation_id");
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();

    $sql_delete = "DELETE FROM delegation WHERE delegation_id = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param('i', $delegation_id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $conn->rollback();
        $response['message'] = "Delegation ID $delegation_id ไม่มีอยู่ในระบบ";
        error_log("Delegation ID $delegation_id ไม่มีอยู่ในระบบ");
        echo json_encode($response);
        exit;
    }
    $response = [
        'type' => 'success',
        'message' => "ยกเลิกอำนาจสำเร็จ (Delegation ID: $delegation_id)"
    ];
    error_log("ยกเลิกการมอบอำนาจ ID $delegation_id");
    $stmt->close();
    $conn->commit();
} else {
    $response['message'] = 'การดำเนินการไม่ถูกต้อง';
    error_log("การกระทำไม่ถูกต้อง: $action");
}

$conn->close();
echo json_encode($response);
ob_end_flush();
?>