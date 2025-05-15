<?php
include('conn/conn.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    
    if (isset($_FILES['profilePic'])) {
        $file = $_FILES['profilePic'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileError = $file['error'];
        
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $fileExt;
        
        $uploadDir = '../admin/uploads/';
        $uploadPath = $uploadDir . $newFileName;
        
        if ($fileError === 0) {
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                // Update database
                $sql = "UPDATE employees SET pic = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $newFileName, $id);
                
                if ($stmt->execute()) {
                    // Update session variable
                    $_SESSION['img'] = $newFileName;
                    $_SESSION['success'] = "Profile picture updated successfully.";
                } else {
                    $_SESSION['error'] = "Error updating profile picture in database.";
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = "Error uploading file.";
            }
        } else {
            $_SESSION['error'] = "Error in file upload.";
        }
    }
}

// Redirect back to personal.php
header("Location: personal.php");
exit();
?>
