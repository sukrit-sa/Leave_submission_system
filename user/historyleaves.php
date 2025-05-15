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
$year = date('Y');
$currentDate = "$day $month $year";

// เพิ่มฟังก์ชันสำหรับตรวจสอบปีงบประมาณ
function getFiscalYear($date) {
    $month = (int)date('m', strtotime($date));
    $year = (int)date('Y', strtotime($date));
    if ($month >= 10) {
        return $year + 1; // เปลี่ยนเป็นปีถัดไปสำหรับเดือน ต.ค.-ธ.ค.
    } else {
        return $year; // ปีปัจจุบันสำหรับเดือน ม.ค.-ก.ย.
    }
}

// เพิ่มการเลือกปีงบประมาณ
$current_fiscal_year = isset($_GET['fiscal_year']) ? (int)$_GET['fiscal_year'] : getFiscalYear(date('Y-m-d'));
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
    <style>
        .nav-tabs .nav-link {
            color: #666;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            margin-right: 5px;
        }
        .nav-tabs .nav-link.active {
            color: #495057;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        .tab-content {
            border: 1px solid #dee2e6;
            border-top: none;
            padding: 20px;
        }
    </style>
    <title>สถานะการลา</title>
</head>

<body>
    <?php include('component/sidebar.php'); ?>

    <main class="main container3" id="main">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">ประวัติการลา</h3>
                <p class="card-text mt-2 mb-0">วันที่ปัจจุบัน: <?php echo $currentDate; ?></p>
                <p class="card-text mb-0">ปีงบประมาณ: <?php 
                    $selected_year = isset($_GET['fiscal_year']) ? (int)$_GET['fiscal_year'] : $current_fiscal_year;
                    echo $selected_year ; 
                ?></p>
                
                <!-- เพิ่มตัวเลือกปีงบประมาณ -->
                <div class="mt-3">
                    <form method="get" class="d-flex align-items-center">
                        <label for="fiscal_year" class="me-2">ปีงบประมาณ:</label>
                        <select name="fiscal_year" id="fiscal_year" class="form-select" style="width: auto;" onchange="this.form.submit()">
                            <?php
                            // คำนวณปีงบประมาณปัจจุบัน
                            $current_fiscal_year = getFiscalYear(date('Y-m-d'));
                            
                            // แสดงตัวเลือกย้อนหลัง 5 ปี จนถึงปีปัจจุบัน
                            $start_year = $current_fiscal_year - 5;
                            $end_year = $current_fiscal_year;
                            
                            for ($year = $end_year; $year >= $start_year; $year--) {
                                $selected = ($year == (isset($_GET['fiscal_year']) ? (int)$_GET['fiscal_year'] : $current_fiscal_year)) ? 'selected' : '';
                                echo "<option value=\"$year\" $selected>$year</option>";
                            }
                            ?>
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <?php
                // Define fiscal year dates
                $selected_year = isset($_GET['fiscal_year']) ? (int)$_GET['fiscal_year'] : $current_fiscal_year;
                $fiscal_start = ($selected_year - 1) . '-10-01';
                $fiscal_end = $selected_year . '-09-30';

                // Query for all leaves (will be used in the "all" tab)
                $sql_status = "SELECT leaves.*, CONCAT(pr.prefixname, e.fname, ' ', e.lname) as fullname, 
                              leavetype.leavetypename, leaves.file
                              FROM leaves
                              LEFT JOIN employees e ON leaves.employeesid = e.id
                              LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                              LEFT JOIN leavetype ON leaves.leavetype = leavetype.leavetypeid
                              WHERE leaves.employeesid = $userID 
                              AND leaves.leavestatus = 'อนุมัติ'
                              AND leaves.note = 'ลา'
                              AND leaves.leavestart BETWEEN '$fiscal_start' AND '$fiscal_end'
                              ORDER BY leaves.leavestart ASC";
                $result_status = mysqli_query($conn, $sql_status);

                // ดึงข้อมูลประเภทการลาทั้งหมด
                $sql_leavetypes = "SELECT DISTINCT leavetype.leavetypeid, leavetype.leavetypename
                                 FROM leaves
                                 LEFT JOIN leavetype ON leaves.leavetype = leavetype.leavetypeid
                                 WHERE leaves.employeesid = $userID 
                                 AND leaves.leavestatus = 'อนุมัติ'
                                 AND leaves.note = 'ลา'
                                 AND leaves.leavestart BETWEEN '$fiscal_start' AND '$fiscal_end'
                                 ORDER BY leavetype.leavetypename";
                $result_leavetypes = mysqli_query($conn, $sql_leavetypes);
                ?>

                <!-- แท็บเมนู -->
                <ul class="nav nav-tabs" id="leaveTypeTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" 
                                data-bs-target="#all" type="button" role="tab">ทั้งหมด</button>
                    </li>
                    <?php while ($type = mysqli_fetch_assoc($result_leavetypes)) { ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="type-<?php echo $type['leavetypeid']; ?>-tab" 
                                    data-bs-toggle="tab" data-bs-target="#type-<?php echo $type['leavetypeid']; ?>" 
                                    type="button" role="tab"><?php echo $type['leavetypename']; ?></button>
                        </li>
                    <?php } ?>
                </ul>

                <!-- เนื้อหาแท็บ -->
                <div class="tab-content" id="leaveTypeContent">
                    <!-- แท็บทั้งหมด -->
                    <div class="tab-pane fade show active" id="all" role="tabpanel">
                        <table id="allLeaveTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>ประเภทการลา</th>
                                    <th>วันที่เริ่มลา</th>
                                    <th>วันที่สิ้นสุด</th>
                                    <th>จำนวนวัน</th>
                                    <th>การจัดการ</th>
                                    <th>ไฟล์แนบ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($result_status, 0);
                                $i = 1;
                                while ($row = mysqli_fetch_assoc($result_status)) {
                                    include('component/leave_row.php');
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- แท็บแยกตามประเภท -->
                    <?php
                    mysqli_data_seek($result_leavetypes, 0);
                    while ($type = mysqli_fetch_assoc($result_leavetypes)) {
                        $type_id = $type['leavetypeid'];
                        ?>
                        <div class="tab-pane fade" id="type-<?php echo $type_id; ?>" role="tabpanel">
                            <table id="leaveTable-<?php echo $type_id; ?>" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ลำดับ</th>
                                        <th>ชื่อ-นามสกุล</th>
                                        <th>ประเภทการลา</th>
                                        <th>วันที่เริ่มลา</th>
                                        <th>วันที่สิ้นสุด</th>
                                        <th>จำนวนวัน</th>
                                        <th>การจัดการ</th>
                                        <th>ไฟล์แนบ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql_type = "SELECT leaves.*, CONCAT(pr.prefixname, e.fname, ' ', e.lname) as fullname, 
                                                leavetype.leavetypename, leaves.file
                                                FROM leaves
                                                LEFT JOIN employees e ON leaves.employeesid = e.id
                                                LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                                                LEFT JOIN leavetype ON leaves.leavetype = leavetype.leavetypeid
                                                WHERE leaves.employeesid = $userID 
                                                AND leaves.leavestatus = 'อนุมัติ'
                                                AND leaves.note = 'ลา'
                                                AND leaves.leavetype = $type_id
                                                AND leaves.leavestart BETWEEN '$fiscal_start' AND '$fiscal_end'
                                                ORDER BY leaves.leavestart ASC";
                                    $result_type = mysqli_query($conn, $sql_type);
                                    $i = 1;
                                    while ($row = mysqli_fetch_assoc($result_type)) {
                                        include('component/leave_row.php');
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?>

                    <!-- ย้าย script DataTable ไปไว้ด้านล่างของ HTML -->
                    </div>
                </div>
            </div>
        </main>

    <script src="assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#statusTable').DataTable({
                responsive: true,
                language: {
                    url: 'assets/js/datatable-lang/th.json'
                }
            });
        });
    </script>
    <script>
        function showFilePreview(fileUrl) {
            const fileExt = fileUrl.split('.').pop().toLowerCase();

            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                // สำหรับรูปภาพ
                Swal.fire({
                    imageUrl: fileUrl,
                    imageAlt: 'ไฟล์แนบ',
                    width: 'auto',
                    maxWidth: '95%',
                    customClass: {
                        popup: 'swal-image-popup',
                        image: 'swal-responsive-image'
                    },
                    confirmButtonText: 'ปิด'
                });
            } else if (fileExt === 'pdf') {
                // สำหรับไฟล์ PDF
                window.open(fileUrl, '_blank');
            } else if (['doc', 'docx'].includes(fileExt)) {
                // สำหรับไฟล์ Word ให้ดาวน์โหลดไฟล์แทน
                Swal.fire({
                    title: 'ดาวน์โหลดไฟล์',
                    text: 'คลิกปุ่มดาวน์โหลดเพื่อเปิดไฟล์ Word',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'ดาวน์โหลด',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = fileUrl;
                    }
                });
            } else {
                // สำหรับไฟล์ประเภทอื่นๆ ให้ดาวน์โหลด
                window.location.href = fileUrl;
            }
        }
    </script>
    <style>
        .swal-image-popup {
            padding: 10px !important;
        }

        .swal-responsive-image {
            max-width: 100% !important;
            max-height: 80vh !important;
            width: auto !important;
            height: auto !important;
            object-fit: contain;
        }
    </style>
</body>

</html>