<?php
include('../conn/conn.php');

// ดึงข้อมูลการลาทั้งหมดที่ได้รับอนุมัติ
$sql = "SELECT DATE(leavestart) as start_date, 
        DATE(leaveend) as end_date,
        employeesid
        FROM leaves 
        WHERE leavestatus = 'อนุมัติ'";

$result = $conn->query($sql);

// อาร์เรย์สำหรับเก็บจำนวนคนลาต่อวัน
$leave_counts = array();

while ($row = $result->fetch_assoc()) {
    $start = new DateTime($row['start_date']);
    $end = new DateTime($row['end_date']);
    
    // ลูปผ่านทุกวันในช่วงวันที่ลา
    $interval = DateInterval::createFromDateString('1 day');
    $period = new DatePeriod($start, $interval, $end->modify('+1 day')); // รวมวันสุดท้าย
    
    foreach ($period as $date) {
        // ข้ามวันเสาร์ (6) และอาทิตย์ (7)
        if ($date->format('N') >= 6) {
            continue;
        }
        
        $dateStr = $date->format('Y-m-d');
        // เพิ่มจำนวนคนลาในวันที่นี้
        if (!isset($leave_counts[$dateStr])) {
            $leave_counts[$dateStr] = 0;
        }
        $leave_counts[$dateStr]++;
    }
}

// สร้างอาร์เรย์สำหรับเหตุการณ์ในปฏิทิน
$events = array();
foreach ($leave_counts as $date => $count) {
    $events[] = array(
        'title' => 'ลา ' . $count . ' คน',
        'start' => $date,
        'end' => $date, // เหตุการณ์สำหรับวันเดียว
        'backgroundColor' => 'green',
        'className' => 'text-center'
    );
}

// ส่งออกผลลัพธ์เป็น JSON
header('Content-Type: application/json');
echo json_encode($events);
?>