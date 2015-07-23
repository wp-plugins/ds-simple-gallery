<?php
/*
  Plugin Name: Ds Simple Gallery
  Version: 1.0
  Description: A simple plugin that adds an image gallery.
  Plugin URI: http://example.com/
  Author: Pankaj Chaudhary
  Author URI: http://example.com/
 */

$plugin_dir = plugin_basename(__FILE__);
$plugin_dir = str_replace(basename($plugin_dir), '', $plugin_dir);
define('WRSIMPLEGALLERY_DIR', WP_PLUGIN_DIR . '/' . $plugin_dir);
define('WRSIMPLEGALLERY_URL', WP_PLUGIN_URL . '/' . $plugin_dir);
define('WRSIMPLEGALLERY_DEBUG', false);
define('WRSIMPLEGALLERY_VERSION', '1.0');

define('WRSG_OPTIONS_FRAMEWORK_URL', WRSIMPLEGALLERY_URL . 'admin/');
define('WRSG_OPTIONS_FRAMEWORK_DIRECTORY', WRSIMPLEGALLERY_DIR . 'admin/');
define('WRSG_OPTIONS_FRAMEWORK_NAME', 'Gallery Settings');
define('WRSG_OPTIONS_FRAMEWORK_TAG', 'wrsimplegallery');
require_once (WRSG_OPTIONS_FRAMEWORK_DIRECTORY . 'options-framework.php');

class wrsimplegallery {

    private static $instance;
    private $admin_thumbnail_size = 109;
    private $thumbnail_size_w = 150;
    private $thumbnail_size_h = 150;

    public static function forge() {
        if (!isset(self::$instance)) {
            $className = __CLASS__;
            self::$instance = new $className;
        }
        return self::$instance;
    }

    private function __construct() {
        $this->thumbnail_size_w = wrsg_of_get_option('wrsimplegallery_thumb_width');
        $this->thumbnail_size_h = wrsg_of_get_option('wrsimplegallery_thumb_height');

        add_action('admin_print_scripts-post.php', array(&$this, 'admin_print_scripts'));
        add_action('admin_print_scripts-post-new.php', array(&$this, 'admin_print_scripts'));
        add_action('admin_print_styles', array(&$this, 'admin_print_styles'));
        add_action('wp_print_scripts', array(&$this, 'print_scripts'));
        add_action('wp_print_styles', array(&$this, 'print_styles'));
        add_action('init', array(&$this, 'load_plugin_textdomain'));
        add_action( 'init', array(&$this, 'wp_really_simple_gallery') );
        add_filter('the_content', array(&$this, 'output_gallery'), wrsg_of_get_option('wrsimplegallery_filter_priority', 10));
        add_image_size('wrsimplegallery_admin_thumb', $this->admin_thumbnail_size, $this->admin_thumbnail_size, true);
        add_image_size('wrsimplegallery_thumb', $this->thumbnail_size_w, $this->thumbnail_size_h, true);
        add_shortcode('wrsgallery', array(&$this, 'shortcode'));
        if (is_admin()) {
            add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
            add_action('admin_init', array(&$this, 'add_meta_boxes'), 1);
            add_action('save_post', array(&$this, 'save_post_meta'), 9, 1);
            add_action('wp_ajax_wrsimplegallery_get_thumbnail', array(&$this, 'ajax_get_thumbnail'));
            add_action('wp_ajax_wrsimplegallery_get_all_thumbnail', array(&$this, 'ajax_get_all_attachments'));
        }
    }
    
