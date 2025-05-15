<tr>
    <td><?php echo $i++; ?></td>
    <td><?php echo $row['fullname']; ?></td>
    <td><?php echo $row['leavetypename']; ?></td>
    <td><?php echo date('d/m/Y', strtotime($row['leavestart'])); ?></td>
    <td><?php echo date('d/m/Y', strtotime($row['leaveend'])); ?></td>
    <td><?php echo $row['day']; ?></td>
    <td>
        <?php if ($row['leavestatus'] == 'อนุมัติ') { ?>
            <a href="print_leave.php?leavesid=<?php echo $row['leavesid']; ?>" class="btn btn-primary btn-sm" target="_blank">
                <i class="ri-printer-line"></i> พิมพ์
            </a>
        <?php } ?>
    </td>
    <td>
        <?php if (!empty($row['file'])) : ?>
            <a href="javascript:void(0)" onclick="showFilePreview('../uploads/leaves/<?php echo urlencode($row['file']); ?>')" class="btn btn-info btn-sm">
                <i class="ri-file-line"></i> ดูไฟล์
            </a>
        <?php else : ?>
            -
        <?php endif; ?>
    </td>
</tr>