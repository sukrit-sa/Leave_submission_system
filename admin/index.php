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

   <title>Dashboard</title>
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
</style>

<body>
   <!-- Include Sidebar -->
   <?php include('component/sidebar.php'); ?>

   <!--=============== MAIN ===============-->
   <main class="main container3" id="main">
      <div class="container-fluid">
         <!-- Overview Section -->
         <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="text-dark">ภาพรวมระบบ</h4>
         </div>
         <div class="row mt-3">
            <!-- จำนวนพนักงานทั้งหมด -->
            <div class="col-xl-3 col-md-6 mb-4">
               <div class="card border-left-primary shadow h-100 py-2">
                  <div class="card-body">
                     <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                           <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">พนักงานทั้งหมด</div>
                           <div class="h5 mb-0 font-weight-bold text-gray-800">
                              <?php
                              include('conn/conn.php');
                              $sql = "SELECT COUNT(*) as total FROM employees";
                              $result = $conn->query($sql);
                              $row = $result->fetch_assoc();
                              echo $row['total'];
                              ?>
                           </div>
                        </div>
                        <div class="col-auto">
                           <i class="ri-user-3-line fa-2x text-gray-300"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>

            <!-- จำนวนใบลาที่รออนุมัติ -->
            <div class="col-xl-3 col-md-6 mb-4">
               <div class="card border-left-warning shadow h-100 py-2">
                  <div class="card-body">
                     <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                           <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">ใบลารออนุมัติ</div>
                           <div class="h5 mb-0 font-weight-bold text-gray-800">
                              <?php
                              $sql = "SELECT COUNT(*) as total FROM leaves WHERE leavestatus = 'รออนุมัติ'";
                              $result = $conn->query($sql);
                              $row = $result->fetch_assoc();
                              echo $row['total'];
                              ?>
                           </div>
                        </div>
                        <div class="col-auto">
                           <i class="ri-time-line fa-2x text-gray-300"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>

            <!-- จำนวนใบลาที่อนุมัติแล้ว -->
            <div class="col-xl-3 col-md-6 mb-4">
               <div class="card border-left-success shadow h-100 py-2">
                  <div class="card-body">
                     <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                           <div class="text-xs font-weight-bold text-success text-uppercase mb-1">ใบลาที่อนุมัติแล้ว</div>
                           <div class="h5 mb-0 font-weight-bold text-gray-800">
                              <?php
                              $sql = "SELECT COUNT(*) as total FROM leaves WHERE leavestatus = 'อนุมัติ'";
                              $result = $conn->query($sql);
                              $row = $result->fetch_assoc();
                              echo $row['total'];
                              ?>
                           </div>
                        </div>
                        <div class="col-auto">
                           <i class="ri-check-line fa-2x text-gray-300"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>

            <!-- จำนวนใบลาที่ไม่อนุมัติ -->
            <div class="col-xl-3 col-md-6 mb-4">
               <div class="card border-left-danger shadow h-100 py-2">
                  <div class="card-body">
                     <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                           <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">ใบลาที่ไม่อนุมัติ</div>
                           <div class="h5 mb-0 font-weight-bold text-gray-800">
                              <?php
                              $sql = "SELECT COUNT(*) as total FROM leaves WHERE leavestatus = 'ไม่อนุมัติ'";
                              $result = $conn->query($sql);
                              $row = $result->fetch_assoc();
                              echo $row['total'];
                              ?>
                           </div>
                        </div>
                        <div class="col-auto">
                           <i class="ri-close-line fa-2x text-gray-300"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
         <h4 class="text-dark">การติดตามสถานะการลา</h4>
      </div>
      <div class="row mt-3">
         <!-- Tracking Cards Row -->
         <div class="row mt-3">
            <!-- รอหัวหน้าอนุมัติ -->
            <div class="col-xl-4 col-md-6 mb-4">
               <div class="card border-left-info shadow h-100 py-2">
                  <div class="card-body">
                     <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                           <div class="text-xs font-weight-bold text-info text-uppercase mb-1">รอหัวหน้าอนุมัติ</div>
                           <div class="h5 mb-0 font-weight-bold text-gray-800">
                              <?php
                              $sql = "SELECT COUNT(*) as total FROM leaves 
                                     WHERE leavestatus = 'รอหัวหน้าอนุมัติ' 
                                     ";
                              $result = $conn->query($sql);
                              $row = $result->fetch_assoc();
                              echo $row['total'];
                              ?>
                           </div>
                        </div>
                        <div class="col-auto">
                           <i class="ri-user-follow-line fa-2x text-gray-300"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>


            <!-- รอรองผู้อำนวยการอนุมัติ -->
            <div class="col-xl-4 col-md-6 mb-4">
               <div class="card border-left-primary shadow h-100 py-2">
                  <div class="card-body">
                     <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                           <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">รอรองผู้อำนวยการอนุมัติ</div>
                           <div class="h5 mb-0 font-weight-bold text-gray-800">
                              <?php
                              $sql = "SELECT COUNT(*) as total FROM leaves 
                                     WHERE leavestatus = 'รอรองผอ.อนุมัติ' ";
                              $result = $conn->query($sql);
                              $row = $result->fetch_assoc();
                              echo $row['total'];
                              ?>
                           </div>
                        </div>
                        <div class="col-auto">
                           <i class="ri-user-star-line fa-2x text-gray-300"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>

            <!-- รอผู้อำนวยการอนุมัติ -->
            <div class="col-xl-4 col-md-6 mb-4">
               <div class="card border-left-success shadow h-100 py-2">
                  <div class="card-body">
                     <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                           <div class="text-xs font-weight-bold text-success text-uppercase mb-1">รอผู้อำนวยการอนุมัติ</div>
                           <div class="h5 mb-0 font-weight-bold text-gray-800">
                              <?php
                              $sql = "SELECT COUNT(*) as total FROM leaves 
                                     WHERE leavestatus = 'รอผอ.อนุมัติ' ";
                              $result = $conn->query($sql);
                              $row = $result->fetch_assoc();
                              echo $row['total'];
                              ?>
                           </div>
                        </div>
                        <div class="col-auto">
                           <i class="ri-user-settings-line fa-2x text-gray-300"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
      </div>
      </div>
      <?php include('component/footer.php'); ?>
   </main>

   <!--=============== MAIN JS ===============-->
   <script src="assets/js/main.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>