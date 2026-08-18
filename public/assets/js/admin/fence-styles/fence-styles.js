/**
 * FC Admin — Fence Styles list + edit (writable/fences/*.php).
 */
(function (global) {
    'use strict';

    var API_LIST = fcApiUrl('fenceStyles', 'action=list');
    var API_GET = fcApiUrl('fenceStyles', 'action=get');
    var API_SAVE = fcApiUrl('fenceStyles', 'action=save');
    var API_FENCE_COLORS = fcApiUrl('settings', 'action=fence-colors');
    var TOAST_SAVE = 'fc-fence-style-save';

    var escapeHtml = global.FC.util.escapeHtml;

    function editRoute(slug) {
        return 'products/fence-styles/edit/' + encodeURIComponent(slug);
    }

    function navigate(route) {
        if (typeof global.fcAdminNavigate === 'function') {
            global.fcAdminNavigate(route);
            return;
        }
        global.location.href = fcAdminUrl(route);
    }

    function toast(kind, message, toastId) {
        global.FC.util.toast(kind, message, toastId);
    }

    function renderLoading(message) {
        return (
            '<div class="flex flex-col items-center justify-center gap-3 p-12 text-slate-500">' +
            '<i class="fa-solid fa-spinner fa-spin text-2xl text-indigo-500" aria-hidden="true"></i>' +
            '<p class="text-sm">' +
            escapeHtml(message || 'Loading…') +
            '</p>' +
            '</div>'
        );
    }

    function renderError(message) {
        return (
            '<div class="m-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">' +
            '<p class="font-semibold">Could not load fence styles</p>' +
            '<p class="mt-1 text-sm">' +
            escapeHtml(message) +
            '</p>' +
            '</div>'
        );
    }

    function renderStyleCard(style, canEdit) {
        var liveBadge = style.live
            ? '<span class="fc-admin-fence-style-badge fc-admin-fence-style-badge--live">Live</span>'
            : '<span class="fc-admin-fence-style-badge fc-admin-fence-style-badge--draft">Draft</span>';

        var imageSrc = style.imageUrl || style.image || '';
        var imageHtml = imageSrc
            ? '<img src="' + escapeHtml(imageSrc) + '" alt="" loading="lazy" decoding="async">'
            : '<span class="fc-admin-fence-style-img-placeholder" aria-hidden="true">' +
              '<i class="fa-solid fa-image text-2xl text-slate-300"></i></span>';

        var inner =
            '<div>' +
            '<div class="fencing-style-img">' +
            imageHtml +
            liveBadge +
            '</div>' +
            '<div class="fencing-style-title fw-bold">' +
            escapeHtml(style.title) +
            '</div>' +
            '</div>';

        if (!canEdit) {
            return (
                '<div class="fencing-style-item fc-admin-fence-style-item" aria-label="' +
                escapeHtml(style.title) +
                '">' +
                inner +
                '</div>'
            );
        }

        var route = editRoute(style.slug);

        return (
            '<a href="' +
            escapeHtml(fcAdminUrl(route)) +
            '" class="fencing-style-item fc-admin-fence-style-item fc-admin-fence-style-link" data-route="' +
            escapeHtml(route) +
            '" data-title="Edit ' +
            escapeHtml(style.title) +
            '" aria-label="Edit ' +
            escapeHtml(style.title) +
            '">' +
            inner +
            '</a>'
        );
    }

    function bindListLinks(container) {
        container.querySelectorAll('.fc-admin-fence-style-link[data-route]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                navigate(link.getAttribute('data-route'));
            });
        });
    }

    function renderListPage(data) {
        var styles = data.styles || [];
        var canEdit = data.canEdit !== false;

        if (!styles.length) {
            return (
                '<div class="p-8 text-center text-sm text-slate-500">No fence styles found in writable/fences.</div>'
            );
        }

        return (
            '<div class="fc-fs-styles-page">' +
            '<div class="fc-admin-fence-styles">' +
            '<div class="fc-admin-fence-styles__grid">' +
            styles
                .map(function (style) {
                    return renderStyleCard(style, canEdit);
                })
                .join('') +
            '</div></div></div>'
        );
    }

    function loadFenceStyles(container) {
        if (!container) {
            return Promise.resolve();
        }

        if (container.querySelector('[data-fc-fence-styles-server]')) {
            return hydrateFromServer(container);
        }

        if (typeof global.fcAdminUrl === 'function') {
            global.location.href = global.fcAdminUrl('products/fence-styles');
            return Promise.resolve();
        }

        container.innerHTML = renderLoading('Loading fence styles…');

        return fetch(API_LIST, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok) {
                        throw new Error((data && data.error) || 'Request failed');
                    }
                    return data;
                });
            })
            .then(function (data) {
                if (!data.ok) {
                    throw new Error(data.error || 'Failed to load fence styles');
                }
                container.innerHTML = renderListPage(data);
                bindListLinks(container);
            })
            .catch(function (err) {
                container.innerHTML = renderError(err.message || 'Unknown error');
            });
    }

    function readBootstrapData() {
        var el = document.getElementById('fc-fence-styles-bootstrap');
        if (!el) {
            return null;
        }

        try {
            return JSON.parse(el.textContent || '');
        } catch (e) {
            return null;
        }
    }

    function hydrateFromServer(container) {
        if (!container || !container.querySelector('[data-fc-fence-styles-server]')) {
            return Promise.resolve(false);
        }

        bindListLinks(container);
        container.removeAttribute('aria-busy');

        return Promise.resolve(true);
    }

    function saveFenceConfig(slug, config, csrf, onOk, onFail) {
        toast('saving', 'Saving fence style…', TOAST_SAVE);

        fetch(API_SAVE, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ slug: slug, config: config, csrf: csrf })
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok) {
                        throw new Error((data && data.error) || 'Save failed');
                    }
                    return data;
                });
            })
            .then(function (data) {
                if (!data.ok) {
                    throw new Error(data.error || 'Save failed');
                }
                toast('ok', data.message || 'Fence style saved.', TOAST_SAVE);
                if (typeof onOk === 'function') {
                    onOk(data);
                }
            })
            .catch(function (err) {
                toast('error', err.message || 'Could not save fence style.', TOAST_SAVE);
                if (typeof onFail === 'function') {
                    onFail(err);
                }
            });
    }

    function loadFenceColorCatalog() {
        return fetch(API_FENCE_COLORS, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok || !data || !data.ok) {
                        return [];
                    }
                    return Array.isArray(data.fenceColors) ? data.fenceColors : [];
                });
            })
            .catch(function () {
                return [];
            });
    }

    function loadFenceStyleEdit(container, slug) {
        if (!container || !slug) {
            return Promise.resolve();
        }

        if (
            global.FcFenceStyleCodeEditor &&
            typeof global.FcFenceStyleCodeEditor.preload === 'function'
        ) {
            global.FcFenceStyleCodeEditor.preload();
        }

        container.innerHTML = renderLoading('Loading fence style…');

        return Promise.all([
            fetch(API_GET + '&slug=' + encodeURIComponent(slug), {
                method: 'GET',
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            }).then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok) {
                        throw new Error((data && data.error) || 'Request failed');
                    }
                    return data;
                });
            }),
            loadFenceColorCatalog()
        ])
            .then(function (results) {
                var data = results[0];
                var fenceColorCatalog = results[1];
                if (!data.ok || !data.style) {
                    throw new Error(data.error || 'Failed to load fence style');
                }
                var Edit = global.FcAdminFenceStyleEdit;
                if (!Edit || typeof Edit.renderEditPage !== 'function') {
                    throw new Error('Fence style editor module failed to load.');
                }
                var config = data.config && typeof data.config === 'object' ? data.config : {};
                var canEdit = data.canEdit !== false;
                container.innerHTML = Edit.renderEditPage(data.style, config, data.fileMeta || {}, {
                    fenceColorCatalog: fenceColorCatalog,
                    canEdit: canEdit
                });
                Edit.bindEditForm(container, data.style.slug, data.style, config, data.fileMeta || {}, {
                    fenceColorCatalog: fenceColorCatalog,
                    canEdit: canEdit,
                    csrf: data.csrf,
                    onBack: function () {
                        navigate('products/fence-styles');
                    },
                    onToast: toast,
                    onSave: function (nextConfig, onOk, onFail) {
                        if (!canEdit) {
                            if (typeof onFail === 'function') {
                                onFail(new Error('You do not have permission to edit fence styles.'));
                            }
                            return;
                        }
                        saveFenceConfig(data.style.slug, nextConfig, data.csrf, onOk, onFail);
                    }
                });
            })
            .catch(function (err) {
                container.innerHTML =
                    renderError(err.message || 'Unknown error') +
                    '<div class="px-4 pb-6"><button type="button" id="fc-fence-style-back-fail" class="btn btn-sm btn-dark fw-semibold">Back to Fence Styles</button></div>';
                var failBack = container.querySelector('#fc-fence-style-back-fail');
                if (failBack) {
                    failBack.addEventListener('click', function () {
                        navigate('products/fence-styles');
                    });
                }
            });
    }

    function parseEditSlug(route) {
        var prefix = 'products/fence-styles/edit/';
        if (route.indexOf(prefix) !== 0) {
            return '';
        }
        try {
            return decodeURIComponent(route.slice(prefix.length));
        } catch (e) {
            return route.slice(prefix.length);
        }
    }

    global.FcAdminFenceStyles = {
        load: loadFenceStyles,
        hydrateFromServer: hydrateFromServer,
        loadEdit: loadFenceStyleEdit,
        parseEditSlug: parseEditSlug
    };

    class FenceStylesPage extends global.FC.PageController {
        hydrate(container) {
            hydrateFromServer(container);
        }
    }
    global.FC.PageRegistry.register('products/fence-styles', new FenceStylesPage());
})(window);
