let Planner = {
    init: function() {
        this.reload_fence_items();
    },

    //----------------------------------------------------------------------------------

    reload_fence_items: function() {
        if (HELPER.getSearchParams('action') == 'clear-all') {
            clearFencingData();
            location.href = location.origin + location.pathname;
        }

        this.reloadFencingData();

        var items = localStorage.getItem('custom_fence-section') ?? 1;
        var section = HELPER.getSearchParams('section');
        var tab = HELPER.getSearchParams('tab');
        var form = HELPER.getSearchParams('form');

        for (let i = 1; i <= items; i++) {

            var index = i - 1;
            var sectionTab = `<div class="fencing-tab fencing-tab-selected fc-d-none fc-section-${i}">
                    <div class="fencing-tab-name">
                        <span class="ftm-title">SECTION</span> <span class="fencing-tab-number">${i}</span>
                        <div class="ftm-measurement"></div>
                    </div>
                </div>`;

            $('.fencing-tab-container-area').append(sectionTab);
            $('.fencing-tab').removeClass('fencing-tab-selected');
            $('.fencing-tab:last-child').addClass('fencing-tab-selected');

            var custom_fence_tabs = localStorage.getItem('custom_fence-' + index);
            const data_tabs = custom_fence_tabs ? JSON.parse(custom_fence_tabs) : [];
            mesurement = data_tabs[0]?.calculateValue ? parseInt(data_tabs[0]?.calculateValue).toLocaleString() + ' ' + FENCES.defaultValues.unit : '';

            $('.fencing-tab-selected').find('.ftm-measurement').html(mesurement);
            $('.js-btn-delete-fence').show();
            $('.js-fc-form-step').hide();

        }

        if (tab) {
            $('.fc-section-step').hide();
            $('[data-tab="' + tab + '"]').show();
        }

        setTimeout(function() {
            if (section) {
                $('.fencing-tab-selected').removeClass('fencing-tab-selected');
                $('.fc-section-' + section).addClass('fencing-tab-selected');
            }
            $('.fencing-tab.fencing-tab-selected:visible').click();
        }, 100);

        if (tab == 2 && form) {
            $('#submit-modal').show();
            $('.fc-form-plan').hide();
            $('[data-formtab="' + form + '"]').show();
        }


        // Store default section count
        if (localStorage.getItem('custom_fence-section') == null && $('.fencing-tab').length == 1) {
            localStorage.setItem('custom_fence-section', 1);
        }

        // Initiate tab container scroll
        HELPER.tabContainerScroll();

        // Restore form data when the page loads
        restoreFormData();

        if (HELPER.getSearchParams('qid')) {
            this.planCart();
        }

        if ($('.fc-form-check-img input:checked').length) {
            $('.form-tab-4').closest('form').find('[type="submit"]')
                .removeClass('disabled')
                .removeAttr('disabled');
        }

        if (fence = HELPER.getSearchParams('fence')) {
            clearFencingData();
            var fence = HELPER.getSearchParams('fence');
            $('.fencing-style-item[data-slug="' + fence + '"]').trigger('click');
        }
    },

    //----------------------------------------------------------------------------------

    planCart: function() {
        FCModal.close('#submit-modal');
        $('.fc-loader-overlay').show();
        if (HELPER.getSearchParams('qid')) {
            $('.li-create small').html('Reloading your plan...');
        } else {
            $('.li-create small').html('Updating your plan...');
        }
        submit_fence_planner('update');
    },

    //----------------------------------------------------------------------------------

    reloadFencingData: function() {
        if (HELPER.getSearchParams('qid') && !fc_fence_info.fence_data) {
            clearFencingData();
            location.href = location.origin + location.pathname;
        }

        if (fc_fence_info?.length == 0) {
            return;
        }

        var custom_fence_items = JSON.parse(fc_fence_info.fence_data, true);

        $(custom_fence_items).each(function(k, v) {

            v.form[0].style = v.form[0].style;
            v.form[0].tab = v.form[0].tab - 1;

            localStorage.setItem('custom_fence-' + v.form[0].tab, JSON.stringify(v.form));

            if (v.settings) {
                localStorage.setItem('custom_fence-' + v.form[0].tab + '-' + v.form[0].style, JSON.stringify(v.settings));
            }
        });

        var cart_items = JSON.parse(fc_fence_info.cart_items_data);
        
        $(cart_items).each(function(k, v) {
            localStorage.setItem('cart_items-' + k, JSON.stringify(v));
        });

        localStorage.setItem('custom_fence-section', fc_fence_info.section_count);
        localStorage.setItem('project-plans', fc_fence_info.project_plans_data);
    }

    //----------------------------------------------------------------------------------

}

Planner.init();
