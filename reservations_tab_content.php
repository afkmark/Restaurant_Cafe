<?php
// reservations_tab_content.php - shared partial used by admin_reservations.php
$reservations = $reservations ?? [];
?>
<div class="table-data">
    <div class="order">
        <div class="head">
            <h3>Manage Reservations</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Guests</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $r): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($r['id']) ?></td>
                        <td><?= htmlspecialchars($r['username'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['date'] ?? '') ?></td>
                        <td><?= htmlspecialchars(substr($r['time'] ?? '', 0, 5)) ?></td>
                        <td><?= htmlspecialchars($r['guests'] ?? '') ?></td>
                        <td><?= ucfirst(htmlspecialchars($r['status'] ?? '')) ?></td>
                        <td>
                            <?php if (($r['status'] ?? '') !== 'approved'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                                    <button name="res_status" value="approved">Approve</button>
                                </form>
                            <?php endif; ?>
                            <?php if (($r['status'] ?? '') !== 'rejected'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                                    <button name="res_status" value="rejected">Reject</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>