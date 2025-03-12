<?php

namespace WenprisePinyinSlug;

use Overtrue\Pinyin\Pinyin;

class Helpers
{

    /**
     * 获取设置的值
     *
     * @param string $section 选项所属的设置区域
     * @param string $option  选项名称
     * @param string $default 找不到选项值时的默认值
     *
     * @return mixed
     */
    public static function get_option($section, $option, $default = '')
    {
        $options = get_option($section);

        if (isset($options[ $option ])) {
            return $options[ $option ];
        }

        return $default;
    }


    /**
     * 转换拼音的通用功能
     *
     * @param        $name
     * @param string $type
     *
     * @return string 转换后的拼音
     */
    public static function slug_convert($name, $type = 'post')
    {
        $use_translator_api = (int)self::get_option('wprs_pinyin_slug', 'type', 0);

        $name = urldecode($name);

        if ($name == '自动草稿') {
            return $name;
        }

        $slug = '';
        error_log($name);

        if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $name)) {
            if ($use_translator_api === 2) {
                $slug = self::slug_translator($name);
            } else {
                $slug = self::slug_pinyin_convert($name);
            }
        }

        if (empty($slug)) {
            return $name;
        }

        return apply_filters('wenprise_converted_slug', sanitize_title($slug), $name, $type);
    }


    /**
     * 拼音转换方式
     *
     * @param $name
     *
     * @return string
     */
    public static function slug_pinyin_convert($name)
    {

        $divider = self::get_option('wprs_pinyin_slug', 'divider', '-');
        $type    = (int)self::get_option('wprs_pinyin_slug', 'type', 0);
        $length  = (int)self::get_option('wprs_pinyin_slug', 'length', 60);

        $pinyin = new Pinyin();

        if ($type === 0) {
            $slug = $pinyin->permalink($name, $divider, PINYIN_KEEP_ENGLISH);
        } elseif ($type === 1) {
            $slug = $pinyin->abbr($name, $divider, PINYIN_KEEP_ENGLISH);
        }elseif ($type === 3) {
            $slug = self::generate_by_deepseek($name);
        } else {
            $slug = self::slug_translator($name);
        }

        $slug = self::trim_slug($slug, $length, $divider);

        return strtolower($slug);

    }


    /**
     * 百度翻译转换方式
     *
     * @param $name
     *
     * @return string
     */
    public static function slug_translator($name)
    {
        $length = (int)self::get_option('wprs_pinyin_slug', 'length', 60);

        $api_url  = 'https://fanyi-api.baidu.com/api/trans/vip/translate';
        $app_id   = self::get_option('wprs_pinyin_slug', 'baidu_app_id', false);
        $app_key  = self::get_option('wprs_pinyin_slug', 'baidu_api_key', false);
        $app_salt = rand(10000, 99999);

        if ( ! $app_id || ! $app_key) {

            $result = false;

        } else {

            // 签名
            $str  = $app_id . $name . $app_salt . $app_key;
            $sign = md5($str);

            // 请求数据
            $args = [
                'q'     => $name,
                'from'  => 'auto',
                'to'    => 'en',
                'appid' => $app_id,
                'salt'  => $app_salt,
                'sign'  => $sign,
            ];

            // 发送请求
            $response = wp_remote_post($api_url, [
                    'method'      => 'POST',
                    'timeout'     => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking'    => true,
                    'headers'     => [],
                    'body'        => $args,
                    'cookies'     => [],
                ]
            );

            // 获取返回数据
            if (is_wp_error($response)) {
                $result = false;
            } else {
                $data = json_decode(wp_remote_retrieve_body($response));

                if ( ! isset($data->error_code)) {
                    $divider = self::get_option('wprs_pinyin_slug', 'divider', '-');

                    $result = $data->trans_result[ 0 ]->dst;
                    $result = str_replace(' ', $divider, $result);
                    $result = self::trim_slug($result, $length);
                } else {
                    $result = false;
                }
            }
        }

        if ( ! $result) {
            $result = self::slug_pinyin_convert($name);
        }

        error_log('启动翻译转换结果: ' . $result);

        return $result;
    }


    public static function generate_by_deepseek($name)
    {
        // 移除 HTML 标签
        $content = wp_strip_all_tags($name);

        // 构建提示词
        $prompt = "请根据以下文章标题和内容生成两个内容：\n\n";
        $prompt .= "1. 充分考虑 SEO 和语义化原则，根据文章标题生成在 URL 中使用的文章 slug\n";
        $prompt .= "文章标题：" . $content . "\n\n";
        $prompt .= "请按照以下格式返回结果：\n";
        $prompt .= "[Article Slug]\n";

        // 调用 API
        $response = self::call_deepseek_api($prompt);

        if (is_wp_error($response)) {
            return $response;
        }

        // 解析响应
        return $response['choices'][0]['message']['content'];
    }


    /**
     * 调用 DeepSeek API
     *
     * @param string $user_message 用户消息
     *
     * @return array|\WP_Error 响应或错误
     */
    public static function call_deepseek_api($user_message)
    {
        $api_key = self::get_option('wprs_pinyin_slug', 'deepseek_api_key', false);;

        if (empty($api_key)) {
            return new \WP_Error('missing_api_key', '请先设置 DeepSeek API 密钥');
        }

        // 准备请求头
        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ];

        // 准备请求体
        $body = [
            'model'    => 'deepseek-chat',
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => 'You are a helpful assistant.',
                ],
                [
                    'role'    => 'user',
                    'content' => $user_message,
                ],
            ],
            'stream'   => false,
        ];

        // 发送请求
        $response = wp_remote_post(
            'https://api.deepseek.com/chat/completions',
            [
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking'    => true,
                'headers'     => $headers,
                'body'        => json_encode($body),
                'sslverify'   => true,
            ]
        );

        // 检查是否有错误
        if (is_wp_error($response)) {
            return $response;
        }

        // 获取响应代码
        $response_code = wp_remote_retrieve_response_code($response);

        // 检查响应代码
        if ($response_code !== 200) {
            return new \WP_Error('deepseek_api_error', '请求失败，状态码: ' . $response_code);
        }

        // 获取响应体
        $response_body = wp_remote_retrieve_body($response);

        // 解析 JSON 响应
        return json_decode($response_body, true);
    }


    /**
     * 裁剪文本
     *
     * @param string $input
     * @param int    $length
     * @param string $divider
     * @param bool   $strip_html
     *
     * @return bool|string
     */
    public static function trim_slug($input, $length, $divider = '-', $strip_html = true)
    {

        // strip tags, if desired
        if ($strip_html) {
            $input = strip_tags($input);
        }

        // 如果字符串已经比裁剪程度短了，无需再裁剪，直接返回。
        if ( ! $length || strlen($input) <= $length) {
            return $input;
        }

        $trimmed_text = substr($input, 0, $length);

        // 查找最后截取字符串的最后一个分隔符位置
        if ($divider !== '') {
            $last_space = strrpos(substr($input, 0, $length), $divider);

            if ($last_space) {
                $trimmed_text = substr($input, 0, $last_space);
            }
        }

        return $trimmed_text;
    }
}
