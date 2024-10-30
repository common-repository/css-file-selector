(function($) {
    var last_select;

    function refresh_state()
    {
        last_select = $('[name="gil_css_file_selector_file[]"]').last();

        if (last_select.val() == '') {
            //If no option selected, we can't add other select element
            $('.add-select-css').attr('disabled', 'true').removeClass('button-primary').addClass('button-default');
        }
        last_select.change(function()
        {
            if ($(this).val() != '') {
                $('.add-select-css').removeAttr('disabled').removeClass('button-default').addClass('button-primary');
            }
        });
    }

    if (typeof jQuery.fn.jquery === 'function') {
        $('body').on('change', '[name="gil_css_file_selector_file[]"]', function()
        {
            if ($(this).val() == '' && $('[name="gil_css_file_selector_file[]"]').length > 1) {
                $(this).remove();
            }

            refresh_state();
        });
    } else {
        $('[name="gil_css_file_selector_file[]"]').live('change', function()
        {
            if ($(this).val() == '' && $('[name="gil_css_file_selector_file[]"]').length > 1) {
                $(this).remove();
            }

            refresh_state();
        });
    }

    $('.add-select-css').click(function(e) {
        e.preventDefault();
        if (!e.target.disabled) {
            var clone = last_select.clone();
            clone.find('option').first().attr('selected', 'true');
            last_select.after(clone);
            refresh_state();
        }
    });

    refresh_state();
}(jQuery));