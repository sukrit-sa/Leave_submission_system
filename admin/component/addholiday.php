<div class="modal fade" id="addHolidayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มวันหยุด</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addHolidayForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">วันที่</label>
                        <input type="date" class="form-control" name="holidayday" id="holiday_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รายละเอียด</label>
                        <input type="text" class="form-control" name="holidayname" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-outline-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#addHolidayForm').submit(function(e) {
            e.preventDefault();
            var formData = {
                date: $('input[name="holidayday"]').val(),
                description: $('input[name="holidayname"]').val()
            };
            
            $.ajax({
                url: 'add/addholiday.php',
                type: 'POST',
                data: formData,
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
                        console.log('Error response:', response);
                        Swal.fire({
                            icon: 'error',
                            title: 'ผิดพลาด!',
                            text: response.message || 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    Swal.fire({
                        icon: 'error',
                        title: 'ผิดพลาด!',
                        text: 'ไม่สามารถติดต่อกับเซิร์ฟเวอร์ได้ กรุณาลองใหม่อีกครั้ง'
                    });
                }
            });
        });
    });
</script>