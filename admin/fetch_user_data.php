<?php
include('conn/conn.php');

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาด'];

if (isset($_POST['employee_id'])) {
    $employee_id = $_POST['employee_id'];

    // ดึงข้อมูลผู้ใช้
    $sql_user_info = "SELECT e.id, CONCAT(pr.prefixname, e.fname, ' ', e.lname) AS fullname, 
                            p.positionname, e.staffstatus, 
                            sd.subdepartname, hd.headepartname 
                     FROM employees e 
                     LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                     LEFT JOIN position p ON e.position = p.positionid
                     LEFT JOIN subdepart sd ON e.department = sd.subdepartid
                     LEFT JOIN headepart hd ON sd.headepartid = hd.headepartid
                     WHERE e.id = ?";
    $stmt_user_info = $conn->prepare($sql_user_info);
    $stmt_user_info->bind_param("i", $employee_id);
    $stmt_user_info->execute();
    $result_user_info = $stmt_user_info->get_result();
    $row_user_info = $result_user_info->fetch_assoc();
    $stmt_user_info->close();

    if ($row_user_info) {
        $staffstatus = $row_user_info['staffstatus'];

        // ดึงประเภทการลา
        $leaveTypes = [];
        if ($staffstatus !== null) {
            $sql_leaveday = "SELECT ld.leavetype, lt.leavetypeid, lt.leavetypename 
                             FROM leaveday ld 
                             JOIN leavetype lt ON ld.leavetype = lt.leavetypeid 
                             WHERE ld.empid = ? AND ld.staffstatus = ?";
            $stmt_leaveday = $conn->prepare($sql_leaveday);
            $stmt_leaveday->bind_param("ii", $employee_id, $staffstatus);
            $stmt_leaveday->execute();
            $result_leaveday = $stmt_leaveday->get_result();

            while ($row = $result_leaveday->fetch_assoc()) {
                $leaveTypes[] = [
                    'leavetypeid' => $row['leavetypeid'],
                    'leavetypename' => $row['leavetypename']
                ];
            }
            $stmt_leaveday->close();

            usort($leaveTypes, function ($a, $b) {
                return $a['leavetypeid'] - $b['leavetypeid'];
            });
        }

        // ดึงระดับของผู้ใช้
        $sql_user_level = "SELECT r.level 
                          FROM employees e
                          LEFT JOIN position p ON e.position = p.positionid
                          LEFT JOIN role r ON p.roleid = r.roleid
                          WHERE e.id = ?";
        $stmt_level = $conn->prepare($sql_user_level);
        $stmt_level->bind_param("i", $employee_id);
        $stmt_level->execute();
        $user_level = $stmt_level->get_result()->fetch_assoc()['level'];
        $stmt_level->close();

        // ดึงหัวหน้างาน (level 2)
        $sql_sup = "SELECT e.id, CONCAT(pr.prefixname, e.fname, ' ', e.lname) AS fullname, r.level
                    FROM employees e 
                    LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                    LEFT JOIN position p ON e.position = p.positionid
                    LEFT JOIN role r ON p.roleid = r.roleid
                    WHERE r.level = 2";
        $stmt_sup = $conn->prepare($sql_sup);
        $stmt_sup->execute();
        $result_sup = $stmt_sup->get_result();
        $supervisors = $result_sup->fetch_all(MYSQLI_ASSOC);
        $stmt_sup->close();

        // ดึงรองผู้อำนวยการ (level 3)
        $sql_deputy = "SELECT e.id, CONCAT(pr.prefixname, e.fname, ' ', e.lname) AS fullname, r.level
                       FROM employees e 
                       LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                       LEFT JOIN position p ON e.position = p.positionid
                       LEFT JOIN role r ON p.roleid = r.roleid
                       WHERE r.level = 3";
        $stmt_deputy = $conn->prepare($sql_deputy);
        $stmt_deputy->execute();
        $result_deputy = $stmt_deputy->get_result();
        $deputies = $result_deputy->fetch_all(MYSQLI_ASSOC);
        $stmt_deputy->close();

        // ดึงผู้อำนวยการ (level 4 หรือ 3)
        $sql_director = "SELECT e.id, CONCAT(pr.prefixname, e.fname, ' ', e.lname) AS fullname, r.level
                         FROM employees e 
                         LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                         LEFT JOIN position p ON e.position = p.positionid
                         LEFT JOIN role r ON p.roleid = r.roleid
                         WHERE r.level = 4 OR r.level = 3";
        $result_director = $conn->query($sql_director);
        $directors = $result_director->fetch_all(MYSQLI_ASSOC);

        $response = [
            'status' => 'success',
            'data' => [
                'fullname' => $row_user_info['fullname'],
                'position' => $row_user_info['positionname'],
                'headepart' => $row_user_info['headepartname'],
                'subdepart' => $row_user_info['subdepartname'],
                'leave_types' => $leaveTypes,
                'supervisors' => $supervisors,
                'deputies' => $deputies,
                'directors' => $directors,
                'user_level' => $user_level
            ]
        ];
    } else {
        $response['message'] = 'ไม่พบข้อมูลผู้ใช้';
    }
}

echo json_encode($response);
$conn->close();
?>