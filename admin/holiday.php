<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Add jQuery first -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <title>ปฏิทินวันหยุด</title>
    <style>
        .fc-day-today {
            background-color: rgb(249, 144, 64) !important;
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
</head>

<body>
    <?php include('component/sidebar.php'); ?>
    <?php include('component/addholiday.php'); ?>

    <main class="main container3" id="main">
        <!-- <div class="d-flex justify-content-between align-items-center mb-3">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
                <i class="ri-add-line"></i> เพิ่มวันหยุด
            </button>
        </div> -->
        <div style="max-width: 800px; margin: 0 auto;" class="mt-5">
            <div id="calendar"></div>
        </div>
        <?php include('component/footer.php'); ?>
    </main>

    <!-- Remove jQuery from here since it's now in head -->
    <script src="assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Wrap deleteHoliday function in document ready -->
    <script>
        $(document).ready(function() {
            window.deleteHoliday = function(id) {
                $.ajax({
                    url: 'delete/deleteholiday.php',
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
                    }
                });
            };
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'th',
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek'
                },
                buttonText: {
                    today: 'วันนี้',
                    month: 'เดือน',
                    week: 'สัปดาห์'
                },
                events: 'add/getholidays.php',
                eventClassNames: 'holiday-event',
                selectable: true,
                select: function(info) {
                    var today = new Date();
                    var selected = new Date(info.start);

                    // if (selected < today) {
                    //     Swal.fire({
                    //         icon: 'error',
                    //         title: 'ไม่สามารถเพิ่มวันหยุดย้อนหลังได้',
                    //         text: 'กรุณาเลือกวันในอนาคต'
                    //     });
                    //     return;
                    // }

                    $('#addHolidayModal').modal('show');
                    $('#holiday_date').val(info.startStr);
                },
                eventClick: function(info) {
                    Swal.fire({
                        title: 'รายละเอียดวันหยุด',
                        html: `วันที่: ${info.event.start.toLocaleDateString('th-TH')}<br>
                              รายละเอียด: ${info.event.title}`,
                        showCancelButton: true,
                        confirmButtonText: 'ลบ',
                        cancelButtonText: 'ปิด',
                        confirmButtonColor: '#d33'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            deleteHoliday(info.event.id);
                        }
                    });
                }
            });
            calendar.render();
        });
    </script>
</body>

</html>