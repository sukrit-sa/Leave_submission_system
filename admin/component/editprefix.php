<div class="modal fade" id="editPrefixModal" tabindex="-1" aria-labelledby="editPrefixModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPrefixModalLabel">แก้ไขคำนำหน้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editPrefixForm">
                    <input type="hidden" name="id" id="editPrefixId">
                    <div class="mb-3">
                        <label for="editPrefixName" class="form-label">คำนำหน้า</label>
                        <input type="text" class="form-control" id="editPrefixName" name="name" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">ปิด</button>
                        <button type="submit" class="btn btn-sm btn-outline-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>