$(function() {

    ajaxMoreSetup();

    function ajaxMoreSetup()
    {
        $('a.tao-ajax-more').unbind('click').click(function() {
            ajaxMore($(this));
            return false;
        });
    }

    function ajaxMore($link)
    {
        var $url = $link.attr('data-url');
        $link.addClass('disabled');
        $.get($url, function(data) {
            $link.replaceWith(data);
            ajaxMoreSetup();
        })
    }
});

