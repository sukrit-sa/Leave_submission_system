<?php
// Set timezone to Thailand
date_default_timezone_set('Asia/Bangkok');

// Format current date in Thai
$thaiMonths = [
    1 => 'มกราคม',
    2 => 'กุมภาพันธ์',
    3 => 'มีนาคม',
    4 => 'เมษายน',
    5 => 'พฤษภาคม',
    6 => 'มิถุนายน',
    7 => 'กรกฎาคม',
    8 => 'สิงหาคม',
    9 => 'กันยายน',
    10 => 'ตุลาคม',
    11 => 'พฤศจิกายน',
    12 => 'ธันวาคม'
];

$day = date('d');
$month = $thaiMonths[(int)date('m')];
$year = date('Y') + 543;
$currentDate = "$day $month $year";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/table.css">
    <title>สถานะการลา</title>
</head>

<body>
    <?php include('component/sidebar.php'); ?>

    <main class="main container3" id="main">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">สถานะใบลา</h3>
                <p class="card-text mt-2 mb-0">วันที่ปัจจุบัน: <?php echo $currentDate; ?></p>
            </div>
            <div class="card-body">
                <!-- Add Tab Navigation -->
                <ul class="nav nav-tabs mb-3" id="leaveStatusTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                            รอดำเนินการ
                            <?php
                            $count_pending = mysqli_query($conn, "SELECT COUNT(*) as count FROM leaves WHERE leavestatus IN ('รอหัวหน้าอนุมัติ', 'รอรองผอ.อนุมัติ', 'รอผอ.อนุมัติ')");
                            $pending_num = mysqli_fetch_assoc($count_pending)['count'];
                            if ($pending_num > 0) {
                                echo '<span class="badge rounded-pill bg-warning ms-2">' . $pending_num . '</span>';
                            }
                            ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab">
                            อนุมัติ
                            <?php
                            $count_approved = mysqli_query($conn, "SELECT COUNT(*) as count FROM leaves WHERE leavestatus = 'อนุมัติ'");
                            $approved_num = mysqli_fetch_assoc($count_approved)['count'];
                            if ($approved_num > 0) {
                                echo '<span class="badge rounded-pill bg-success ms-2">' . $approved_num . '</span>';
                            }
                            ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button" role="tab">
                            ไม่อนุมัติ
                            <?php
                            $count_rejected = mysqli_query($conn, "SELECT COUNT(*) as count FROM leaves WHERE leavestatus = 'ไม่อนุมัติ'");
                            $rejected_num = mysqli_fetch_assoc($count_rejected)['count'];
                            if ($rejected_num > 0) {
                                echo '<span class="badge rounded-pill bg-danger ms-2">' . $rejected_num . '</span>';
                            }
                            ?>
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="leaveStatusTabContent">
                    <!-- Pending Tab -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel">
                        <table id="pendingTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>ทำรายการ</th>
                                    <th>ประเภทการลา</th>
                                    <th>สถานะ</th>
                                    <th>วันที่เริ่มลา</th>
                                    <th>วันที่สิ้นสุด</th>
                                    <th>จำนวนวัน</th>
                                    <th>พิมพ์</th>

                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_pending = "SELECT leaves.*, CONCAT(pr.prefixname, e.fname, ' ', e.lname) as fullname, leavetype.leavetypename 
                                           FROM leaves
                                           LEFT JOIN employees e ON leaves.employeesid = e.id
                                           LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                                           LEFT JOIN leavetype ON leaves.leavetype = leavetype.leavetypeid
                                           WHERE leaves.leavestatus IN ('รอหัวหน้าอนุมัติ', 'รอรองผอ.อนุมัติ', 'รอผอ.อนุมัติ')";
                                displayLeaveStatus($conn, $sql_pending);
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Approved Tab -->
                    <div class="tab-pane fade" id="approved" role="tabpanel">
                        <table id="approvedTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>ทำรายการ</th>
                                    <th>ประเภทการลา</th>
                                    <th>สถานะ</th>
                                    <th>วันที่เริ่มลา</th>
                                    <th>วันที่สิ้นสุด</th>
                                    <th>จำนวนวัน</th>
                                    <th>พิมพ์</th>

                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_approved = "SELECT leaves.*, CONCAT(pr.prefixname, e.fname, ' ', e.lname) as fullname, leavetype.leavetypename 
                                           FROM leaves
                                           LEFT JOIN employees e ON leaves.employeesid = e.id
                                           LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                                           LEFT JOIN leavetype ON leaves.leavetype = leavetype.leavetypeid
                                           WHERE leaves.leavestatus = 'อนุมัติ'";
                                displayLeaveStatus($conn, $sql_approved);
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Rejected Tab -->
                    <div class="tab-pane fade" id="rejected" role="tabpanel">
                        <table id="rejectedTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>ทำรายการ</th>
                                    <th>ประเภทการลา</th>
                                    <th>สถานะ</th>
                                    <th>เหตุผล</th>
                                    <th>วันที่เริ่มลา</th>
                                    <th>วันที่สิ้นสุด</th>
                                    <th>จำนวนวัน</th>
                                    <th>พิมพ์</th>

                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_rejected = "SELECT leaves.*, CONCAT(pr.prefixname, e.fname, ' ', e.lname) as fullname, leavetype.leavetypename 
                                           FROM leaves
                                           LEFT JOIN employees e ON leaves.employeesid = e.id
                                           LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                                           LEFT JOIN leavetype ON leaves.leavetype = leavetype.leavetypeid
                                           WHERE leaves.leavestatus = 'ไม่อนุมัติ'";
                                displayLeaveStatus($conn, $sql_rejected);
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            <?php
            // Helper function to display leave status
            function displayLeaveStatus($conn, $sql)
            {
                $result = mysqli_query($conn, $sql);
                $i = 1;
                while ($row = mysqli_fetch_assoc($result)) {
            ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo $row['fullname']; ?></td>
                        <td> <?php echo $row['note']; ?></td>
                        <td><?php echo $row['leavetypename']; ?></td>
                        <td style="background-color: <?php
                                                        switch ($row['leavestatus']) {
                                                            case 'รอหัวหน้าอนุมัติ':
                                                                echo '#FFE082; color: black';
                                                                break;
                                                            case 'รอรองผอ.อนุมัติ':
                                                                echo '#FFD54F; color: black';
                                                                break;
                                                            case 'รอผอ.อนุมัติ':
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
                        <?php if ($row['leavestatus'] == 'ไม่อนุมัติ'): ?>
                            <td><?php echo $row['reason']; ?></td>
                        <?php endif; ?>
                        <td><?php echo date('d/m/Y', strtotime($row['leavestart'])); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['leaveend'])); ?></td>
                        <td><?php
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
                        <td>
                            <?php
                            // รวมการแสดงปุ่มทั้งสองเข้าด้วยกัน
                            if ($row['leavestatus'] == 'อนุมัติ') {
                                if ($row['note'] == 'ยกเลิกลาและคืนวันลา' || $row['note'] == 'ยกเลิกลา') {
                                    // แสดงปุ่มพิมพ์ใบยกเลิก
                            ?>
                                    <button type="button" class="btn btn-info btn-sm print-cancel-btn"
                                        onclick="window.open('print_cancel.php?leavesid=<?= $row['leavesid'] ?>', '_blank')"
                                        style="background-color: #0dcaf0; color:aliceblue">
                                        <i class="ri-printer-line"></i> พิมพ์ใบยกเลิก
                                    </button>
                                <?php
                                } elseif ($row['note'] == 'ลา') {
                                    // แสดงปุ่มพิมพ์ใบลา
                                ?>
                                    <a href="print_leave.php?leavesid=<?php echo $row['leavesid']; ?>" class="btn btn-primary btn-sm" target="_blank">
                                        <i class="ri-printer-line"></i> พิมพ์ใบลา
                                    </a>
                            <?php
                                }
                            }
                            ?>
                        </td>
                    </tr>
            <?php
                }
            }
            ?>

            <script src="assets/js/main.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
            <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
            <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

            <script>
                $(document).ready(function() {
                    // เพิ่มการจัดการ Local Storage สำหรับแท็บ
                    const activeTab = localStorage.getItem('activeLeaveTab');
                    if (activeTab) {
                        // เปิดแท็บที่เคยเลือกไว้
                        $(`#leaveStatusTabs button[data-bs-target="${activeTab}"]`).tab('show');
                    }

                    // บันทึกแท็บที่เลือกลงใน Local Storage
                    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                        localStorage.setItem('activeLeaveTab', $(e.target).data('bs-target'));
                    });

                    // Initialize DataTables for each tab
                    $('#pendingTable').DataTable({
                        responsive: true,
                        language: {
                            url: 'assets/js/datatable-lang/th.json'
                        }
                    });

                    $('#approvedTable').DataTable({
                        responsive: true,
                        language: {
                            url: 'assets/js/datatable-lang/th.json'
                        }
                    });

                    $('#rejectedTable').DataTable({
                        responsive: true,
                        language: {
                            url: 'assets/js/datatable-lang/th.json'
                        }
                    });
                });
            </script>

        </div>
        <?php include('component/footer.php'); ?>
    </main>

</body>

</html>