<?php
ini_set('session.gc_maxlifetime', 36000);
session_start();
if (!isset($_SESSION['userid'])||  $_SESSION['role'] == 'admin') {
    session_destroy();
    header("Location: ../login/login.php");
    exit();
}
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 36000)) {
    // ถ้าไม่ได้ใช้งานเกิน 10 ชั่วโมง ให้ทำลาย session
    session_unset();
    session_destroy();
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();
$userID = $_SESSION['ID'];
$userName = $_SESSION["Prefix"] . '' . $_SESSION['FirstName'] . ' ' . $_SESSION['LastName'];
$staffid = $_SESSION["Staffid"];
$department = $_SESSION["Department"];
$headdepart = $_SESSION["Headdepart"];
$position =  $_SESSION["Position"];
$workage = $_SESSION["workage"];
$pic =  $_SESSION["img"];
$roleid = $_SESSION["roleid"];
$departid = $_SESSION["departid"];


date_default_timezone_set('Asia/Bangkok');
// $thaiMonths = [
//     1 => 'มกราคม',
//     2 => 'กุมภาพันธ์',
//     3 => 'มีนาคม',
//     4 => 'เมษายน',
//     5 => 'พฤษภาคม',
//     6 => 'มิถุนายน',
//     7 => 'กรกฎาคม',
//     8 => 'สิงหาคม',
//     9 => 'กันยายน',
//     10 => 'ตุลาคม',
//     11 => 'พฤศจิกายน',
//     12 => 'ธันวาคม'
// ];

// $day = date('d');
// $month = $thaiMonths[(int)date('m')];
// $year = date('Y') + 543; // Convert to Buddhist year
// $currentDate = "$day $month $year";
$currentDate = date('d/m/Y');
// Format current date in Thai


?>

<head>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0-alpha3/css/bootstrap.min.css">
    <style>
        /* .sidebar {
            background:rgb(255, 255, 255);
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        } */

        .sidebar__link {
            padding: 12px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            color: #64748b;
            margin: 4px 8px;
        }

        .sidebar__link:hover {
            background: #f1f5f9;
            transform: translateX(5px);
            color: #df6c2a;
        }

        .sidebar__user {
            padding: 20px;
            margin-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .sidebar__user img {
            border-radius: 50%;
            border: 3px solid #e2e8f0;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .sidebar__title {
            color: #475569;
            font-size: 0.95rem;
            margin-bottom: 15px;
            padding-left: 15px;
            font-weight: 600;
        }

        .active-link {
            background: rgb(253, 234, 213);
            color: #df6c2a;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
        }

        .header__logo {
            font-weight: 600;
            color: #df6c2a;
            font-size: 1.2rem;
        }

        .header__toggle {
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: #df6c2a;
        }

        .header__toggle:hover {
            background: #f0f9ff;
        }

        .sidebar__actions {
            padding: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .sidebar__link i {
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .sidebar__actions a {
            color: rgb(235, 136, 87);
        }

        .sidebar__actions a:hover {
            color: #df6c2a;
        }
    </style>



</head>

<!-- header.php -->
<header class="header3" id="header">
    <div class="header__container">

        <a href="index.php" class="header__logo" style="text-decoration: none;">
            <span class="header__logo">ระบบการยื่นใบลา </span> <i class="ri-mail-unread-fill"></i>
        </a>
        <button class="header__toggle" id="header-toggle">
            <i class="ri-menu-line"></i>
        </button>

    </div>
</header>

<!-- sidebar.php -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar__container">
        <div class="sidebar__user">
            <img src="../admin/uploads/<?php echo $pic; ?>" alt="" width="50" height="50">
            <div class="sidebar__info">

                <h3><?php echo $userName; ?></h3>
                <span class="sidebar__title2"><?php echo $department; ?></span>

            </div>
        </div>

        <div class="sidebar__content">
            <div>
                <!-- <h3 class="sidebar__title">เมนู</h3> -->
                <div class="sidebar__list">
                    <a href="index.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active-link' : ''; ?>">
                        <i class=" ri-home-2-line"></i>
                        <span>หน้าแรก</span>
                    </a>

                    <?php
                    include('conn/conn.php');
                    if (strpos($position, 'หัวหน้า') !== false || strpos($position, 'ผู้อำนวยการ') !== false):
                        $sql_pending = "SELECT COUNT(*) as count FROM leaves WHERE 
                        leavestatus = 'รอหัวหน้าอนุมัติ'  AND leaves.approver1 = $userID or
                        leavestatus = 'รอรองผอ.อนุมัติ' AND leaves.approver2 = $userID or
                        leavestatus = 'รอผอ.อนุมัติ' AND leaves.approver3 = $userID
                        ";
                        $result_pending = mysqli_query($conn, $sql_pending);
                        $row_pending = mysqli_fetch_assoc($result_pending);
                        $pending_count = $row_pending['count'];
                    ?>
                        <a href="appover.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'appover.php') ? 'active-link' : ''; ?>" style="position: relative;">
                            <i class="ri-checkbox-line"></i>
                            <span>ใบลาที่ต้องอนุมัติ</span>
                            <?php if ($pending_count > 0): ?>
                                <span style="position: absolute; top: -5px; right: -5px; background-color: #dc3545; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; min-width: 20px; text-align: center;"><?php echo $pending_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="viewleave.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'viewleave.php') ? 'active-link' : ''; ?>" style="position: relative;">
                        <i class="ri-file-list-3-line"></i>
                            <span>วันลาพนักงาน</span>
                           
                        </a>
                    <?php endif; ?>

                    <a href="sendleave.php" class="sidebar__link  <?php echo (basename($_SERVER['PHP_SELF']) == 'sendleave.php') ? 'active-link' : ''; ?>">
                        <i class="ri-file-edit-line"></i>
                        <span>ยื่นใบลา</span>
                    </a>

                    <?php
                    include('conn/conn.php');
                    $sql_count = "SELECT COUNT(*) as count FROM leaves WHERE employeesid = '$userID' and leavestatus != 'ไม่อนุมัติ' and leavestatus!= 'อนุมัติ'";
                    $result_count = mysqli_query($conn, $sql_count);
                    $row_count = mysqli_fetch_assoc($result_count);
                    $leave_count = $row_count['count'];
                    ?>
                    <a href="leavestatus.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'leavestatus.php') ? 'active-link' : ''; ?>" style="position: relative;">
                        <i class="ri-time-line"></i>
                        <span>สถานะใบลา</span>
                        <?php if ($leave_count > 0): ?>
                            <span style="position: absolute; top: -5px; right: -5px; background-color: #dc3545; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; min-width: 20px; text-align: center;"><?php echo $leave_count; ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="cancel_leave.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'cancel_leave.php') ? 'active-link' : ''; ?>">
                        <i class="ri-delete-back-2-line"></i>
                        <span>ขอยกเลิกการลา</span>
                    </a>

                    <a href="historyleaves.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'historyleaves.php') ? 'active-link' : ''; ?>">
                        <i class="ri-history-line"></i>
                        <span>ประวัติการลา</span>
                    </a>



                    <a href="personal.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'personal.php') ? 'active-link' : ''; ?>">
                        <i class="ri-user-settings-line"></i>
                        <span>ข้อมูลส่วนตัว</span>
                    </a>

                    <a href="leavesday.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'leavesday.php') ? 'active-link' : ''; ?>">
                        <i class="ri-calendar-check-line"></i>
                        <span>จำนวนวันลา</span>
                    </a>
                </div>
            </div>

            <!-- <div>
                <h3 class="sidebar__title">ตั้งค่า</h3>
                <div class="sidebar__list">
                    <a href="employee.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'employee.php') ? 'active-link' : ''; ?>">
                        <i class=" ri-user-line"></i>
                        <span>พนักงาน</span>
                    </a>

                    <a href="department.php" class=" sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'department.php') ? 'active-link' : ''; ?>">
                        <i class=" ri-building-4-line"></i>
                        <span>หน่วยงาน</span>
                    </a>

                    <a href="subdepart.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'subdepart.php') ? 'active-link' : ''; ?>">
                        <i class=" ri-community-line"></i>
                        <span>งาน</span>
                    </a>


                    <a href="leavetype.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'leavetype.php') ? 'active-link' : ''; ?>">
                        <i class="ri-settings-3-line"></i>
                        <span>ประเภทการลา </span>
                    </a>

                    <a href="staffstatus.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'staffstatus.php') ? 'active-link' : ''; ?>">
                        <i class=" ri-parent-line"></i>
                        <span>สถานะบุคลากร</span>
                    </a>

                    <a href="position.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'position.php') ? 'active-link' : ''; ?>">
                        <i class=" ri-group-line"></i>
                        <span>ตำแหน่ง</span>
                    </a>

                    <a href="role.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'role.php') ? 'active-link' : ''; ?>">
                        <i class="ri-shield-user-line"></i>
                        <span>บทบาท</span>
                    </a>


                    <a href="prefix.php" class="sidebar__link <?php echo (basename($_SERVER['PHP_SELF']) == 'prefix.php') ? 'active-link' : ''; ?>">
                        <i class="ri-parking-box-line"></i>
                        <span>คำนำหน้า</span>
                    </a>


                </div>
            </div> -->
        </div>

        <div class="sidebar__actions">
            <a href="../login/logout.php">
                <i class=" ri-logout-box-line sidebar__link sidebar__theme ">
                    <span>ออกจากระบบ</span>
                </i>
            </a>
        </div>
    </div>
</nav>

<!-- Add this script before closing body tag -->
<script>
    // Store scroll position
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.querySelector('.sidebar__content');
        const activeLink = document.querySelector('.active-link');

        if (activeLink) {
            activeLink.scrollIntoView({
                behavior: 'auto',
                block: 'center'
            });
        }

        // Save scroll position when navigating
        sidebar.addEventListener('scroll', function() {
            localStorage.setItem('sidebarScrollPos', sidebar.scrollTop);
        });

        // Restore scroll position
        const savedScrollPos = localStorage.getItem('sidebarScrollPos');
        if (savedScrollPos) {
            sidebar.scrollTop = parseInt(savedScrollPos);
        }
    });
</script>