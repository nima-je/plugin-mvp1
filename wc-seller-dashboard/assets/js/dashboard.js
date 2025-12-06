(function($){
    $(document).ready(function(){
        $('.wcsd-tab-button').on('click', function(e){
            e.preventDefault();
            var target = $(this).data('target');
            $('.wcsd-tab-button').attr('aria-pressed', 'false');
            $(this).attr('aria-pressed', 'true');
            $('.wcsd-tab-content').removeClass('is-active');
            $('#' + target).addClass('is-active');
        });
    });
})(jQuery);
