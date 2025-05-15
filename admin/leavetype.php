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
    <title>ประเภทการลา</title>
</head>

<body>
    <!-- Include Sidebar -->
    <?php
    include('component/sidebar.php');
    include('component/addleavetype.php');
    include('component/editleavetype.php');
    ?>

    <!--=============== MAIN ===============-->
    <main class="main container3" id="main">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">ประเภทการลา</h3>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addLeaveTypeModal">
                    <i class="ri-add-line"></i> เพิ่มประเภทการลา
                </button>
            </div>
            <div class="card-body">
                <table id="leaveTypeTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>ชื่อประเภทการลา</th>
                            <th>สถาณะงาน</th>
                            <th>อายุการทำงาน</th>
                            <th>สิทธิ์วันลาต่อปี</th>
                            <th>สิทธิ์วันลาลาสะสม</th>
                            <th>สิ่งที่ต้องแนบ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        include('conn/conn.php');
                        $sql = "SELECT * FROM leavetype
                                LEFT JOIN staffstatus ON leavetype.staffid = staffstatus.staffid
                                
                                ";
                        $result = mysqli_query($conn, $sql);
                        while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                            <tr>
                                <td><?php echo $row['leavetypeid']; ?></td>
                                <td><?php echo $row['leavetypename']; ?></td>
                                <td><?php echo $row['staffname']; ?></td>
                                <td>
                                    <?php
                                    if ($row['workage'] == 3) {
                                        echo "ไม่มีเงื่อนไข";
                                    } else {
                                        $workageday = $row['workageday']; // ใช้ workageday อย่างเดียว
                                        if ($workageday > 12) {
                                            $years = floor($workageday / 12); // คำนวณปี
                                            $months = $workageday % 12; // เดือนที่เหลือ
                                            echo ($row['workage'] == 1 ? 'มากกว่า ' : 'น้อยกว่า ') . $years . " ปี";
                                            if ($months > 0) {
                                                echo " " . $months . " เดือน";
                                            }
                                        } else {
                                            echo ($row['workage'] == 1 ? 'มากกว่า ' : 'น้อยกว่า ') . $workageday . " เดือน";
                                        }
                                    }
                                    ?>
                                </td>
                                <td><?php echo $row['leaveofyear']; ?> วัน</td>
                                <td><?= $row['stackleaveday'] ? $row['stackleaveday'] . " วัน" : "ไม่มี"; ?></td>
                                <td><?= $row['nameform'] ? $row['nameform'] . "" : "ไม่มี"; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-warning edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editLeaveTypeModal"
                                        data-leavetypeid="<?php echo $row['leavetypeid']; ?>"
                                        data-leavetypename="<?php echo $row['leavetypename']; ?>">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteLeaveType(<?php echo $row['leavetypeid']; ?>)">
                                        <i class="ri-delete-bin-line"></i>
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

    <!--=============== MAIN JS ===============-->
    <script src="assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Remove duplicate scripts below -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('#leaveTypeTable').DataTable({
                responsive: true,
                autoWidth: false,
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
        });

        function deleteLeaveType(id) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "คุณต้องการลบข้อมูลประเภทการลานี้ใช่หรือไม่?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'delete/deleteleavetype.php',
                        type: 'POST',
                        data: {
                            id: id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: response.message,
                                    timer: 1000
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ผิดพลาด!',
                                    text: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'ผิดพลาด!',
                                text: 'ไม่สามารถลบข้อมูลได้'
                            });
                        }
                    });
                }
            });
        }
    </script>


</body>

</html>