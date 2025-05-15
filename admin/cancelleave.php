<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/table.css">
    <title>ขอยกเลิกการลา</title>
    <style>
        .table-responsive {
            width: 100%;
        }

        table.dataTable {
            width: 100% !important;
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #c82333;
        }
    </style>
</head>

<body>
    <?php include('component/sidebar.php'); ?>
    <?php
    include('conn/conn.php');
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['userid'])) {
        header("Location: ../login/login.php");
        exit();
    }

    $userID = $_SESSION['ID'];
    $userName = $_SESSION["Prefix"] . '' . $_SESSION['FirstName'] . ' ' . $_SESSION['LastName'];
    $staffid = $_SESSION["Staffid"];
    $department = $_SESSION["Department"];
    $headdepart = $_SESSION["Headdepart"];
    $position = $_SESSION["Position"];
    $workage = $_SESSION["workage"];
    $pic = $_SESSION["img"];
    $roleid = $_SESSION["roleid"];
    $departid = $_SESSION["departid"];

    date_default_timezone_set('Asia/Bangkok');

    // Format current date in Thai (พ.ศ.)
 

    // วันที่ปัจจุบันสำหรับการเปรียบเทียบ
    $currentDateTime = new DateTime();
    $currentDateTime->setTime(0, 0, 0); // ตั้งเวลาเป็น 00:00:00 เพื่อเปรียบเทียบเฉพาะวันที่

    // ดึงข้อมูลใบลาของผู้ใช้
    $sql_leaves = "SELECT leaves.leavesid, leaves.leavestatus, leaves.leavetype, leaves.leavestart, leaves.leaveend, 
                   leavetype.leavetypename, leaves.note, leaves.day,concat(pr.prefixname,' ',e.fname,' ',e.lname) as fullname
                   FROM leaves 
                   LEFT JOIN employees e ON leaves.employeesid = e.id
                    LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                   JOIN leavetype ON leaves.leavetype = leavetype.leavetypeid 
                   WHERE leaves.leavestatus IN ('รอหัวหน้าอนุมัติ', 'รอรองผอ.อนุมัติ', 'รอผอ.อนุมัติ', 'อนุมัติ', 'รออนุมัติ') AND leaves.note = 'ลา' ";
    $result_leaves = mysqli_query($conn, $sql_leaves);
    $leaves = [];

    while ($row = mysqli_fetch_assoc($result_leaves)) {
        // แปลงวันที่เริ่มลาเป็น DateTime
        $startDate = new DateTime($row['leavestart']);
        $startDate->setTime(0, 0, 0); // ตั้งเวลาเป็น 00:00:00 เพื่อเปรียบเทียบเฉพาะวันที่

        // กรองเฉพาะใบลาที่วันที่เริ่มลายังไม่เลยวันที่ปัจจุบัน
        if ($startDate >= $currentDateTime) {
            $leaves[] = $row;
        }
    }
    ?>
    <main class="main container3" id="main">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">ขอยกเลิกการลา</h3>
                <p class="card-text mt-2 mb-0">วันที่ปัจจุบัน: <?= $currentDate ?></p>
            </div>
            <div class="card-body">
                <?php if (empty($leaves)): ?>
                    <div class="alert alert-info mt-3" role="alert">
                        ไม่มีใบลาที่สามารถยกเลิกได้
                    </div>
                <?php else: ?>
                    <div class="table-responsive mt-3">
                        <table id="leaveTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>ทำรายการ</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>สถานะ</th>
                                    <th>ประเภทการลา</th>
                                    <th>วันที่เริ่มลา</th>
                                    <th>วันที่สิ้นสุด</th>
                                    <th>จำนวนวัน</th>
                                    <th>การดำเนินการ</th>
                               
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaves as $leave): ?>
                                    <?php
                                    // แปลงวันที่เริ่มลาเป็นรูปแบบ d/m/Y (พ.ศ.)
                                    $startDate = new DateTime($leave['leavestart']);
                                    $startDateFormatted = $startDate->format('d/m/') . ($startDate->format('Y') ); //+ 543

                                    // แปลงวันที่สิ้นสุดเป็นรูปแบบ d/m/Y (พ.ศ.)
                                    $endDate = new DateTime($leave['leaveend']);
                                    $endDateFormatted = $endDate->format('d/m/') . ($endDate->format('Y') );//+ 543

                                    // คำนวณจำนวนวันลาใหม่
                                    $start = new DateTime($leave['leavestart']);
                                    $end = new DateTime($leave['leaveend']);
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

                                    // วันที่ปัจจุบัน
                                    $currentDateTime = new DateTime();
                                    $currentDateTime->setTime(0, 0, 0); // ตั้งเวลาเป็น 00:00:00 เพื่อเปรียบเทียบเฉพาะวันที่

                                    // วันที่เริ่มลา - 1 วัน
                                    $startDateMinusOne = clone $startDate;
                                    $startDateMinusOne->modify('-1 day');
                                    $startDateMinusOne->setTime(0, 0, 0);

                                    // ตรวจสอบว่าสามารถยกเลิกได้หรือไม่ (ต้องยกเลิกก่อนวันเริ่มลา 1 วัน)
                                    $canCancel = $currentDateTime <= $startDateMinusOne;
                                    ?>
                                    <tr data-leavesid="<?= $leave['leavesid'] ?>">
                                        <td><?= $leave['leavesid'] ?></td>
                                        <td>
                                        <?= $leave['note'] ?>
                                        </td>
                                        <td><?= $leave['fullname'] ?></td> 
                                        <td style="background-color: <?php
                                                                        switch ($leave['leavestatus']) {
                                                                            case 'รอหัวหน้าอนุมัติ':
                                                                                echo '#FFE082; color: black'; // สีเหลืองอ่อน
                                                                                break;
                                                                            case 'รอรองผอ.นุมัติ':
                                                                                echo '#FFD54F; color: black'; // สีเหลืองกลาง
                                                                                break;
                                                                            case 'รอผอ.อนุมัติ':
                                                                                echo '#FFC107; color: black'; // สีเหลืองเข้ม
                                                                                break;
                                                                            case 'รออนุมัติ':
                                                                                echo '#FFB300; color: black'; // สีเหลืองส้ม
                                                                                break;
                                                                            case 'อนุมัติ':
                                                                                echo '#198754; color: white';
                                                                                break;
                                                                            case 'รอยกเลิก':
                                                                                echo '#E83F25; color: white';
                                                                                break;
                                                                            case 'ไม่อนุมัติ':
                                                                                echo '#DC3545; color: white';
                                                                                break;
                                                                        }
                                                                        ?>">
                                            <?php echo $leave['leavestatus']; ?>
                                        </td>
                                        <td><?= $leave['leavetypename'] ?></td>
                                        <td><?= $startDateFormatted ?></td>
                                        <td><?= $endDateFormatted ?></td>
                                        <td><?= $workdays ?></td> <!-- แสดงจำนวนวันลาที่คำนวณใหม่ -->
                                        <td>
                                            <?php if ($canCancel): ?>
                                                <?php if (($leave['leavestatus'] == 'อนุมัติ' && $leave['note'] == 'ยกเลิกลาและคืนวันลา') || ($leave['leavestatus'] == 'อนุมัติ' && $leave['note'] == 'ยกเลิกลา')): ?>
                                                    <span class="text-success">ยกเลิกสำเร็จ</span>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-cancel btn-sm cancel-leave-btn"
                                                        data-leavesid="<?= $leave['leavesid'] ?>" style="background-color: #E83F25; color:aliceblue">
                                                        ยกเลิก
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-danger">ไม่สามารถยกเลิกได้</span>
                                            <?php endif; ?>
                                        </td>
                                   
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php include('component/footer.php'); ?>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#leaveTable').DataTable({
                responsive: true,
                autoWidth: false,
                scrollX: true,
                pageLength: 5,
                lengthMenu: [
                    [5, 15, 25, 50, -1],
                    [5, 15, 25, 50, "ทั้งหมด"]
                ],
                language: {
                    lengthMenu: "แสดง _MENU_ รายการ",
                    zeroRecords: "ไม่พบข้อมูล",
                    info: "แสดงหน้า _PAGE_ จาก _PAGES_",
                    infoEmpty: "ไม่มีข้อมูล",
                    infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
                    search: "ค้นหา:",
                    paginate: {
                        first: "หน้าแรก",
                        last: "หน้าสุดท้าย",
                        next: "ถัดไป",
                        previous: "ก่อนหน้า"
                    }
                }
            });

            // จัดการปุ่มยกเลิก
            $(document).on('click', '.cancel-leave-btn', function() {
                const leavesid = $(this).data('leavesid');

                Swal.fire({
                    title: 'คุณแน่ใจหรือไม่?',
                    text: "คุณต้องการยกเลิกใบลานี้หรือไม่?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ใช่, ยกเลิก!',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../admin/process/process_cancel_leave.php',
                            type: 'POST',
                            data: {
                                leavesid: leavesid
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'สำเร็จ',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(() => {
                                        // อัพเดทตารางโดยการลบแถวที่ยกเลิก
                                        $('tr[data-leavesid="' + leavesid + '"]').remove();
                                        // อัพเดทจำนวนใน sidebar (ถ้ามี)
                                        const leaveCount = parseInt($('.sidebar__link[href="leavestatus.php"] span:last').text()) - 1;
                                        if (leaveCount > 0) {
                                            $('.sidebar__link[href="leavestatus.php"] span:last').text(leaveCount);
                                        } else {
                                            $('.sidebar__link[href="leavestatus.php"] span:last').remove();
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'เกิดข้อผิดพลาด',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 1500
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                let errorMessage = 'เกิดข้อผิดพลาดในการส่งคำขอ';
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    errorMessage = response.message || errorMessage;
                                } catch (e) {
                                    errorMessage = xhr.responseText || error;
                                }
                                console.log('Status:', status);
                                console.log('Error:', error);
                                console.log('Response:', xhr.responseText);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด',
                                    text: errorMessage,
                                    showConfirmButton: false,
                                    timer: 1500
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