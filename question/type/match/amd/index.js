require(['jquery'], function($) {
    $(document).ready(function() {
        window.console.log("hello");
        $('.matching').each(function(i, d) {
            var b = $(d).find('button');
            $(b).html($(d).find('[selected=selected]').html());
            var c = $(d).attr('class').split(' ')[3];
            if ($('select.' + c).is(':disabled')) {
                $(b).attr('disabled', 'disabled');
            } else {
                $(this).find('li').click(function() {
                    $(b).html($(this).html());
                    var v = $(this).attr('value');
                    // eslint-disable-next-line no-unused-expressions
                    $('select.' + c).find('option:selected').removeAttr('selected');
                    var opt = $('select.' + c).find('option')[v];
                    $(opt).attr('selected', 'selected');
                    window.console.log($(d).find('[selected=selected]').removeAttr('selected'));
                    $(this).attr('selected', 'selected');
                });
        }

        });
    });
});