<?php
include("sql/data-employee.php");
// include("sql/getemployee.php");
?>
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
   <!-- DataTables CSS -->
   <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
   <!-- Add Responsive DataTables CSS -->
   <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
   <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
   <!-- Add SweetAlert2 CSS -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
   <link rel="stylesheet" href="assets/css/table.css">
   <title>ข้อมูลพนักงาน</title>
</head>
<style>
   .table th,
   .table td {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 150px;
   }
</style>

<body>
   <?php
   include('component/sidebar.php');
   include('component/addemployee.php');
   include('component/editemployee.php');
   ?>

   <!--=============== MAIN ===============-->
   <main class="main container3" id="main">


      <div class="card">
         <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">ข้อมูลพนักงาน</h3>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
               <i class="ri-add-line"></i> เพิ่มพนักงาน
            </button>
         </div>
         <div class="card-body">
            <table id="employeeTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%;">
               <thead>
                  <tr>
                     <th width="5%">ลำดับ</th>
                     <th hidden>ลำดับ</th>

                     <th width="15%">ชื่อ-นามสกุล</th>
                     <th>รูป</th>
                     <th width="15%">อีเมล</th>
                     <th width="15%">งาน</th>
                     <th width="15%">ตำแหน่ง</th>
                     <th width="12%">สถานะงาน</th>
                     <th width="10%">วันที่เริ่มงาน</th>
                     <th width="10%">วันที่บรรจุ</th>
                     <th width="5%">จัดการ</th>
                  </tr>
               </thead>
               <tbody>
                  <?php
                  $i = 1;
                  while ($row = $result4->fetch_assoc()):
                  ?>
                     <tr>
                        <td><?php echo $i++; ?></td>
                        <td hidden><?php echo $row['id']; ?></td>

                        <td><?php echo $row['fullname']; ?></td>
                        <td><img src="uploads/<?php echo $row['pic']; ?> " alt="" width="50px"></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['subdepartname']; ?></td>
                        <td><?php echo $row['positionname']; ?></td>
                        <td><?php echo $row['staffstatus']; ?></td>
                        <td><?php
                              $start = new DateTime($row['startwork']);
                              $now = new DateTime();
                              $interval = $start->diff($now);
                              $formatted_date = $start->format('d/m/Y');

                              if ($interval->y > 0) {
                                 echo $formatted_date . ' (' . $interval->y . ' ปี)';
                              } else {
                                 echo $formatted_date . ' (' . $interval->m . ' เดือน)';
                              }
                              ?></td>
                        <td><?php
                              if ($row['startappoint']) {
                                 $start = new DateTime($row['startappoint']);
                                 $now = new DateTime();
                                 $interval = $start->diff($now);
                                 $formatted_date = $start->format('d/m/Y');

                                 if ($interval->y > 0) {
                                    echo $formatted_date . ' (' . $interval->y . ' ปี)';
                                 } else {
                                    echo $formatted_date . ' (' . $interval->m . ' เดือน)';
                                 }
                              } else {
                                 echo '-';
                              }
                              ?></td>
                        <td>
                           <button class="btn btn-sm btn-outline-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editEmployeeModal"
                              data-id="<?php echo $row['id']; ?>"
                       
                            
                              data-fname="<?php echo $row['fname']; ?>"
                              data-lname="<?php echo $row['lname']; ?>"
                              data-prefix="<?php echo $row['prefixid']; ?>"
                              data-department="<?php echo $row['department']; ?>"
                              data-position="<?php echo $row['positionid']; ?>"
                              data-status="<?php echo $row['staffid']; ?>"
                              data-startwork="<?php echo $row['startwork']; ?>"
                              data-startappoint="<?php echo $row['startappoint']; ?>"
                              data-email="<?php echo $row['email']; ?>">
                              <i class="ri-edit-line"></i>
                           </button>
                           <button class="btn btn-sm btn-outline-danger" onclick="deleteEmployee(<?php echo $row['id']; ?>)">
                              <i class="ri-delete-bin-line"></i>
                           </button>
                        </td>
                     </tr>
                  <?php endwhile; ?>
               </tbody>
            </table>
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
   <script>
      $(document).ready(function() {
         $('#employeeTable').DataTable({
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
   </script>

   <!-- Add SweetAlert2 JS -->
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


</body>

<!-- Add this script at the bottom of the file, before </body> -->
<script>
   function deleteEmployee(id) {
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
               url: 'delete/deleteemployee.php',
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

<script>
   // Check for success message from URL
   $(document).ready(function() {
      // DataTable configuration...

      // Show success message if exists
      <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
         Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: 'บันทึกข้อมูลเรียบร้อยแล้ว',
            timer: 1000
         });
      <?php endif; ?>

      // Show error message if exists
      <?php if (isset($_GET['error'])): ?>
         Swal.fire({
            icon: 'error',
            title: 'ผิดพลาด!',
            text: '<?php echo $_GET['error']; ?>',
         });
      <?php endif; ?>
   });
</script>

<script>
   // Handle edit form submission
   $('#editEmployeeForm').on('submit', function(e) {
      e.preventDefault();
      var formData = new FormData(this);

      $.ajax({
         url: 'update/updateemployee.php',
         type: 'POST',
         data: formData,
         processData: false,
         contentType: false,
         success: function(response) {
            try {
               var res = JSON.parse(response);
               if (res.status === 'success') {
                  Swal.fire({
                     icon: 'success',
                     title: 'สำเร็จ!',
                     text: 'อัพเดทข้อมูลเรียบร้อยแล้ว',
                     timer: 1000,
                     showConfirmButton: false
                  }).then(() => {
                     $('#editEmployeeModal').modal('hide');
                     location.reload();
                  });
               } else {
                  Swal.fire({
                     icon: 'error',
                     title: 'ผิดพลาด!',
                     text: res.message || 'เกิดข้อผิดพลาดในการอัพเดทข้อมูล'
                  });
               }
            } catch (e) {
               Swal.fire({
                  icon: 'error',
                  title: 'ผิดพลาด!',
                  text: 'เกิดข้อผิดพลาดในการประมวลผลข้อมูล'
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

</html>

</html>