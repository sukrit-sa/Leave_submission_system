<?php
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $prefix = $_POST['prefix'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    // Check if email already exists
    $check_email = "SELECT email FROM employees WHERE email = ?";
    $stmt_check = $conn->prepare($check_email);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'อีเมลนี้ถูกใช้งานแล้ว']);
        exit();
    }
    
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $subdepart = $_POST['subdepart'];
    $position = $_POST['position'];
    $status = $_POST['status'];
    // Format the date to MySQL format (YYYY-MM-DD)
    $start_date = date('Y-m-d', strtotime($_POST['start_date']));

    // Handle file upload
    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        // Insert into database
        $sql = "INSERT INTO employees (prefix,gender,fname, lname, email, password, department, position, staffstatus, startwork, pic) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssiiiss", $prefix,$gender ,$firstname, $lastname, $email, $password, $subdepart, $position, $status, $start_date, $new_filename);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'เพิ่มข้อมูลพนักงานสำเร็จ']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการอัพโหลดรูปภาพ']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>