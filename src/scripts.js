jQuery(document).ready(function($) {

    function wprs_pinyin_slug_check_select() {
        var selected_type = $('select[name="wprs_pinyin_slug[type]"]').val(),
            baidu_translate_el = $('input[name="wprs_pinyin_slug[baidu_app_id]"], input[name="wprs_pinyin_slug[baidu_api_key]"]').parent().parent();
        var deepseek_el = $('input[name="wprs_pinyin_slug[deepseek_api_key]"]').parent().parent();
        var type = parseInt(selected_type);

        if (type === 2) {
            baidu_translate_el.show();
            deepseek_el.hide();
        } else if(type === 3) {
            baidu_translate_el.hide();
            deepseek_el.show();
        } else {
            baidu_translate_el.hide();
            deepseek_el.hide();
        }
    }

    wprs_pinyin_slug_check_select();

    $('select[name="wprs_pinyin_slug[translator_api]"]').change(function() {
        wprs_pinyin_slug_check_select();
    });

    $('select[name="wprs_pinyin_slug[type]"]').change(function() {
        wprs_pinyin_slug_check_select();
    });

});