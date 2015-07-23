<?php

/**
 * A unique identifier is defined to store the options in the database and reference them from the theme.
 * By default it uses the theme name, in lowercase and without spaces, but this can be changed if needed.
 * If the identifier changes, it'll appear as if the options have been reset.
 * 
 */
function wrsg_optionsframework_option_name() {
    $wrsg_optionsframework_settings = get_option('wrsg_optionsframework');
    $wrsg_optionsframework_settings['id'] = WRSG_OPTIONS_FRAMEWORK_TAG;
    update_option('wrsg_optionsframework', $wrsg_optionsframework_settings);
}

/**
 * Defines an array of options that will be used to generate the settings page and be saved in the database.
 * When creating the 'id' fields, make sure to use all lowercase and no spaces.
 *  
 */
function wrsg_optionsframework_options() {
    $colorboxpath = WRSIMPLEGALLERY_URL . 'colorbox/';
    $layoutpath = WRSIMPLEGALLERY_URL . 'layout/';
    $options = array();

    $options[] = array('name' => __('Basic Settings', 'wrsimplegallery'),
        'type' => 'heading');

    $options[] = array('name' => __('Caption', 'wrsimplegallery'),
        'desc' => __('A formatted string to be used as a caption.<br>%title% - Image title<br>%alt% - Alternative Text<br>%filename% - Filename<br>', 'wrsimplegallery'),
        'id' => 'wrsimplegallery_caption',
        'std' => '%title%',
        'class' => '',
        'type' => 'textarea');

    $post_types_default = array('gallery' => '1');
    //$post_types = get_post_types();
    $post_types = array('gallery' => 'gallery');
    
    unset($post_types['attachment']);
    unset($post_types['revision']);
    unset($post_types['nav_menu_item']);
    unset($post_types['mediapage']);
    unset($post_types['post']);
    unset($post_types['page']);
    
    $options[] = array('name' => __('Post Types', 'wrsimplegallery'),
        'desc' => __('What post types do you want to have galleries enabled', 'wrsimplegallery'),
        'id' => 'wrsimplegallery_post_types',
        'std' => $post_types_default,
        'options' => $post_types,
        'type' => 'multicheck');

    $options[] = array('name' => __('Show on single posts only', 'wrsimplegallery'),
        'desc' => __('This will stop galleries from showing when more than one post is being shown.', 'wrsimplegallery'),
        'id' => 'single_only',
        'std' => '1',
        'type' => 'checkbox');

    $options[] = array('name' => __('Append to posts', 'wrsimplegallery'),
        'desc' => __('This will append the gallery to the bottom of the post. You can use the shortcode "[wrsgallery]" or [wrsgallery id=1] if you want the gallery to be inserted with the post. To Show all galleries in an album view on your custom template. You can use the shortcode "[wrsalbum]".', 'wrsimplegallery'),
        'id' => 'append_gallery',
        'std' => '1',
        'type' => 'checkbox');

    $options[] = array('name' => __('Colorbox Settings', 'wrsimplegallery'),
        'type' => 'heading');

    $options[] = array('name' => __('Use Colorbox', 'wrsimplegallery'),
        'desc' => __('Check to use colorbox. Sometimes it can conflict with other plugins such as Shopp.', 'wrsimplegallery'),
        'id' => 'use_colorbox',
        'std' => '1',
        'type' => 'checkbox');
    
    $options[] = array('name' => __('Use Transition', 'wrsimplegallery'),
        'desc' => __('Select colorbox transition effects.', 'wrsimplegallery'),
        'id' => 'use_effect',
        'std' => 'elastic',
        "type" => "select",
        "options" => array("none" => "none", "elastic" => 'elastic', "fade" => 'fade',));

    $options[] = array('name' => __('Colorbox Theme', 'wrsimplegallery'),
        'id' => 'wrsimplegallery_colorbox_theme',
        'std' => 'theme1',
        'type' => 'images',
        'options' => array(
            'theme1' => $colorboxpath . 'themes/theme1/style.png',
            'theme2' => $colorboxpath . 'themes/theme2/style.png',
            'theme3' => $colorboxpath . 'themes/theme3/style.png',
            'theme4' => $colorboxpath . 'themes/theme4/style.png',
            'theme5' => $colorboxpath . 'themes/theme5/style.png',
            'theme6' => $colorboxpath . 'themes/theme6/style.png',
            'theme7' => $colorboxpath . 'themes/theme7/style.png',
            'theme8' => $colorboxpath . 'themes/theme8/style.png',
            'theme9' => $colorboxpath . 'themes/theme9/style.png',
            'theme10' => $colorboxpath . 'themes/theme10/style.png',
            'theme11' => $colorboxpath . 'themes/theme11/style.png'
        )
    );   

    return $options;
}