<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDepartmentModalLabel">เพิ่มหน่วยงาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addDepartmentForm" action="add/adddepartment.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="headepartname" class="form-label">ชื่อหน่วยงาน</label>
                        <input type="text" class="form-control" id="headepartname" name="headepartname" required>
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