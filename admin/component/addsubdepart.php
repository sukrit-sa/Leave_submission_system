<!-- Add Sub Department Modal -->
<div class="modal fade" id="addSubDepartmentModal" tabindex="-1" aria-labelledby="addSubDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSubDepartmentModalLabel">เพิ่มงาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSubDepartmentForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="subdepartname" class="form-label">ชื่องาน</label>
                        <input type="text" class="form-control" id="subdepartname" name="subdepartname" required>
                    </div>
                    <div class="mb-3">
                        <label for="headepartid" class="form-label">หน่วยงาน</label>
                        <select class="form-select" id="headepartid" name="headepartid" required>
                            <option value="">เลือกหน่วยงาน</option>
                            <?php
                            include('conn/conn.php');
                            $sql = "SELECT * FROM headepart ORDER BY headepartname ASC";
                            $result = mysqli_query($conn, $sql);
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<option value='" . $row['headepartid'] . "'>" . $row['headepartname'] . "</option>";
                            }
                            ?>
                        </select>
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
    $('#addSubDepartmentForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: 'add/addsubdepart.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        $('#addSubDepartmentModal').modal('hide');
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
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์'
                });
            }
        });
    });
</script>