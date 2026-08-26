<?php
/**
 * FC — 404 Not Found (admin + frontend).
 *
 * Read-only template: NotFoundHandler::abort() assembles the complete view-model
 * and renders view('errors.404', $data), so every variable arrives ready — no
 * prep block at all. Escaping via the global e() helper.
 *
 * @var string $title
 * @var string $message
 * @var string $homeUrl
 * @var string $homeLabel
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($title); ?></title>
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; background: #f8fafc; color: #1d2327; }
        .wrap { max-width: 28rem; margin: 4rem auto; padding: 2rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; text-align: center; }
        .code {
            margin: 0 0 .35rem;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94a3b8;
        }
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
    </style>
</head>
<body>
    <div class="wrap">
        <p class="code">Error 404</p>
        <h1>Page not found</h1>
        <p><?php echo e($message); ?></p>
        <div class="actions">
            <a class="btn" href="<?php echo e($homeUrl); ?>"><?php echo e($homeLabel); ?></a>
        </div>
    </div>
</body>
</html>
