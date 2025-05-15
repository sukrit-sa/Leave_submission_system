<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <!-- เพิ่ม Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- เพิ่ม Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <title>ปฏิทินการลา</title>
</head>

<body>
    <?php include('component/sidebar.php'); ?>
    
    <main class="main container3" id="main">
        <div class="container-fluid">
            <div class="row mt-3">
                <!-- ปฏิทิน -->
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">ปฏิทินการลา</h5>
                        </div>
                        <div class="card-body">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
                <!-- รายชื่อผู้ลา -->
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">รายชื่อผู้ลาวันนี้</h5>
                        </div>
                        <div class="card-body" id="today-leaves">
                            <!-- รายชื่อจะถูกเพิ่มด้วย JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include('component/footer.php'); ?>
    </main>

    <!-- เพิ่ม JavaScript สำหรับดึงและแสดงรายชื่อ -->
    <script>
        function loadTodayLeaves(date = null) {
            $.ajax({
                url: 'process/get_today_leaves.php',
                type: 'GET',
                data: { date: date }, // ส่งวันที่ไปยัง PHP
                success: function(response) {
                    let html = '';
                    let headerText = date ? `รายชื่อผู้ลาวันที่ ${date}` : 'รายชื่อผู้ลาวันนี้';
                    
                    // อัพเดทหัวข้อ
                    $('.card-title.mb-0').text(headerText);
                    
                    if (response.length > 0) {
                        response.forEach(function(leave) {
                            html += `<div class="mb-2">
                                <strong>${leave.fullname}</strong> - ${leave.leavetype}
                            </div>`;
                        });
                    } else {
                        html = '<p class="text-muted">ไม่มีผู้ลาในวันที่เลือก</p>';
                    }
                    $('#today-leaves').html(html);
                },
                error: function() {
                    $('#today-leaves').html('<p class="text-danger">ไม่สามารถโหลดข้อมูลได้</p>');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'th',
                height: 'auto',
                contentHeight: 600,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth'
                },
                buttonText: {
                    today: 'วันนี้'
                },
                dateClick: function(info) {
                    $.ajax({
                        url: 'process/get_leaves_by_date.php',
                        type: 'GET',
                        dataType: 'json', // Explicitly specify JSON
                        data: {
                            date: info.dateStr
                        },
                        success: function(response) {
                            let html = '<h6 class="mb-3">รายชื่อผู้ลาวันที่ ' + info.dateStr + '</h6>';
                            if (response && response.length > 0) {
                                response.forEach(function(leave) {
                                    html += `<div class="mb-2 p-2 border-bottom">
                                        <strong>${leave.fullname}</strong><br>
                                        <span class="text-muted">ประเภท: ${leave.leavetype}</span>
                                    </div>`;
                                });
                            } else {
                                html = '<p class="text-muted">ไม่มีผู้ลาในวันที่เลือก</p>';
                            }
                            $('#leaveModalBody').html(html);
                            const modalElement = document.getElementById('leaveModal');
                            const modal = new bootstrap.Modal(modalElement);
                            modal.show();
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', error);
                            console.error('Response:', xhr.responseText);
                            $('#leaveModalBody').html('<p class="text-danger">ไม่สามารถโหลดข้อมูลได้</p>');
                            const modalElement = document.getElementById('leaveModal');
                            const modal = new bootstrap.Modal(modalElement);
                            modal.show();
                        }
                    });
                },
                events: function(fetchInfo, successCallback, failureCallback) {
                    $.ajax({
                        url: 'process/get_leave_events.php',
                        type: 'GET',
                        success: function(response) {
                            successCallback(response);
                        },
                        error: function() {
                            failureCallback();
                        }
                    });
                },
                eventDidMount: function(info) {
                    info.el.title = 'จำนวนผู้ลา: ' + info.event.extendedProps.count + ' คน';
                }
            });
            calendar.render();
            loadTodayLeaves(); // โหลดข้อมูลวันนี้เมื่อเริ่มต้น
        });
    </script>

    <style>
        .fc-day-today {
            background-color: rgb(255, 139, 45) !important;
        }

        .fc-event {
            cursor: pointer;
        }

        .holiday-event {
            background-color: #ff4d4d;
            border: none;
        }

        /* Add these styles for a more compact calendar */
        .card-body {
            max-width: 800px;
            margin: 0 auto;
        }

        .fc {
            font-size: 0.9em;
        }

        .fc .fc-toolbar.fc-header-toolbar {
            margin-bottom: 0.5em;
        }

        .fc .fc-button {
            padding: 0.2em 0.4em;
        }

        .fc .fc-daygrid-day {
            min-height: 50px;
        }

        .fc .fc-daygrid-day-frame {
            min-height: 50px;
        }
    </style>

    <!-- ลบส่วนนี้ออกทั้งหมด เพราซ้ำซ้อนกับด้านบน -->
    <!-- <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'th',
                height: 'auto',
                contentHeight: 600,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth'
                },
                buttonText: {
                    today: 'วันนี้'
                },
                events: function(fetchInfo, successCallback, failureCallback) {
                    $.ajax({
                        url: 'process/get_leave_events.php',
                        type: 'GET',
                        data: {
                            start: fetchInfo.startStr,
                            end: fetchInfo.endStr
                        },
                        success: function(response) {
                            // แปลงข้อมูลและเพิ่ม class สำหรับวันหยุด
                            const events = response.map(event => ({
                                ...event,
                                title: `ผู้ลา ${event.count} คน`,
                                display: 'block',
                                className: event.isHoliday ? 'holiday-event text-center' : 'text-center'
                            }));
                            successCallback(events);
                        },
                        error: function() {
                            failureCallback();
                        }
                    });
                },
                eventDidMount: function(info) {
                    info.el.title = 'จำนวนผู้ลา: ' + info.event.extendedProps.count + ' คน';
                }
            });
            calendar.render();
        });
    </script> -->
    

    <script src="assets/js/main.js"></script>
    <!-- ลบบรรทัดนี้ออก -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script> -->
    <!-- Modal -->
    <div class="modal fade" id="leaveModal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="leaveModalLabel">รายชื่อผู้ลา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="leaveModalBody">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>