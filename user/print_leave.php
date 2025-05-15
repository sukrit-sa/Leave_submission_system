<?php
include('conn/conn.php');

// อาร์เรย์ชื่อเดือนภาษาไทย
$thai_months = [
    1 => 'มกราคม',
    2 => 'กุมภาพันธ์',
    3 => 'มีนาคม',
    4 => 'เมษายน',
    5 => 'พฤษภาคม',
    6 => 'มิถุนายน',
    7 => 'กรกฎาคม',
    8 => 'สิงหาคม',
    9 => 'กันยายน',
    10 => 'ตุลาคม',
    11 => 'พฤศจิกายน',
    12 => 'ธันวาคม'
];

// ตรวจสอบว่ามีการส่ง leavesid มาหรือไม่
if (isset($_GET['leavesid'])) {
    $leaveId = $_GET['leavesid'];

    // ดึงข้อมูลแอดมิน
    $sql_admin = "SELECT 
                CONCAT(pr.prefixname, e.fname, ' ', e.lname) as admin_name,
                p.positionname as admin_position,
                e.signature as admin_signature
                FROM employees e
                INNER JOIN admin a ON e.id = a.id
                LEFT JOIN prefix pr ON e.prefix = pr.prefixid
                LEFT JOIN position p ON e.position = p.positionid
                WHERE e.id = a.id
                limit 1";

    $result_admin = $conn->query($sql_admin);
    $admin = $result_admin->fetch_assoc();

    // ใช้ prepared statement เพื่อป้องกัน SQL Injection
    $sql = "SELECT leaves.*, 
            CONCAT(pr.prefixname, e.fname, ' ', e.lname) as fullname,
            leavetype.leavetypename,
            position.positionname,
            subdepart.subdepartname,
            leaveday.day,
            leaveday.stackleaveday,
            headepart.headepartname,
            leaves.address,
            CONCAT(pr.prefixname, e1.fname, ' ', e1.lname) as approver1_name,
            CONCAT(pr.prefixname, e2.fname, ' ', e2.lname) as approver2_name,
            CONCAT(pr.prefixname, e3.fname, ' ', e3.lname) as approver3_name,
            p1.positionname as position1,
            p2.positionname as position2,
            p3.positionname as position3,
            e.signature,
            e1.signature as signature1,
            e2.signature as signature2,
            e3.signature as signature3
            FROM leaves
            LEFT JOIN employees e ON leaves.employeesid = e.id
            LEFT JOIN prefix pr ON e.prefix = pr.prefixid
            LEFT JOIN leavetype ON leaves.leavetype = leavetype.leavetypeid
            LEFT JOIN position ON e.position = position.positionid
            LEFT JOIN subdepart ON e.department = subdepart.subdepartid
            left join leaveday on leaves.employeesid = leaveday.empid
            left join headepart on subdepart.headepartid = headepart.headepartid
            LEFT JOIN employees e1 ON leaves.approver1 = e1.id
            LEFT JOIN employees e2 ON leaves.approver2 = e2.id
            LEFT JOIN employees e3 ON leaves.approver3 = e3.id
            LEFT JOIN position p1 ON e1.position = p1.positionid
            LEFT JOIN position p2 ON e2.position = p2.positionid
            LEFT JOIN position p3 ON e3.position = p3.positionid
            LEFT JOIN admin ON admin.id = e.id
            WHERE leaves.leavesid = ? and leaveday.empid = leaves.employeesid and leaves.leavetype = leaveday.leavetype";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $leaveId); // "i" หมายถึง integer
    $stmt->execute();
    $result = $stmt->get_result();
    $leave = $result->fetch_assoc();

    // Calculate working days
    $start = new DateTime($leave['leavestart']);
    $end = new DateTime($leave['leaveend']);
    $interval = new DateInterval('P1D');
    $daterange = new DatePeriod($start, $interval, $end->modify('+1 day'));

    $workdays = 0;
    // Get holidays from database
    $sql_holidays = "SELECT holidayday FROM holiday";
    $result_holidays = $conn->query($sql_holidays);
    $holidays = [];

    if ($result_holidays) {
        while ($holiday = $result_holidays->fetch_assoc()) {
            $holidays[] = $holiday['holidayday'];
        }
    }

    foreach ($daterange as $date) {
        // Check if it's a weekday (1-5) AND not a holiday
        if ($date->format('N') < 6 && !in_array($date->format('Y-m-d'), $holidays)) {
            $workdays++;
        }
    }
    $leave['leaveday'] = $workdays;

    // คำนวณวันลาที่ผ่านมาในรอบปีงบประมาณ แยกเป็น 2 ช่วง
    $currentYear = date('Y');
    $fiscalYear = (date('m') >= 10) ? $currentYear + 1 : $currentYear;
    $fiscalStart = ($fiscalYear - 1) . '-10-01';
    $fiscalMid = ($fiscalYear - 1) . '-03-31';  // สิ้นสุดช่วงที่ 1
    $fiscalMidStart = ($fiscalYear - 1) . '-04-01';  // เริ่มต้นช่วงที่ 2
    $fiscalEnd = $fiscalYear . '-09-30';

    // คำนวณวันลาช่วงที่ 1 (ต.ค. - มี.ค.)
    $sql_past_leaves_period1 = "SELECT l.leavestart, l.leaveend, lt.leavetypename 
                              FROM leaves l
                              JOIN leavetype lt ON l.leavetype = lt.leavetypeid
                              WHERE l.employeesid = ? 
                              AND l.leavetype = ? 
                              AND l.leavestart >= ? 
                              AND l.leavestart <= ?
                              AND l.leavestart < ?
                              AND l.leavesid != ?
                              ORDER BY l.leavestart ASC";

    $stmt_period1 = $conn->prepare($sql_past_leaves_period1);
    $stmt_period1->bind_param("iisssi", $leave['employeesid'], $leave['leavetype'], $fiscalStart, $fiscalMid, $leave['leavestart'], $leaveId);
    $stmt_period1->execute();
    $result_period1 = $stmt_period1->get_result();

    // คำนวณวันลาช่วงที่ 2 (เม.ย. - ก.ย.)
    $sql_past_leaves_period2 = "SELECT l.leavestart, l.leaveend, lt.leavetypename 
                              FROM leaves l
                              JOIN leavetype lt ON l.leavetype = lt.leavetypeid
                              WHERE l.employeesid = ? 
                              AND l.leavetype = ? 
                              AND l.leavestart >= ? 
                              AND l.leavestart <= ?
                              AND l.leavestart < ?
                              AND l.leavesid != ?
                              ORDER BY l.leavestart ASC";

    $stmt_period2 = $conn->prepare($sql_past_leaves_period2);
    $stmt_period2->bind_param("iisssi", $leave['employeesid'], $leave['leavetype'], $fiscalMidStart, $fiscalEnd, $leave['leavestart'], $leaveId);
    $stmt_period2->execute();
    $result_period2 = $stmt_period2->get_result();

    // คำนวณวันทำการจริงสำหรับช่วงที่ 1
    $total_workdays_period1 = 0;
    $leave_type_name = '';
    while ($past_leave = $result_period1->fetch_assoc()) {
        $leave_type_name = $past_leave['leavetypename'];
        $start = new DateTime($past_leave['leavestart']);
        $end = new DateTime($past_leave['leaveend']);
        $interval = new DateInterval('P1D');
        $daterange = new DatePeriod($start, $interval, $end->modify('+1 day'));

        foreach ($daterange as $date) {
            if ($date->format('N') < 6 && !in_array($date->format('Y-m-d'), $holidays)) {
                $total_workdays_period1++;
            }
        }
    }

    // คำนวณวันทำการจริงสำหรับช่วงที่ 2
    $total_workdays_period2 = 0;
    while ($past_leave = $result_period2->fetch_assoc()) {
        $leave_type_name = $past_leave['leavetypename'];
        $start = new DateTime($past_leave['leavestart']);
        $end = new DateTime($past_leave['leaveend']);
        $interval = new DateInterval('P1D');
        $daterange = new DatePeriod($start, $interval, $end->modify('+1 day'));

        foreach ($daterange as $date) {
            if ($date->format('N') < 6 && !in_array($date->format('Y-m-d'), $holidays)) {
                $total_workdays_period2++;
            }
        }
    }

    $stmt_period1->close();
    $stmt_period2->close();

    // ตรวจสอบว่าวันลาครั้งนี้อยู่ในช่วงใด
    $current_leave_start = new DateTime($leave['leavestart']);
    $current_period_workdays = 0;

    if ($current_leave_start >= new DateTime($fiscalStart) && $current_leave_start <= new DateTime($fiscalMid)) {
        // ถ้าอยู่ในช่วงที่ 1
        $current_period_workdays = $total_workdays_period1;
    } else {
        // ถ้าอยู่ในช่วงที่ 2
        $current_period_workdays = $total_workdays_period2;
    }

    // คำนวณรวมวันลาทั้งหมดของช่วงปัจจุบัน
    $total_leave_days = $current_period_workdays + $workdays;

    $stmt->close();
} else {
    // ถ้าไม่มี leavesid ส่งมา
    echo "<p class='text-danger'>กรุณาระบุรหัสใบลา</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบลา</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            line-height: 1.2;
            margin: 0 auto;
            padding: 10px;
            font-size: 14px;
        }

        .header-center {
            text-align: center;
            font-size: 14px;
        }

        .header-center p {
            margin: 3px 0;
        }

        .date-right {
            text-align: right;
        }

        .content {
            width: 100%;
        }

        .dotted-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            width: auto;
            flex-grow: 1;
        }

        .content p {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }

        .content p>span.dotted-line:only-child {
            flex-grow: 1;
        }

        .flex-container {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .table-section {
            width: 45%;
            text-align: center;
        }

        .table-section p {
            margin: 5px 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .table-section .dotted-line {
            text-align: center;
            margin: 5px 5px;
        }

        .table-section img {
            display: block;
            margin: 0 auto;
        }

        .signature-section {
            width: 50%;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 12px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="header-center">
        <p>แบบใบ<?php echo $leave['leavetypename']; ?></p>
    </div>
    <div class="date-right">
        <p>มหาวิทยาลัยเทคโนโลยีราชมงคลอีสาน</p>
        <p>วันที่ <span class="dotted-line"><?php echo date('d', strtotime($leave['send_date'])) . '/' . $thai_months[date('n', strtotime($leave['send_date']))] . '/' . (date('Y', strtotime($leave['send_date'])) + 543); ?></span></p>
    </div>
    <div class="content">
        <p>เรื่อง ขอ<?php echo $leave['leavetypename']; ?></p>
        <p>เรียน ผู้อำนวยการสำนักส่งเสริมการและงานทะเบียน</p>
        <p style="text-align:center;">ข้าพเจ้า <span class="dotted-line"><?php echo $leave['fullname']; ?></span> ตำแหน่ง <span class="dotted-line"><?php echo $leave['positionname']; ?></span></p>
        <p style="text-align:center;">หน่วยงาน <span class="dotted-line"><?php echo $leave['headepartname']; ?> </span> งาน <span class="dotted-line"><?php echo $leave['subdepartname']; ?> </span></p>
        <p style="text-align:center;">
            <?php if ($leave['leavetypename'] == 'ลาพักผ่อน'): ?>
                <?php 
                    // คำนวณวันลาประจำปีก่อน แล้วค่อยลบจากวันสะสม
                    $remaining_annual = $leave['day'];
                    $remaining_stack = $leave['stackleaveday'];
                    
                    // ลบจากวันลาประจำปีก่อน
                    $remaining_annual = $remaining_annual - $total_leave_days;
                    
                    // ถ้าวันลาประจำปีติดลบ ให้ไปลดจากวันลาสะสม
                    if ($remaining_annual < 0) {
                        $remaining_stack += $remaining_annual; // บวกค่าติดลบเข้าไป (เท่ากับการลบ)
                        $remaining_annual = 0;
                    }
                    
                    // ป้องกันค่าติดลบ
                    $remaining_stack = max(0, $remaining_stack);
                ?>
                มีวันลาสะสมคงเหลือ <span class="dotted-line"><?php echo ($remaining_stack == 0) ? '-' : $remaining_stack; ?></span> วันทำการ
                มีสิทธิลาประจำปีนี้ <span class="dotted-line"><?php echo ($remaining_annual == 0) ? '-' : $remaining_annual; ?></span> วันทำการ
            <?php elseif ($leave['leavetypename'] == 'ลาป่วย'): ?>
                <?php
                    // ดึงข้อมูลวันลาป่วยครั้งล่าสุด
                    $sql_last_sick_leave = "SELECT leavestart, leaveend 
                                          FROM leaves 
                                          WHERE employeesid = ? 
                                          AND leavetype = ? 
                                          AND leavestart < ?
                                          AND leavestatus = 'อนุมัติ'
                                          ORDER BY leavestart DESC 
                                          LIMIT 1";
                    $stmt_last_sick = $conn->prepare($sql_last_sick_leave);
                    $stmt_last_sick->bind_param("iis", $leave['employeesid'], $leave['leavetype'], $leave['leavestart']);
                    $stmt_last_sick->execute();
                    $result_last_sick = $stmt_last_sick->get_result();
                    $last_sick = $result_last_sick->fetch_assoc();
                ?>
                ขอลาป่วย เนื่องจาก <span class="dotted-line"><?php echo $leave['reason']; ?></span>
            <?php endif; ?>
        </p>
        
        <?php if ($leave['leavetypename'] == 'ลาป่วย' && $last_sick): ?>
            <?php
                // คำนวณวันทำการของการลาครั้งสุดท้าย
                $start_last = new DateTime($last_sick['leavestart']);
                $end_last = new DateTime($last_sick['leaveend']);
                $interval_last = new DateInterval('P1D');
                $daterange_last = new DatePeriod($start_last, $interval_last, $end_last->modify('+1 day'));
                
                $workdays_last = 0;
                foreach ($daterange_last as $date) {
                    if ($date->format('N') < 6 && !in_array($date->format('Y-m-d'), $holidays)) {
                        $workdays_last++;
                    }
                }
            ?>
            <p style="text-align:center;">
                ข้าพเจ้า ได้ลาป่วยครั้งสุดท้ายวันที่ <span class="dotted-line">
                <?php echo date('d', strtotime($last_sick['leavestart'])) . ' ' . 
                          $thai_months[date('n', strtotime($last_sick['leavestart']))] . ' ' . 
                          (date('Y', strtotime($last_sick['leavestart'])) + 543); ?>
                </span> 
                ถึงวันที่ <span class="dotted-line">
                <?php echo date('d', strtotime($last_sick['leaveend'])) . ' ' . 
                          $thai_months[date('n', strtotime($last_sick['leaveend']))] . ' ' . 
                          (date('Y', strtotime($last_sick['leaveend'])) + 543); ?>
                </span>
                มีกำหนด <span class="dotted-line"><?php echo $workdays_last; ?></span> วันทำการ
            </p>
        <?php endif; ?>
        
     
        
        <p style="text-align:center;">ขอลาตั้งแต่วันที่ <span class="dotted-line"><?php echo date('d', strtotime($leave['leavestart'])) . ' ' . $thai_months[date('n', strtotime($leave['leavestart']))] . ' ' . (date('Y', strtotime($leave['leavestart'])) + 543); ?></span> ถึงวันที่ <span class="dotted-line"><?php echo date('d', strtotime($leave['leaveend'])) . ' ' . $thai_months[date('n', strtotime($leave['leaveend']))] . ' ' . (date('Y', strtotime($leave['leaveend'])) + 543); ?></span> มีกำหนด <span class="dotted-line"><?php echo $leave['leaveday']; ?> </span>วันทำการ</p>
        <p style="text-align:center;">ในระหว่างลาจะติดต่อข้าพเจ้าได้ที่ <span class="dotted-line"><?php echo $leave['address']; ?></span></p>
    </div>

    <div class="flex-container">
        <!-- ตารางสถิติการลา -->
        <div class="table-section">
            <table>
                <thead>
                    <tr>
                        <th colspan="4">สถิติการลา<?php echo $leave['leavetypename']; ?>ในปีงบประมาณนี้ (<?php echo $current_leave_start <= new DateTime($fiscalMid) ? 'ช่วงที่ 1' : 'ช่วงที่ 2'; ?>)</th>
                    </tr>
                    <tr>
                        <th>ลามาแล้ว<br>(วันทำการ)</th>
                        <th>ลาครั้งนี้<br>(วันทำการ)</th>
                        <th>รวมเป็น<br>(วันทำการ)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $current_period_workdays; ?></td>
                        <td><?php echo $leave['leaveday']; ?></td>
                        <td><?php echo $total_leave_days; ?></td>
                    </tr>
                </tbody>
            </table><br>
            <p>ผู้ตรวจสอบ</p>
            <p>ลงชื่อ <span class="dotted-line"><?php echo !empty($admin['admin_signature']) ? '<img src="../admin/uploads/' . $admin['admin_signature'] . '" alt="" width="100px" height="50px">' : ''; ?></span></p>
            <p>( <span class="dotted-line"><?php echo $admin['admin_name']; ?></span> )</p>
            <p>ตำแหน่ง <span class="dotted-line"><?php echo $admin['admin_position']; ?></span></p>
        </div>

        <!-- ส่วนลงชื่อ -->
        <div class="signature-section">
            <div class="signature-box">
                <p>ขอแสดงความนับถือ</p>
                <p>ลงชื่อ <span class="dotted-line"><img src="../admin/uploads/<?php echo $leave['signature']; ?>" alt="" width="100px" height="40px"></span></p>
                <p>(<span class="dotted-line"><?php echo $leave['fullname']; ?></span>)</p> <br>  
            </div>
            <div class="approval-section">
                <p>ความเห็นผู้บังคับบัญชา</p>
                <p style="text-align: center;"><span class="dotted-line"><?php echo $leave['leavestatus']; ?></span></p>
                <?php if (!empty($leave['approver1_name'])): ?>
                    <p>ลงชื่อ <span class="dotted-line"><?php echo !empty($leave['signature1']) ? '<img src="../admin/uploads/' . $leave['signature1'] . '" alt="" width="100px" height="40px">' : ''; ?></span></p>
                    <p>( <span class="dotted-line"><?php echo $leave['approver1_name']; ?></span> )</p>
                    <p>ตำแหน่ง <span class="dotted-line"><?php echo $leave['position1']; ?></span></p>
                    <p>วันที่ <span class="dotted-line"><?php echo !empty($leave['approved_date1']) ? date('d', strtotime($leave['approved_date1'])) . '/' . $thai_months[date('n', strtotime($leave['approved_date1']))] . '/' . (date('Y', strtotime($leave['approved_date1'])) + 543) : '-'; ?></span></p>
                <?php endif; ?>

                <?php if (!empty($leave['approver2_name'])): ?>
                    <p>ลงชื่อ <span class="dotted-line"><?php echo !empty($leave['signature2']) ? '<img src="../admin/uploads/' . $leave['signature2'] . '" alt="" width="100px" height="40px">' : ''; ?></span></p>
                    <p>( <span class="dotted-line"><?php echo $leave['approver2_name']; ?></span> )</p>
                    <p>ตำแหน่ง <span class="dotted-line"><?php echo $leave['position2']; ?></span></p>
                    <p>วันที่ <span class="dotted-line"><?php echo !empty($leave['approved_date2']) ? date('d', strtotime($leave['approved_date2'])) . '/' . $thai_months[date('n', strtotime($leave['approved_date2']))] . '/' . (date('Y', strtotime($leave['approved_date2'])) + 543) : '-'; ?></span></p>
                <?php endif; ?>

                <p>คำสั่ง
                    <span style="margin-left: 10px;">
                        <label style="margin-right: 10px;"><?php echo ($leave['leavestatus'] == 'อนุมัติ') ? '✓' : ''; ?> อนุญาต</label>
                        <label><?php echo ($leave['leavestatus'] == 'ไม่อนุมัติ') ? '✓' : ''; ?> ไม่อนุญาต</label>
                    </span>
                </p>

                <?php if (!empty($leave['approver3_name'])): ?>
                    <p>ลงชื่อ <span class="dotted-line"><?php echo !empty($leave['signature3']) ? '<img src="../admin/uploads/' . $leave['signature3'] . '" alt="" width="100px" height="40px">' : ''; ?></span></p>
                    <p>( <span class="dotted-line"><?php echo $leave['approver3_name']; ?></span> )</p>
                    <?php 
                        $sql_role = "SELECT r.level 
                        FROM leaves l
                        LEFT JOIN employees e3 ON l.approver3 = e3.id
                        LEFT JOIN role r ON e3.position = r.roleid
                        WHERE l.leavesid = " . $_GET['leavesid'];
                        $result_role = $conn->query($sql_role);
                        $role = $result_role->fetch_assoc();
                        
                        if ($role['level'] != 4): 
                    ?>
                        <span class="dotted-line">รักษาการแทนผู้อำนวยการ</span>
                    <?php endif; ?>
                    <p>ำแหน่ง <span class="dotted-line"><?php echo $leave['position3']; ?></span></p>
                    <p>วันที่ <span class="dotted-line"><?php echo !empty($leave['approved_date3']) ? date('d', strtotime($leave['approved_date3'])) . '/' . $thai_months[date('n', strtotime($leave['approved_date3']))] . '/' . (date('Y', strtotime($leave['approved_date3'])) + 543) : '-'; ?></span></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>

<?php $conn->close(); ?>