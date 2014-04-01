/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

!function($) {
    var width_collapsed = '45px';
    var width_expanded  = '200px';
    var api;
    var throttleTimeout;

    function handleProjectTooltip() {
        $('.project-title-container').popover({
            html: true,
            placement: 'right',
            container: 'body',
            trigger: 'hover',
            delay: {show: 1000, hide: 300}
        });
    }

    function getSidebarUserPreference() {
        return localStorage.getItem('sidebar-size');
    }

    function setSidebarUserPreference(new_width) {
        localStorage.setItem('sidebar-size', new_width);
    }

    function updateSidebarWidth(new_width, duration) {
        $('.sidebar-nav').animate({
            width: new_width
        }, duration, updateNavbar(new_width));
        $('.sidebar-nav li a').css({
            width: parseInt(new_width) + 'px'
        });
        $('.main').animate({
            marginLeft: new_width
        }, duration);
    }

    function updateNavbar(new_width) {
        if (new_width == width_expanded) {
            $('.navbar .nav:first-child li.current-project').remove();
        } else {
            $('.navbar .nav:first-child').prepend('<li class="current-project">' + $('.project-title-container').html() + '</li>');
        }
    }

    function updateSidebarIcon(direction, show_only_icon) {
        $('.sidebar-collapse').removeClass('icon-chevron-left icon-chevron-right').addClass('icon-chevron-' + direction);
    }

    function updateSidebarTitle(show_only_icon) {
        if (show_only_icon) {
            $('.project-title-container').css({
                visibility: 'hidden'
            });
        } else {
            $('.project-title-container').css({
                visibility: 'visible'
            });
        }
    }

    function updateSidebarServices(show_only_icon, duration) {
        if (show_only_icon) {
            $('.sidebar-about').hide();
            $('.sidebar-nav li a > span').hide();
            $('.sidebar-nav li a').tooltip('enable');
        } else {
            $('.sidebar-nav li a > span').show();
            $('.sidebar-nav li a').tooltip('disable');
            $('.sidebar-about').show(duration);
        }
    }

    function sidebarCollapseEvent(duration) {
        var current_size   = getSidebarUserPreference();
        var new_size       = width_expanded;
        var new_direction  = 'left';
        var show_only_icon = false;

        if (current_size == width_expanded) {
            new_size       = width_collapsed
            new_direction  = 'right';
            show_only_icon = true;
        }

        setSidebarUserPreference(new_size);

        updateSidebarTitle(show_only_icon);
        updateSidebarWidth(new_size, duration);
        updateSidebarIcon(new_direction, show_only_icon);
        updateSidebarServices(show_only_icon, duration);
        updateCustomScrollbar();
    }

    function updateCustomScrollbar() {
        var current_size = getSidebarUserPreference();

        if (current_size == width_expanded) {
            api.destroy();
            throttleTimeout = null;
            initCustomScrollbar();
        }
    }

    function initCustomScrollbar() {
        $('.sidebar-nav').jScrollPane({
            verticalGutter: 0
        });
        api = $('.sidebar-nav').data('jsp');

        $(window).bind('resize', function() {
            if (! throttleTimeout) {
                throttleTimeout = setTimeout(updateCustomScrollbar, 50);
            }
        });
    }

    $(document).ready(function() {
        var current_size = getSidebarUserPreference();

        if ($('.sidebar-nav').length > 0) {
            handleProjectTooltip();
            initCustomScrollbar();

            $('.sidebar-nav li a').tooltip({
                placement: 'right',
                container: 'body'
            });

            if (current_size == null || current_size == width_expanded) {
                updateSidebarTitle(false);
                updateSidebarWidth(width_expanded, 0);
                updateSidebarIcon('left', false);
                updateSidebarServices(false, 100);
            } else {
                updateSidebarTitle(true);
                updateSidebarWidth(width_collapsed, 0);
                updateSidebarIcon('right', true);
                updateSidebarServices(true, 100);
            }

            $('.sidebar-collapse').click(function() {
                sidebarCollapseEvent(100);
            });
        }
    });
}(window.jQuery);
