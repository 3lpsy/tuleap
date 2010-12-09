/**
 * Copyright (c) STMicroelectronics, 2010. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

var Codendi_RTE_Light = Class.create({
    initialize: function (element) {
        this.element = $(element);
        this.rte     = false;

        // If there is an element named after the editor's id + label add
        // a toggler on it
        if ($(element+'_label')) {
            var toggle = Builder.node('a', {'class': 'rte_toggle', href: '#'});
            toggle.appendChild(document.createTextNode(' Rich text editor '));

            // Must use Event.observe(toggle... instead of toggle.observe(...
            // Otherwise IE cannot manage it. Oo
            Event.observe(toggle, 'click', this.toggle.bindAsEventListener(this));

            $(element+'_label').appendChild(toggle);
        }

        // Can also double click on text area to activate HTML edition
        this.element.observe('dblclick', this.toggle.bindAsEventListener(this));
    },
    init_rte: function() {
        tinyMCE.init({
                // General options
                mode : "exact",
                elements : this.element.id,
                theme : "advanced",

                // Inherit language from Codendi default (see Layout class)
                language : useLanguage,

                // Theme options
                theme_advanced_toolbar_location : "top",
                theme_advanced_toolbar_align : "left",
                theme_advanced_statusbar_location : "bottom",
                theme_advanced_buttons1 : "bold,italic,blockquote,formatselect,image,|,bullist,numlist,|,link,unlink,|,code",
                theme_advanced_buttons2 : "",
                theme_advanced_buttons3 : "",
                theme_advanced_resizing : true,
                theme_advanced_blockformats : "p,pre",
                
                codendi:null //cheat to not have to remove the last comma in elements above. #*%@ IE !
        });
        this.rte = true;
    },
    toggle: function() {
        if (!this.rte) {
            this.init_rte();
        } else {
            if (!tinyMCE.get(this.element.id)) {
                tinyMCE.execCommand("mceAddControl", false, this.element.id);
            } else {
                tinyMCE.execCommand("mceRemoveControl", false, this.element.id);
            }
        }
    }
});

var Codendi_RTE_Light_Tracker_FollowUp = Class.create(Codendi_RTE_Light, {
    initialize: function ($super, element, format) {
        $super(element);

        var label = $(element+'_label');

        // This div contains comment format selection buttons
        var div = Builder.node('div', {'class' : 'rte_format'});
        var bold = document.createElement("b");
        bold.appendChild(document.createTextNode("Comment format : "));
        div.appendChild(bold);

        // Add a radio button that tells that the content format is text
        // The value is defined in Artifact class.
        var text_button = Builder.node('input', {'name'     : 'comment_format',
                                                 'type'     : 'radio',
                                                 'value'    : '0',
                                                 'checked'  : 'checked',
                                                 'id'       : 'comment_format_text'});
        div.appendChild(text_button);
        div.appendChild(document.createTextNode('Text'));

        // Add a radio button that tells that the content format is HTML
        // The value is defined in Artifact class.
        var html_button = Builder.node('input', {'name' : 'comment_format',
                                                 'type' : 'radio',
                                                 'value': '1',
                                                 'id'   : 'comment_format_html'});
        div.appendChild(html_button);
        div.appendChild(document.createTextNode('HTML'));

        label.appendChild(div);

        // This div is used to clear the CSS of the pervious div
        var div_clear = Builder.node('div', {'class' : 'rte_clear'});
        label.appendChild(div_clear);

        if (format == 'html') {
            this.switchButtonToHtml();
        } else {
            $('comment_format_text').checked = true;
        }
        
    },

    toggle: function ($super) {
        this.switchButtonToHtml();
        $super();
    },

    /**
     * Disable the radio button that tells that the content is text
     * Check the radio button that tells that the content is HTML
     */
    switchButtonToHtml: function () {
        $('comment_format_text').disabled = true;
        $('comment_format_html').checked  = true;
    }
});
