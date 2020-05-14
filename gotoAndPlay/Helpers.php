<?php
namespace gotoAndPlay;

use Exception;

class Helpers {

    protected static $version;

    protected static $manifest;

    protected static $map;

    public static function getManifest() {

        if (!isset(self::$manifest)) {
            try {
                $file = THEME_DIRECTORY . sprintf('inc%1$smanifest.json', DIRECTORY_SEPARATOR);
                if (file_exists($file)) {
                    self::$manifest = json_decode(file_get_contents($file));
                } else {
                    self::$manifest = false;
                }
            } catch (Exception $e) {
                self::$manifest = false;
            }
        }

        return self::$manifest;
    }

    public static function getVersion() {
        if (!isset(self::$version)) {
            $manifest = self::getManifest();
            if ($manifest && isset($manifest->version)) {
                self::$version = $manifest->version;
            } else {
                self::$version = false;
            }
        }

        return self::$version;
    }

    public static function getMap() {
        if (!isset(self::$map)) {
            try {
                $file = THEME_DIRECTORY . sprintf('inc%1$smap.json', DIRECTORY_SEPARATOR);
                if (file_exists($file)) {
                    self::$map = json_decode(file_get_contents($file));
                    if (is_object(self::$map)) {
                        foreach (self::$map as $key => $value) {
                            self::$map->{$key} = str_replace('\\', DIRECTORY_SEPARATOR, $value);
                        }
                    }
                } else {
                    self::$map = false;
                }
            } catch (Exception $e) {
                self::$map = false;
            }
        }

        return self::$map;
    }

    public static function parseLangCode($code) {
        switch (mb_strtolower($code)) {
            case "en":
                return "eng";

            break;
            case "ru":
                return "rus";

            break;
            case "fi":
                return "fin";

            break;
            case "et":
                return "est";

            break;
        }

        return $code;
    }

    private static function filterEmail($results, $link = true) {
        $email = false;
        if (is_array($results)) {
            foreach ($results as $result) {
                if (filter_var($result, FILTER_VALIDATE_EMAIL)) {
                    $email = $result;
                }
            }

            if (!$email) {
                $results = join('', $results);
            }
        }

        return $email ? '<noscript><span style="unicode-bidi:bidi-override;direction:rtl;">' . strrev($email) . '</span></noscript><script type="text/javascript">document.write(\'' . str_rot13($link ? '<a href="mailto:' . $email . '">' . $email . '</a>' : $email) . '\'.replace(/[a-zA-Z]/g, function(c){return String.fromCharCode((c<="Z"?90:122)>=(c=c.charCodeAt(0)+13)?c:c-26);}));</script>' : $results;
    }

    public static function replaceEmail($content, $post_id = false, $field = false) {
        if (!is_admin()) {
            if (isset($field['type']) && $field['type'] != 'url') {
                $content = preg_replace_callback("/([\w-?&;#~=\.\/]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?))/i", [Helpers::class, "filterEmail"], preg_replace("~<a .*?href=[\'|\"]mailto:(.*?)[\'|\"].*?>.*?</a>~", "$1", $content));
            }
        }

        return $content;
    }

    public static function getFormattedContent($content) {
        return apply_filters('the_content', $content);
    }

    public static function getImageId($post_id = false) {
        $image_id = false;
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        if ($post_id) {
            if (has_post_thumbnail($post_id)) {
                $image_id = get_post_thumbnail_id($post_id);
            }
        }

        return $image_id;
    }

    public static function getImage($image_id = false, $size = "full") {
        if (!$image_id) {
            $image_id = self::getImageId();
        }

        $image = wp_get_attachment_image_src($image_id, $size);
        if (is_array($image)) {
            return $image[0];
        } else {
            return false;
        }
    }

    public static function getImageSrcSet($image_id = false, $size = "full") {
        if (!$image_id) {
            $image_id = self::getImageId();
        }

        $srcset = wp_get_attachment_image_srcset($image_id, $size);
        if (!$srcset) {
            return Helpers::getImage($image_id, $size);
        } else {
            return $srcset;
        }
    }

