<div class="modal fade" id="editLeaveTypeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขประเภทการลา</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editLeaveTypeForm">
                <input type="hidden" name="leavetypeid" id="edit_leavetypeid">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ชื่อประเภทการลา</label>
                            <input type="text" class="form-control" name="leavetypename" id="edit_leavetypename" required>
                        </div>
                           <div class="col-md-6 mb-3">
                            <label class="form-label">เพศ</label>
                            <select class="form-select" name="gender" id="edit_gender" required>
                                <option value="ทั้งหมด">ทั้งหมด</option>
                                <option value="ชาย">ชาย</option>
                                <option value="หญิง">หญิง</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">สถานะงาน</label>
                            <select class="form-select" name="staffid" id="edit_staffid" required>
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
                            <label class="form-label">เงื่อนไขอายุงาน</label>
                            <select class="form-select" name="workage" id="edit_workage" required>
                                <option value="1">มากกว่า</option>
                                <option value="2">น้อยกว่า</option>
                                <option value="3">ไม่มีเงื่อนไข</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3" id="edit_workageInput">
                            <label class="form-label">จำนวนเดือน</label>
                            <input type="number" class="form-control" name="workageday" id="edit_workageday" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">สิทธิ์วันลาต่อปี</label>
                            <input type="number" class="form-control" name="leaveofyear" id="edit_leaveofyear" required min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">สิทธิ์วันลาสะสม</label>
                            <input type="number" class="form-control" name="stackleaveday" id="edit_stackleaveday" min="0">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">สิ่งที่ต้องแนบ</label>
                            <input type="text" class="form-control" name="nameform" id="edit_nameform" placeholder="ไม่มีไม่ต้องกรอก">
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
        $('.edit-btn').click(function() {
            var id = $(this).data('leavetypeid');

            // Fetch leave type data
            $.ajax({
                url: 'update/fetchleavetype.php',
                type: 'POST',
                data: {
                    id: id
                },
                dataType: 'json',
                success: function(data) {
                    $('#edit_leavetypeid').val(data.leavetypeid);
                    $('#edit_gender').val(data.gender);
                    $('#edit_leavetypename').val(data.leavetypename);
                    $('#edit_staffid').val(data.staffid);
                    $('#edit_workage').val(data.workage);
                    $('#edit_workageday').val(data.workageday);
                    $('#edit_leaveofyear').val(data.leaveofyear);
                    $('#edit_stackleaveday').val(data.stackleaveday);
                    $('#edit_nameform').val(data.nameform);

                    if (data.workage == '3') {
                        $('#edit_workageInput').hide();
                        $('#edit_workageday').prop('required', false);
                    } else {
                        $('#edit_workageInput').show();
                        $('#edit_workageday').prop('required', true);
                    }
                }
            });
        });

        $('#edit_workage').change(function() {
            if ($(this).val() == '3') {
                $('#edit_workageInput').hide();
                $('#edit_workageday').prop('required', false);
            } else {
                $('#edit_workageInput').show();
                $('#edit_workageday').prop('required', true);
            }
        });

        $('#editLeaveTypeForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'update/updateleavetype.php',
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
                }
            });
        });
    });
</script>