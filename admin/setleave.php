<?php
include('conn/conn.php');

// ตรวจสอบว่า session มี userID หรือไม่ (สมมติว่ามีการกำหนด $userID อยู่แล้ว)

// คำนวณอายุงาน (totalMonths)
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

// กำหนดวันที่ปัจจุบันสำหรับ send_date
$currentDate = date('Y-m-d');

// ดึงวันหยุดจากตาราง holiday เพื่อส่งไปยัง JavaScript
$sql_holidays = "SELECT holidayday FROM holiday";
$result_holidays = $conn->query($sql_holidays);
$holidays = [];
while ($row = $result_holidays->fetch_assoc()) {
    $holidays[] = $row['holidayday'];
}
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
</head>

<body>
    <main class="main container3" id="main">
        <?php include('component/sidebar.php');

        // ดึงข้อมูลผู้ใช้ทั้งหมดสำหรับ dropdown
        $sql_all_users = "SELECT e.id, CONCAT(pr.prefixname, e.fname, ' ', e.lname) AS fullname 
                          FROM employees e 
                          LEFT JOIN prefix pr ON e.prefix = pr.prefixid";
        $result_all_users = $conn->query($sql_all_users);
        $all_users = $result_all_users->fetch_all(MYSQLI_ASSOC);

        // ดึงข้อมูลของผู้ใช้ที่ล็อกอินเพื่อตั้งค่าเริ่มต้น
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
        $stmt_user_info->bind_param("i", $userID);
        $stmt_user_info->execute();
        $result_user_info = $stmt_user_info->get_result();
        $row_user_info = $result_user_info->fetch_assoc();
        $stmt_user_info->close();

        $userName = $row_user_info ? $row_user_info['fullname'] : '';
        $position = $row_user_info ? $row_user_info['positionname'] : '';
        $staffstatus = $row_user_info ? $row_user_info['staffstatus'] : null;
        $subdepartname = $row_user_info ? $row_user_info['subdepartname'] : '';
        $headepartname = $row_user_info ? $row_user_info['headepartname'] : '';

        // ดึงข้อมูลจากตาราง leaveday โดยใช้ empid และ staffstatus
        $leaveTypes = [];
        if ($staffstatus !== null) {
            $sql_leaveday = "SELECT ld.leavetype, lt.leavetypeid, lt.leavetypename 
                             FROM leaveday ld 
                             JOIN leavetype lt ON ld.leavetype = lt.leavetypeid 
                             WHERE ld.empid = ? AND ld.staffstatus = ?";
            $stmt_leaveday = $conn->prepare($sql_leaveday);
            $stmt_leaveday->bind_param("ii", $userID, $staffstatus);
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
        ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">แบบฟอร์มการลา</h3>
            </div>
            <div class="card-body">
                <div class="leave-form-container">
                    <form id="leaveForm" action="add/process_setleave.php" method="post" enctype="multipart/form-data">
                        <table class="table table-bordered">
                            <tbody>
                                <input type="hidden" name="employeeid" id="employeeid" value="<?php echo $userID ?>">
                                <tr>
                                    <td><label for="employee_select" class="form-label">เลือกผู้ใช้</label></td>
                                    <td>
                                        <select class="form-select" id="employee_select" name="employee_select">
                                            <?php foreach ($all_users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>" <?php echo $user['id'] == $userID ? 'selected' : ''; ?>>
                                                    <?php echo $user['fullname']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
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
                                        <input type="text" class="form-control" id="headepart" name="headepart" value="<?php echo $headepartname ?>" readonly><br>
                                        <input type="text" class="form-control" id="subdepart" name="subdepart" value="<?php echo $subdepartname ?>" readonly>
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
                                    <td><input type="date" class="form-control" id="leavestart" name="leavestart" required></td>
                                </tr>
                                <tr>
                                    <td><label for="leaveend" class="form-label">วันที่สิ้นสุดการลา</label></td>
                                    <td><input type="date" class="form-control" id="leaveend" name="leaveend" required></td>
                                </tr>
                                <!-- <tr>
                                    <td><label class="form-label">จำนวนวันที่ลา</label></td>
                                    <td><span id="leaveDaysCount">0 วัน</span></td>
                                </tr> -->
                                <tr>
                                    <td><label for="send_date" class="form-label">วันที่ส่งใบลา</label></td>
                                    <td><input type="date" class="form-control" id="send_date" name="send_date" value="<?php echo $currentDate; ?>" required></td>
                                </tr>
                                <tr>
                                    <td><label for="address" class="form-label">ในระหว่างลาติดต่อได้ที่</label></td>
                                    <td>
                                        <textarea class="form-control" id="address" name="address" rows="2" required placeholder="กรุณากรอกที่อยู่หรือเบอร์โทรศัพท์ที่สามารถติดต่อได้ระหว่างการลา"></textarea>
                                    </td>
                                </tr>
                                <tr id="reasonRow" style="display: none;">
                                    <td><label for="reasontext" class="form-label">เหตุผลลา</label></td>
                                    <td>
                                        <textarea class="form-control" id="reasontext" name="reasontext" rows="2" placeholder="เหตุผลการลา"></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td>ผู้บังคับบัญชา</td>
                                    <td>
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">หัวหน้างาน</label>
                                                <select class="form-select supervisor-select" id="supervisor" name="supervisor">
                                                    <option value="">-- เลือกหัวหน้างาน --</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">วันที่หัวหน้ารับรอง</label>
                                                <input type="date" class="form-control" id="approved_date1" name="approved_date1" disabled>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">รองผู้อำนวยการ</label>
                                                <select class="form-select deputy-select" id="deputy" name="deputy">
                                                    <option value="">-- เลือกรองผู้อำนวยการ --</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label">วันที่รองผอ.รับรอง</label>
                                                <input type="date" class="form-control" id="approved_date2" name="approved_date2" disabled>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="form-label">ผู้อำนวยการ</label>
                                                <select class="form-select director-select" id="director" name="director">
                                                    <option value="">-- เลือกผู้อำนวยการ --</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">วันที่ผอ.อนุมัติ</label>
                                                <input type="date" class="form-control" id="approved_date3" name="approved_date3" disabled>
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
                // เก็บวันหยุดจากตาราง holiday
                const holidays = <?php echo json_encode($holidays); ?>;

                // ฟังก์ชันคำนวณจำนวนวันที่ลา
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

                // ฟังก์ชันสำหรับดึงข้อมูลผู้ใช้เมื่อเลือกจาก dropdown
                function fetchUserData(employeeId) {
                    $.ajax({
                        url: 'fetch_user_data.php',
                        type: 'POST',
                        data: {
                            employee_id: employeeId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                // อัพเดตข้อมูลในฟอร์ม
                                $('#employeeid').val(employeeId);
                                $('#userName').val(response.data.fullname);
                                $('#position').val(response.data.position);
                                $('#headepart').val(response.data.headepart);
                                $('#subdepart').val(response.data.subdepart);

                                // อัพเดตประเภทการลา
                                let leavetypeOptions = '<option value="">-- เลือกประเภทการลา --</option>';
                                if (response.data.leave_types.length > 0) {
                                    response.data.leave_types.forEach(function(leaveType) {
                                        leavetypeOptions += `<option value="${leaveType.leavetypeid}">${leaveType.leavetypename}</option>`;
                                    });
                                } else {
                                    leavetypeOptions += '<option value="">ไม่มีข้อมูลประเภทการลา</option>';
                                }
                                $('#leavetype').html(leavetypeOptions);

                                // อัพเดตผู้บังคับบัญชา
                                let supervisorOptions = '<option value="">-- เลือกหัวหน้างาน --</option>';
                                response.data.supervisors.forEach(function(sup) {
                                    supervisorOptions += `<option value="${sup.id}">${sup.fullname}</option>`;
                                });
                                $('#supervisor').html(supervisorOptions);

                                let deputyOptions = '<option value="">-- เลือกรองผู้อำนวยการ --</option>';
                                response.data.deputies.forEach(function(dep) {
                                    deputyOptions += `<option value="${dep.id}">${dep.fullname}</option>`;
                                });
                                $('#deputy').html(deputyOptions);

                                let directorOptions = '<option value="">-- เลือกผู้อำนวยการ --</option>';
                                response.data.directors.forEach(function(dir) {
                                    directorOptions += `<option value="${dir.id}">${dir.fullname}${dir.level == 3 ? ' (รักษาราชการแทน)' : ''}</option>`;
                                });
                                $('#director').html(directorOptions);

                                // เรียกฟังก์ชันจัดการวันที่รับรอง/อนุมัติ
                                updateApprovalDates();
                                updateLeaveDaysCount(); // อัพเดทจำนวนวันที่ลาเมื่อดึงข้อมูลผู้ใช้
                                updateFormFields(); // อัพเดทฟอร์ม รวมถึงการจัดการช่องเหตุผล
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ข้อผิดพลาด!',
                                    text: response.message || 'ไม่สามารถดึงข้อมูลผู้ใช้ได้',
                                    confirmButtonText: 'ตกลง'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'ข้อผิดพลาด!',
                                text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง',
                                confirmButtonText: 'ตกลง'
                            });
                        }
                    });
                }

                // ฟังก์ชันจัดการสถานะของ input วันที่รับรอง/อนุมัติ
                function updateApprovalDates() {
                    if ($('#supervisor').val() === '' || $('#supervisor').prop('disabled')) {
                        $('#approved_date1').prop('disabled', true);
                        $('#approved_date1').val('');
                    } else {
                        $('#approved_date1').prop('disabled', false);
                    }

                    if ($('#deputy').val() === '' || $('#deputy').prop('disabled')) {
                        $('#approved_date2').prop('disabled', true);
                        $('#approved_date2').val('');
                    } else {
                        $('#approved_date2').prop('disabled', false);
                    }

                    if ($('#director').val() === '' || $('#director').prop('disabled')) {
                        $('#approved_date3').prop('disabled', true);
                        $('#approved_date3').val('');
                    } else {
                        $('#approved_date3').prop('disabled', false);
                    }
                }

                // ฟังก์ชันจัดการวันที่และช่องเหตุผลสำหรับ "ลาป่วย"
                function updateFormFields() {
                    const leaveType = $('#leavetype option:selected').text();
                    const leavestartInput = $('#leavestart');
                    const leaveendInput = $('#leaveend');
                    const reasonRow = $('#reasonRow');
                    const reasonInput = $('#reasontext');

                    // จัดการวันที่
                    if (leaveType.includes('ลาป่วย')) {
                        leavestartInput.removeAttr('min');
                        leavestartInput.removeAttr('max');
                        leaveendInput.removeAttr('min');
                        leaveendInput.removeAttr('max');
                        // แสดงช่องเหตุผลและทำให้ required
                        reasonRow.show();
                        reasonInput.prop('required', true);
                    } else {
                        leavestartInput.attr('min', '<?php echo $currentDate; ?>');
                        leaveendInput.attr('min', '<?php echo $currentDate; ?>');
                        // ซ่อนช่องเหตุผลและลบ required
                        reasonRow.hide();
                        reasonInput.prop('required', false);
                        reasonInput.val(''); // ล้างค่าเหตุผลเมื่อซ่อน

                        // ล้างวันที่ถ้าย้อนหลัง
                        const startDate = new Date(leavestartInput.val());
                        const endDate = new Date(leaveendInput.val());
                        const todayDate = new Date('<?php echo $currentDate; ?>');

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

                // เมื่อเลือกผู้ใช้จาก dropdown
                $('#employee_select').on('change', function() {
                    const employeeId = $(this).val();
                    if (employeeId) {
                        fetchUserData(employeeId);
                    }
                });

                // เมื่อเลือกประเภทการลา
                $('#leavetype').on('change', function() {
                    updateFormFields();
                });

                // เมื่อเลือกผู้บังคับบัญชา ให้อัพเดตสถานะของ input วันที่
                $('#supervisor, #deputy, #director').on('change', function() {
                    updateApprovalDates();
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

                // Date validation for send_date and approved dates
                $('#send_date, #approved_date1, #approved_date2, #approved_date3').on('change', function() {
                    const sendDate = $('#send_date').val() ? new Date($('#send_date').val()) : null;
                    const approvedDate1 = $('#approved_date1').val() ? new Date($('#approved_date1').val()) : null;
                    const approvedDate2 = $('#approved_date2').val() ? new Date($('#approved_date2').val()) : null;
                    const approvedDate3 = $('#approved_date3').val() ? new Date($('#approved_date3').val()) : null;

                    // ตรวจสอบว่า send_date ต้องมีค่า (เพราะฟิลด์นี้เป็น required)
                    if (!sendDate) {
                        Swal.fire({
                            icon: 'error',
                            title: 'ข้อผิดพลาด!',
                            text: 'กรุณาระบุวันที่ส่งใบลา'
                        });
                        $('#send_date').val('');
                        return;
                    }
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

                    // ฟังก์ชันสำหรับแปลงวันที่จาก "dd/MM/yyyy" เป็น "yyyy-MM-dd"
                    function convertDateFormat(dateStr) {
                        if (!dateStr) return null; // ถ้าไม่มีค่า ให้คืนเป็น null
                        if (dateStr.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
                            const parts = dateStr.split('/');
                            return `${parts[2]}-${parts[1]}-${parts[0]}`; // แปลงเป็น "yyyy-MM-dd"
                        }
                        return dateStr; // ถ้าเป็นรูปแบบ "yyyy-MM-dd" อยู่แล้ว ให้ส่งคืนตามเดิม
                    }

                    // ดึงค่าและแปลงรูปแบบวันที่
                    const sendDate = convertDateFormat($('#send_date').val()) || null;
                    const approvedDate1 = convertDateFormat($('#approved_date1').val()) || null;
                    const approvedDate2 = convertDateFormat($('#approved_date2').val()) || null;
                    const approvedDate3 = convertDateFormat($('#approved_date3').val()) || null;

                    // สร้าง FormData ใหม่และกำหนดค่าเอง
                    var formData = new FormData();
                    formData.append('employeeid', $('#employeeid').val() || '');
                    formData.append('employee_select', $('#employee_select').val() || '');
                    formData.append('userName', $('#userName').val() || '');
                    formData.append('position', $('#position').val() || '');
                    formData.append('headepart', $('#headepart').val() || '');
                    formData.append('subdepart', $('#subdepart').val() || '');
                    formData.append('leavetype', $('#leavetype').val() || '');
                    formData.append('leavestart', $('#leavestart').val() || '');
                    formData.append('leaveend', $('#leaveend').val() || '');
                    formData.append('send_date', sendDate || '');
                    formData.append('address', $('#address').val() || '');
                    formData.append('supervisor', $('#supervisor').val() || '');
                    formData.append('reasontext', $('#reasontext').val() || '');

                    // เพิ่ม approved_date1 เฉพาะเมื่อมีค่า
                    if (approvedDate1) {
                        formData.append('approved_date1', approvedDate1);
                    }

                    formData.append('deputy', $('#deputy').val() || '');

                    // เพิ่ม approved_date2 เฉพาะเมื่อมีค่า
                    if (approvedDate2) {
                        formData.append('approved_date2', approvedDate2);
                    }

                    formData.append('director', $('#director').val() || '');

                    // เพิ่ม approved_date3 เฉพาะเมื่อมีค่า
                    if (approvedDate3) {
                        formData.append('approved_date3', approvedDate3);
                    }

                    // เพิ่มไฟล์ (ถ้ามี)
                    var files = $('#file')[0].files;
                    for (var i = 0; i < files.length; i++) {
                        formData.append('file[]', files[i]);
                    }

                    // ดีบั๊ก: ตรวจสอบค่าใน FormData
                    for (var pair of formData.entries()) {
                        console.log(pair[0] + ': ' + pair[1]);
                    }

                    $.ajax({
                        url: 'add/process_setleave.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            try {
                                response = typeof response === 'string' ? JSON.parse(response) : response;

                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'สำเร็จ!',
                                        text: 'บันทึกข้อมูลการลาเรียบร้อยแล้ว',
                                        timer: 1500,
                                        showConfirmButton: false
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
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ข้อผิดพลาด!',
                                    text: 'เกิดข้อผิดพลาดในการประมวลผลข้อมูล',
                                    confirmButtonText: 'ตกลง'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', status, error);
                            Swal.fire({
                                icon: 'error',
                                title: 'ข้อผิดพลาด!',
                                text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง',
                                confirmButtonText: 'ตกลง'
                            });
                        }
                    });
                });

                // ดึงข้อมูลเริ่มต้นเมื่อโหลดหน้า
                fetchUserData(<?php echo $userID; ?>);
            });
        </script>
          <?php include('component/footer.php'); ?>
    </main>
</body>

</html>

<?php
$conn->close();
?>