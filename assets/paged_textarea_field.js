(function($)
{
    var new_textarea = '<textarea name="fields[body][]" rows="15" cols="50" class="current"></textarea>';

    $(document).ready(function()
    {
        $('div.field-paged_textarea')
            .attr('tabindex', '0')
            .on('focusin', function(event)
            {
                if (!$(this).hasClass('focused')) {
                    $(this).addClass('focused').find('textarea.current').focus();
                }
                event.stopPropagation();
            })
            .on('focusout', function(event)
            {
                $(this).removeClass('focused');
            });
            //.on('action.paged_textarea', function(event, action, param)
        $('div.field-paged_textarea div.border-box')
            .resizable({'handles': 's'})
            .resize(function(event)
            {
                //alert($(this).find('div.ctrl-panel').height());
                var h = $(this).height() - $(this).find('div.control-panel').height();
                $(this).find('textarea').height(h - 4);
            })
            .on('redraw-page-buttons', function(event)
            {
                var html = '';
                $(this).find('textarea').each(function(i, textarea)
                {
                    var n = i + 1;
                    if ($(textarea).hasClass('current')) {
                        html += '<button type="button" class="page current">' + n + '</button>';
                    } else {
                        html += '<button type="button" class="page" data-action="page" data-page="' + i + '">' + n + '</button>';
                    }
                });
                $(this).find('div.pages').html(html);
            })
            .on('click', 'button', function(event)
            {
                var container = event.delegateTarget;
                var textareas = $(container).find('textarea');
                var current = $(textareas).filter('.current');
                var redraw = true;
                switch ($(this).data('action')) {
                    case 'add':
                        $(current).removeClass('current');
                        $(textareas).last().after(new_textarea);
                        break;
                    case 'add-before':
                        $(current).removeClass('current').before(new_textarea);
                        $(container).find('textarea.current').focus();
                        break;
                    case 'add-after':
                        $(current).removeClass('current').after(new_textarea);
                        $(container).find('textarea.current').focus();
                        break;
                    case 'remove':
                        var switch_to = $(current).prev('textarea');
                        if ($(switch_to).length == 0) {
                            switch_to = $(current).next('textarea');
                            if ($(switch_to).length == 0) {
                                break;
                            }
                        }
                        var remove = true;
                        if ($(current).val() != '') {
                            remove = confirm('Page is not empty. Remove?');
                        }
                        if (remove) {
                            $(current).remove();
                            $(switch_to).addClass('current').focus();
                        }
                        break;
                    case 'page':
                        $(current).removeClass('current');
                        $($(textareas)[$(this).data('page')]).addClass('current').focus();
                        break;
                    default:
                        redraw = false;
                        break;
                }
                if (redraw) $(container).trigger('redraw-page-buttons');

            })
            .trigger('redraw-page-buttons');
    });

})(jQuery.noConflict());