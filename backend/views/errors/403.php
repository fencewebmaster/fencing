<?php
/**
 * FC Admin — 403 Forbidden.
 *
 * @var string $fc403Message
 * @var string $fcAdminBase
 * @var bool $fc403IsSwitched
 * @var array{id?:int,login?:string,display_name?:string}|null $fc403SwitchFrom
 * @var array{id?:int,login?:string,display_name?:string}|null $fc403CurrentUser
 * @var string $fc403SwitchBackUrl
 */

declare(strict_types=1);

$message = isset($fc403Message) ? (string) $fc403Message : 'You do not have permission to access this page.';
$base = isset($fcAdminBase) ? rtrim((string) $fcAdminBase, '/') : '';
$home = $base !== '' ? $base . '/dashboard' : '/';
$isSwitched = !empty($fc403IsSwitched);
$switchBackUrl = isset($fc403SwitchBackUrl) ? (string) $fc403SwitchBackUrl : '';
$switchFrom = is_array($fc403SwitchFrom ?? null) ? $fc403SwitchFrom : null;
$currentUser = is_array($fc403CurrentUser ?? null) ? $fc403CurrentUser : null;

$asName = '';
if ($currentUser !== null) {
    $asName = (string) (($currentUser['display_name'] ?? '') !== ''
        ? $currentUser['display_name']
        : ($currentUser['login'] ?? ''));
}
$fromName = '';
if ($switchFrom !== null) {
    $fromName = (string) (($switchFrom['display_name'] ?? '') !== ''
        ? $switchFrom['display_name']
        : ($switchFrom['login'] ?? 'admin'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Forbidden</title>
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; background: #f8fafc; color: #1d2327; }
        .wrap { max-width: 28rem; margin: 4rem auto; padding: 2rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; text-align: center; }
        h1 { margin: 0 0 .5rem; font-size: 1.5rem; }
        p { margin: 0 0 1.25rem; color: #64748b; line-height: 1.5; }
        .actions { display: flex; flex-wrap: wrap; gap: .65rem; justify-content: center; }
        a.btn {
            display: inline-block;
            padding: .5rem 1rem;
            background: #f67925;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
        }
        a.btn:hover { filter: brightness(1.05); }
        a.btn-secondary {
            background: #1d2327;
        }
        .switch-note {
            margin: 0 0 1.25rem;
            padding: .65rem .75rem;
            border-radius: 6px;
            background: #1d2327;
            color: #f0f0f1;
            font-size: .8125rem;
            line-height: 1.45;
            text-align: left;
        }
        .switch-note strong { color: #fff; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>403 Forbidden</h1>
        <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if ($isSwitched && $switchBackUrl !== '') : ?>
        <div class="switch-note" role="status">
            <?php if ($asName !== '') : ?>
            Logged in as <strong><?php echo htmlspecialchars($asName, ENT_QUOTES, 'UTF-8'); ?></strong>.
            <?php endif; ?>
            <?php if ($fromName !== '') : ?>
            Return to <strong><?php echo htmlspecialchars($fromName, ENT_QUOTES, 'UTF-8'); ?></strong>?
            <?php else : ?>
            Return to your original admin account?
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="actions">
            <?php if ($isSwitched && $switchBackUrl !== '') : ?>
            <a class="btn" href="<?php echo htmlspecialchars($switchBackUrl, ENT_QUOTES, 'UTF-8'); ?>">Switch back</a>
            <?php endif; ?>
            <a class="btn<?php echo ($isSwitched && $switchBackUrl !== '') ? ' btn-secondary' : ''; ?>" href="<?php echo htmlspecialchars($home, ENT_QUOTES, 'UTF-8'); ?>">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
