<?php 

?>
<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">แก้ไขข้อมูลพนักงาน</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <form id="editEmployeeForm" action="update/updateemployee.php" method="POST">

               <div class="table-responsive">
                  <table class="table table-borderless">

                     <!-- <td>ลำดับ</td> -->
                     <input type="hidden" id="edit_id" name="id" class="form-control" readonly required>

                     <tr>
                        <td>คำนำหน้า</td>
                        <td>
                           <select id="edit_prefix" name="prefix" class="form-control" required>
                              <option value="">เลือกคำนำหน้า</option>
                              <?php
                              $query = "SELECT * FROM prefix";
                              $result = mysqli_query($conn, $query);
                              while ($row = mysqli_fetch_assoc($result)) {
                                 echo "<option value='" . $row['prefixid'] . "'>" . $row['prefixname'] . "</option>";
                              }
                              mysqli_free_result($result);
                              ?>
                           </select></td>
                     </tr>

                  


                        <tr>
                        <td><label for="gender" class="form-label">เพศ</label></td>
                        <td>
                           <select class="form-select" id="edit_gender" name="gender" >
                              <option value="">เลือกเพศ</option>
                              <option value="ชาย">ชาย</option>
                              <option value="หญิง">หญิง</option>
                           </select>
                        </td>
                     </tr>
                     <tr>
                        <td>ชื่อ</td>
                        <td><input type="text" id="edit_fname" name="fname" class="form-control" required></td>
                     </tr>
                     <tr>
                        <td>นามสกุล</td>
                        <td><input type="text" id="edit_lname" name="lname" class="form-control" required></td>
                     </tr>
                     <tr>
                        <td>อีเมล</td>
                        <td><input type="email" id="edit_email" name="email" class="form-control" required></td>
                     </tr>
                     <tr>
                        <td>งาน</td>
                        <td>
                           <select id="edit_department" name="department" class="form-control" required>
                              <option value="">เลือกงาน</option>
                              <?php
                              $query = "SELECT * FROM subdepart";
                              $result = mysqli_query($conn, $query);
                              while ($row = mysqli_fetch_assoc($result)) {
                                 echo "<option value='" . $row['subdepartid'] . "'>" . $row['subdepartname'] . "</option>";
                              }
                              ?>
                           </select>
                        </td>
                     </tr>
                     <tr>
                        <td>ตำแหน่ง</td>
                        <td>
                           <select id="edit_position" name="position" class="form-control" required>
                              <option value="">เลือกตำแหน่ง</option>
                              <?php
                              $query = "SELECT * FROM position";
                              $result = mysqli_query($conn, $query);
                              while ($row = mysqli_fetch_assoc($result)) {
                                 echo "<option value='" . $row['positionid'] . "'>" . $row['positionname'] . "</option>";
                              }
                              ?>
                           </select>
                        </td>
                     </tr>
                     <tr>
                        <td>สถานะงาน</td>
                        <td>
                           <select id="edit_status" name="status" class="form-control" required>
                              <option value="">เลือกสถานะงาน</option>
                              <?php
                              $query = "SELECT * FROM staffstatus";
                              $result = mysqli_query($conn, $query);
                              while ($row = mysqli_fetch_assoc($result)) {
                                 echo "<option value='" . $row['staffid'] . "'>" . $row['staffname'] . "</option>";
                              }
                              ?>
                           </select>
                        </td>
                     </tr>
                     <tr>
                        <td>วันที่เริ่มงาน</td>
                        <td><input type="date" id="edit_startwork" name="startwork" class="form-control" required></td>
                     </tr>
                     <tr>
                        <td>วันที่บรรจุ</td>
                        <td><input type="date" id="edit_startappoint" name="startappoint" class="form-control" ></td>
                     </tr>
                     </tbody>
                  </table>
               </div>
            </form>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" form="editEmployeeForm" class="btn btn-outline-primary">บันทึก</button>
         </div>
      </div>
   </div>
</div>


<script>
   // อัพเดทข้อมูล
   $(document).on('click', '.edit-btn', function() {
      // ดึงข้อมูลจาก data-* attributes
      var id = $(this).data('id');
      var gender = $(this).data('gender');
      var fname = $(this).data('fname');
      var lname = $(this).data('lname');
      var prefix = $(this).data('prefix');
      var department = $(this).data('department');
      var position = $(this).data('position');
      var status = $(this).data('status');
      var startwork = $(this).data('startwork');
      var startappoint = $(this).data('startappoint');
      var email = $(this).data('email');

      // ตั้งค่าฟิลด์ใน Modal
      $('#edit_id').val(id);
      $('#edit_gender').val(gender);
      $('#edit_fname').val(fname);
      $('#edit_lname').val(lname);
      $('#edit_prefix').val(prefix);
      $('#edit_department').val(department);
      $('#edit_subdepartment').val(position);
      $('#edit_position').val(position);
      $('#edit_status').val(status);
      $('#edit_startwork').val(startwork);
      $('#edit_startappoint').val(startappoint);
      $('#edit_email').val(email);

      // เปิด Modal
      $('#editModal').modal('show');
   });
</script>