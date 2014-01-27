/**
 * Copyright (c) Enalean SAS - 2014. All rights reserved
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

/**
 * Handle Tuleap modal
 */
(function ($) {
    tuleap.modal = {
        init: function() {
            var self = this;

            this.setSidePanelHeight();

            $('.tuleap-modal-side-panel:first-child .tuleap-modal-side-panel-grip').click(function() {
                self.toggleLeftSidePanel($(this));
            });
            $('.tuleap-modal-side-panel:last-child .tuleap-modal-side-panel-grip').click(function() {
                self.toggleRightSidePanel($(this));
            });
            $('.tuleap-modal-close').click(function() {
                self.closeModal();
            });
        },

        toggleLeftSidePanel: function(grip) {
            var panel_content = grip.siblings('.tuleap-modal-side-panel-content');
            panel_content.toggle();

            if (panel_content.css('display') === 'block') {
                var new_margin_left = parseInt($('.tuleap-modal').css('margin-left')) - parseInt(panel_content.css('width'));
                $('.tuleap-modal').css('margin-left', new_margin_left + 'px');
            } else {
                var new_margin_left = parseInt($('.tuleap-modal').css('margin-left')) + parseInt(panel_content.css('width'));
                $('.tuleap-modal').css('margin-left', new_margin_left + 'px');
            }
        },

        toggleRightSidePanel: function(grip) {
            grip.siblings('.tuleap-modal-side-panel-content').toggle();
        },

        setSidePanelHeight: function() {
            $('.tuleap-modal').css('height', $('.tuleap-modal-main-panel').outerHeight());
            $('.tuleap-modal-side-panel').css('height', $('.tuleap-modal-main-panel').outerHeight());
            $('.tuleap-modal-side-panel-grip > span').css('width', $('.tuleap-modal-main-panel').outerHeight());
        },

        closeModal: function() {
            $('.tuleap-modal-background, .tuleap-modal').fadeOut(150).remove();
        }
    }
})(jQuery);