    // Register the Custom Music Review Post Type
 
function wp_really_simple_gallery() {
 
    $labels = array(
        'name'                  => _x( 'Galleries', 'wrsimplegallery' ),
        'singular_name'         => _x( 'Gallery', 'wrsimplegallery' ),
        'menu_name'             => _x( 'Gallery', 'wrsimplegallery' ),
        'name_admin_bar'        => _x( 'Gallery', 'wrsimplegallery' ),
        'all_items'             =>  _x( 'All Galleries', 'wrsimplegallery' ),
        'add_new'               => _x( 'Add New Gallery', 'wrsimplegallery' ),
        'add_new_item'          => _x( 'Add New Gallery', 'wrsimplegallery' ),
        'edit_item'             => _x( 'Edit Gallery', 'wrsimplegallery' ),
        'new_item'              => _x( 'New Gallery', 'wrsimplegallery' ),
        'view_item'             => _x( 'View Gallery', 'wrsimplegallery' ),
        'search_items'          => _x( 'Search Galleries', 'wrsimplegallery' ),
        'not_found'             => _x( 'No gallery found', 'wrsimplegallery' ),
        'not_found_in_trash'    => _x( 'No gallery found in Trash', 'wrsimplegallery' ),
    );
 
    $args = array(
        'labels'                => $labels,
        'hierarchical'          => true,
        'description'           => 'A simple plugin that adds an image gallery',
        'supports'              => array( 'title'),
        'taxonomies'            => array( 'genres' ),
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 100,
        'menu_icon'             => 'dashicons-camera',
        'show_in_nav_menus'     => true,
        'publicly_queryable'    => true,
        'exclude_from_search'   => false,
        'has_archive'           => true,
        'query_var'             => true,
        'can_export'            => true,
        'rewrite'               => true,
        'capability_type'       => 'post'
    );
 
    register_post_type( 'gallery', $args );
}

    public function admin_print_scripts() {
        wp_enqueue_script('media-upload');
        wp_enqueue_script('wrsimplegallery-admin-scripts', WRSIMPLEGALLERY_URL . 'wp-gallery-admin.js', array('jquery'), WRSIMPLEGALLERY_VERSION);
		wp_enqueue_script('plupload', WRSIMPLEGALLERY_URL . 'plupload.full.min.js', array('jquery'));
    }

    public function admin_print_styles() {
        wp_enqueue_style('wrsimplegallery-admin-style', WRSIMPLEGALLERY_URL . 'wp-gallery-admin.css', array(), WRSIMPLEGALLERY_VERSION);
    }

    public function add_meta_boxes() {
        $post_types = wrsg_of_get_option('wrsimplegallery_post_types');
        $post_types = ($post_types !== false) ? $post_types : array('page' => '1', 'post' => '1');

        foreach ($post_types as $type => $value) {
            if ($value == '1') {
                add_meta_box(
                        'wrsimplegallery', __('WP Really Simple Gallery', 'wrsimplegallery'), array(&$this, 'inner_custom_box'), $type
                );
            }
        }
    }

    public function inner_custom_box($post) {
        $options = array();
        $gallery = get_post_meta($post->ID, 'wrsimplegallery_gallery', true);
        wp_nonce_field(basename(__FILE__), 'wrsimplegallery_gallery_nonce');

        $upload_size_unit = $max_upload_size = wp_max_upload_size();
        $sizes = array('KB', 'MB', 'GB');

        for ($u = -1; $upload_size_unit > 1024 && $u < count($sizes) - 1; $u++) {
            $upload_size_unit /= 1024;
        }

        if ($u < 0) {
            $upload_size_unit = 0;
            $u = 0;
        } else {
            $upload_size_unit = (int) $upload_size_unit;
        }

        $upload_action_url = admin_url('async-upload.php');
        $post_params = array(
            "post_id" => $post->ID,
            "_wpnonce" => wp_create_nonce('media-form'),
            "short" => "1",
        );

        $post_params = apply_filters('upload_post_params', $post_params); // hook change! old name: 'swfupload_post_params'

        $plupload_init = array(
            'runtimes' => 'html5,silverlight,flash,html4',
            'browse_button' => 'wrsg-plupload-browse-button',
            'file_data_name' => 'async-upload',
            'multiple_queues' => true,
            'max_file_size' => $max_upload_size . 'b',
            'url' => $upload_action_url,
            'flash_swf_url' => includes_url('js/plupload/plupload.flash.swf'),
            'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
            'filters' => array(array('title' => __('Allowed Files'), 'extensions' => '*')),
            'multipart' => true,
            'urlstream_upload' => true,
            'multipart_params' => $post_params
        );
        ?>
        <script type="text/javascript">
            var POST_ID = <?php echo $post->ID; ?>;
            var WPSGwpUploaderInit = <?php echo json_encode($plupload_init) ?>;
        </script>

        <input id="wrsg-plupload-browse-button" class="button" type="button" value="<?php echo __('Upload Image', 'wrsimplegallery'); ?>" rel="" />
        <input id="wrsimplegallery_delete_all_button" class="button" type="button" value="<?php echo __('Delete All', 'wrsimplegallery'); ?>" rel="" />
        <span class="spinner" id="wrsimplegallyer_spinner"></span>
        <div id="wrsimplegallery_container">
            <ul id="wrsimplegallery_thumbs" class="clearfix"><?php
                $gallery = (is_string($gallery)) ? @unserialize($gallery) : $gallery;
                if (is_array($gallery) && count($gallery) > 0) {
                    foreach ($gallery as $id) {
                        echo $this->admin_thumb($id);
                    }
                }
                ?>
            </ul>
        </div>
        <?php
    }

