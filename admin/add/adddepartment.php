<?php
include('../conn/conn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $response = array();
    
    try {
        $headepartname = mysqli_real_escape_string($conn, $_POST['headepartname']);
        
        if (empty($headepartname)) {
            throw new Exception("กรุณากรอกชื่อแผนก");
        }

        $check_sql = "SELECT * FROM headepart WHERE headepartname = '$headepartname'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            throw new Exception("มีชื่อแผนกนี้อยู่ในระบบแล้ว");
        }

        $sql = "INSERT INTO headepart (headepartname) VALUES ('$headepartname')";
        
        if (mysqli_query($conn, $sql)) {
            $response['status'] = 'success';
            $response['message'] = 'เพิ่มข้อมูลสำเร็จ';
        } else {
            throw new Exception("Error: " . $sql . "<br>" . mysqli_error($conn));
        }
    } catch (Exception $e) {
        $response['status'] = 'error';
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    mysqli_close($conn);
} else {
    header("Location: ../department.php");
    exit();
}
?>