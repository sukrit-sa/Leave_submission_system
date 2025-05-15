<?php
include('conn/conn.php');
$i = 1;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/table.css">
    <title>ข้อมูลวันลาของฉัน</title>
    <style>
        .table-responsive {
            width: 100%;
        }

        table.dataTable {
            width: 100% !important;
        }
    </style>
</head>

<body>
    <?php 
    include('component/sidebar.php');
    if (!isset($_SESSION['ID'])) {
        die("ไม่พบข้อมูลผู้ใช้ กรุณาล็อกอินใหม่");
    }
    $userID = $_SESSION['ID'];

    $customCurrentDate = new DateTime('');

    // ดึงข้อมูลช่วงปีงบประมาณจากตาราง year
    $sql_year = "SELECT yearstart1, yearend2 FROM year LIMIT 1";
    $result_year = $conn->query($sql_year);
    $yearData = $result_year->fetch_assoc();

    if (!$yearData) {
        die("ไม่พบข้อมูลในตาราง year");
    }

    $yearstart1 = $yearData['yearstart1'];
    $yearend2 = $yearData['yearend2'];

    // ดึงข้อมูลพนักงาน (เฉพาะผู้ใช้ที่ล็อกอิน)
    $sql_employees = "SELECT id, fname, lname, staffstatus, startwork FROM employees WHERE id = ?";
    $stmt_employees = $conn->prepare($sql_employees);
    $stmt_employees->bind_param('i', $userID);
    $stmt_employees->execute();
    $result_employees = $stmt_employees->get_result();

    $employees = [];
    if ($row = $result_employees->fetch_assoc()) {
        $startDate = new DateTime($row['startwork']);
        $totalMonths = max(0, $customCurrentDate->diff($startDate)->y * 12 + $customCurrentDate->diff($startDate)->m);

        $employees[$row['id']] = [
            'fullname' => $row['fname'] . ' ' . $row['lname'],
            'staffstatus' => $row['staffstatus'],
            'totalMonths' => $totalMonths
        ];
    } else {
        die("ไม่พบข้อมูลพนักงานสำหรับ ID: $userID");
    }
    $stmt_employees->close();

    // ดึงข้อมูลจากตาราง leaveday เพื่อหาประเภทการลาของผู้ใช้
    $sql_leaveday = "SELECT ld.empid, ld.leavetype, ld.day, ld.stackleaveday, 
                            lt.leavetypename, lt.leaveofyear, lt.stackleaveday as stackleaveday_max,
                            e.fname, e.lname, e.staffstatus, e.startwork
                     FROM leaveday ld
                     JOIN leavetype lt ON ld.leavetype = lt.leavetypeid
                     JOIN employees e ON ld.empid = e.id
                     WHERE ld.empid = ?";
    $stmt_leaveday = $conn->prepare($sql_leaveday);
    $stmt_leaveday->bind_param('i', $userID);
    $stmt_leaveday->execute();
    $result_leaveday = $stmt_leaveday->get_result();

    $leaveData = [];
    $usedLeavetypenames = [];
    while ($row = $result_leaveday->fetch_assoc()) {
        $startDate = new DateTime($row['startwork']);
        $totalMonths = max(0, $customCurrentDate->diff($startDate)->y * 12 + $customCurrentDate->diff($startDate)->m);

        // นับจำนวนครั้งที่ลาจากตาราง leaves
        $sql_leave_count = "SELECT COUNT(*) as leave_count 
                            FROM leaves 
                            WHERE employeesid = ? 
                            AND leavetype = ? 
                            AND leavestart BETWEEN ? AND ?
                            AND leavestatus = 'อนุมัติ'
                            AND note = 'ลา'";
        $stmt_leave_count = $conn->prepare($sql_leave_count);
        $stmt_leave_count->bind_param('iiss', $userID, $row['leavetype'], $yearstart1, $yearend2);
        $stmt_leave_count->execute();
        $result_leave_count = $stmt_leave_count->get_result();
        $leave_count = $result_leave_count->fetch_assoc()['leave_count'] ?? 0;
        $stmt_leave_count->close();

        $leaveData[$row['empid']][$row['leavetypename']] = [
            'leavetype' => $row['leavetype'],
            'leavetypeid' => $row['leavetype'], // เก็บ leavetypeid เพื่อใช้ใน SQL
            'fullname' => $row['fname'] . ' ' . $row['lname'],
            'staffstatus' => $row['staffstatus'],
            'day' => $row['day'] ?? 0,
            'stackleaveday' => $row['stackleaveday'] ?? 0,
            'leaveofyear' => $row['leaveofyear'] ?? 0,
            'stackleaveday_max' => $row['stackleaveday_max'] ?? 0,
            'totalMonths' => $totalMonths,
            'leave_count' => $leave_count
        ];

        // เพิ่ม leavetypename ที่พบใน leaveday
        if (!in_array($row['leavetypename'], $usedLeavetypenames)) {
            $usedLeavetypenames[] = $row['leavetypename'];
        }
    }
    $stmt_leaveday->close();
    sort($usedLeavetypenames);

    // ดึงข้อมูล staffstatus เพื่อใช้ในการแสดงผล
    $sql_staffstatus = "SELECT staffid, staffname FROM staffstatus";
    $result_staffstatus = $conn->query($sql_staffstatus);
    $staffstatusNames = [];
    while ($row = $result_staffstatus->fetch_assoc()) {
        $staffstatusNames[$row['staffid']] = $row['staffname'];
    }

    // ดึงข้อมูลการลาทั้งหมดในช่วงปีงบประมาณจากตาราง leaves
    $sql_leaves = "SELECT lt.leavetypename, SUM(l.day) as total_leave_days 
                   FROM leaves l 
                   JOIN leavetype lt ON l.leavetype = lt.leavetypeid 
                   WHERE l.employeesid = ? 
                   AND l.leavestart BETWEEN ? AND ? 
                   AND l.leavestatus = 'อนุมัติ'
                   GROUP BY lt.leavetypename";
    $stmt_leaves = $conn->prepare($sql_leaves);
    $stmt_leaves->bind_param("iss", $userID, $yearstart1, $yearend2);
    $stmt_leaves->execute();
    $result_leaves = $stmt_leaves->get_result();

    $leaveDaysByType = [];
    while ($row = $result_leaves->fetch_assoc()) {
        $leaveDaysByType[$row['leavetypename']] = $row['total_leave_days'] ?? 0;
    }
    $stmt_leaves->close();
    ?>
    <main class="main container3" id="main">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">ข้อมูลจำนวนวันลาของฉัน</h3>
                <p class="card-text mt-2 mb-0">วันที่ปัจจุบัน: <?= $customCurrentDate->format('d/m/') . ($customCurrentDate->format('Y')) ?></p>
            </div>
            <div class="card-body">
                <?php if (empty($usedLeavetypenames)): ?>
                    <div class="alert alert-info mt-3" role="alert">
                        ยังไม่มีข้อมูลวันลาของคุณในระบบ
                    </div>
                <?php else: ?>
                    <!-- สร้างแท็บสำหรับแต่ละประเภทการลาที่มีข้อมูล -->
                    <ul class="nav nav-tabs" id="leaveTypeTab" role="tablist">
                        <?php foreach ($usedLeavetypenames as $index => $leavetypename): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= $index === 0 ? 'active' : '' ?>"
                                    id="<?= str_replace(' ', '-', $leavetypename) ?>-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#<?= str_replace(' ', '-', $leavetypename) ?>"
                                    type="button"
                                    role="tab"
                                    aria-controls="<?= str_replace(' ', '-', $leavetypename) ?>"
                                    aria-selected="<?= $index === 0 ? 'true' : 'false' ?>">
                                    <?= $leavetypename ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <!-- เนื้อหาของแต่ละแท็บ -->
                    <div class="tab-content" id="leaveTypeTabContent">
                        <?php foreach ($usedLeavetypenames as $index => $leavetypename): ?>
                            <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>"
                                id="<?= str_replace(' ', '-', $leavetypename) ?>"
                                role="tabpanel"
                                aria-labelledby="<?= str_replace(' ', '-', $leavetypename) ?>-tab">
                                <div class="table-responsive mt-3">
                                    <table id="example-<?= str_replace(' ', '-', $leavetypename) ?>"
                                        class="table table-striped table-bordered dt-responsive nowrap"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>ลำดับ</th>
                                                <th>ชื่อ-นามสกุล</th>
                                                <th>สถานะพนักงาน</th>
                                                <th>วันลาประจำปี</th>
                                                <th>วันลาสะสม</th>
                                                <th>จำนวนครั้งที่ลา</th>
                                                <th>จำนวนวันที่ลา</th>
                                                <th>วันลาคงเหลือ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (isset($employees[$userID]) && isset($leaveData[$userID][$leavetypename])): ?>
                                                <tr>
                                                    <?php
                                                    $empData = $employees[$userID];
                                                    $leaveRecord = $leaveData[$userID][$leavetypename];
                                                    $day = $leaveRecord['day'];
                                                    $stackleaveday = $leaveRecord['stackleaveday'];
                                                    $leaveofyear = $leaveRecord['leaveofyear'];
                                                    $stackleaveday_max = $leaveRecord['stackleaveday_max'];
                                                    // ใช้ leavetypename จาก leaveDaysByType แทนการคำนวณใหม่
                                                    $total_leave_days = $leaveDaysByType[$leavetypename] ?? 0;
                                                    ?>
                                                    <td><?= $i ?></td>
                                                    <td><?= $empData['fullname'] ?></td>
                                                    <td><?= isset($staffstatusNames[$empData['staffstatus']]) ? $staffstatusNames[$empData['staffstatus']] : 'ไม่ระบุ' ?></td>
                                                    <td><?= $day ?>/<?= $leaveofyear ?: '0' ?></td>
                                                    <td><?= $stackleaveday ?>/<?= $stackleaveday_max ?: '0' ?></td>
                                                    <td><?= $leaveRecord['leave_count'] ?> ครั้ง</td>
                                                    <td><?= $total_leave_days ?> วัน</td>
                                                    <td><?= $stackleaveday+$day ?> วัน</td>
                                                </tr>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7">ไม่พบข้อมูลสำหรับประเภทการลานี้</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php include('component/footer.php'); ?>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            var dataTables = {};
            <?php foreach ($usedLeavetypenames as $leavetypename): ?>
                dataTables['<?= str_replace(' ', '-', $leavetypename) ?>'] = $('#example-<?= str_replace(' ', '-', $leavetypename) ?>').DataTable({
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
            <?php endforeach; ?>

            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                var targetTab = $(e.target).data('bs-target');
                var tableId = targetTab.replace('#', 'example-');
                if (dataTables[tableId.replace('example-', '')]) {
                    dataTables[tableId.replace('example-', '')].columns.adjust().responsive.recalc();
                }
            });

            var firstTab = $('.nav-tabs .nav-link.active').data('bs-target');
            var firstTableId = firstTab.replace('#', 'example-');
            if (dataTables[firstTableId.replace('example-', '')]) {
                dataTables[firstTableId.replace('example-', '')].columns.adjust().responsive.recalc();
            }

            $(window).on('resize', function() {
                $.each(dataTables, function(key, table) {
                    table.columns.adjust().responsive.recalc();
                });
            });
        });
    </script>
</body>

</html>

<?php $conn->close(); ?>