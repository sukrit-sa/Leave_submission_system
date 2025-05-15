<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer Design</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome สำหรับไอคอน -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* สไตล์สำหรับ Footer */
        footer {
            background-color:rgb(255, 255, 255); /* สีพื้นหลังขาว */
            color:rgb(0, 0, 0); /* สีข้อความเทาเข้ม */
            padding: 2rem 0;
            font-size: 0.9rem;
            border-radius: 15px; /* ขอบโค้งสำหรับสไตล์ Card */
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1); /* เงาบางสำหรับพื้นหลังขาว */
            margin: 2rem auto; /* ระยะห่างจากขอบหน้า */
            max-width: 100%; /* จำกัดความกว้างให้ดูเป็น Card */
        }

        footer h5 {
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #000000; /* สีดำสำหรับหัวข้อ */
        }

        footer a {
            color: #333333; /* สีข้อความลิงก์ */
            text-decoration: none;
            transition: color 0.3s ease;
        }

        footer a:hover {
            color: #F28C38; /* สีส้มเมื่อ hover */
        }

        footer .contact-info i {
            margin-right: 0.5rem;
            color: #F28C38; /* สีไอคอนส้ม */
        }

        footer .btn-contact {
            background-color: transparent;
            border-color: #F28C38; /* ขอบปุ่มสีส้ม */
            color: #F28C38; /* ตัวอักษรสีส้ม */
            transition: all 0.3s ease;
        }

        footer .btn-contact:hover {
            background-color: #F28C38; /* พื้นหลังสีส้มเมื่อ hover */
            color: #FFFFFF; /* ตัวอักษรขาว */
        }

        footer .border-top {
            border-color: rgba(0, 0, 0, 0.1) !important; /* เส้นขอบจางสำหรับพื้นหลังขาว */
        }

        /* Responsive Adjustments */
        @media (max-width: 576px) {
            footer {
                text-align: center;
                padding: 1.5rem 0;
                margin: 1rem auto;
            }

            footer .col-sm-12 {
                margin-bottom: 1.5rem;
            }

            footer .btn-contact {
                font-size: 0.85rem;
                padding: 0.4rem 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <!-- ส่วนติดต่อเมื่อมีปัญหา -->
                <div class="col-md-12 col-sm-12 mb-4">
                    <h5>เมื่อมีปัญหา โปรดติดต่อ</h5>
                    <ul class="list-unstyled contact-info">
                        <li><i class="fas fa-envelope"></i> <a href="mailto:sukrit.sa@rmuti.ac.th">sukrit.sa@rmuti.ac.th</a> หรือ <a href="mailto:siwakorn.po@rmuti.ac.th">siwakorn.po@rmuti.ac.th</a></li>
                        <li><i class="fas fa-clock"></i> จันทร์-ศุกร์: 10:00 - 17:00</li>
                    </ul>
                    <!-- <a href="/contact-form" class="btn btn-contact btn-sm mt-3">ส่งคำถาม</a> -->
                </div>
            </div>

            <!-- ส่วนล่างสุด -->
            <div class="row border-top pt-3 mt-4">
                <div class="col-12 text-center">
                    <p class="mb-0">© <?= date('Y') ?> สำนักส่งเสริมวิชาการและงานทะเบียน. สงวนลิขสิทธิ์.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS และ Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>