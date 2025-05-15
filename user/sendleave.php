<?php
include('conn/conn.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/table.css">

    <title>Dashboard</title>
</head>
<style>
    .card {
        max-width: 1000px;
        margin: 2rem auto;
    }

    .table {
        width: 100%;
    }

    .table td:first-child {
        width: 200px;
        background-color: #f8f9fa;
    }

    .table td:last-child {
        width: 70%;
    }
</style>

<body>
    <main class="main container3" id="main">
        <?php
        include('component/sidebar.php');

        // 1. ดึง staffstatus และ department ของผู้ใช้จากตาราง employees
        $sql_user_info = "SELECT staffstatus, department FROM employees WHERE id = ?";
        $stmt_user_info = $conn->prepare($sql_user_info);
        $stmt_user_info->bind_param("i", $userID);
        $stmt_user_info->execute();
        $result_user_info = $stmt_user_info->get_result();
        $row_user_info = $result_user_info->fetch_assoc();
        $staffstatus = $row_user_info ? $row_user_info['staffstatus'] : null;
        $user_department = $row_user_info ? $row_user_info['department'] : null;
        $stmt_user_info->close();

        // 2. ดึงข้อมูลจากตาราง leaveday โดยใช้ empid และ staffstatus
        $leaveTypes = [];
       if ($staffstatus !== null) {
    // 1. ดึง gender ของพนักงานจากตาราง employees
    $sql_employee = "SELECT gender FROM employees WHERE id = ?";
    $stmt_employee = $conn->prepare($sql_employee);
    $stmt_employee->bind_param("i", $userID);
    $stmt_employee->execute();
    $result_employee = $stmt_employee->get_result();
    $employee = $result_employee->fetch_assoc();
    $employee_gender = $employee['gender'] ?? '';
    // $employee_gender = 'หญิง';
   
    $stmt_employee->close();

    // 2. สร้าง query สำหรับ leaveday โดยเพิ่มเงื่อนไข gender
    $sql_leaveday = "SELECT ld.leavetype, lt.leavetypeid, lt.leavetypename 
                     FROM leaveday ld 
                     JOIN leavetype lt ON ld.leavetype = lt.leavetypeid
                     JOIN employees e ON ld.empid = e.id
                     WHERE ld.empid = ? 
                       AND ld.staffstatus = ?
                       AND (lt.gender = ? OR lt.gender ='ทั้งหมด')";
    $stmt_leaveday = $conn->prepare($sql_leaveday);
    $stmt_leaveday->bind_param("iis", $userID, $staffstatus, $employee_gender);
    $stmt_leaveday->execute();
    $result_leaveday = $stmt_leaveday->get_result();

    // 3. เก็บข้อมูลลงใน array $leaveTypes
    $leaveTypes = [];
    while ($row = $result_leaveday->fetch_assoc()) {
        $leaveTypes[] = [
            'leavetypeid' => $row['leavetypeid'],
            'leavetypename' => $row['leavetypename']
        ];
    }
    $stmt_leaveday->close();

    // 4. จัดเรียงตาม leavetypeid
    usort($leaveTypes, function ($a, $b) {
        return $a['leavetypeid'] - $b['leavetypeid'];
    });
}

        // 4. คำนวณอายุงาน (totalMonths) เพื่อใช้ในส่วนอื่น (ถ้าต้องการ)
        $sql_startwork = "SELECT startwork FROM employees WHERE id = ?";
        $stmt_startwork = $conn->prepare($sql_startwork);
        $stmt_startwork->bind_param("i", $userID);
        $stmt_startwork->execute();
        $result_startwork = $stmt_startwork->get_result();
        $row = $result_startwork->fetch_assoc();

        $totalMonths = 0;
        if ($row && !empty($row['startwork'])) {
            $startwork = $row['startwork'];
            $startworkDateTime = new DateTime($startwork);

            // แปลงปี พ.ศ. เป็น ค.ศ. ถ้าจำเป็น
            if ($startworkDateTime->format('Y') > date('Y')) {
                $startworkYear = $startworkDateTime->format('Y') - 543;
                $startwork = $startworkYear . $startworkDateTime->format('-m-d');
            }

            $currentDate = new DateTime();
            $startDate = new DateTime($startwork);
            $interval = $currentDate->diff($startDate);
            $totalMonths = ($interval->y * 12) + $interval->m;
        }
        $stmt_startwork->close();

        // 5. ดึงวันหยุดจากตาราง holiday เพื่อส่งไปยัง JavaScript
        $sql_holidays = "SELECT holidayday FROM holiday";
        $result_holidays = $conn->query($sql_holidays);
        $holidays = [];
        while ($row = $result_holidays->fetch_assoc()) {
            $holidays[] = $row['holidayday'];
        }
        ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">แบบฟอร์มการลา<?php echo  $employee_gender; ?></h3>
            </div>
            <div class="card-body">
                <div class="leave-form-container">
                    <form id="leaveForm" action="add/process_leave.php" method="post" enctype="multipart/form-data">
                        <table class="table table-bordered">
                            <tbody>
                                <input type="hidden" name="employeeid" value="<?php echo $userID ?>">
                                <tr>
                                    <td><label for="userName" class="form-label">ชื่อ</label></td>
                                    <td>
                                        <input type="text" class="form-control" id="userName" name="userName" value="<?php echo $userName ?>" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <td><label for="position" class="form-label">ตำแหน่ง</label></td>
                                    <td>
                                        <input type="text" class="form-control" id="position" name="position" value="<?php echo $position ?>" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <td><label class="form-label">สังกัด</label></td>
                                    <td>
                                        <input type="text" class="form-control" id="headdepart" name="headdepart" value="<?php echo $headdepart ?>" readonly><br>
                                        <input type="text" class="form-control" id="department" name="department" value="<?php echo $department ?>" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <td><label class="form-label">ประเภทการลา</label></td>
                                    <td>
                                        <select class="form-select" id="leavetype" name="leavetype" required>
                                            <option value="">-- เลือกประเภทการลา --</option>
                                            <?php
                                            if (!empty($leaveTypes)) {
                                                foreach ($leaveTypes as $row) {
                                                    echo '<option value="' . $row['leavetypeid'] . '">' . $row['leavetypename'] . '</option>';
                                                }
                                            } else {
                                                echo '<option value="">ไม่มีข้อมูลประเภทการลา</option>';
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td><label for="leavestart" class="form-label">วันที่เริ่มต้นลา</label></td>
                                    <td><input type="date" class="form-control" id="leavestart" name="leavestart" min="<?php echo date('Y-m-d'); ?>" required></td>
                                </tr>
                                <tr>
                                    <td><label for="leaveend" class="form-label">วันที่สิ้นสุดการลา</label></td>
                                    <td><input type="date" class="form-control" id="leaveend" name="leaveend" min="<?php echo date('Y-m-d'); ?>" required></td>
                                </tr>
                                <!-- <tr>
                                    <td><label class="form-label">จำนวนวันที่ลา</label></td>
                                    <td><span id="leaveDaysCount">0 วัน</span></td>
                                </tr> -->
                                <tr>
                                    <td><label for="address" class="form-label">ในระหว่างลาติดต่อได้ที่</label></td>
                                    <td>
                                        <textarea class="form-control" id="address" name="address" rows="2" required placeholder="กรุณากรอกที่อยู่หรือเบอร์โทรศัพท์ที่สามารถติดต่อได้ระหว่างการลา"></textarea>
                                    </td>
                                </tr>
                                <tr id="reasonRow" style="display: none;">
                                    <td><label for="reason" class="form-label">เหตุผล</label></td>
                                    <td>
                                        <textarea class="form-control" id="reason" name="reason" rows="2" placeholder="เหตุผลการลา"></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td>ผู้บังคับบัญชา</td>
                                    <td>
                                        <?php
                                        // Get user's role level
                                        $sql_user_level = "SELECT r.level 
                                                         FROM employees e
                                                         LEFT JOIN position p ON e.position = p.positionid
                                                         LEFT JOIN role r ON p.roleid = r.roleid
                                                         WHERE e.id = ?";
                                        $stmt_level = $conn->prepare($sql_user_level);
                                        $stmt_level->bind_param("i", $userID);
                                        $stmt_level->execute();
                                        $user_level = $stmt_level->get_result()->fetch_assoc()['level'];
                                        $stmt_level->close();

                                        // Query for หัวหน้างาน (level 2) ใน department เดียวกัน
                                        $sql_sup = "SELECT e.id, CONCAT(pr.prefixname, e.fname, ' ', e.lname) AS fullname, r.level
                                                   FROM employees e 
                                                   LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                                                   LEFT JOIN position p ON e.position = p.positionid
                                                   LEFT JOIN role r ON p.roleid = r.roleid
                                                   WHERE r.level = 2 AND e.department = ? ";
                                        $stmt_sup = $conn->prepare($sql_sup);
                                        $stmt_sup->bind_param("i", $user_department);
                                        $stmt_sup->execute();
                                        $result_sup = $stmt_sup->get_result();
                                        $supervisors = $result_sup->fetch_all(MYSQLI_ASSOC);
                                        $stmt_sup->close();

                                        // Query for รองผู้อำนวยการ (level 3) ใน department เดียวกัน
                                        $sql_deputy = "SELECT e.id, CONCAT(pr.prefixname, e.fname, ' ', e.lname) AS fullname, r.level
                                                        FROM employees e 
                                                        LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                                                        LEFT JOIN position p ON e.position = p.positionid
                                                        LEFT JOIN role r ON p.roleid = r.roleid
                                                        LEFT JOIN delegation d ON e.id = d.empid
                                                        WHERE r.level = 3 AND e.department = ? OR  d.subdepartid = ?";
                                        $stmt_deputy = $conn->prepare($sql_deputy);
                                        $stmt_deputy->bind_param("ii", $user_department, $user_department);
                                        $stmt_deputy->execute();
                                        $result_deputy = $stmt_deputy->get_result();
                                        $deputies = $result_deputy->fetch_all(MYSQLI_ASSOC);
                                        $stmt_deputy->close();

                                        // Query for ผู้อำนวยการ (level 4 หรือ 3) - ไม่ต้องกรอง department
                                        $sql_director = "SELECT e.id, CONCAT(pr.prefixname, e.fname, ' ', e.lname) AS fullname, r.level
                                                         FROM employees e 
                                                         LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                                                         LEFT JOIN position p ON e.position = p.positionid
                                                         LEFT JOIN role r ON p.roleid = r.roleid
                                                         WHERE r.level = 4 OR r.level = 3";
                                        $result_director = $conn->query($sql_director);
                                        $directors = $result_director->fetch_all(MYSQLI_ASSOC);
                                        ?>
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">หัวหน้างาน</label>
                                                <select class="form-select supervisor-select" id="supervisor" name="supervisor" <?php echo ($user_level >= 2) ? 'disabled' : 'required'; ?>>
                                                    <option value="">-- เลือกหัวหน้างาน --</option>
                                                    <?php foreach ($supervisors as $sup): ?>
                                                        <option value="<?php echo $sup['id']; ?>">
                                                            <?php echo $sup['fullname']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">รองผู้อำนวยการ</label>
                                                <select class="form-select deputy-select" id="deputy" name="deputy" <?php echo ($user_level >= 3) ? 'disabled' : 'required'; ?>>
                                                    <option value="">-- เลือกรองผู้อำนวยการ --</option>
                                                    <?php foreach ($deputies as $dep): ?>
                                                        <option value="<?php echo $dep['id']; ?>">
                                                            <?php echo $dep['fullname']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">ผู้อำนวยการ</label>
                                                <select class="form-select director-select" id="director" name="director" <?php echo ($user_level >= 4) ? 'disabled' : 'required'; ?>>
                                                    <option value="">-- เลือกผู้อำนวยการ --</option>
                                                    <?php foreach ($directors as $dir): ?>
                                                        <option value="<?php echo $dir['id']; ?>">
                                                            <?php echo $dir['fullname']; ?>
                                                            <?php echo ($dir['level'] == 3) ? ' (รักษาราชการแทน)' : ''; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><label for="file" class="form-label">สิ่งที่ต้องแนบ</label></td>
                                    <td>
                                        <input type="file" class="form-control" id="file" name="file[]" multiple>
                                        <small class="form-text text-muted"></small>
                                        <small id="fileRequired" class="form-text text-danger">* จำเป็นต้องแนบใบรับรองแพทย์สำหรับการลาป่วย 3 วันขึ้นไป หรือ ลาคลอดบุตร</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="text-center">
                                        <button type="submit" class="btn btn-custom btn btn-outline-primary">ยืนยัน</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        </div>

        <script src="assets/js/main.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            $(document).ready(function() {
                // เก็บค่า min เริ่มต้น (วันที่ปัจจุบัน)
                const today = '<?php echo date('Y-m-d'); ?>';
                // เก็บวันหยุดจากตาราง holiday
                const holidays = <?php echo json_encode($holidays); ?>;

                // ฟังก์ชันคำนวณจำนวนวันที่ลา (เหมือนกับใน process_leave.php)
                function calculateLeaveDays(startDateStr, endDateStr, leaveType) {
                    if (!startDateStr || !endDateStr) return 0;

                    const startDate = new Date(startDateStr);
                    const endDate = new Date(endDateStr);
                    let days = 0;

                    let currentDate = new Date(startDate);
                    while (currentDate <= endDate) {
                        const dayOfWeek = currentDate.getDay(); // 0 (อาทิตย์) ถึง 6 (เสาร์)
                        const currentDateStr = currentDate.toISOString().split('T')[0];

                        // ตรวจสอบว่าเป็น "ลาป่วย" หรือไม่
                        if (leaveType.includes('ลาป่วย')) {
                            // ไม่นับวันเสาร์ (6) และอาทิตย์ (0) และวันหยุดในตาราง holiday
                            if (dayOfWeek !== 0 && dayOfWeek !== 6 && !holidays.includes(currentDateStr)) {
                                days++;
                            }
                        } else {
                            // ประเภทการลาอื่น ๆ (ใช้กติกาเดิม)
                            if (dayOfWeek !== 0 && dayOfWeek !== 6 && !holidays.includes(currentDateStr)) {
                                days++;
                            }
                        }

                        currentDate.setDate(currentDate.getDate() + 1);
                    }
                    return days;
                }

                // ฟังก์ชันอัพเดทจำนวนวันที่ลา
                function updateLeaveDaysCount() {
                    const startDate = $('#leavestart').val();
                    const endDate = $('#leaveend').val();
                    const leaveType = $('#leavetype option:selected').text();
                    const days = calculateLeaveDays(startDate, endDate, leaveType);
                    $('#leaveDaysCount').text(`${days} วัน`);
                    return days;
                }

                // ฟังก์ชันจัดการ attribute min ของวันที่และเหตุผล
                function updateFormFields() {
                    const leaveType = $('#leavetype option:selected').text();
                    const leavestartInput = $('#leavestart');
                    const leaveendInput = $('#leaveend');
                    const reasonRow = $('#reasonRow');
                    const reasonInput = $('#reason');

                    // จัดการวันที่
                    if (leaveType.includes('ลาป่วย')) {
                        leavestartInput.removeAttr('min');
                        leaveendInput.removeAttr('min');
                        // แสดงช่องเหตุผลและทำให้ required
                        reasonRow.show();
                        reasonInput.prop('required', true);
                    } else {
                        leavestartInput.attr('min', today);
                        leaveendInput.attr('min', today);
                        // ซ่อนช่องเหตุผลและลบ required
                        reasonRow.hide();
                        reasonInput.prop('required', false);
                        reasonInput.val(''); // ล้างค่าเหตุผลเมื่อซ่อน

                        // ล้างวันที่ถ้าย้อนหลัง
                        const startDate = new Date(leavestartInput.val());
                        const endDate = new Date(leaveendInput.val());
                        const todayDate = new Date(today);

                        if (leavestartInput.val() && startDate < todayDate) {
                            leavestartInput.val('');
                        }
                        if (leaveendInput.val() && endDate < todayDate) {
                            leaveendInput.val('');
                        }
                    }
                    updateLeaveDaysCount();
                    checkFileRequired();
                }

                // เรียกฟังก์ชันเมื่อเลือกประเภทการลา
                $('#leavetype').on('change', function() {
                    updateFormFields();
                });

                // อัพเดทจำนวนวันเมื่อวันที่เปลี่ยน
                $('#leavestart, #leaveend').on('change', function() {
                    const startDate = new Date($('#leavestart').val());
                    const endDate = new Date($('#leaveend').val());

                    if (startDate && endDate && startDate > endDate) {
                        Swal.fire({
                            icon: 'error',
                            title: 'ข้อผิดพลาด!',
                            text: 'วันที่สิ้นสุดต้องมากกว่าหรือเท่ากับวันที่เริ่มต้น'
                        });
                        $('#leavestart').val('');
                        $('#leaveend').val('');
                    }
                    updateLeaveDaysCount();
                    checkFileRequired();
                });

                // Function to check if file is required
                function checkFileRequired() {
                    const leaveType = $('#leavetype option:selected').text();
                    const days = updateLeaveDaysCount();

                    if ((leaveType.includes('ลาป่วย') && days > 2) || leaveType.includes('ลาคลอดบุตร')) {
                        $('#file').prop('required', true);
                        $('#fileRequired').show();
                        return true;
                    } else {
                        $('#file').prop('required', false);
                        $('#fileRequired').hide();
                        return false;
                    }
                }

                // Check file requirement when leave type or dates change
                $('#leavetype, #leavestart, #leaveend').on('change', function() {
                    checkFileRequired();
                });

                $('#leaveForm').on('submit', function(e) {
                    e.preventDefault();

                    if (checkFileRequired() && !$('#file').val()) {
                        Swal.fire({
                            icon: 'error',
                            title: 'กรุณาแนบไฟล์',
                            text: 'การลาป่วย 3 วันขึ้นไป หรือลาคลอดบุตร จำเป็นต้องแนบใบรับรองแพทย์'
                        });
                        return false;
                    }

                    var formData = new FormData(this);

                    $.ajax({
                        url: 'add/process_leave.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log('Raw response:', response);
                            try {
                                response = typeof response === 'string' ? JSON.parse(response) : response;
                                console.log('Parsed response:', response);
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'สำเร็จ!',
                                        text: response.message,
                                        showConfirmButton: true
                                    }).then(() => {
                                        window.location.href = 'leavestatus.php';
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'ไม่สามารถบันทึกได้',
                                        text: response.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล',
                                        confirmButtonText: 'ตกลง'
                                    });
                                }
                            } catch (e) {
                                console.error('JSON Parse Error:', e);
                                console.error('Response causing error:', response);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ข้อผิดพลาด!',
                                    text: 'เกิดข้อผิดพลาดในการประมวลผลข้อมูล: ' + response,
                                    confirmButtonText: 'ตกลง'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', status, error);
                            console.error('XHR:', xhr);
                            console.error('Response Text:', xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'ข้อผิดพลาด!',
                                text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + (xhr.responseText || 'ไม่สามารถดึงข้อมูลเพิ่มเติมได้'),
                                confirmButtonText: 'ตกลง'
                            });
                        }
                    });
                });

                // เรียกฟังก์ชันตอนโหลดหน้าเพื่อตั้งค่าสถานะเริ่มต้น
                updateFormFields();
            });
        </script>
        <?php include('component/footer.php'); ?>
    </main>
</body>

</html>

<?php
$conn->close();
?>