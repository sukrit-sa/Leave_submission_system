<!-- Add Employee Modal -->

<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="addEmployeeModalLabel">เพิ่มข้อมูลพนักงาน</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <form id="addEmployeeForm" enctype="multipart/form-data" action="add/addemployee.php" method="POST">
               <div class="table-responsive">
                  <table class="table table-borderless">
                     <tr>
                        <td width="30%"><label for="image" class="form-label">รูปภาพ</label></td>
                        <td><input type="file" class="form-control" id="image" accept="image/*" name="image" required></td>
                     </tr>
                     <tr>
                        <td><label for="prefix" class="form-label">คำนำหน้า</label></td>
                        <td>
                           <select class="form-select" id="prefix" name="prefix" required>
                              <option value="">เลือกคำนำหน้า</option>
                              <?php while ($row = $result6->fetch_assoc()): ?>
                                 <option value="<?php echo $row['prefixid']; ?>"><?php echo $row['prefixname']; ?></option>
                              <?php endwhile; ?>
                           </select>
                        </td>
                     </tr>
                     <tr>
                        <td><label for="gender" class="form-label">เพศ</label></td>
                        <td>
                           <select class="form-select" id="gender" name="gender" required>
                              <option value="ชาย">ชาย</option>
                              <option value="หญิง">หญิง</option>
                           </select>
                        </td>
                     </tr>
                     <tr>
                        <td><label for="firstname" class="form-label">ชื่อ</label></td>
                        <td><input type="text" class="form-control" id="firstname" name="firstname" required></td>
                     </tr>
                     <tr>
                        <td><label for="lastname" class="form-label">นามสกุล</label></td>
                        <td><input type="text" class="form-control" id="lastname" name="lastname" required></td>
                     </tr>
                     <tr>
                        <td><label for="email" class="form-label">อีเมล</label></td>
                        <td><input type="email" class="form-control" id="email" name="email" required></td>
                     </tr>
                     <tr>
                        <td><label for="password" class="form-label">รหัสผ่าน</label></td>
                        <td>
                           <div class="input-group">
                              <input type="password" class="form-control" id="password" name="password" required>
                              <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                 <i class="ri-eye-line"></i>
                              </button>
                           </div>
                        </td>
                     </tr>
                     <tr>
                        <td><label for="headepart" class="form-label">หน่วยงาน</label></td>
                        <td>
                           <select class="form-select" id="headepart" name="headepart" required>
                              <option value="">เลือกหน่วยงาน</option>
                              <?php while ($row = $result8->fetch_assoc()): ?>
                                 <option value="<?php echo $row['headepartid']; ?>"><?php echo $row['headepartname']; ?></option>
                              <?php endwhile; ?>
                           </select>
                        </td>
                     </tr>
                     <tr>
                        <td><label for="subdepart" class="form-label">งาน</label></td>
                        <td>
                           <select class="form-select" id="subdepart" name="subdepart" required>
                              <option value="">เลือกงาน</option>
                              <?php while ($row = $result7->fetch_assoc()): ?>
                                 <option value="<?php echo $row['subdepartid']; ?>"><?php echo $row['subdepartname']; ?></option>
                              <?php endwhile; ?>
                           </select>
                        </td>
                     </tr>
                     <tr>
                        <td><label for="position" class="form-label">ตำแหน่ง</label></td>
                        <td>
                           <select class="form-select" id="position" name="position" required>
                              <option value="">เลือกตำแหน่ง</option>
                              <?php while ($row = $result5->fetch_assoc()): ?>
                                 <option value="<?php echo $row['positionid']; ?>"><?php echo $row['positionname']; ?></option>
                              <?php endwhile; ?>
                           </select>
                        </td>
                     </tr>
                     <tr>
                        <td><label for="status" class="form-label">สถานะ</label></td>
                        <td>
                           <select class="form-select" id="status" name="status" required>
                              <option value="">เลือกสถานะ</option>
                              <?php while ($row = $result3->fetch_assoc()): ?>
                                 <option value="<?php echo $row['staffid']; ?>"><?php echo $row['staffname']; ?></option>
                              <?php endwhile; ?>
                           </select>
                        </td>
                     </tr>
                     <tr>
                        <td><label for="start_date" class="form-label">วันที่เริ่มงาน(ค.ศ.)</label></td>
                        <td><input type="date" class="form-control" id="start_date" name="start_date" required></td>
                     </tr>
                     <tr>
                        <td><label for="start_app" class="form-label">วันที่บรรจุ(ค.ศ.)</label></td>
                        <td><input type="date" class="form-control" id="start_app" name="start_app" ></td>
                     </tr>
                  </table>
               </div>
            </form>
         </div>

         <!-- Add this script for dynamic subdepart selection -->


         <!-- Password toggle script remains the same -->
         <script>
            document.getElementById('togglePassword').addEventListener('click', function() {
               const passwordInput = document.getElementById('password');
               const icon = this.querySelector('i');

               if (passwordInput.type === 'password') {
                  passwordInput.type = 'text';
                  icon.className = 'ri-eye-off-line';
               } else {
                  passwordInput.type = 'password';
                  icon.className = 'ri-eye-line';
               }
            });
         </script>
         <div class="modal-footer">
            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" form="addEmployeeForm" class="btn btn-outline-primary">บันทึก</button>
         </div>
      </div>
   </div>
</div>

<script>
   $(document).ready(function() {
      $('#addEmployeeForm').on('submit', function(e) {
         e.preventDefault();

         var formData = new FormData(this);

         $.ajax({
            url: 'add/addemployee.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
               var res = JSON.parse(response);
               if (res.status === 'success') {
                  Swal.fire({
                     icon: 'success',
                     title: 'สำเร็จ!',
                     text: res.message,
                     timer: 1500,
                     showConfirmButton: false
                  }).then(() => {
                     $('#addEmployeeModal').modal('hide');
                     location.reload();
                  });
               } else {
                  Swal.fire({
                     icon: 'error',
                     title: 'ผิดพลาด!',
                     text: res.message
                  });
               }
            },
            error: function(xhr, status, error) {
               Swal.fire({
                  icon: 'error',
                  title: 'ผิดพลาด!',
                  text: 'เกิดข้อผิดพลาดในการส่งข้อมูล'
               });
            }
         });
      });
   });
</script>