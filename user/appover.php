<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--=============== REMIXICONS ===============-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css">
    <!--=============== CSS ===============-->
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- รวม FullCalendar CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <link rel="stylesheet" href="assets/css/table.css">

    <title>Dashboard</title>
    <!-- Add this line in the head section -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<style>
    .border-left-primary {
        border-left: 4px solid #4e73df !important;
    }

    .border-left-success {
        border-left: 4px solid #1cc88a !important;
    }

    .border-left-warning {
        border-left: 4px solid #f6c23e !important;
    }

    .border-left-danger {
        border-left: 4px solid #e74a3b !important;
    }

    .text-gray-300 {
        color: #dddfeb !important;
    }

    .text-gray-800 {
        color: #5a5c69 !important;
    }

    .shadow {
        box-shadow: 0 .15rem 1.75rem 0 rgba(58, 59, 69, .15) !important;
    }

    .border-left-info {
        border-left: 4px solid #36b9cc !important;
    }

    .btn-boder.border-success:hover {
        background-color: #198754;
        color: white;
        transition: all 0.3s ease;
    }

    .btn-boder.border-danger:hover {
        background-color: #dc3545;
        color: white;
        transition: all 0.3s ease;
    }
</style>

<body>
    <!-- Include Sidebar -->
    <?php include('component/sidebar.php'); ?>

    <!--=============== MAIN ===============-->
    <main class="main container3" id="main">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">รายการรออนุมัติ</h3>
            </div>
            <div class="card-body">
                <table id="leaveTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>ยื่น</th>
                            <th hidden>id</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>ประเภทการลา</th>
                            <th>วันที่เริ่มลา</th>
                            <th>วันที่สิ้นสุด</th>
                            <th>จำนวนวัน</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        include('conn/conn.php');
                        $sql = "SELECT leaves.*, CONCAT(pr.prefixname, e.fname, ' ', e.lname) as fullname, 
                               leavetype.leavetypename 
                               FROM leaves
                               LEFT JOIN employees e ON leaves.employeesid = e.id
                               LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                               LEFT JOIN leavetype ON leaves.leavetype = leavetype.leavetypeid
                               WHERE leaves.leavestatus = 'รอหัวหน้าอนุมัติ' AND leaves.approver1 = $userID
                               OR leaves.leavestatus = 'รอรองผอ.อนุมัติ' AND leaves.approver2 = $userID
                               OR leaves.leavestatus = 'รอผอ.อนุมัติ' AND leaves.approver3 = $userID
                               ";
                        $result = mysqli_query($conn, $sql);
                        $i = 1;
                        while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo $row['note']; ?></td>
                                <td hidden><?php echo $row['leavesid']; ?></td>
                                <td><?php echo $row['fullname']; ?></td>
                                <td><?php echo $row['leavetypename']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['leavestart'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['leaveend'])); ?></td>
                                <td><?php echo $row['day']; ?></td>
                                <td style="background-color: #FFE082; color: black;">
                                    <?php echo $row['leavestatus']; ?>
                                </td>
                                <td>
                                    <button class="btn btn-boder border-success btn-sm" 
                                        data-leavesid="<?php echo $row['leavesid']; ?>"
                                        data-status="<?php echo $row['leavestatus']; ?>"
                                        data-employeesid = "<?php echo $row['employeesid'];?>"
                                        data-leavetype = "<?php echo $row['leavetype'];?>"
                                        data-day = "<?php echo $row['day'];?>"
                                        data-note = "<?php echo $row['note'];?>"
                                        >
                                        <i class="ri-check-line"></i>
                                    </button>
                                    <button class="btn btn-boder border-danger btn-sm reject-btn" 
                                        data-leavesid="<?php echo $row['leavesid']; ?>"
                                        data-status="<?php echo $row['leavestatus']; ?>"
                                        data-employeesid="<?php echo $row['employeesid']; ?>">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include('component/footer.php'); ?>
    </main>

    <!-- เพิ่ม DataTables CSS และ JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

  
    <!--=============== MAIN JS ===============-->
    <script src="assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#leaveTable').DataTable({
                responsive: true,
                language: {
                    url: 'assets/js/datatable-lang/th.json'
                }
            });

            // Use event delegation for the approve button
            $('#leaveTable').on('click', '.btn-boder.border-success', function() {
                const leaveId = $(this).data('leavesid');
                const currentStatus = $(this).data('status');
                const employeesid = $(this).data('employeesid');
                const leavetype = $(this).data('leavetype');
                const leaveday = $(this).data('day');
                const note = $(this).data('note');

                
                console.log(leaveId);
                console.log(currentStatus);
                console.log('id'+employeesid);
                console.log('type'+leavetype);
                console.log('day'+leaveday);

                Swal.fire({
                    title: 'ยืนยันการอนุมัติ?',
                    text: "คุณต้องการอนุมัติใบลานี้ใช่หรือไม่?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ใช่, อนุมัติ!',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'process/approve_leave.php',
                            type: 'POST',
                            data: {
                                leave_id: leaveId,
                                current_status: currentStatus,
                                employeesid: employeesid,
                                leavetype: leavetype,
                                leaveday: leaveday,
                                note:note

                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'อนุมัติสำเร็จ!',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'เกิดข้อผิดพลาด!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX Error:', status, error);
                                console.log('Response:', xhr.responseText);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด!',
                                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้: ' + error,
                                    footer: 'รายละเอียด: ' + xhr.responseText
                                });
                            }
                        });
                    }
                });
            });
            // ... existing code ...
            // เพิ่ม event handler สำหรับปุ่มไม่อนุมัติ
            $('#leaveTable').on('click', '.reject-btn', function() {
                const leaveId = $(this).data('leavesid');
                const currentStatus = $(this).data('status');
                const employeesid = $(this).data('employeesid');

                Swal.fire({
                    title: 'ระบุเหตุผลการไม่อนุมัติ',
                    input: 'textarea',
                    inputLabel: 'เหตุผล',
                    inputPlaceholder: 'กรุณาระบุเหตุผลการไม่อนุมัติ...',
                    inputAttributes: {
                        'aria-label': 'กรุณาระบุเหตุผลการไม่อนุมัติ'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ไม่อนุมัติ',
                    cancelButtonText: 'ยกเลิก',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'กรุณาระบุเหตุผลการไม่อนุมัติ!'
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'process/reject_leave.php',
                            type: 'POST',
                            data: {
                                leave_id: leaveId,
                                current_status: currentStatus,
                                employeesid: employeesid,
                                reject_reason: result.value
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'ไม่อนุมัติสำเร็จ!',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'เกิดข้อผิดพลาด!',
                                        text: response.message
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด!',
                                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
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