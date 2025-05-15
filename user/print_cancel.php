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

    // คำนวณวันลาที่ผ่านมาในรอบปีงบประมาณ
    $currentYear = date('Y');
    $fiscalYear = (date('m') >= 10) ? $currentYear + 1 : $currentYear;
    $fiscalStart = ($fiscalYear - 1) . '-10-01';
    $fiscalEnd = $fiscalYear . '-09-30';

    $sql_past_leaves = "SELECT SUM(DATEDIFF(leaveend, leavestart) + 1) as total_days 
                       FROM leaves 
                       WHERE employeesid = ? 
                       AND leavetype = ? 
                       AND leavestart >= ? 
                       AND leavestart <= ?
                       AND leavesid != ?";

    $stmt_past = $conn->prepare($sql_past_leaves);
    $stmt_past->bind_param("iissi", $leave['employeesid'], $leave['leavetype'], $fiscalStart, $fiscalEnd, $leaveId);
    $stmt_past->execute();
    $result_past = $stmt_past->get_result();
    $past_leaves = $result_past->fetch_assoc();

    // คำนวณวันทำการจริงสำหรับวันลาที่ผ่านมา
    $total_past_workdays = 0;
    if ($past_leaves['total_days'] > 0) {
        $sql_past_details = "SELECT leavestart, leaveend 
                           FROM leaves 
                           WHERE employeesid = ? 
                           AND leavetype = ? 
                           AND leavestart >= ? 
                           AND leavestart <= ?
                           AND leavesid != ?";

        $stmt_details = $conn->prepare($sql_past_details);
        $stmt_details->bind_param("iissi", $leave['employeesid'], $leave['leavetype'], $fiscalStart, $fiscalEnd, $leaveId);
        $stmt_details->execute();
        $result_details = $stmt_details->get_result();

        while ($past_leave = $result_details->fetch_assoc()) {
            $start = new DateTime($past_leave['leavestart']);
            $end = new DateTime($past_leave['leaveend']);
            $interval = new DateInterval('P1D');
            $daterange = new DatePeriod($start, $interval, $end->modify('+1 day'));

            foreach ($daterange as $date) {
                if ($date->format('N') < 6 && !in_array($date->format('Y-m-d'), $holidays)) {
                    $total_past_workdays++;
                }
            }
        }

        $stmt_details->close();
    }
    $stmt_past->close();

    // คำนวณรวมวันลาทั้งหมด
    $total_leave_days = $total_past_workdays + $workdays;

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
            margin: 15px 0;
        }

        .content p>span.dotted-line:only-child {
            flex-grow: 1;
        }

        .flex-container {
            display: flex;
            justify-content: end;
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
            align-items: end;
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
        <p>แบบใบขอยกเลิกวันลา</p>
    </div>
    <div class="date-right">
        <p>มหาวิทยาลัยเทคโนโลยีราชมงคลอีสาน</p>
        <p>วันที่ <span class="dotted-line"><?php echo date('d', strtotime($leave['send_cancel'])) . '/' . $thai_months[date('n', strtotime($leave['send_cancel']))] . '/' . (date('Y', strtotime($leave['send_date'])) + 543); ?></span></p>
    </div>
    <div class="content">
        <p>เรื่อง ขอยกเลิกวันลา</p>
        <p>เรียน ผู้อำนวยการสำนักส่งเสริมการและงานทะเบียน</p> <br>
        <p style="text-align:center;">ข้าพเจ้า <span class="dotted-line"><?php echo $leave['fullname']; ?></span> ตำแหน่ง <span class="dotted-line"><?php echo $leave['positionname']; ?></span></p>
        <p style="text-align:center;">หน่วยงาน <span class="dotted-line"><?php echo $leave['headepartname']; ?> </span> งาน <span class="dotted-line"><?php echo $leave['subdepartname']; ?> </span></p>
     
        <p style="text-align:center;">ได้รับอนุญาตให้ลาตั้งแต่วันที่ <span class="dotted-line"><?php echo date('d', strtotime($leave['leavestart'])) . ' ' . $thai_months[date('n', strtotime($leave['leavestart']))] . ' ' . (date('Y', strtotime($leave['leavestart'])) + 543); ?></span> ถึงวันที่ <span class="dotted-line"><?php echo date('d', strtotime($leave['leaveend'])) . ' ' . $thai_months[date('n', strtotime($leave['leaveend']))] . ' ' . (date('Y', strtotime($leave['leaveend'])) + 543); ?></span> รวม <span class="dotted-line"><?php echo $leave['leaveday']; ?> </span>วันทำการ</p>
        <p style="text-align:center;">จึงขอยกเลิกวันลา  <span class="dotted-line"> <?php echo $leave['leavetypename']; ?></span> จำนวน <span class="dotted-line"> <?php echo $leave['leaveday']; ?></span></p>
        <p style="text-align:center;">ตั้งแต่วันที่ <span class="dotted-line"><?php echo date('d', strtotime($leave['leavestart'])) . ' ' . $thai_months[date('n', strtotime($leave['leavestart']))] . ' ' . (date('Y', strtotime($leave['leavestart'])) + 543); ?></span> ถึงวันที่ <span class="dotted-line"><?php echo date('d', strtotime($leave['leaveend'])) . ' ' . $thai_months[date('n', strtotime($leave['leaveend']))] . ' ' . (date('Y', strtotime($leave['leaveend'])) + 543); ?></span></p>

    </div>

    <div class="flex-container">
        <!-- ตารางสถิติการลา -->
     

        <!-- ส่วนลงชื่อ -->
        <div class="signature-section">
            <div class="signature-box">
                <br>
                <p>ขอแสดงความนับถือ</p>
                <p>ลงชื่อ <span class="dotted-line"><img src="../admin/uploads/<?php echo $leave['signature']; ?>" alt="" width="100px" height="40px"></span></p>
                <p>(<span class="dotted-line"><?php echo $leave['fullname']; ?></span>)</p> <br>  
            </div>
            <div class="approval-section">
                <p>ความเห็นผู้บังคับบัญชา</p>
                <p style="text-align: center;"><span class="dotted-line"><?php echo $leave['leavestatus']; ?></span></p>
                <p>ลงชื่อ <span class="dotted-line"><?php echo !empty($leave['signature1']) ? '<img src="../admin/uploads/' . $leave['signature1'] . '" alt="" width="100px" height="40px">' : ''; ?></span></p>
                <p>( <span class="dotted-line"><?php echo $leave['approver1_name']; ?></span> )</p>
                <p>ตำแหน่ง <span class="dotted-line"><?php echo $leave['position1']; ?></span></p>
                <p>วันที่ <span class="dotted-line"><?php echo date('d', strtotime($leave['approved_cancel1'])) . '/' . $thai_months[date('n', strtotime($leave['approved_cancel1']))] . '/' . (date('Y', strtotime($leave['approved_cancel1'])) + 543); ?></span></p>
                <br>
                <p>คำสั่ง
                    <span style="margin-left: 10px;">
                        <label style="margin-right: 10px;"><?php echo ($leave['leavestatus'] == 'อนุมัติ') ? '✓' : ''; ?> อนุญาต</label>
                        <label><?php echo ($leave['leavestatus'] == 'ไม่อนุมัติ') ? '✓' : ''; ?> ไม่อนุญาต</label>
                    </span>
                </p>
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
                <p>ตำแหน่ง <span class="dotted-line"><?php echo $leave['position3']; ?></span></p>
                <p>วันที่ <span class="dotted-line"><?php echo date('d', strtotime($leave['approved_cancel3'])) . '/' . $thai_months[date('n', strtotime($leave['approved_cancel3']))] . '/' . (date('Y', strtotime($leave['approved_cancel3'])) + 543); ?></span></p>
            </div>
        </div>
    </div>
</body>

</html>

<?php $conn->close(); ?>