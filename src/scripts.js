jQuery(document).ready(function($) {

    function wprs_pinyin_slug_check_select() {
        var check_el = $('select[name="wprs_pinyin_slug[translator_api]"]').val(),
            check_el2 = $('select[name="wprs_pinyin_slug[type]"]').val(),
            condition_el = $(
                'input[name="wprs_pinyin_slug[baidu_app_id]"], input[name="wprs_pinyin_slug[baidu_api_key]"]').
                parent().
                parent();

        // 根据是否需要发票显示
        if (parseInt(check_el) === 0 || parseInt(check_el2) !== 2) {
            condition_el.hide();
        } else {
            condition_el.show();
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