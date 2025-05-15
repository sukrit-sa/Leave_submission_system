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
   <title>ข้อมูลงาน</title>
</head>

<body>
   <?php
   include('component/sidebar.php');
   include('component/addsubdepart.php');
   include('component/editsubdepart.php');
   ?>

   <main class="main container3" id="main">
      <div class="card">
         <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">ข้อมูลงาน</h3>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addSubDepartmentModal">
               <i class="ri-add-line"></i> เพิ่มงาน
            </button>
         </div>
         <div class="card-body">
            <table id="subDepartmentTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
               <thead>
                  <tr>
                     <th>ลำดับ</th>
                     <th>งาน</th>
                     <th>หน่วยงาน</th>
                     <th>จัดการ</th>
                  </tr>
               </thead>
               <tbody>
                  <?php
                  include('conn/conn.php');
                  $sql = "SELECT subdepart.*, headepart.headepartname 
                  FROM subdepart 
                  LEFT JOIN headepart ON subdepart.headepartid = headepart.headepartid 
                  ORDER BY subdepart.subdepartid DESC";
                  $result = mysqli_query($conn, $sql);
                  while ($row = mysqli_fetch_assoc($result)) {
                  ?>
                     <tr>
                        <td><?php echo $row['subdepartid']; ?></td>
                        <td><?php echo $row['subdepartname']; ?></td>
                        <td><?php echo $row['headepartname']; ?></td>
                        <td>
                           <button class="btn btn-sm btn-outline-warning edit-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#editSubDepartmentModal"
                                data-subdepartid="<?php echo $row['subdepartid']; ?>"
                                data-subdepartname="<?php echo $row['subdepartname']; ?>"
                                data-headepartid="<?php echo $row['headepartid']; ?>">
                                <i class="ri-edit-line"></i>
                             </button>
                           <button class="btn btn-sm btn-outline-danger" onclick="deleteSubDepartment(<?php echo $row['subdepartid']; ?>)">
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

   <script src="assets/js/main.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
   <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
   <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
   <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
   <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

   <script>
      $(document).ready(function() {
         $('#subDepartmentTable').DataTable({
            responsive: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [
               [10, 15, 25, 50, -1],
               [10, 15, 25, 50, "ทั้งหมด"]
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

      function deleteSubDepartment(id) {
         Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "คุณต้องการลบข้อมูลแผนกย่อยนี้ใช่หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
         }).then((result) => {
            if (result.isConfirmed) {
               $.ajax({
                  url: 'delete/deletesubdepart.php',
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
                           timer: 1500
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