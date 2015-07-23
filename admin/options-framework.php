<?php
/*
  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/* Basic plugin definitions */

define('WRSG_OPTIONS_FRAMEWORK_VERSION', '0.9');

/* Make sure we don't expose any info if called directly */

if (!function_exists('add_action')) {
    echo "Hi there!  I'm just a little plugin, don't mind me.";
    exit;
}

/* If the user can't edit theme options, no use running this plugin */

add_action('init', 'wrsg_optionsframework_rolescheck');

function wrsg_optionsframework_rolescheck() {
    add_action('admin_menu', 'wrsg_optionsframework_add_page');
    add_action('admin_init', 'wrsg_optionsframework_init');
    add_action('admin_init', 'wrsg_optionsframework_mlu_init');
}

/* Loads the file for option sanitization */

add_action('init', 'wrsg_optionsframework_load_sanitization');

function wrsg_optionsframework_load_sanitization() {
    require_once dirname(__FILE__) . '/options-sanitize.php';
}

/*
 * Creates the settings in the database by looping through the array
 * we supplied in options.php.  This is a neat way to do it since
 * we won't have to save settings for headers, descriptions, or arguments.
 *
 * Read more about the Settings API in the WordPress codex:
 * http://codex.wordpress.org/Settings_API
 *
 */

function wrsg_optionsframework_init() {

    // Include the required files
    require_once dirname(__FILE__) . '/options-interface.php';
    require_once dirname(__FILE__) . '/options-medialibrary-uploader.php';
	require_once dirname(__FILE__) . '/options.php';
	
    $wrsg_optionsframework_settings = get_option('wrsg_optionsframework');

    // Updates the unique option id in the database if it has changed
    wrsg_optionsframework_option_name();

    // Gets the unique id, returning a default if it isn't defined
    if (isset($wrsg_optionsframework_settings['id'])) {
        $option_name = $wrsg_optionsframework_settings['id'];
    } else {
        $option_name = 'wrsg_optionsframework';
    }

    // If the option has no saved data, load the defaults
    if (!get_option($option_name)) {
        wrsg_optionsframework_setdefaults();
    }

    // Registers the settings fields and callback
    register_setting('wrsg_optionsframework', $option_name, 'wrsg_optionsframework_validate');
}

/*
 * Adds default options to the database if they aren't already present.
 * May update this later to load only on plugin activation, or theme
 * activation since most people won't be editing the options.php
 * on a regular basis.
 *
 * http://codex.wordpress.org/Function_Reference/add_option
 *
 */

function wrsg_optionsframework_setdefaults() {

    $wrsg_optionsframework_settings = get_option('wrsg_optionsframework');

    // Gets the unique option id
    $option_name = $wrsg_optionsframework_settings['id'];

    /*
     * Each theme will hopefully have a unique id, and all of its options saved
     * as a separate option set.  We need to track all of these option sets so
     * it can be easily deleted if someone wishes to remove the plugin and
     * its associated data.  No need to clutter the database.  
     *
     */

    if (isset($wrsg_optionsframework_settings['knownoptions'])) {
        $knownoptions = $wrsg_optionsframework_settings['knownoptions'];
        if (!in_array($option_name, $knownoptions)) {
            array_push($knownoptions, $option_name);
            $wrsg_optionsframework_settings['knownoptions'] = $knownoptions;
            update_option('wrsg_optionsframework', $wrsg_optionsframework_settings);
        }
    } else {
        $newoptionname = array($option_name);
        $wrsg_optionsframework_settings['knownoptions'] = $newoptionname;
        update_option('wrsg_optionsframework', $wrsg_optionsframework_settings);
    }

    // Gets the default options data from the array in options.php
    $options = wrsg_optionsframework_options();

    // If the options haven't been added to the database yet, they are added now
    $values = wrsg_of_get_default_values();

    if (isset($values)) {
        add_option($option_name, $values); // Add option with default settings
    }
}

/* Add a subpage called "Theme Options" to the appearance menu. */

if (!function_exists('wrsg_optionsframework_add_page')) {

    function wrsg_optionsframework_add_page() {

        $wrsg_of_page = add_menu_page(WRSG_OPTIONS_FRAMEWORK_NAME, WRSG_OPTIONS_FRAMEWORK_NAME, 'edit_plugins', WRSG_OPTIONS_FRAMEWORK_TAG, 'wrsg_optionsframework_page', 'dashicons-admin-generic');
        
        // Adds actions to hook in the required css and javascript
        add_action("admin_print_styles-$wrsg_of_page", 'wrsg_optionsframework_load_styles');
        add_action("admin_print_scripts-$wrsg_of_page", 'wrsg_optionsframework_load_scripts');
    }

}

/* Loads the CSS */

function wrsg_optionsframework_load_styles() {
    wp_enqueue_style('admin-style', WRSG_OPTIONS_FRAMEWORK_URL . 'css/admin-style.css');
}

/* Loads the javascript */

function wrsg_optionsframework_load_scripts() {

    // Inline scripts from options-interface.php
    add_action('admin_head', 'wrsg_of_admin_head');

    // Enqueued scripts
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('options-custom', WRSG_OPTIONS_FRAMEWORK_URL . 'js/options-custom.js', array('jquery'));
}

function wrsg_of_admin_head() {

    // Hook to add custom scripts
    do_action('wrsg_optionsframework_custom_scripts');
}

