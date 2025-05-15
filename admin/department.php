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
   <!-- Add DataTables & SweetAlert CSS -->
   <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
   <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
   <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
   <link rel="stylesheet" href="assets/css/table.css">
   <title>ข้อมูลหน่วยงาน</title>
</head>

<body>
   <!-- Include Sidebar -->
   <?php
   include('component/sidebar.php');
   include('component/addheadpert.php');
   include('component/editdepartment.php');

   ?>

   <!--=============== MAIN ===============-->
   <main class="main container3" id="main">

      <div class="card">
         <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">ข้อมูลหน่วยงาน</h3>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
               <i class="ri-add-line"></i> เพิ่มหน่วยงาน
            </button>
         </div>
         <div class="card-body">
            <table id="departmentTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%; margin-bottom: 0;">
               <thead>
                  <tr>
                     <th>ลำดับ</th>
                     <th>ชื่อหน่วยงาน</th>
                     <th>จัดการ</th>
                  </tr>
               </thead>
               <tbody>
                  <?php
                  include('conn/conn.php');
                  $sql = "SELECT * FROM headepart ORDER BY headepartid DESC";
                  $result = mysqli_query($conn, $sql);
                  while ($row = mysqli_fetch_assoc($result)) {
                  ?>
                     <tr>
                        <td><?php echo $row['headepartid']; ?></td>
                        <td><?php echo $row['headepartname']; ?></td>
                        <td>
                           <button class="btn btn-sm btn-outline-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editDepartmentModal" data-id="<?php echo $row['headepartid']; ?>" data-name="<?php echo $row['headepartname']; ?>">
                              <i class="ri-edit-line"></i>
                           </button>
                           <button class="btn btn-sm btn-outline-danger" onclick="deleteDepartment(<?php echo $row['headepartid']; ?>)">
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

   <!-- Add scripts before closing body tag -->
   <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
   <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
   <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
   <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

   <script>
      $(document).ready(function() {
         $('#departmentTable').DataTable({
            responsive: true,
            autoWidth: false,
            pageLength: 5,
            lengthMenu: [
               [5, 10, 25, 50, -1],
               [5, 10, 25, 50, "ทั้งหมด"]
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

      function deleteDepartment(id) {
         Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "คุณต้องการลบข้อมูลแผนกนี้ใช่หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
         }).then((result) => {
            if (result.isConfirmed) {
               $.ajax({
                  url: 'delete/deletedepartment.php',
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
                           timer: 1000,
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

   <script>
      // Add Department Form Handler
      $('#addDepartmentForm').on('submit', function(e) {
         e.preventDefault();

         $.ajax({
            url: 'add/adddepartment.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
               if (response.status === 'success') {
                  Swal.fire({
                     icon: 'success',
                     title: 'สำเร็จ!',
                     text: response.message,
                     timer: 1000,
                     showConfirmButton: false
                  }).then(() => {
                     $('#addDepartmentModal').modal('hide');
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
                  text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์'
               });
            }
         });
      });
   </script>
   <script>
      // เพิ่มฟังก์ชันสำหรับการแก้ไข
      $(document).on('click', '.edit-btn', function() {
         var id = $(this).data('id');
         var name = $(this).data('name');

         $('#editDepartmentId').val(id);
         $('#editDepartmentName').val(name);
      });

      // จัดการการส่งฟอร์มแก้ไข
      $('#editDepartmentForm').on('submit', function(e) {
         e.preventDefault();

         $.ajax({
            url: 'update/updatedepartment.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
               if (response.status === 'success') {
                  Swal.fire({
                     icon: 'success',
                     title: 'สำเร็จ!',
                     text: response.message,
                     timer: 1000,
                     showConfirmButton: false
                  }).then(() => {
                     $('#editDepartmentModal').modal('hide');
                     location.reload();
                  });
               } else {
                  Swal.fire({
                     icon: 'error',
                     title: 'ผิดพลาด!',
                     text: response.message || 'ไม่สามารถแก้ไขข้อมูลได้'
                  });
               }
            },
            error: function() {
               Swal.fire({
                  icon: 'error',
                  title: 'ผิดพลาด!',
                  text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์'
               });
            }
         });
      });
   </script>

</body>

</body>
</body>

</html>