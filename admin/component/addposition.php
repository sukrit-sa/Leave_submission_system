<div class="modal fade" id="addPositionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มตำแหน่ง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addPositionForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ตำแหน่ง</label>
                        <input type="text" class="form-control" name="positionname" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">บทบาท</label>
                        <select class="form-select" name="roleid" required>
                            <option value="">เลือกบทบาท</option>
                            <?php
                            include('conn/conn.php');
                            $sql = "SELECT * FROM role";
                            $result = mysqli_query($conn, $sql);
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<option value='" . $row['roleid'] . "'>" . $row['rolename'] . "</option>";
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