    public static function getYoutubeUrl($url = '', $params = []) {

        if (is_array($params)) {
            $httpParams = http_build_query($params);
        } else {
            $httpParams = '';
        }

        preg_match("#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $url, $matches);
        if (is_array($matches) && count($matches) > 0) {
            return '//www.youtube.com/embed/' . $matches[0] . $httpParams;
        } else {
            return $url;
        }
    }

    public static function timeElapsedString($timestamp, $min = false) {
        $stamp = (time() - $timestamp);

        if ($stamp < 1) {
            return __('hetk tagasi', 'tavex');
        }

        $a = [
            (365 * 24 * 60 * 60) => 'year',
            (30 * 24 * 60 * 60) => 'month',
            (24 * 60 * 60) => 'day',
            (60 * 60) => 'hour',
            60 => 'minute',
            1 => 'second',
        ];
        if ($min) {
            $aPlural = $aSingular = [
                'year' => __('a', 'tavex'),
                'month' => __('k', 'tavex'),
                'day' => __('p', 'tavex'),
                'hour' => __('t', 'tavex'),
                'minute' => __('m', 'tavex'),
                'second' => __('s', 'tavex'),
            ];
        } else {
            $aSingular = [
                'year' => __('aasta', 'tavex'),
                'month' => __('kuu', 'tavex'),
                'day' => __('päev', 'tavex'),
                'hour' => __('tund', 'tavex'),
                'minute' => __('minut', 'tavex'),
                'second' => __('sekund', 'tavex'),
            ];
            $aPlural   = [
                'year' => __('aastat', 'tavex'),
                'month' => __('kuud', 'tavex'),
                'day' => __('päeva', 'tavex'),
                'hour' => __('tundi', 'tavex'),
                'minute' => __('minutit', 'tavex'),
                'second' => __('sekundit', 'tavex'),
            ];
        }

        foreach ($a as $secs => $str) {
            $d = ($stamp / $secs);
            if ($d >= 1) {
                $r = round($d);

                return $r . ($min ? '' : ' ') . ($r > 1 ? $aPlural[$str] : $aSingular[$str]) . ' ' . __('tagasi', 'tavex');
            }
        }
    }

    public static function parseFloat($number) {
        $number = floatval(str_replace(',', '.', $number));

        return $number;
    }

    public static function getFormattedPrice($price, $currency = true) {
        if (!is_numeric($price)) {
            $price = Helpers::parseFloat($price);
        }

        if (!$price) {
            $price = 0;
        }

        return (number_format($price, 2, '.', ' ') . ($currency ? ' ' . self::getCurrencySign() : ''));
    }

    public static function getCurrencySign() {
        return '€';
    }

    public static function getSocialMedia($url) {
        $socialMedia = get_field('social_media', 'options');
        $social      = [
            'title' => __('Jaga', 'kafo'),
            'logos' => [],
        ];

        if ($socialMedia) {
            foreach ($socialMedia as $socialItem) {
                $element           = [
                    'icon' => $socialItem['social_icon'],
                    'class' => $socialItem['social_type'],
                    'link' => $url,
                ];
                $social['logos'][] = $element;
            }
        }

        return $social;
    }

    public static function highlightText($string, $word) {
        return preg_replace("/" . $word . "/i", "<b>\$0</b>", $string);
    }

    public static function getHeroBackground($id = false) {
        $url = get_field('hero_video_url', $id);

        if(preg_match("/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*/", $url, $output_array)) {
            $video_id = $output_array[5];
        } else {
            $video_id = false;
        }

        $xl = get_field('hero_img_xl', $id);
        $lg = get_field('hero_img_lg', $id);
        $md = get_field('hero_img_md', $id);
        $sm = get_field('hero_img_sm', $id);
        $xs = get_field('hero_img_xs', $id);

        if ($xl) {
            $default = $xl;
        } else if ($lg) {
            $default = $lg;
        } else if ($md) {
            $default = $md;
        } else if ($sm) {
            $default = $sm;
        } else {
            $default = $xs;
        }

        return [
            'video' => $video_id,
            'xl' => Helpers::getImage($xl ? $xl : $default, '2560x1440'),
            'lg' => Helpers::getImage($lg ? $lg : $default, '1920x660'),
            'lg2x' => Helpers::getImage($lg ? $lg : $default, '3840x1320'),
            'md' => Helpers::getImage($md ? $md : $default, '1440x660'),
            'md2x' => Helpers::getImage($md ? $md : $default, '2880x1320'),
            'sm' => Helpers::getImage($sm ? $sm : $default, '768x560'),
            'sm2x' => Helpers::getImage($sm ? $sm : $default, '1536x1120'),
            'xs' => Helpers::getImage($xs ? $xs : $default, '320x52'),
            'xs2x' => Helpers::getImage($xs ? $xs : $default, '640x1040'),
        ];
    }

    public static function getEmoBackground($id = false) {

        $image = get_sub_field('image', $id);

        return [
            'lg' => Helpers::getImage($image, '1920x1110'),
            'lg2x' => Helpers::getImage($image, '3840x2220'),
            'md' => Helpers::getImage($image, '1440x1110'),
            'md2x' => Helpers::getImage($image, '2880x2220'),
            'sm' => Helpers::getImage($image, '768x830'),
            'sm2x' => Helpers::getImage($image, '1536x1660'),
            'xs' => Helpers::getImage($image, '520x730'),
            'xs2x' => Helpers::getImage($image, '1040x1460'),
        ];
    }

    public static function isShopPage() {
        return is_shop() || is_product_category() || is_product();
    }

    public static function validateRecaptcha() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

		static $requestIsValid = -1;
		if(-1 !== $requestIsValid)
			return $requestIsValid;

		if(empty($_POST['g-recaptcha-response']))
			return false;

		$response = wp_remote_retrieve_body(wp_remote_get( add_query_arg( array(
			'secret'   => '6Lcg1IcUAAAAALAJKt8bldKahGfk6SpADiTnBmnl',
			'response' => $_POST['g-recaptcha-response'],
			'remoteip' => $ip,
		), 'https://www.google.com/recaptcha/api/siteverify' ) ));


		if(empty($response) || !( $json = json_decode( $response ) ) || empty($json->success)){

			return $requestIsValid =  false;
		}

		return $requestIsValid = true;
	}

}
