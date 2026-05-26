<?php
/**
 * Users Page (Admin only)
 * Displays a list of all registered user accounts.
 */

require_once __DIR__ . '/../includes/user.php';

requireAdmin();  // Redirects non-admins with an error flash

$conn    = getDBConnection();
$result  = $conn->query('SELECT id, username, email, full_name, role, created_at FROM users ORDER BY created_at DESC');
$users   = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

$pageTitle  = 'User Management';
$activePage = 'users';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">

  <div class="panel">
    <div class="panel-header">
      <div class="panel-title">
        <i class="fa-solid fa-users" style="color:var(--info);"></i>
        Registered Users
      </div>
      <span class="badge badge-default"><?= count($users) ?> user(s)</span>
    </div>

    <?php if (empty($users)): ?>
      <div class="empty-state">
        <div class="empty-icon">👥</div>
        <h4>No users found</h4>
      </div>
    <?php else: ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Full Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Registered</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $i => $u): ?>
            <tr>
              <td class="td-muted"><?= $i + 1 ?></td>
              <td style="font-weight:600;"><?= sanitize($u['full_name']) ?></td>
              <td><span class="sku-code"><?= sanitize($u['username']) ?></span></td>
              <td class="td-muted"><?= sanitize($u['email']) ?></td>
              <td>
                <span class="badge <?= $u['role'] === 'admin' ? 'badge-info' : 'badge-default' ?>">
                  <?= ucfirst(sanitize($u['role'])) ?>
                </span>
              </td>
              <td class="td-muted"><?= formatDate($u['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
