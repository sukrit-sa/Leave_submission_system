<?php
session_start();
include('conn.php');

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
</head>
<body>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Check email and password first
    $stmt = $conn->prepare("SELECT * FROM employees 
                            LEFT JOIN subdepart ON employees.department = subdepart.subdepartid
                            LEFT JOIN headepart on subdepart.headepartid = headepart.headepartid
                            LEFT JOIN prefix ON employees.prefix = prefix.prefixid
                            LEFT JOIN position ON employees.position = position.positionid
                            LEFT JOIN role on position.roleid = role.roleid
                            WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Check if user is admin
            $admin_sql = "SELECT a.adminid FROM admin a WHERE a.id = '{$row['id']}'";
            $admin_result = mysqli_query($conn, $admin_sql);

            $_SESSION['name'] =$row["prefixname"] . ' ' . $row['fname'] . ' ' . $row['lname'];
            $_SESSION['userid'] = $row['id'];
            $_SESSION["ID"] = $row["id"];
            $_SESSION["Prefix"] = $row["prefixname"];
            $_SESSION["FirstName"] = $row["fname"];
            $_SESSION["LastName"] = $row["lname"];
            $_SESSION["Staffid"] = $row["staffstatus"];
            $_SESSION["Department"] = $row["subdepartname"];
            $_SESSION["Headdepart"] = $row["headepartname"];
            $_SESSION["Position"] = $row["positionname"];
            $_SESSION["img"] = $row["pic"];
            $_SESSION["workage"] = $row["startwork"];
            $_SESSION["roleid"] = $row["roleid"];
            $_SESSION["departid"] = $row["subdepartid"];


            if (mysqli_num_rows($admin_result) == 1) {
                $_SESSION['role'] = 'admin';
                $_SESSION['success'] = "ยินดีต้อนรับ คุณ" . $_SESSION['name'];
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'เข้าสู่ระบบสำเร็จ',
                        text: 'ยินดีต้อนรับ คุณ" . $_SESSION['name'] . "',
                        timer: 1000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href='../admin/index.php';
                    });
                </script>";
            } else {
                $_SESSION['role'] = 'user';
                $_SESSION['success'] = "ยินดีต้อนรับ คุณ" . $_SESSION['name'];
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'เข้าสู่ระบบสำเร็จ',
                        text: 'ยินดีต้อนรับ คุณ" . $_SESSION['name'] . "',
                        timer: 1000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href='../user/index.php';
                    });
                </script>";
            }
        } else {
            $_SESSION['error'] = 'รหัสผ่านไม่ถูกต้อง';
            header('Location: ../index.php');
        }
    } else {
        $_SESSION['error'] = 'ไม่พบอีเมลนี้ในระบบ';
        header('Location: ../index.php');
    }
} else {
    header('Location: ../index.php');
}
mysqli_close($conn);