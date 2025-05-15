<?php
$i = 1;
session_start();
ob_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
set_time_limit(120);

try {
    include('conn/conn.php');
} catch (Exception $e) {
    error_log("Failed to include conn.php: " . $e->getMessage());
    http_response_code(500);
    die("Internal Server Error: Database connection failed");
}

date_default_timezone_set('Asia/Bangkok');
$customCurrentDate = new DateTime();

// ดึงข้อมูลจากตาราง year
try {
    $sql_year = "SELECT yearstart1, yearend1, yearstart2, yearend2, `update` FROM year LIMIT 1";
    $result_year = $conn->query($sql_year);
    if (!$result_year) {
        throw new Exception("Error fetching year data: " . $conn->error);
    }
    $yearData = $result_year->fetch_assoc();
} catch (Exception $e) {
    error_log($e->getMessage());
    $yearData = [];
}

// ดึงข้อมูลหน่วยงานย่อย
try {
    $sql_subdepart = "SELECT subdepartid, subdepartname FROM subdepart ORDER BY subdepartname";
    $result_subdepart = $conn->query($sql_subdepart);
    if (!$result_subdepart) {
        throw new Exception("Error fetching subdepartments: " . $conn->error);
    }
    $subdepartments = [];
    while ($row = $result_subdepart->fetch_assoc()) {
        $subdepartments[$row['subdepartid']] = $row['subdepartname'];
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $subdepartments = [];
}

// ดึงข้อมูลพนักงานที่เป็นรองผู้อำนวยการและหัวหน้างาน
try {
    $sql_delegates = "SELECT e.id, pre.prefixname, e.fname, e.lname, e.department AS subdepartid, s.subdepartname, r.rolename, p.positionname
                      FROM employees e
                      LEFT JOIN prefix pre ON e.prefix = pre.prefixid
                      LEFT JOIN subdepart s ON e.department = s.subdepartid
                      LEFT JOIN position p ON e.position = p.positionid
                      LEFT JOIN role r ON p.roleid = r.roleid
                      WHERE r.rolename IN ('รองผู้อำนวยการ')";
    $result_delegates = $conn->query($sql_delegates);
    if (!$result_delegates) {
        throw new Exception("Error fetching delegates: " . $conn->error);
    }

    $delegates = [];
    while ($row = $result_delegates->fetch_assoc()) {
        $delegates[$row['id']] = [
            'prefix' => $row['prefix'] ?: '',
            'fullname' => ($row['prefixname'] ? $row['prefixname'] . '' : '') . $row['fname'] . ' ' . $row['lname'],
            'rolename' => $row['rolename'] ?: 'ไม่ระบุ',
            'subdepartid' => $row['subdepartid'],
            'subdepartname' => $row['subdepartname'] ?: 'ไม่ระบุ',
            'positionname' => $row['positionname'] ?: 'ไม่ระบุ'
        ];
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $delegates = [];
}

// ดึงข้อมูลการมอบอำนาจ
try {
    $sql_delegation = "SELECT d.delegation_id, d.empid, d.subdepartid, e.fname, e.lname, pre.prefixname, s.subdepartname
                       FROM delegation d
                       JOIN employees e ON d.empid = e.id
                       LEFT JOIN prefix pre ON e.prefix = pre.prefixid
                       JOIN subdepart s ON d.subdepartid = s.subdepartid";
    $result_delegation = $conn->query($sql_delegation);
    if (!$result_delegation) {
        error_log("Error fetching delegation: " . $conn->error);
        throw new Exception("Error fetching delegation: " . $conn->error);
    }

    $delegations = [];
    $row_count = $result_delegation->num_rows;
    error_log("Delegation query returned $row_count rows");
    while ($row = $result_delegation->fetch_assoc()) {
        $delegations[] = $row;
    }
} catch (Exception $e) {
    error_log("Delegation fetch error: " . $e->getMessage());
    $delegations = [];
}

// จัดการ alert
$showAlert = false;
$alertType = '';
$alertMessage = '';
if (isset($_SESSION['alert'])) {
    $showAlert = true;
    $alertType = $_SESSION['alert']['type'];
    $alertMessage = $_SESSION['alert']['message'];
    unset($_SESSION['alert']);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css" onerror="console.error('Failed to load Remixicon')">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" onerror="console.error('Failed to load Bootstrap CSS')">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" onerror="console.error('Failed to load DataTables CSS')">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" onerror="console.error('Failed to load DataTables Responsive CSS')">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" onerror="console.error('Failed to load SweetAlert2 CSS')">
    <?php
    if (file_exists('assets/css/styles.css')) {
        echo '<link rel="stylesheet" href="assets/css/styles.css">';
    }
    if (file_exists('assets/css/table.css')) {
        echo '<link rel="stylesheet" href="assets/css/table.css">';
    }
    ?>
    <title>มอบอำนาจ</title>
    <style>
        .delegation-section { margin-top: 20px; }
        .table-responsive { width: 100%; }
        table.dataTable { width: 100% !important; }
    </style>
</head>
<body>
    <?php
    try {
        include('component/sidebar.php');
    } catch (Exception $e) {
        error_log("Failed to include sidebar.php: " . $e->getMessage());
        echo '<div class="alert alert-danger">Error: Cannot load sidebar</div>';
    }
    ?>
    <main class="main container3" id="main">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">รักษาราชการแทน</h3>
                <p class="card-text mt-2 mb-0">วันที่ปัจจุบัน: <?= $customCurrentDate->format('d/m/') . ($customCurrentDate->format('Y')) ?></p>
            </div>
            <div class="card-body">
                <!-- ส่วนมอบอำนาจ -->
                <div class="delegation-section">
                    <h4 W>มอบอำนาจ</h4>
                    <form id="delegateForm" method="POST" class="mb-3">
                        <div class="row">
                            <div class="col-md-5">
                                <label for="subdepartid" class="form-label">เลือกหน่วยงานย่อย</label>
                                <select name="subdepartid" id="subdepartid" class="form-control" required>
                                    <option value="">-- เลือกหน่วยงานย่อย --</option>
                                    <?php foreach ($subdepartments as $id => $name): ?>
                                        <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label for="empid" class="form-label">เลือกพนักงาน</label>
                                <select name="empid" id="empid" class="form-control" required>
                                    <option value="">-- เลือกพนักงาน --</option>
                                    <?php foreach ($delegates as $empid => $delegate): ?>
                                        <option value="<?= $empid ?>">
                                            <?= htmlspecialchars($delegate['fullname']) ?> (<?= htmlspecialchars($delegate['rolename']) ?>, <?= htmlspecialchars($delegate['subdepartname']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 align-self-end">
                                <button type="submit" class="btn btn-primary">รักษาราชการแทน</button>
                            </div>
                        </div>
                    </form>

                    <!-- ตารางแสดงการมอบอำนาจ -->
                    <div class="table-responsive">
                        <?php if (empty($delegations)): ?>
                            <div class="alert alert-warning">ไม่มีข้อมูลการมอบอำนาจในระบบ</div>
                        <?php endif; ?>
                        <table id="delegation-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                <th>ลำดับ</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>หน่วยงานย่อย</th>
                                    <th>การดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php  foreach ($delegations as $delegation): ?>
                                    <tr>
                                        <td><?= $i?></td>
                                       <td><?= htmlspecialchars($delegation['prefixname'].$delegation['fname'] . ' ' . $delegation['lname']) ?></td>
                                        <td><?= htmlspecialchars($delegation['subdepartname']) ?></td>
                                      
                                        <td>
                                            <form class="revokeForm" method="POST">
                                                <input type="hidden" name="delegation_id" value="<?= $delegation['delegation_id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">ยกเลิก</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php $i++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php include('component/footer.php'); ?>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" onerror="console.error('Failed to load Bootstrap JS')"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js" onerror="console.error('Failed to load DataTables JS')"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js" onerror="console.error('Failed to load DataTables Bootstrap JS')"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js" onerror="console.error('Failed to load DataTables Responsive JS')"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js" onerror="console.error('Failed to load DataTables Responsive Bootstrap JS')"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" onerror="console.error('Failed to load SweetAlert2 JS')"></script>
    <?php
    if (file_exists('assets/js/main.js')) {
        echo '<script src="assets/js/main.js"></script>';
    }
    ?>
    <script>
        $(document).ready(function() {
            // ตั้งค่า DataTable
            try {
                $('#delegation-table').DataTable({
                    responsive: true,
                    autoWidth: false,
                    scrollX: true,
                    pageLength: 5,
                    lengthMenu: [[5, 15, 25, 50, -1], [5, 15, 25, 50, "ทั้งหมด"]],
                    language: {
                        lengthMenu: "แสดง _MENU_ รายการ",
                        zeroRecords: "ไม่พบข้อมูล",
                        info: "แสดงหน้า _PAGE_ จาก _PAGES_",
                        infoEmpty: "ไม่มีข้อมูล",
                        infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
                        search: "ค้นหา:",
                        paginate: { first: "หน้าแรก", last: "หน้าสุดท้าย", next: "ถัดไป", previous: "ก่อนหน้า" }
                    }
                });
            } catch (e) {
                console.error('DataTable initialization failed:', e);
            }

            // จัดการฟอร์มมอบอำนาจ
            $('#delegateForm').on('submit', function(e) {
                e.preventDefault();
                let formData = $(this).serialize() + '&action=delegate';
                console.log('Delegation Form Data:', formData); // Log the form data
                $.ajax({
                    url: '../admin/process/process_delegation.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire({
                            icon: response.type,
                            title: response.type === 'success' ? 'สำเร็จ' : 'เกิดข้อผิดพลาด',
                            text: response.message,
                            confirmButtonText: 'ตกลง'
                        }).then(() => {
                            if (response.type === 'success') {
                                window.location.reload();
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error);
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้',
                            confirmButtonText: 'ตกลง'
                        });
                    }
                });
            });

            // จัดการฟอร์มยกเลิกอำนาจ
            $('.revokeForm').on('submit', function(e) {
                e.preventDefault();
                let formData = $(this).serialize() + '&action=revoke';
                console.log('Revoke Form Data:', formData); // Log the form data
                Swal.fire({
                    title: 'ยืนยันการยกเลิกอำนาจ?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../admin/process/process_delegation.php',
                            type: 'POST',
                            data: formData,
                            dataType: 'json',
                            success: function(response) {
                                Swal.fire({
                                    icon: response.type,
                                    title: response.type === 'success' ? 'สำเร็จ' : 'เกิดข้อผิดพลาด',
                                    text: response.message,
                                    confirmButtonText: 'ตกลง'
                                }).then(() => {
                                    if (response.type === 'success') {
                                        window.location.reload();
                                    }
                                });
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX error:', status, error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด',
                                    text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้',
                                    confirmButtonText: 'ตกลง'
                                });
                            }
                        });
                    }
                });
            });

            // แสดง SweetAlert จาก session
            <?php if ($showAlert): ?>
                Swal.fire({
                    icon: <?= json_encode($alertType) ?>,
                    title: <?= $alertType === 'success' ? json_encode('สำเร็จ') : json_encode('เกิดข้อผิดพลาด') ?>,
                    text: <?= json_encode($alertMessage) ?>,
                    confirmButtonText: 'ตกลง'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.reload();
                    }
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>

<?php
$conn->close();
ob_end_flush();
?>