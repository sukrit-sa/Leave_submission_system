<div class="modal fade" id="addRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มบทบาท</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addRoleForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อบทบาท</label>
                        <input type="text" class="form-control" name="rolename" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เลเวล</label>
                        <input type="number" class="form-control" name="level" required min="1">
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