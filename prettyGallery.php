<?php

/*
  Plugin Name: prettyGallery
  Plugin URI: http://webbyscots.com/
  Description: A simple plugin that replaces wp Gallery Shortcode with a prettyPhoto gallery, whereby the first image is the only
 * one showing and acts as an anchor to open the prettyPhotoGallery
  Author: Liam Bailey (WebbyScots)
  Author URI: http://webbyscots.com/author/liambailey/

  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
$ppGall = new prettyGallery();
class prettyGallery {

    function __construct() {
        define('WSWPPGPATH', trailingslashit(dirname(__FILE__)));
        define('WSWPDEBUG_MODE',false);
        define('WSWPPGURL', trailingslashit(plugin_dir_url(__FILE__)));
        add_action('wp_enqueue_scripts',array(&$this,'wp_enqueue_scripts'));
        remove_shortcode('gallery', 'gallery_shortcode');
        add_shortcode('gallery', array(&$this, 'gallery_shortcode_delux'));
    }
    
    function wp_enqueue_scripts() {
        wp_enqueue_script( 'prettyphoto', WSWPPGURL . 'js/jquery.prettyPhoto.min.js', array( 'jquery' ), '3.1.4',true );
        wp_enqueue_script( 'prettygallery', WSWPPGURL . 'js/prettyGallery.js',array('prettyphoto'),'1.0.0',true);
        wp_enqueue_style( 'prettyphoto',  WSWPPGURL . 'css/prettyPhoto.css', false, '3.1.4', 'screen' );
    }


    /**
     * The Gallery shortcode Deluxe.
     *
     * Completely ripped off from the WP Gallery Shortcode function. 
     * Thank god it is open source - but adds the rel attribute needed
     * to make our galleries play with the prettyPhoto media plugin.
     *
     * @param array $attr Attributes of the shortcode.
     * @return string HTML content to display gallery.
     */
    function gallery_shortcode_delux($attr) {
        $post = get_post();

        static $instance = 0;
        $instance++;

        if (!empty($attr['ids'])) {
            // 'ids' is explicitly ordered, unless you specify otherwise.
            if (empty($attr['orderby']))
                $attr['orderby'] = 'post__in';
            $attr['include'] = $attr['ids'];
        }
        
        $is_active = (isset($attr['link']) && 'file' == $attr['link']);

        // Allow plugins/themes to override the default gallery template.
        $output = apply_filters('post_gallery', '', $attr);
        if ($output != '')
            return $output;

        // We're trusting author input, so let's at least make sure it looks like a valid orderby statement
        if (isset($attr['orderby'])) {
            $attr['orderby'] = sanitize_sql_orderby($attr['orderby']);
            if (!$attr['orderby'])
                unset($attr['orderby']);
        }

        extract(shortcode_atts(array(
            'order' => 'ASC',
            'orderby' => 'menu_order ID',
            'id' => $post->ID,
            'itemtag' => 'dl',
            'icontag' => 'dt',
            'captiontag' => 'dd',
            'columns' => 3,
            'size' => 'thumbnail',
            'include' => '',
            'exclude' => ''
                        ), $attr));

        $id = intval($id);
        if ('RAND' == $order)
            $orderby = 'none';

        if (!empty($include)) {
            $_attachments = get_posts(array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby));

            $attachments = array();
            foreach ($_attachments as $key => $val) {
                $attachments[$val->ID] = $_attachments[$key];
            }
        } elseif (!empty($exclude)) {
            $attachments = get_children(array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby));
        } else {
            $attachments = get_children(array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby));
        }

        if (empty($attachments))
            return '';

        if (is_feed()) {
            $output = "\n";
            foreach ($attachments as $att_id => $attachment)
                $output .= wp_get_attachment_link($att_id, $size, true) . "\n";
            return $output;
        }

        $itemtag = tag_escape($itemtag);
        $captiontag = tag_escape($captiontag);
        $icontag = tag_escape($icontag);
        $valid_tags = wp_kses_allowed_html('post');
        if (!isset($valid_tags[$itemtag]))
            $itemtag = 'dl';
        if (!isset($valid_tags[$captiontag]))
            $captiontag = 'dd';
        if (!isset($valid_tags[$icontag]))
            $icontag = 'dt';

        $columns = intval($columns);
        $itemwidth = $columns > 0 ? floor(100 / $columns) : 100;
        $float = is_rtl() ? 'right' : 'left';

        $selector = "gallery-{$instance}";
        $rel = "prettyPhoto[gallery-{$instance}]";

        $gallery_style = $gallery_div = '';
        if (apply_filters('use_default_gallery_style', true))
            $gallery_style = "
		<style type='text/css'>
			#{$selector} {
				margin: auto;
			}
			#{$selector} .gallery-item {
				float: {$float};
				margin-top: 10px;
				text-align: center;
				width: {$itemwidth}%;
			}
			#{$selector} img {
				border: 2px solid #cfcfcf;
			}
			#{$selector} .gallery-caption {
				margin-left: 0;
			}
		</style>
		<!-- see gallery_shortcode() in wp-includes/media.php -->";
        $size_class = sanitize_html_class($size);
        $gallery_div = "<div id='$selector' class='gallery galleryid-{$id} gallery-columns-{$columns} gallery-size-{$size_class}'>";
        $output = apply_filters('gallery_style', $gallery_style . "\n\t\t" . $gallery_div);

        $i = 0;
        foreach ($attachments as $id => $attachment) {
            if (isset($attr['link']) && 'file' == $attr['link']) {
                /*$image =
                $link = ($i == 0) ? wp_get_attachment_link($id, $size, false, false) : wp_get_attachment_link($id, 'full', false, false,'a');
                $link = preg_match( '/rel="/', $link ) ? str_replace('rel="', 'rel="' . $rel . '"', $link ) : str_replace( '<a ', '<a rel="' . $rel .'"', $link );
                if ($i > 0) {
                  $link = preg_match('/style="/',$link) ? str_replace('/style="/','/style="display:none"/',$link) : str_replace("<a ","<a style='display:none' ");
                } */
                $bigurl = wp_get_attachment_image_src($id,'full',false);
                $bigurl = $bigurl[0];
                $anchor = ($i==0) ? wp_get_attachment_image($id,$size,false) : "";
                $link = "<a href='{$bigurl}' rel='{$rel}'>{$anchor}</a>";

            } else {
                $link = wp_get_attachment_link($id, $size, true, false);
            }

            $output .= ($is_active && $i == 0 || !$is_active) ? "<{$itemtag} class='gallery-item'>" : "";
            $output .= ($is_active && $i == 0 || !$is_active) ? "<{$icontag} class='gallery-icon'>" : "";
		$output .= $link;
		$output .= ($is_active && $i == count($attachments)-1 || !$is_active) ? "</{$icontag}>" : "";
            
                if ($captiontag && trim($attachment->post_excerpt) && ($is_active && $i == 0 || !$is_active)) {
                    $output .= "
				<{$captiontag} class='wp-caption-text gallery-caption'>
				" . wptexturize($attachment->post_excerpt) . "
				</{$captiontag}>";
                }
            
             $output .= ($is_active && $i == count($attachments)-1 || !$is_active) ? "</{$itemtag}>" : "";
            if ($columns > 0 && ++$i % $columns == 0  && ($is_active && $i == 0 || !$is_active))
                $output .= '<br style="clear: both" />';
        }

        $output .= "
			<br style='clear: both;' />
		</div>\n";

        return $output;
    }

}

?>
