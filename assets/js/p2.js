ProjectPlan = {

    init: function() {
        this.reload_fence_items();
        this.countDownTimer();
    },

    reload_fence_items: function() {

        var items = localStorage.getItem('custom_fence-section');

        for (let i = 0; i < items; i++) {


            var section = `<div class="border p-3 rounded mb-4 mx-2">
                <div class="row align-items-center mb-3">
                    <div class="col fw-bold">
                        SECTION ${i+1}  
                    </div>
                    <div class="col-auto text-end">
                        <a href="${base_url}?section=${i+1}" class="btn btn-sm text-uppercase btn-orange fw-bold">
                            <i class="fa-regular fa-pen-to-square me-1"></i>
                            <span>Edit Details</span>
                        </a>    
                    </div>
                </div>
                <div class="plan-item">
                    <div id="pp-${i}" class="dl-row"><div class="fc-result"><div class="fencing-panel-container"><div class="fc-loader-gif"></div></div></div></div>
                </div>
            </div> `;

            $('#fc-fence-list').append(section);

            if ((i + 1) % 3 === 0 & i != 1) {
                $('#fc-fence-list').append(`<div style="dl-page-separator"></div>`);
            }

            setTimeout(function() {
              ProjectPlan.reload_load_fencing_items(i);
              ProjectPlan.load_center_point(i);
            });

            if ($('#pp-' + i + ' .panel-post').hasClass('raked-panel-post')) {
                $('#pp-' + i).addClass('has-raked');
            }
        }

    },

    //----------------------------------------------------------------------------------

    load_center_point: function(tab) {


        var custom_fence_tab = localStorage.getItem('custom_fence-' + tab),
            custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [];

        var overall = `<div class="fc-overall">${(custom_fence_tab[0]?.calculateValue).toLocaleString()} Overall</div>`;

        $('#pp-' + tab + ' .fc-result').append(overall);

        // FIRST PANEL
        $('#pp-' + tab + ' .fencing-panel-container .fencing-panel-item')
            .first()
            .addClass('first-item');

        // LAST PANEL
        $('#pp-' + tab + ' .fencing-panel-container .fencing-panel-item')
            .last()
            .addClass('last-item');
        
        // 1 RAKED 
        if( $('#pp-' + tab + ' .fencing-panel-container .raked-panel .raked-panel-container').length == 1 ) {
            $('#pp-' + tab + ' .fencing-panel-container .raked-panel').addClass('first-item last-item');
        } else {
            $('#pp-' + tab + ' .fencing-panel-container .left_raked-panel')
                .first()
                .addClass('first-item');

            $('#pp-' + tab + ' .fencing-panel-container .right_raked-panel')
                .first()
                .addClass('last-item');            
        }

        // NO POST ON LEFT
        if( $('#pp-' + tab + ' .fencing-panel-container .left-panel-post.no-post').length ) {
            $('#pp-' + tab + ' .fencing-panel-container .fc-center-point')
                .first()
                .addClass('cp_no-post--left');
        }

        // NO POST ON RIGHT
        if( $('#pp-' + tab + ' .fencing-panel-container .right-panel-post.no-post').length ) {
            $('#pp-' + tab + ' .fencing-panel-container .fc-center-point')
                .last()
                .addClass('cp_no-post--right');
        }

        // remove Last center point in the middle panels
        $('#pp-' + tab + ' .fencing-panel-container .fencing-panel-item .fc-last-c-p')
            .not(":first")
            .not(":last").remove();


    },

    //----------------------------------------------------------------------------------

    reload_load_fencing_items: function(tab) {
        
        var center_post = FENCE.settings.item.center_point;

        var custom_fence_tab = localStorage.getItem('custom_fence-' + tab),
            custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [];

        var i = custom_fence_tab[0]?.style;

        var custom_fence = localStorage.getItem('custom_fence-' + tab + '-' + i),
            custom_fence = custom_fence ? JSON.parse(custom_fence) : [],
            info = fc_data[i];

        var fencingPanelContainer = '#pp-' + tab + ' .fencing-panel-container';

        $(fencingPanelContainer).html('').attr('data-type', info?.slug);

        var fence_height_filtered_data = info?.form?.filter(function(item) {
            return item.slug == 'fence_height';
        });

        if (fence_height_filtered_data) {
            $(fencingPanelContainer).addClass('custom-height');
        }

        var cf_data = { item: i, tab: tab },
            calc = calculate_fences(cf_data);

        if (!calc) {
            return;
        }

        var center_point = FENCE.get(i, 'post');

        for (let i = 0; i < calc.long_panel.count; i++) {

            mesurement = $('.measurement-box-number').val();

            var panel_number = i,
                panel_size = calc.long_panel.length,
                panel_unit = 'mm',
                data_key = "post_options";

            var panel_option_value = calc.selected_values.panel_option;

            if (panel_option_value.indexOf('full') !== -1) {
                panel_option_value = panel_option_value.split('_')[0];
            }

            // Fence height
            if (calc.fence_size.height) {
                panel_option_value = panel_option_value.concat("+", calc.fence_size.height);
            }

            var tpl = $('script[data-type="panel_item-' + info.panel_group + '"]').text()
                .replace(/{{data_key}}/gi, center_point)
                .replace(/{{center_point}}/gi, center_point)
                .replace(/{{center_post}}/gi, center_post)
                .replace(/{{panel_value}}/gi, panel_option_value)
                .replace(/{{panel_size}}/gi, panel_size + 'W')
                .replace(/{{panel_size_center}}/gi, (panel_size + center_point) + 'W')
                .replace(/{{panel_unit}}/gi, '<br>PANEL')
                .replace(/{{panel_number}}/gi, panel_number);

            if( panel_size > FENCE.get(info?.slug, 'minPanelWidthOnGate') ) {                         
                $(`#pp-${tab} .fencing-panel-container`).append(tpl);
            } 

            $('#pp-' + tab + ' .fencing-panel-item').css({ 'width': panel_size * 0.10 });
        }

        var tpl = $('script[data-type="panel_spacing-' + info.panel_group + '"]').text()
            .replace(/{{center_point}}/gi, center_point);

        $(`#pp-${tab} .fencing-panel-container`).append(tpl);


        // Check if panel is less than 1 - On Gate
        if($(`#pp-${tab} .single-panel, #pp-${tab} #panel-item-0`).length == 0) {
            $(`#pp-${tab} .panel-post`).after('<div id="panel-item-x" class="single-panel"></div>'); 
        }


        if (calc.short_panel.count) {

            for (let i = 0; i < 1; i++) {

                var panel_number = i,
                    panel_size = calc.short_panel.length,
                    panel_unit = FENCES.defaultValues.unit;
                panel_option_value = calc.selected_values.panel_option;

                if (panel_option_value.indexOf('full') !== -1) {
                    panel_option_value = panel_option_value.split('_')[0];
                }

                // Fence height
                if (calc.fence_size.height) {
                    panel_option_value = panel_option_value.concat("+", calc.fence_size.height);
                }

                var tpl = $('script[data-type="short_panel_item-' + info.panel_group + '"]').text()
                    .replace(/{{center_point}}/gi, center_point)
                    .replace(/{{center_post}}/gi, center_post)
                    .replace(/{{panel_size}}/gi, panel_size + 'W')
                    .replace(/{{panel_value}}/gi, panel_option_value)
                    .replace(/{{panel_size_center}}/gi, (panel_size + center_point) + 'W')
                    .replace(/{{panel_unit}}/gi, '<br>PANEL')
                    .replace(/{{panel_number}}/gi, panel_number);


                $(`#pp-${tab} .fencing-panel-container`).append(tpl);

                $('#pp-' + tab + ' .short-panel-item').attr('data-id', calc.long_panel.count + 1)
                    .attr('id', 'panel-item-' + (calc.long_panel.count + 1))
                    .css({ 'width': panel_size * 0.10 });

            }

            var tpl = $('script[data-type="panel_spacing-' + info.panel_group + '"]').text()
                .replace(/{{center_point}}/gi, center_point);

            $(`#pp-${tab} .fencing-panel-container`).append(tpl);

        }

        this.re_update_gate('edit', tab);

        // $('.fpsn-b:not(:first-child):not(:last-child)').remove();

        $(`#pp-${tab} .fencing-panel-container`).prepend('<div class="left_raked-panel raked-panel"></div>');
        $(`#pp-${tab} .fencing-panel-container`).append('<div class="right_raked-panel raked-panel"></div>');

        this.re_update_raked_panels(['left_raked', 'right_raked'], tab);

        // Panel off-cut
        if (calc.offcut_panel.count && calc.offcut_panel.length) {
            var tpl = $('script[data-type="offcut"]').text()
                .replace(/{{slug}}/gi, 'panel-offcut')
                .replace(/{{name}}/gi, 'Panel')
                .replace(/{{count}}/gi, calc.offcut_panel.count)
                .replace(/{{group}}/gi, info.panel_group)
                .replace(/{{width}}/gi, calc.offcut_panel.length);

            $(`#pp-${tab} .fencing-panel-container`).append(tpl);

            $('#pp-' + tab + ' .fencing-offcut.panel-offcut .offcut-body').css({ 'width': calc.offcut_panel.length * 0.10 });
        }

        // Custom gate off-cut
        if (calc.offcut_gate_panel.count && calc.offcut_gate_panel.length) {
            var tpl = $('script[data-type="offcut"]').text()
                .replace(/{{slug}}/gi, 'gate-offcut')
                .replace(/{{name}}/gi, 'Gate')
                .replace(/{{count}}/gi, calc.offcut_gate_panel.count)
                .replace(/{{group}}/gi, info.panel_group)
                .replace(/{{width}}/gi, calc.offcut_gate_panel.length);

            $(`#pp-${tab} .fencing-panel-container`).append(tpl);

            $('#pp-' + tab + ' .fencing-offcut.gate-offcut .offcut-body').css({ 'max-width': calc.offcut_gate_panel.length * 0.10 });
        }

        // Remove offcut - On Gate
        if( custom_fence_tab[0].mbn <= FENCE.get(info?.slug, 'minOnGate') ) {
             $('.fencing-offcut').remove();
        }


        if (calc.fence_size.height) {
            $('#pp-' + tab + ' .fencing-panel-item, #pp-' + tab + ' .short-panel-item, #pp-' + tab + ' .fencing-offcut .offcut-body').css({ 'height': calc.fence_size.height * 0.10 });

            $('#pp-' + tab + ' .panel-post.opt-1').css({ 'height': (calc.fence_size.height * 0.10) + 25 });
            $('#pp-' + tab + ' .panel-post.opt-2').css({ 'height': (calc.fence_size.height * 0.10) + 35 });
        }

    },

    //----------------------------------------------------------------------------------

    re_update_gate: function(action, tab) {

        var center_post = FENCE.settings.item.center_point;

        var custom_fence_tab = localStorage.getItem('custom_fence-' + tab),
            custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [];

        var i = custom_fence_tab[0]?.style;

        var custom_fence = localStorage.getItem('custom_fence-' + tab + '-' + i),
            custom_fence = custom_fence ? JSON.parse(custom_fence) : [],
            info = fc_data[i];

        var find_gate = custom_fence.filter(function(item) {
            return item.control_key == 'gate';
        });

        if (find_gate.length) {
            placement = find_gate[0]?.settings?.placement;
        } else {

            placement = 0;

        }

        var center_point = FENCE.get(i, 'post'),
            mesurement = $('.measurement-box-number').val();

        var cf_data = { item: i, tab: tab },
            calc = calculate_fences(cf_data);

        var panel_size = calc.gate.length,
            panel_unit = 'mm',
            gate_size = calc.gate.width
            panel_size_center = gate_size + 20 + 20 + center_point;



        if (action == 'add' || action == 'edit') {

            if (placement == -1 ) {
 
                var tpl = $('script[data-type="panel_gate-' + info.panel_group + '-r"]').text()
                    .replace(/{{center_point}}/gi, center_point)
                    .replace(/{{panel_size}}/gi, gate_size)
                    .replace(/{{panel_size_center}}/gi, panel_size_center)
                    .replace(/{{center_post}}/gi, center_post)
                    .replace(/{{panel_unit}}/gi, panel_unit);

                $('#pp-' + tab + ' #panel-item-0, #pp-' + tab + ' #panel-item-x').before(tpl);

            } else if (find_gate.length && placement >= 0) {

                var tpl = $('script[data-type="panel_gate-' + info.panel_group + '-l"]').text()
                    .replace(/{{center_point}}/gi, center_point)
                    .replace(/{{panel_size}}/gi, gate_size)
                    .replace(/{{panel_size_center}}/gi, panel_size_center)
                    .replace(/{{center_post}}/gi, center_post)
                    .replace(/{{panel_unit}}/gi, panel_unit);

                $('#pp-' + tab + ' #panel-item-' + placement).after(tpl);


            } else if (action == 'add' && placement == 0) {

                temp = $('script[data-type="panel_gate-' + info.panel_group + '-l"]');

                if($('.fencing-panel-items [data-key="panel_options"]').length) {
                    temp = $('script[data-type="panel_gate-' + info.panel_group + '-r"]')
                }

                var tpl = temp.text()
                    .replace(/{{center_point}}/gi, center_point)
                    .replace(/{{panel_size}}/gi, gate_size)
                    .replace(/{{panel_size_center}}/gi, panel_size_center)
                    .replace(/{{center_post}}/gi, center_post)
                    .replace(/{{panel_unit}}/gi, panel_unit);

                var panelID = $('[data-cart-key="panel_options"].fencing-panel-item').attr('id');

                $('#pp-' + tab + ' #'+panelID+', .fencing-panel-items .raked-panel-container').after(tpl);

            }

        }

/*
        if (action == 'add' || action == 'edit') {

            if (placement == -1 || $('#pp-' + tab + ' #panel-item-x').length ) {

                var tpl = $('script[data-type="panel_gate-' + info.panel_group + '-r"]').text()
                    .replace(/{{center_point}}/gi, center_point)
                    .replace(/{{panel_size}}/gi, gate_size)
                    .replace(/{{panel_size_center}}/gi, panel_size_center)
                    .replace(/{{panel_unit}}/gi, panel_unit);

                $('#pp-' + tab + ' #panel-item-0, #pp-' + tab + ' #panel-item-x').before(tpl);

            }


            if (find_gate.length && placement >= 0 || action == 'add' && placement == 0) {

                var tpl = $('script[data-type="panel_gate-' + info.panel_group + '-l"]').text()
                    .replace(/{{center_point}}/gi, center_point)
                    .replace(/{{panel_size}}/gi, gate_size)
                    .replace(/{{panel_size_center}}/gi, panel_size_center)
                    .replace(/{{panel_unit}}/gi, panel_unit);

                $('#pp-' + tab + ' #panel-item-' + placement).after(tpl);

            }

        }*/

        $('#pp-' + tab + ' .fencing-panel-gate').prepend('<span class="fc-gate-spacing fc-gate-left-spacing">20</span>')
            .append('<span class="fc-gate-spacing fc-gate-right-spacing">20</span>')
            .attr('data-cart-value', 1)
            .css({ 'max-width': calc.gate.length * 0.1 });

    },

    //----------------------------------------------------------------------------------

    re_update_raked_panels: function(side, tab) {

        var center_post = FENCE.settings.item.center_point;

        var custom_fence_tab = localStorage.getItem('custom_fence-' + tab),
            custom_fence_tab = custom_fence_tab ? JSON.parse(custom_fence_tab) : [];

        var i = custom_fence_tab[0]?.style;

        var custom_fence = localStorage.getItem('custom_fence-' + tab + '-' + i),
            custom_fence = custom_fence ? JSON.parse(custom_fence) : [],
            info = fc_data[i];

        var filtered_data = custom_fence.filter(function(item) {
            return item.control_key == 'add_step_up_panels';
        });

        var settings = filtered_data[0]?.settings;

        var cf_data = { item: i, tab: tab },
            calc = calculate_fences(cf_data);

        var center_post = FENCE.settings.item.center_point;

        $(side).each(function(k, v) {

            // Side
            var side_part = v.replace('_raked', ''),
                has_post = 'yes-post',
                center_point = FENCE.get(i, 'post');

            var filtered_side_data = custom_fence.filter(function(item) {
                return item.control_key == side_part + '_side';
            });

            if (filtered_side_data) {

                if (filtered_side_data.length) {
                    var has_post = $(filtered_side_data[0].settings).map(function(k, item) {
                        if (item.key == side_part + '_option') {
                            return item.val;
                        }
                    }).get().join("");
                }

                if (has_post != 'yes-post' && has_post) {
                    var has_post = 'no-post ' + side_part + '-panel-post ' + has_post;
                }
            }


            // Raked
            var filtered_settings = settings?.filter(function(item) {
                return item.key == v;
            });

            if (filtered_settings) {

                if (filtered_settings[0]?.val != 'none') {

                    if (side_part == 'left') {
                        panel_w = calc.left_raked.width;
                        panel_h = calc.left_raked.height;
                    } else {
                        panel_w = calc.right_raked.width;
                        panel_h = calc.right_raked.height;
                    }

                    var tpl = $('script[data-type="' + v + '-panel-' + info.panel_group + '"]').text()
                        .replace(/{{center_point}}/gi, center_point)
                        .replace(/{{panel_size}}/gi, panel_h)
                        .replace(/{{panel_unit}}/gi, panel_w)
                        .replace(/{{panel_size_center}}/gi, panel_w + center_point)
                        .replace(/{{post}}/gi, has_post)
                        .replace(/{{center_post}}/gi, center_post);

                    if (typeof panel_h !== "undefined") {
                        $('#pp-' + tab + ' .' + v + '-panel').html(tpl);
                    }

                }

            }

            if (side_part == 'left') {
                $('#pp-' + tab + ' .panel-post:not(.post-left):not(.post-right)').first()
                    .addClass('post-left panel-' + has_post)
                    .attr('data-key', "left_side")
                    .attr('post-side', "post_left");

                $('#pp-' + tab + ' .fencing-panel-spacing-number').first().addClass(has_post);
            }

            if (side_part == 'right') {
                $('#pp-' + tab + ' .panel-post:not(.post-left):not(.post-right)').last()
                    .addClass('post-right panel-' + has_post)
                    .attr('data-key', "right_side")
                    .attr('post-side', "post_right");

                $('#pp-' + tab + ' .fencing-panel-spacing-number').last().addClass(has_post);
            }

        });

        // Left Panel post
        var left_panel_post = $('#pp-' + tab + ' .left-panel-post.no-post span').text()
            .replace('(', '')
            .replace(')', '');

        $('#pp-' + tab + ' .left-panel-post.no-post span').text('(-' + left_panel_post + ')');

        // Right Panel Post
        var right_panel_post = $('#pp-' + tab + ' .right-panel-post.no-post span').text().replace('(', '').replace(')', '');
        $('#pp-' + tab + ' .right-panel-post.no-post span').text('(-' + right_panel_post + ')');

        $('#pp-' + tab + ' .no-post-swivel-bracket span').after('<span class="sw sw-top">SW</span><span class="sw sw-bot">SW</span>');

        FENCE.call('load_post_options_all', custom_fence, info, tab, calc);

        FENCE.call('load_post_options_first_last_values', custom_fence, info, tab, calc);

        $('#pp-' + tab + ' .fc-result').css({ 'padding': '' });

        if ($('#pp-' + tab + ' .raked-panel .fencing-raked-panel').length && $('#pp-' + tab + ' .fc-result').css('margin-top') != '70px') {
            $('#pp-' + tab + ' .fc-result').css({ 'padding-top': '40px' });
        } else {
            $('#pp-' + tab + ' .fc-result').css({ 'margin-top': '' });
        }

        $('#pp-' + tab + ' .raked-panel .fencing-panel-item').css({ 'width': 1200 * 0.10 });

    },

    //----------------------------------------------------------------------------------

    countDownTimer: function() {

        var timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

        // 10800000 ms = 3 hours
        setcountDownDate = new Date(Date.now() + 10800000).toLocaleString('en-US', {
            timeZone: timezone,
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });

        var getcountDownDate = localStorage.getItem('countdown-date');

        if (!getcountDownDate) {
            // Set the date we're counting down to
            localStorage.setItem('countdown-date', setcountDownDate);

            var getcountDownDate = localStorage.getItem('countdown-date');
        }


        var countDownDateFormat = new Date(getcountDownDate).getTime(),
            cont = 'fc-countdown-timer';

        // Update the count down every 1 second
        var x = setInterval(function() {

            // Get today's date and time
            var now = new Date().getTime();

            // Find the distance between now and the count down date
            var distance = countDownDateFormat - now;

            // Time calculations for days, hours, minutes and seconds
            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);

            // Display the result in the element with id="demo"
            document.getElementById(cont).innerHTML = hours + "hrs " + minutes + "mins " + seconds + "secs ";

            // If the count down is finished, write some text
            if (distance <= 1) {
                clearInterval(x);
                localStorage.removeItem('countdown-date');
                document.getElementById(cont).innerHTML = '<div class="fc-loader-gif"></div>';
                ProjectPlan.countDownTimer();
            }

        }, 1000);

    }

    //----------------------------------------------------------------------------------

}

ProjectPlan.init();