    private function admin_thumb($id) {
        global  $post;
        $image = wp_get_attachment_image_src($id, 'wrsimplegallery_admin_thumb', true);
        $cover = get_post_meta( $post->ID, 'wrsimplegallery_setcover', true );
        ?>
        <li><img src="<?php echo $image[0]; ?>" width="<?php echo $image[1]; ?>" height="<?php echo $image[2]; ?>" /><a href="#" class="wrsimplegallery_remove"><?php echo __('Remove', 'wrsimplegallery'); ?></a><input type="hidden" name="wrsimplegallery_thumb[]" value="<?php echo $id; ?>" />
            <span><input type="radio" name="wrsimplegallery_setcover" <?php if($cover == $id){echo "checked ";}?> value="<?php echo $id; ?>" /><?php echo __('Set as cover image', 'wrsimplegallery'); ?></span>
        </li>
        
        <?php
    }

    public function ajax_get_thumbnail() {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        echo $this->admin_thumb($_POST['imageid']);
        die;
    }

    public function ajax_get_all_attachments() {
        $post_id = $_POST['post_id'];
        $included = (isset($_POST['included'])) ? $_POST['included'] : array();

        $attachments = get_children(array(//do only if there are attachments of these qualifications
            'post_parent' => $post_id,
            'post_type' => 'attachment',
            'numberposts' => -1,
            'order' => 'ASC',
            'post_mime_type' => 'image', //MIME Type condition
                )
        );
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        if (count($attachments) > 0) {
            foreach ($attachments as $a) {
                if (!in_array($a->ID, $included)) {
                    echo $this->admin_thumb($a->ID);
                }
            }
        }
        die;
    }

    private function thumb($id, $post_id) {
        $info = get_posts(array('p' => $id, 'post_type' => 'attachment'));
        $url = wp_get_attachment_url($id);
        if (wrsg_of_get_option('wrsimplegallery_use_timthumb', '0') === '1') {
            $width = $this->thumbnail_size_w;
            $height = $this->thumbnail_size_h;
            $image = array(
                WRSIMPLEGALLERY_URL . 'timthumb.php?src=' . $url . '&q=85&w=' . $width . '&h=' . $height,
                $width,
                $height
            );
        } else {
            $image = wp_get_attachment_image_src($id);
        }
        $title_string = wrsg_of_get_option('wrsimplegallery_caption', '%title%');
        $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
        $data = array(
            '%title%' => $info[0]->post_title,
            '%alt%' => $alt,
            '%filename%' => basename($url),
            '%caption%' => $info[0]->post_excerpt,
            "\n" => ' - '
        );
        $title = str_replace(array_keys($data), $data, $title_string);
        return '<li><a href="' . $url . '" title="' . $title . '" rel="wrsimplegallery_group_' . $post_id . '"><img src="' . $image[0] . '" width="' . $image[1] . '" height="' . $image[2] . '" alt="' . $info[0]->post_title . '" /></a></li>';
    }

    public function save_post_meta($post_id) {
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return '';
        }
        if (!isset($_POST['wrsimplegallery_gallery_nonce']) || !wp_verify_nonce($_POST['wrsimplegallery_gallery_nonce'], basename(__FILE__)))
            return (isset($post_id)) ? $post_id : 0;
        
