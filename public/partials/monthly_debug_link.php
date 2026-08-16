<?php

declare(strict_types=1);
if (function_exists('auth_is_admin') && auth_is_admin()): ?>
  <p><a href="<?= e(admin_url('monthly_api_diagnostics.php')) ?>">月額API診断</a></p>
<?php endif; ?>
