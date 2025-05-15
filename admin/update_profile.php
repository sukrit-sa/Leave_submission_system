<?php
include('conn/conn.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $prefix = $_POST['prefix'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    
    // Base SQL query
    $sql = "UPDATE employees SET 
            prefix = ?, 
            fname = ?, 
            lname = ?, 
            email = ?";
    
    // Parameters array
    $params = [$prefix, $fname, $lname, $email];
    $types = "ssss"; // string types for the parameters
    
    // If password is provided, add it to the update
    if (!empty($_POST['password']) && $_POST['password'] === $_POST['confirm_password']) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql .= ", password = ?";
        $params[] = $password;
        $types .= "s";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $id;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "อัพเดทข้อมูลสำเร็จ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัพเดทข้อมูล";
    }
    
    $stmt->close();
    header("Location: personal.php");
    exit();
}
?>