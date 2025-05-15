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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/table.css">

    <!-- Add jQuery library -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

    <title>จัดการผู้ดูแลระบบ</title>
</head>

<body>
    <?php include('component/sidebar.php'); ?>

    <main class="main container3" id="main">
        <!-- Add tab navigation -->
        <ul class="nav nav-tabs mb-3" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees" type="button" role="tab">
                    รายชื่อพนักงาน
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="admins-tab" data-bs-toggle="tab" data-bs-target="#admins" type="button" role="tab">
                    รายชื่อผู้ดูแลระบบ
                </button>
            </li>
        </ul>

        <!-- Tab content -->
        <div class="tab-content" id="adminTabContent">
            <!-- Employees tab -->
            <div class="tab-pane fade show active" id="employees" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">รายชื่อพนักงาน</h3>
                    </div>
                    <div class="card-body">
                        <!-- Your existing employeeTable goes here -->
                        <table id="employeeTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_emp = "SELECT employees.id, CONCAT(prefix.prefixname, employees.fname, ' ', employees.lname) AS fullname 
                                          FROM employees 
                                          LEFT JOIN prefix ON employees.prefix = prefix.prefixid
                                          WHERE employees.id NOT IN (SELECT id FROM admin)";
                                $result_emp = mysqli_query($conn, $sql_emp);
                                $i = 1;
                                while ($row = mysqli_fetch_assoc($result_emp)) {
                                ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo $row['fullname']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-success"
                                                onclick="addAdmin(<?php echo $row['id']; ?>)">
                                                <i class="ri-add-line"></i> เพิ่มเป็นผู้ดูแล
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Admins tab -->
            <div class="tab-pane fade" id="admins" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">รายชื่อผู้ดูแลระบบ</h3>
                    </div>
                    <div class="card-body">
                        <!-- Your existing adminTable goes here -->
                        <table id="adminTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                include('conn/conn.php');
                                $sql_admin = "SELECT admin.id, CONCAT(prefix.prefixname, employees.fname, ' ', employees.lname) AS fullname 
                                              FROM admin 
                                              LEFT JOIN employees ON admin.id = employees.id
                                              LEFT JOIN prefix ON employees.prefix = prefix.prefixid";
                                $result_admin = mysqli_query($conn, $sql_admin);
                                $i = 1;
                                while ($row = mysqli_fetch_assoc($result_admin)) {
                                ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo $row['fullname']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteAdmin(<?php echo $row['id']; ?>)">
                                                <i class="ri-delete-bin-line"></i>
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
        <?php include('component/footer.php'); ?>
    </main>

    <!--=============== MAIN JS ===============-->
    <script src="assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    <!-- Update script section -->
    <!-- Add this JavaScript function -->
    <script>
        $(document).ready(function() {
            $('#employeeTable').DataTable({
                responsive: true,
                pageLength: 5,
                lengthMenu: [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "ทั้งหมด"]
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/th.json',
                }
            });

            $('#adminTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/th.json',
                }
            });
        });

        function addAdmin(id) {
            Swal.fire({
                title: 'ยืนยันการเพิ่ม?',
                text: "คุณต้องการเพิ่มผู้ดูแลระบบนี้ใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ใช่, เพิ่ม!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'add/addadmin.php',
                        type: 'POST',
                        data: {
                            id: id
                        },
                        dataType: 'json', // Explicitly expect JSON response
                        beforeSend: function() {
                            // Show loading indicator
                            Swal.showLoading();
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'เพิ่มสำเร็จ!',
                                    text: 'เพิ่มผู้ดูแลระบบเรียบร้อยแล้ว',
                                    timer: 1000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด!',
                                    text: response.message || 'ไม่สามารถเพิ่มผู้ดูแลระบบได้'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด!',
                                text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้: ' + error
                            });
                        }
                    });
                }
            });
        }

        function deleteAdmin(id) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "คุณต้องการลบผู้ดูแลระบบนี้ใช่หรือไม่?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ใช่, ลบ!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'delete/deleteadmin.php',
                        type: 'POST',
                        data: {
                            id: id
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'ลบสำเร็จ!',
                                    text: 'ลบผู้ดูแลระบบเรียบร้อยแล้ว',
                                    timer: 1000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด!',
                                    text: response.message || 'ไม่สามารถลบผู้ดูแลระบบได้'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด!',
                                text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                            });
                        }
                    });
                }
            });
        }
    </script>
</body>

</html>