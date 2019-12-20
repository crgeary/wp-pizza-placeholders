<?php

/**
 * Plugin Name: Pizza Placeholders
 * Plugin URI: https://github.com/crgeary/wp-pizza-placeholders
 * Author: Christopher Geary
 * Author URI: https://crgeary.com
 * Description: Uses <a href="https://img.pizza">img.pizza</a> placeholders, to make development have more pizza.
 * Version: 1.0.0
 */

namespace CrGeary\PizzaPlaceholders;

class PizzaPlaceholders
{
    /**
     * Singleton Instance
     *
     * @var PizzaPlaceholders|null
     */
    protected static $instance = null;

    /**
     * Create a new instance of the PizzaPlaceholders class
     *
     * @return PizzaPlaceholders
     */
    public static function instance()
    {
        if (!is_a(PizzaPlaceholders::$instance, PizzaPlaceholders::class)) {
            PizzaPlaceholders::$instance = new PizzaPlaceholders;
        }
        return PizzaPlaceholders::$instance;
    }

    /**
     * Hook all the things to WordPress
     * 
     * @return void
     */
    protected function __construct()
    {
        if (is_admin()) {
            return;
        }

        add_filter('has_post_thumbnail', '__return_true');

        add_filter('post_thumbnail_html', [$this, 'alterPostThumbnailHtml'], 10, 5);

        add_filter('wp_get_attachment_image_src', [$this, 'alterAttachmentImageSrc'], 10, 4);
    }

    /**
     * Pizza hack for `get_the_post_thumbnail`
     *
     * @param string $html
     * @param int $post_id
     * @param string $post_thumbnail_id
     * @param string|array $size
     * @param string $attr
     *
     * @return string
     */
    public function alterPostThumbnailHtml($html, $post_id, $post_thumbnail_id, $size, $attr)
    {
        return wp_get_attachment_image(null, $size);
    }

    /**
     * Pizza hack for `wp_get_attachment_image_src`
     *
     * @param array $image
     * @param int $attachment_id
     * @param array|string $size
     * @param bool $icon
     *
     * @return bool|array
     */
    public function alterAttachmentImageSrc($image, $attachment_id, $size, $icon)
    {
        if ($image) {
            $image[0] = $this->getPizzaUrl($size[0], $size[1], $attachment_id);
            return $image;
        }

        if (is_string($size)) {
            $size = $this->getImageSize($size);
        }

        if (is_array($size) && !empty($size)) {
            return [
                $this->getPizzaUrl($size[0], $size[1], $attachment_id),
                $size[0],
                $size[1],
                true
            ];
        }

        return false;
    }

    /**
     * Return the dimensions of a given image size
     *
     * @param string $size
     *
     * @return array|null
     */
    protected function getImageSize($size)
    {
        if ('full' === $size) {
            $size = 'large';
        }

        $wp_additional_image_sizes = wp_get_additional_image_sizes();
        $get_intermediate_image_sizes = get_intermediate_image_sizes();

        foreach ($get_intermediate_image_sizes as $_size)
        {
            if ($size !== $_size) {
                continue;
            }

            if (in_array($_size, ['thumbnail', 'medium', 'large'])) {
                return [
                    (int)get_option($_size.'_size_w'),
                    (int)get_option($_size.'_size_h')
                ];
            } elseif (isset($wp_additional_image_sizes[$_size])) {
                return [
                    (int)$wp_additional_image_sizes[$_size]['width'],
                    (int)$wp_additional_image_sizes[$_size]['height']
                ];
            }
        }

        return null;
    }

    /**
     * Generate a random hash, based on the attachment ID
     *
     * @param id $attachment_id
     *
     * @return string
     */
    protected function getRandomHash($attachment_id = null)
    {
        if (is_null($attachment_id)) {
            return md5(mt_rand());
        }

        return md5('attachment:'.$attachment_id);
    }

    /**
     * Return a pizza
     *
     * @param id $width
     * @param id $height
     * @param id $attachment_id
     *
     * @return string
     */
    protected function getPizzaUrl($width, $height, $attachment_id = null)
    {
        return sprintf(
            'https://img.pizza/%d/%d?%s',
            $width,
            $height,
            $this->getRandomHash($attachment_id)
        );
    }
}

PizzaPlaceholders::instance();
