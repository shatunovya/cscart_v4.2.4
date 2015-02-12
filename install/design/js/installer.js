(function(_, $){
    $(document).on('click', function(e)
    {
        var jelm = $(e.target);
        var elm = e.target;

        if (jelm.hasClass('cm-bootstrap-item')) {
            var item = jelm.closest('.item');
            var items_container = item.parent().parent();

            items_container.find('.item').removeClass('item-checked').switchAvailability(true, false);
            item.addClass('item-checked').switchAvailability(false, false);
        }

        if (jelm.hasClass('cm-load-themes')) {
            $('.cm-theme-item.hidden:lt(1)').removeClass('hidden');

            if ($('.cm-theme-item.hidden').length == 0) {
                jelm.prop('disabled', true);
            }

            // Prevent other actions
            return false;
        }
    });

    $(document).on('change', function(e)
    {
        var jelm = $(e.target);
        var elm = e.target;

        if (jelm.hasClass('cm-main-language')) {
            $('.cm-additional-lang-item').prop('disabled', false); // enable all checkboxes

            $('.cm-additional-lang-item').each(function () { // find auto checked and untick them
                self = $(this);
                if (self.data('caAutoTicked')) {
                    self.prop('checked', false).data('caAutoTicked', false);
                }
            });

            var selected = $('#additional_lang_' + jelm.val());
            selected.prop('disabled', true);

            if (!selected.prop('checked')) { // save, that it was unchecked
                selected.data('caAutoTicked', true);
            }
            selected.prop('checked', true);

            $('.cm-additional-lang-main').val(jelm.val()); // add selected main language to the languages array
        }
    });

    $.processNotifications = function(messages)
    {
        var i, class_name, message_elm;
        // Remove old notification
        $('.alert').remove();

        // Create a new ones
        for (i in messages) {
            var message = messages[i];
            
            if (message['type'] == 'E') {
                class_name = 'alert-error';
            } else if (message['type'] == 'W') {
                class_name = 'alert-block';
            } else {
                class_name = '';
            }
            
            if (message['message_state'] == 'E') {
                var close_button = '';
            } else {
                var close_button = '<button type="button" class="close" data-dismiss="alert">&times;</button>';
            }

            var message_code = '<div class="alert alert-block ' + class_name + '">' + close_button + message['message'] + '</div>';
            message_elm = $(message_code);
            message_elm.insertAfter($('a[name=section_' + message['extra'] + ']'));
        }

        if (message_elm) {
            $.scrollToElm(message_elm);
        }
    };

    $.ceEvent('on', 'ce.formajaxpost_setup_step_form', function(data, params) {

        if (typeof(data.force_redirection) != 'undefined') {
            $.redirect(data.force_redirection);
        }

        if (typeof(data.notifications) != 'undefined') {
            $.processNotifications(data.notifications);
        }
    });


}(Tygh, Tygh.$));
