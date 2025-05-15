<?php
include('conn/conn.php');

date_default_timezone_set('Asia/Bangkok');
$currentDate = date('d/m/Y');

// ดึงช่วงปีงบประมาณจากตาราง year
$sql_year = "SELECT yearstart1, yearend2 FROM year LIMIT 1";
$result_year = $conn->query($sql_year);
if ($result_year->num_rows == 0) {
    die("Error: ไม่พบข้อมูลช่วงปีงบประมาณ");
}
$year_data = $result_year->fetch_assoc();
$yearstart1 = $year_data['yearstart1'];
$yearend2 = $year_data['yearend2'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/table.css">
    <title>Dashboard</title>
</head>
<body>
    <?php include('component/sidebar.php'); ?>

    <main class="main container3" id="main">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">รายการใบลา</h3>
                            <p class="card-text mt-2 mb-0">วันที่ปัจจุบัน: <?php echo $currentDate; ?></p>
                        </div>
                        <div class="card-body">
                            <table id="leaveTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ลำดับ</th>
                                        <th>ยื่น</th>
                                        <th hidden>รหัสใบลา</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th>บทบาท</th>
                                        <th>ประเภท</th>
                                        <th>เริ่ม</th>
                                        <th>สิ้นสุด</th>
                                        <th>จำนวนวันที่ลามาแล้ว</th>
                                        <th>จำนวนวัน</th>
                                        <th>หัวหน้างาน</th>
                                        <th>รองผู้อำนวยการ</th>
                                        <th>ผู้อำนวยการ</th>
                                        <th>สถานะ</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT leaves.*, 
                                            CONCAT(pr.prefixname, e.fname, ' ', e.lname) as fullname, 
                                            leavetype.leavetypename, 
                                            e.id, 
                                            r.level, 
                                            r.rolename,
                                            CONCAT(pr_sup.prefixname, sup.fname, ' ', sup.lname) as supervisor_name,
                                            CONCAT(pr_dep.prefixname, dep.fname, ' ', dep.lname) as deputy_name,
                                            CONCAT(pr_dir.prefixname, dir.fname, ' ', dir.lname) as director_name
                                            FROM leaves
                                            LEFT JOIN employees e ON leaves.employeesid = e.id
                                            LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                                            LEFT JOIN leavetype ON leaves.leavetype = leavetype.leavetypeid
                                            LEFT JOIN position p ON e.position = p.positionid
                                            LEFT JOIN role r ON p.roleid = r.roleid
                                            LEFT JOIN employees sup ON leaves.approver1 = sup.id
                                            LEFT JOIN prefix pr_sup ON sup.prefix = pr_sup.prefixid
                                            LEFT JOIN employees dep ON leaves.approver2 = dep.id
                                            LEFT JOIN prefix pr_dep ON dep.prefix = pr_dep.prefixid
                                            LEFT JOIN employees dir ON leaves.approver3 = dir.id
                                            LEFT JOIN prefix pr_dir ON dir.prefix = pr_dir.prefixid
                                            WHERE leaves.leavestatus = 'รอแอดมินอนุมัติ'";
                                    $result = mysqli_query($conn, $sql);
                                    $i = 1;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $rowLevel = $row['level'] ?? 1; // ถ้าไม่มี level ตั้งเป็น 1
                                        $rowRoleName = $row['rolename'] ?? 'พนักงานทั่วไป';

                                        // คำนวณจำนวนวันที่ลามาแล้ว
                                        $employeeId = $row['employeesid'];
                                        $leaveType = $row['leavetype'];
                                        $totalDaysUsed = 0;

                                        $sql_count_leaves = "SELECT SUM(day) as total_days 
                                                            FROM leaves 
                                                            WHERE employeesid = ? 
                                                            AND leavetype = ? 
                                                            AND leavestatus = 'อนุมัติ' 
                                                            AND leavestart >= ? 
                                                            AND leaveend <= ?";
                                        $stmt_count_leaves = $conn->prepare($sql_count_leaves);
                                        $stmt_count_leaves->bind_param("iiss", $employeeId, $leaveType, $yearstart1, $yearend2);
                                        $stmt_count_leaves->execute();
                                        $result_count_leaves = $stmt_count_leaves->get_result();
                                        $leave_stats = $result_count_leaves->fetch_assoc();
                                        $totalDaysUsed = $leave_stats['total_days'] ?? 0;
                                        $stmt_count_leaves->close();
                                    ?>
                                        <tr data-level="<?php echo $rowLevel; ?>">
                                            <td><?php echo $i++; ?></td>
                                            <td><?php echo $row['note'] ?? '-'; ?></td>
                                            <td hidden><?php echo $row['leavesid']; ?></td>
                                            <td><?php echo $row['fullname']; ?></td>
                                            <td><?php echo $rowRoleName; ?></td>
                                            <td><?php echo $row['leavetypename']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['leavestart'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['leaveend'])); ?></td>
                                            <td><?php echo $totalDaysUsed; ?></td>
                                            <td>
                                                <?php
                                                $start = new DateTime($row['leavestart']);
                                                $end = new DateTime($row['leaveend']);
                                                $interval = new DateInterval('P1D');
                                                $daterange = new DatePeriod($start, $interval, $end->modify('+1 day'));

                                                $workdays = 0;
                                                $sql_holidays = "SELECT holidayday FROM holiday";
                                                $result_holidays = $conn->query($sql_holidays);
                                                $holidays = [];

                                                if ($result_holidays) {
                                                    while ($holiday = $result_holidays->fetch_assoc()) {
                                                        $holidays[] = $holiday['holidayday'];
                                                    }
                                                }

                                                foreach ($daterange as $date) {
                                                    if ($date->format('N') < 6 && !in_array($date->format('Y-m-d'), $holidays)) {
                                                        $workdays++;
                                                    }
                                                }
                                                echo $workdays;
                                                ?>
                                            </td>
                                            <td><?php echo $row['supervisor_name'] ?? '-'; ?></td>
                                            <td><?php echo $row['deputy_name'] ?? '-'; ?></td>
                                            <td><?php echo $row['director_name'] ?? '-'; ?></td>
                                            <td style="background-color: <?php
                                                switch ($row['leavestatus']) {
                                                    case 'รออนุมัติ':
                                                        echo '#FFC107; color: black';
                                                        break;
                                                    case 'อนุมัติ':
                                                        echo '#198754; color: white';
                                                        break;
                                                    case 'ไม่อนุมัติ':
                                                        echo '#DC3545; color: white';
                                                        break;
                                                }
                                                ?>">
                                                <?php echo $row['leavestatus']; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-outline-success btn-sm approve-btn"
                                                        data-leavesid="<?php echo $row['leavesid']; ?>"
                                                        data-supervisor-id="<?php echo $row['approver1'] ?? '0'; ?>"
                                                        data-deputy-id="<?php echo $row['approver2'] ?? '0'; ?>"
                                                        data-director-id="<?php echo $row['approver3'] ?? '0'; ?>">
                                                    <i class="ri-check-line"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm reject-btn"
                                                        data-id="<?php echo $row['leavesid']; ?>">
                                                    <i class="ri-close-line"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include('component/footer.php'); ?>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // กำหนด DataTable
            const table = $('#leaveTable').DataTable({
                responsive: true,
                language: {
                    url: 'assets/js/datatable-lang/th.json'
                }
            });

            $('#leaveTable').on('click', '.approve-btn', function() {
                const button = $(this);
                const leaveId = button.data('leavesid');
                const supervisorId = button.data('supervisor-id') || '0';
                const deputyId = button.data('deputy-id') || '0';
                const directorId = button.data('director-id') || '0';

                // ดีบั๊ก HTML ของปุ่ม
                console.log('Button HTML:', button.prop('outerHTML'));

                // ดีบั๊กค่าที่ดึงมา
                console.log('Debug values before sending:', {
                    leaveId: leaveId,
                    supervisorId: supervisorId,
                    deputyId: deputyId,
                    directorId: directorId
                });

                // ตรวจสอบว่า leaveId มีค่า
                if (!leaveId) {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด!',
                        text: 'ไม่พบรหัสใบลา'
                    });
                    return;
                }

                sendApprovalRequest(leaveId, supervisorId, deputyId, directorId);
            });

            function sendApprovalRequest(leaveId, supervisorId, deputyId, directorId) {
                // ส่งข้อมูล
                const dataToSend = {
                    id: leaveId,
                    supervisor_id: supervisorId,
                    deputy_id: deputyId,
                    director_id: directorId
                };

                // ดีบั๊กข้อมูลที่ส่ง
                console.log('Data being sent to approve_leave.php:', dataToSend);

                $.ajax({
                    url: 'process/approve_leave.php',
                    type: 'POST',
                    data: dataToSend,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Response from approve_leave.php:', response);
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'ดำเนินการสำเร็จ!',
                                text: 'ทำการอนุมัติเรียบร้อยแล้ว',
                                showConfirmButton: true
                            }).then(() => {
                                window.location.href = 'leavestatus.php';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด!',
                                text: response.message || 'ไม่สามารถดำเนินการได้'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        let errorMessage = 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.message || errorMessage;
                        } catch (e) {
                            errorMessage = xhr.responseText || error;
                        }
                        console.error('Error:', errorMessage);
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด!',
                            text: errorMessage
                        });
                    }
                });
            }

            $('#leaveTable').on('click', '.reject-btn', function() {
                const leaveId = $(this).data('id');
                console.log('Debug values:', {
                    leaveId: leaveId
                });

                Swal.fire({
                    title: 'ยืนยันการไม่อนุมัติ?',
                    text: "คุณต้องการไม่อนุมัติใบลานี้ใช่หรือไม่?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'ใช่, ไม่อนุมัติ!',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'process/reject_leave.php',
                            type: 'POST',
                            data: {
                                leave_id: leaveId
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'ดำเนินการสำเร็จ!',
                                        text: 'ทำการไม่อนุมัติเรียบร้อยแล้ว',
                                        showConfirmButton: true
                                    }).then(() => {
                                        window.location.href = 'leavestatus.php';
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'เกิดข้อผิดพลาด!',
                                        text: response.message || 'ไม่สามารถดำเนินการได้'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                let errorMessage = 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์';
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    errorMessage = response.message || errorMessage;
                                } catch (e) {
                                    errorMessage = xhr.responseText || error;
                                }
                                console.error('Error:', errorMessage);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด!',
                                    text: errorMessage
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>