/*
 * Builds out the options panel.
 *
 * If we were using the Settings API as it was likely intended we would use
 * do_settings_sections here.  But as we don't want the settings wrapped in a table,
 * we'll call our own custom wrsg_optionsframework_fields.  See options-interface.php
 * for specifics on how each individual field is generated.
 *
 * Nonces are provided using the settings_fields()
 *
 */

if (!function_exists('wrsg_optionsframework_page')) {

    function wrsg_optionsframework_page() {
        $return = wrsg_optionsframework_fields();
        settings_errors();
        ?>

        <div class="wrap">
            <?php screen_icon('themes'); ?>
            <h2 class="nav-tab-wrapper">
                <?php echo $return[1]; ?>
            </h2>

            <div class="metabox-holder">
                <div id="optionsframework" class="postbox">
                    <form action="options.php" method="post">
                        <?php settings_fields('wrsg_optionsframework'); ?>

                        <?php echo $return[0]; /* Settings */ ?>

                        <div id="optionsframework-submit">
                            <input type="submit" class="button-primary" name="update" value="<?php esc_attr_e('Save Options'); ?>" />
                            <input type="submit" class="reset-button button-secondary" name="reset" value="<?php esc_attr_e('Restore Defaults'); ?>" onclick="return confirm( '<?php print esc_js(__('Click OK to reset. Any theme settings will be lost!')); ?>' );" />
                            <div class="clear"></div>
                        </div>
                    </form>
                </div> <!-- / #container -->
            </div>
        </div> <!-- / .wrap -->

        <?php
    }

}

/**
 * Validate Options.
 *
 * This runs after the submit/reset button has been clicked and
 * validates the inputs.
 *
 * @uses $_POST['reset']
 * @uses $_POST['update']
 */
function wrsg_optionsframework_validate($input) {

    /*
     * Restore Defaults.
     *
     * In the event that the user clicked the "Restore Defaults"
     * button, the options defined in the theme's options.php
     * file will be added to the option for the active theme.
     */

    if (isset($_POST['reset'])) {
        add_settings_error('options-framework', 'restore_defaults', __('Default options restored.', 'wrsg_optionsframework'), 'updated fade');
        return wrsg_of_get_default_values();
    }

    /*
     * Udpdate Settings.
     */

    if (isset($_POST['update'])) {
        $clean = array();
        $options = wrsg_optionsframework_options();
        foreach ($options as $option) {

            if (!isset($option['id'])) {
                continue;
            }

            if (!isset($option['type'])) {
                continue;
            }

            $id = preg_replace('/[^a-zA-Z0-9._\-]/', '', strtolower($option['id']));

            // Set checkbox to false if it wasn't sent in the $_POST
            if ('checkbox' == $option['type'] && !isset($input[$id])) {
                $input[$id] = '0';
            }

            // Set each item in the multicheck to false if it wasn't sent in the $_POST
            if ('multicheck' == $option['type'] && !isset($input[$id])) {
                foreach ($option['options'] as $key => $value) {
                    $input[$id][$key] = '0';
                }
            }

            // For a value to be submitted to database it must pass through a sanitization filter
            if (has_filter('wrsg_of_sanitize_' . $option['type'])) {
                $clean[$id] = apply_filters('wrsg_of_sanitize_' . $option['type'], $input[$id], $option);
            }
        }

        add_settings_error('options-framework', 'save_options', __('Options saved.', 'wrsg_optionsframework'), 'updated fade');
        return $clean;
    }

    /*
     * Request Not Recognized.
     */

    return wrsg_of_get_default_values();
}

/**
 * Format Configuration Array.
 *
 * Get an array of all default values as set in
 * options.php. The 'id','std' and 'type' keys need
 * to be defined in the configuration array. In the
 * event that these keys are not present the option
 * will not be included in this function's output.
 *
 * @return    array     Rey-keyed options configuration array.
 *
 * @access    private
 */
function wrsg_of_get_default_values() {
    $output = array();
    $config = wrsg_optionsframework_options();
    foreach ((array) $config as $option) {
        if (!isset($option['id'])) {
            continue;
        }
        if (!isset($option['std'])) {
            continue;
        }
        if (!isset($option['type'])) {
            continue;
        }
        if (has_filter('wrsg_of_sanitize_' . $option['type'])) {
            $output[$option['id']] = apply_filters('wrsg_of_sanitize_' . $option['type'], $option['std'], $option);
        }
    }
    return $output;
}

/**
 * Add Theme Options menu item to Admin Bar.
 */
add_action('wp_before_admin_bar_render', 'wrsg_optionsframework_adminbar');

function wrsg_optionsframework_adminbar() {

    global $wp_admin_bar;

    $wp_admin_bar->add_menu(array(
        'parent' => 'appearance',
        'id' => 'wrsg_of_theme_options',
        'title' => __('Theme Options'),
        'href' => admin_url('themes.php?page=options-framework')
    ));
}

if (!function_exists('wrsg_of_get_option')) {

    /**
     * Get Option.
     *
     * Helper function to return the theme option value.
     * If no value has been saved, it returns $default.
     * Needed because options are saved as serialized strings.
     */
    function wrsg_of_get_option($name, $default = false) {
        $config = get_option('wrsg_optionsframework');

        if (!isset($config['id'])) {
            return $default;
        }

        $options = get_option($config['id']);

        if (isset($options[$name])) {
            return $options[$name];
        }

        return $default;
    }

}