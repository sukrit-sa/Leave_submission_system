<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขบทบาท</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editRoleForm">
                <div class="modal-body">
                    <input type="hidden" name="roleid" id="edit_roleid">
                    <div class="mb-3">
                        <label class="form-label">ชื่อบทบาท</label>
                        <input type="text" class="form-control" name="rolename" id="edit_rolename" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เลเวล</label>
                        <input type="number" class="form-control" name="level" id="edit_level" required min="1">
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