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
        <?php
        include('conn/conn.php');
        // Get current user ID from session
        // $userID = $_SESSION['id'];
        
        // Count pending leaves
        $pending_query = "SELECT COUNT(*) as count FROM leaves WHERE employeesid = '$userID' AND leavestatus IN ('รอหัวหน้าอนุมัติ', 'รอรองผอ.อนุมัติ', 'รอผอ.อนุมัติ' , 'รออนุมัติ')";
        $pending_result = mysqli_query($conn, $pending_query);
        $pending_count = mysqli_fetch_assoc($pending_result)['count'];

        // Count approved leaves
        $approved_query = "SELECT COUNT(*) as count FROM leaves WHERE employeesid = '$userID' AND leavestatus = 'อนุมัติ'";
        $approved_result = mysqli_query($conn, $approved_query);
        $approved_count = mysqli_fetch_assoc($approved_result)['count'];

        // Count rejected leaves
        $rejected_query = "SELECT COUNT(*) as count FROM leaves WHERE employeesid = '$userID' AND leavestatus = 'ไม่อนุมัติ'";
        $rejected_result = mysqli_query($conn, $rejected_query);
        $rejected_count = mysqli_fetch_assoc($rejected_result)['count'];
        ?>

        <div class="row">
            <!-- Pending Leaves Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    รอดำเนินการ</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="ri-time-line fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approved Leaves Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    อนุมัติแล้ว</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $approved_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="ri-checkbox-circle-line fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rejected Leaves Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    ไม่อนุมัติ</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $rejected_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="ri-close-circle-line fa-2x text-gray-300"></i>
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