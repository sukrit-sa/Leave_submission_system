<div class="modal fade" id="addLeaveTypeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มประเภทการลา</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addLeaveTypeForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ชื่อประเภทการลา</label>
                            <input type="text" class="form-control" name="leavetypename" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">สถานะงาน</label>
                            <select class="form-select" name="staffid" required>
                                <?php
                                include('conn/conn.php');
                                $sql = "SELECT * FROM staffstatus";
                                $result = mysqli_query($conn, $sql);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<option value='" . $row['staffid'] . "'>" . $row['staffname'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                          <div class="col-md-6 mb-3">
                            <label class="form-label">เพศ</label>
                            <select class="form-select" name="gender" id="gender" required>
                                <option value="ทั้งหมด">ทั้งหมด</option>
                                <option value="ชาย">ชาย</option>
                                <option value="หญิง">หญิง</option>
                              
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">เงื่อนไขอายุงาน</label>
                            <select class="form-select" name="workage" id="workage" required>
                                <option value="1">มากกว่า</option>
                                <option value="2">น้อยกว่า</option>
                                <option value="3">ไม่มีเงื่อนไข</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3" id="workageInput">
                            <label class="form-label">จำนวนเดือน</label>
                            <input type="number" class="form-control" name="workageday" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">สิทธิ์วันลาต่อปี</label>
                            <input type="number" class="form-control" name="leaveofyear" required min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">สิทธิ์วันลาสะสม</label>
                            <input type="number" class="form-control" name="stackleaveday" min="0">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">สิ่งที่ต้องแนบ</label>
                            <input type="text" class="form-control" name="nameform" placeholder="ไม่มีไม่ต้องกรอก">
                        </div>
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
        $('#workage').change(function() {
            if ($(this).val() == '3') {
                $('#workageInput').hide();
                $('input[name="workageday"]').prop('required', false);
            } else {
                $('#workageInput').show();
                $('input[name="workageday"]').prop('required', true);
            }
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('#addLeaveTypeForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'add/addleavetype.php',
                type: 'POST',
                data: $(this).serialize(),
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
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'ผิดพลาด!',
                        text: 'ไม่สามารถเพิ่มข้อมูลได้'
                    });
                }
            });
        });
    });
</script>