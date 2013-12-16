/**
 * Resource and activity toolbox class.
 *
 * This class is responsible for managing AJAX interactions with activities and resources
 * when viewing a course in editing mode.
 *
 * @module moodle-course-toolboxes
 * @namespace M.course.toolboxes
 */

/**
 * Section toolbox class.
 *
 * This class is responsible for managing AJAX interactions with sections
 * when viewing a course in editing mode.
 *
 * @class section
 * @constructor
 * @extends M.course.toolboxes.toolbox
 */
var SECTIONTOOLBOX = function() {
    SECTIONTOOLBOX.superclass.constructor.apply(this, arguments);
};

Y.extend(SECTIONTOOLBOX, TOOLBOX, {
    /**
     * Initialize the section toolboxes module.
     *
     * Updates all span.commands with relevant handlers and other required changes.
     *
     * @method initializer
     * @protected
     */
    initializer : function() {
        M.course.coursebase.register_module(this);

        // Section Highlighting.
        Y.delegate('click', this.toggle_highlight, SELECTOR.PAGECONTENT, SELECTOR.SECTIONLI + ' ' + SELECTOR.HIGHLIGHT, this);

        // Section Visibility.
        Y.delegate('click', this.toggle_hide_section, SELECTOR.PAGECONTENT, SELECTOR.SECTIONLI + ' ' + SELECTOR.SHOWHIDE, this);

        // Section delete
        Y.delegate('click', this.delete_section, SELECTOR.PAGECONTENT, SELECTOR.SECTIONLI + ' ' + SELECTOR.DELETESECTION, this);
    },

    toggle_hide_section : function(e) {
        // Prevent the default button action.
        e.preventDefault();

        // Get the section we're working on.
        var section = e.target.ancestor(M.course.format.get_section_selector(Y)),
            button = e.target.ancestor('a', true),
            hideicon = button.one('img'),

        // The value to submit
            value,

        // The text for strings and images. Also determines the icon to display.
            action,
            nextaction;

        if (!section.hasClass(CSS.SECTIONHIDDENCLASS)) {
            section.addClass(CSS.SECTIONHIDDENCLASS);
            value = 0;
            action = 'hide';
            nextaction = 'show';
        } else {
            section.removeClass(CSS.SECTIONHIDDENCLASS);
            value = 1;
            action = 'show';
            nextaction = 'hide';
        }

        var newstring = M.util.get_string(nextaction + 'fromothers', 'format_' + this.get('format'));
        hideicon.setAttrs({
            'alt' : newstring,
            'src'   : M.util.image_url('i/' + nextaction)
        });
        button.set('title', newstring);

        // Change the highlight status
        var data = {
            'class' : 'section',
            'field' : 'visible',
            'id'    : Y.Moodle.core_course.util.section.getId(section.ancestor(M.course.format.get_section_wrapper(Y), true)),
            'value' : value
        };

        var lightbox = M.util.add_lightbox(Y, section);
        lightbox.show();

        this.send_request(data, lightbox, function(response) {
            var activities = section.all(SELECTOR.ACTIVITYLI);
            activities.each(function(node) {
                var button;
                if (node.one(SELECTOR.SHOW)) {
                    button = node.one(SELECTOR.SHOW);
                } else {
                    button = node.one(SELECTOR.HIDE);
                }
                var activityid = Y.Moodle.core_course.util.cm.getId(node);

                // NOTE: resourcestotoggle is returned as a string instead
                // of a Number so we must cast our activityid to a String.
                if (Y.Array.indexOf(response.resourcestotoggle, "" + activityid) !== -1) {
                    M.course.resource_toolbox.handle_resource_dim(button, node, action);
                }
            }, this);
        });
    },

    /**
     * Toggle highlighting the current section.
     *
     * @method toggle_highlight
     * @param {EventFacade} e
     */
    toggle_highlight : function(e) {
        // Prevent the default button action.
        e.preventDefault();

        // Get the section we're working on.
        var section = e.target.ancestor(M.course.format.get_section_selector(Y));
        var button = e.target.ancestor('a', true);
        var buttonicon = button.one('img');

        // Determine whether the marker is currently set.
        var togglestatus = section.hasClass('current');
        var value = 0;

        // Set the current highlighted item text.
        var old_string = M.util.get_string('markthistopic', 'moodle');
        Y.one(SELECTOR.PAGECONTENT)
            .all(M.course.format.get_section_selector(Y) + '.current ' + SELECTOR.HIGHLIGHT)
            .set('title', old_string);
        Y.one(SELECTOR.PAGECONTENT)
            .all(M.course.format.get_section_selector(Y) + '.current ' + SELECTOR.HIGHLIGHT + ' img')
            .set('alt', old_string)
            .set('src', M.util.image_url('i/marker'));

        // Remove the highlighting from all sections.
        Y.one(SELECTOR.PAGECONTENT).all(M.course.format.get_section_selector(Y))
            .removeClass('current');

        // Then add it if required to the selected section.
        if (!togglestatus) {
            section.addClass('current');
            value = Y.Moodle.core_course.util.section.getId(section.ancestor(M.course.format.get_section_wrapper(Y), true));
            var new_string = M.util.get_string('markedthistopic', 'moodle');
            button
                .set('title', new_string);
            buttonicon
                .set('alt', new_string)
                .set('src', M.util.image_url('i/marked'));
        }

        // Change the highlight status.
        var data = {
            'class' : 'course',
            'field' : 'marker',
            'value' : value
        };
        var lightbox = M.util.add_lightbox(Y, section);
        lightbox.show();
        this.send_request(data, lightbox);
    },
    delete_section : function(e) {

        // Prevent the default button action
        e.preventDefault();

        // Get the section we're working on
        var section = e.target.ancestor(M.course.format.get_section_selector(Y));

        confirmstring = M.util.get_string('deletesectioncheck', 'moodle');

        // Confirm element removal
        if (!confirm(confirmstring)) {
            return false;
        }

        // Change the highlight status
        var data = {
            'class' : 'section',
            'field' : 'delete',
            'id'    : Y.Moodle.core_course.util.section.getId(section.ancestor(M.course.format.get_section_wrapper(Y), true)),
        };

        var lightbox = M.util.add_lightbox(Y, section);
        lightbox.show();

        this.send_request(data, lightbox, function(responseText) {

            var parentEl = section.ancestor();

            var sectionlist = Y.one(SELECTOR.PAGECONTENT).all(M.course.format.get_section_selector(Y))
            var sectionCount = sectionlist.length;

            var i = 0;
            var found = -1;
            sectionlist.each(function(node) {
                if (found > -1) {
                    sectionlist[i - 1] = sectionlist[i];
                    if (i == sectionCount - 1) {
                        sectionlist = sectionlist.slice(0, -1);
                    }
                } else if (node == section) {
                    found = i;
                }
                i++;
            });
            // Remove any extra text nodes to keep DOM clean.
            var kids = parentEl.all();

            for (var i=0; i<kids.length; i++) {
                if (kids[i].nodeType == 3) {
                    YAHOO.log('Removed extra text node.');
                    parentEl.removeChild(kids[i]);
                }
            }

            parentEl.removeChild(section);

            M.course.format.process_sections(Y, sectionlist, responseText, found, i-1);
            M.course.format.swap_sections(Y, found, found+1);
        });
    }
}, {
    NAME : 'course-section-toolbox',
    ATTRS : {
    }
});

M.course.init_section_toolbox = function(config) {
    return new SECTIONTOOLBOX(config);
};