        $setcover = (isset($_POST['wrsimplegallery_setcover'])) ? $_POST['wrsimplegallery_setcover'] : 0;
        $images = (isset($_POST['wrsimplegallery_thumb'])) ? $_POST['wrsimplegallery_thumb'] : array();
        $gallery = array();
        if (count($images) > 0) {
            foreach ($images as $i => $img) {
                if (is_numeric($img))
                    $gallery[] = $img;
            }
        }
        update_post_meta($post_id, 'wrsimplegallery_gallery', $gallery);
        update_post_meta($post_id, 'wrsimplegallery_setcover', $setcover);
        
        return $post_id;
    }

    public function print_scripts() {
        if (wrsg_of_get_option('use_colorbox', '1') == '1')
            wp_register_script('colorbox', WRSIMPLEGALLERY_URL . 'colorbox/jquery.colorbox-min.js', array('jquery'));
        wp_enqueue_script('wrsimplegallery-scripts', WRSIMPLEGALLERY_URL . 'wp-gallery.js', array('colorbox'));
    }

    public function print_styles() {
        wp_enqueue_style('wrsimplegallery-style', WRSIMPLEGALLERY_URL . 'wp-gallery.css');
        if (wrsg_of_get_option('use_colorbox', '1') == '1')
            wp_enqueue_style('colorbox', WRSIMPLEGALLERY_URL . 'colorbox/themes/' . wrsg_of_get_option('wrsimplegallery_colorbox_theme') . '/colorbox.css');
    }

    private function gallery($post_id = false) {
        global $post;
        $post_id = (!$post_id) ? $post->ID : $post_id;
        $gallery = get_post_meta($post_id, 'wrsimplegallery_gallery', true);
        $gallery = (is_string($gallery)) ? @unserialize($gallery) : $gallery;
        $html = '';
        
        if (is_array($gallery) && count($gallery) > 0) {
            $html = '<div id="wrsimplegallery_container"><ul id="wrsimplegallery" class="clearfix">
                    <input type="hidden" id="effects" value="'.wrsg_of_get_option('use_effect').'"/>';
            foreach ($gallery as $thumbid) {
                $html .= $this->thumb($thumbid, $post_id);
            }
            $html .= '</ul></div>';
        }

        return $html;
    }
    
    public function albumb() {
         global $post;
        $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
        $args=array(  'post_type' => 'gallery', 'post_status' => 'publish',  'posts_per_page' => 12, 'paged' => $paged, 'order_by' => 'date', 'order' => 'ASC');
        $my_query = new WP_Query($args);
		if( $my_query->have_posts() ) {
                                echo'<div id="wrsimplegallery_container"><ul id="wrsimplegallery_albumb" class="clearfix img-list">';
                                    while ($my_query->have_posts()) : $my_query->the_post();
                                        $cover = get_post_meta( $post->ID, 'wrsimplegallery_setcover', true );
                                        $attachment = wp_get_attachment_image_src( $cover );
                                        echo'<li><a class="" href="'.get_the_permalink().'"><img src="'.$attachment[0].'" alt="'.get_the_title().'" /><span class="text-content"><span>'.get_the_title().'</span></span></a></li>';   
                                    endwhile;
                                echo'</ul></div>';
                        }
     }

    public function output_gallery($content) {
        if (post_password_required()) {
            return $content;
        }

        $append_gallery = wrsg_of_get_option('append_gallery', '1');
        if (!post_password_required() && $append_gallery == '1' && (wrsg_of_get_option('single_only', '1') !== '1' || is_singular())) {
            $content .= $this->gallery();
        }
        return $content;
    }

    public function shortcode($atts) {
        extract(shortcode_atts(array(
            'id' => false,
                        ), $atts));
        return $this->gallery($id);
    }
    
    public function load_plugin_textdomain() {
        load_plugin_textdomain('wrsimplegallery', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

}
add_shortcode( 'wrsalbum', array( 'wrsimplegallery', 'albumb' ) );
global $wrsimplegallery;
$wrsimplegallery = wrsimplegallery::forge();