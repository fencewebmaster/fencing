<?php
/**
 * FC Admin — Login page (WordPress auth).
 *
 * Read-only template: AuthPresenter::loginViewData() guarantees every key here as
 * a string. Escaping via the global e() helper; public/index.php's is_array()
 * check on fcLoginPage is the render gate.
 *
 * @var array<string, mixed> $fcLoginPage
 */

use Fc\Admin\Settings\BrandingSettings;
use Fc\Admin\Settings\ThemeSettings;

$page    = $fcLoginPage;
$appName = $page['app_name'];
$logoUrl = $page['logo_url'];
$appBase = $page['app_base'];
$version = $page['version'];
?>
<!DOCTYPE html>
<html lang="en" class="h-full" data-fc-admin-theme="light">
<head>
    <script>
    (function(){try{var t=localStorage.getItem('fc-admin-appearance');document.documentElement.setAttribute('data-fc-admin-theme',t==='dark'?'dark':'light');}catch(e){}})();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo e(rtrim((string) $page['admin_base'], '/') . '/'); ?>">
    <title>Sign in — <?php echo e($appName); ?></title>
    <?php echo ThemeSettings::cssBlock(); ?>
    <link href="<?php echo asset('assets/css/vendor/bootstrap/bootstrap.min.css'); ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo asset('assets/css/vendor/fontawesome/css/all.min.css'); ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo asset('assets/css/vendor/tailwind.css'); ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo asset('assets/css/admin/theme.css'); ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo asset('assets/css/admin/login.css'); ?>">
</head>
<body class="h-full fc-login-body">
    <div id="toast-container" class="fixed top-4 right-4 z-[9999] flex flex-col gap-2"></div>

    <div class="theme-toggle-floating login-theme-float">
        <button
            type="button"
            class="theme-toggle"
            id="fc-login-theme-toggle"
            aria-label="Toggle color theme"
            title="Toggle light / dark"
        >
            <span class="theme-toggle-track">
                <i class="fa-solid fa-sun theme-icon theme-icon-light" aria-hidden="true"></i>
                <i class="fa-solid fa-moon theme-icon theme-icon-dark" aria-hidden="true"></i>
                <span class="theme-toggle-thumb" aria-hidden="true"></span>
            </span>
        </button>
    </div>

    <div class="login-page">
        <div class="login-panel-brand">
            <div class="login-brand-content">
                <h2>Design fences with clarity and confidence</h2>
                <p><?php echo e((string) ($page['tagline'] ?? '')); ?></p>
                <div class="login-features">
                    <?php foreach (($page['features'] ?? []) as $feature) : ?>
                    <div class="login-feature">
                        <i class="fas <?php echo e((string) ($feature['icon'] ?? 'fa-check')); ?>"></i>
                        <?php echo e((string) ($feature['label'] ?? '')); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="login-panel-form">
            <div class="login-card">
                <div class="login-card-header">
                    <div class="login-logo<?php echo $logoUrl !== '' ? ' login-logo--image' : ''; ?>" aria-hidden="true">
                        <?php echo BrandingSettings::logoMarkup($appBase, null, ['img_class' => 'login-logo__img']); ?>
                    </div>
                    <h1>Welcome back</h1>
                    <p>Sign in to <?php echo e($appName); ?></p>
                </div>

                <div class="login-form-card">
                    <form id="login-form" method="post" action="" novalidate autocomplete="on">
                        <input type="hidden" name="csrf_token" value="<?php echo e((string) ($page['csrf'] ?? '')); ?>">
                        <input type="hidden" name="redirect" value="<?php echo e((string) ($page['redirect'] ?? '')); ?>">

                        <div id="login-error" class="alert alert-error hidden mb-5" role="alert">
                            <i class="fas fa-circle-exclamation"></i>
                            <span id="login-error-text"></span>
                        </div>

                        <div class="fc-login-field">
                            <label class="fc-login-field__label" for="username">Username or email</label>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="fc-settings-field"
                                placeholder="Enter your username or email"
                                required
                                autofocus
                                autocomplete="username"
                                autocapitalize="off"
                                autocorrect="off"
                                spellcheck="false"
                            >
                            <p class="fc-login-field__error hidden" data-error="username"></p>
                        </div>

                        <div class="fc-login-field">
                            <label class="fc-login-field__label" for="password">Password</label>
                            <div class="fc-login-field__control fc-login-field__control--password">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="fc-settings-field"
                                    placeholder="Enter your password"
                                    required
                                    autocomplete="current-password"
                                    autocapitalize="off"
                                    autocorrect="off"
                                    spellcheck="false"
                                >
                                <button type="button" id="toggle-password" class="fc-login-field__action" aria-label="Toggle password visibility">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <p class="fc-login-field__error hidden" data-error="password"></p>
                        </div>

                        <div class="mb-6">
                            <label class="form-checkbox" for="remember">
                                <input type="checkbox" id="remember" name="remember">
                                <span class="form-checkbox__label">Remember me</span>
                            </label>
                        </div>

                        <button type="submit" id="login-btn" class="w-full btn-primary btn-lg">
                            <i class="fas fa-arrow-right-to-bracket"></i> Sign in
                        </button>
                    </form>
                </div>

                <p class="login-footer-note">
                    &copy; <?php echo date('Y'); ?> <?php echo e($appName); ?><?php echo $version !== '' ? ' &middot; ' . e($version) : ''; ?>
                </p>
            </div>
        </div>
    </div>

    <script>
        window.FC_LOGIN = {
            api: <?php echo json_encode((string) ($page['login_api'] ?? ''), JSON_UNESCAPED_SLASHES); ?>,
            adminBase: <?php echo json_encode(rtrim((string) ($page['admin_base'] ?? ''), '/'), JSON_UNESCAPED_SLASHES); ?>,
            csrf: <?php echo json_encode((string) ($page['csrf'] ?? '')); ?>,
            redirect: <?php echo json_encode((string) ($page['redirect'] ?? ''), JSON_UNESCAPED_SLASHES); ?>
        };
    </script>
    <script src="<?php echo asset('assets/js/admin/core/namespace.js'); ?>"></script>
    <script src="<?php echo asset('assets/js/admin/core/admin-appearance.js'); ?>"></script>
    <script src="<?php echo asset('assets/js/admin/login.js'); ?>"></script>
</body>
</html>
