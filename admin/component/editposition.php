<div class="modal fade" id="editPositionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขตำแหน่ง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPositionForm">
                <div class="modal-body">
                    <input type="hidden" name="positionid" id="edit_positionid">
                    <div class="mb-3">
                        <label class="form-label">ตำแหน่ง</label>
                        <input type="text" class="form-control" name="positionname" id="edit_positionname" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">บทบาท</label>
                        <select class="form-select" name="roleid" id="edit_roleid" required>
                            <option value="">เลือกบทบาท</option>
                            <?php
                            include 'conn/conn.php';
                            $role_sql = "SELECT * FROM role";
                            $role_result = mysqli_query($conn, $role_sql);
                            while ($role = mysqli_fetch_assoc($role_result)) {
                                echo "<option value='" . $role['roleid'] . "'>" . $role['rolename'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-outline-warning">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>
