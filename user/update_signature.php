<?php
include('conn/conn.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    
    // Handle file upload
    if(isset($_FILES['signature']) && $_FILES['signature']['error'] == 0) {
        $target_dir = "../admin/uploads/";
        $file_extension = strtolower(pathinfo($_FILES["signature"]["name"], PATHINFO_EXTENSION));
        $new_filename = "signature_" . $id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["signature"]["tmp_name"]);
        if($check !== false) {
            if (move_uploaded_file($_FILES["signature"]["tmp_name"], $target_file)) {
                // Update database
                $sql = "UPDATE employees SET signature = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_filename, $id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "อัพเดทลายเซ็นสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัพเดทข้อมูล";
                }
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัพโหลดไฟล์";
            }
        } else {
            $_SESSION['error'] = "ไฟล์ที่อัพโหลดไม่ใช่รูปภาพ";
        }
    }
    
    header("Location: personal.php");
    exit();
}
?>