<?php

/**
 *
 * Plugin Name: WP Redirects - Contact Form 7
 * Description: With this plugin, you may improve your Contact Form 7 setup: you can easily manage form redirects, allow PDF downloads, and link it with a popup for a smooth user experience.
 * Version: 3.0
 * Author: Yudiz Solution Ltd.
 * Author URI: http://www.yudiz.com/
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wp-redirects-contact-form-7
 * Domain Path: /languages
 *
 **/

/**
 * Plugin path url
 */
define('YSPL_CF7R_PLUGIN', __FILE__);

/**
 * Plugin directory path
 */
define('YSPL_CF7R_PLUGIN_DIR', untrailingslashit(dirname(YSPL_CF7R_PLUGIN)));

/**
 * Include redirection table template
 */
require_once YSPL_CF7R_PLUGIN_DIR . ('/includes/templates/redirection_table.php');

/*  Check Contact Form 7 Plugin is activated    */
function yspl_check_cf7()
{
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');

    if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
        if (file_exists(WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php')) {
            // Plugin is installed but not active
            echo '<div class="error"><p>' . sprintf(
                esc_html__('Contact Form 7 is installed but not active. Please %1$sactivate it%2$s to use this plugin.', 'yspl-text-domain'),
                '<a href="' . esc_url(admin_url('plugins.php')) . '">',
                '</a>'
            ) . '</p></div>';
        } else {
            // Plugin is not installed
            echo '<div class="error"><p>' . sprintf(
                esc_html__('Contact Form 7 is not installed. Please %1$sinstall and activate it%2$s to use this plugin.', 'yspl-text-domain'),
                '<a href="' . esc_url(admin_url('plugin-install.php?tab=search&s=contact+form+7')) . '">',
                '</a>'
            ) . '</p></div>';
        }
    }
}
add_action('admin_notices', 'yspl_check_cf7');




/*********************************************/
/**  Start -- Include additional scripts */

/* Following script will be included for wp-admin */
function yspl_cf7r_load_admin_scripts()
{
    wp_enqueue_style('wp-codemirror');
    wp_enqueue_script('wp-codemirror');
    wp_enqueue_script('wp-theme-plugin-editor');
    $cm_settings = wp_enqueue_code_editor(array('type' => 'application/javascript'));
    wp_localize_script('jquery', 'cm_settings', $cm_settings);
    wp_enqueue_media();
    wp_enqueue_script('script', plugin_dir_url(__FILE__) . 'includes/js/admin.js');
}
add_action('admin_enqueue_scripts', 'yspl_cf7r_load_admin_scripts');

/* Enqueue admin-ajax */
function yspl_cf7r_load_wp_scripts()
{
    $nonce = wp_create_nonce('custom_redirect');

    // Register & Enqueue Sweet Alert CSS that will use as Popups
    if (!is_admin()) {
        wp_register_style('yspl_cf7r_sweetalert_css', plugin_dir_url(__FILE__) . 'includes/style/sweetalert2.min.css');
        wp_register_style('yspl_cf7r_frontend_css', plugin_dir_url(__FILE__) . 'includes/style/frontend-style.css');
        wp_enqueue_style("yspl_cf7r_sweetalert_css");
        wp_enqueue_style("yspl_cf7r_frontend_css");
    }

    // Register & Enqueue custom.js that will use ajax
    wp_enqueue_script("jquery");
    wp_register_script('ajax-script', plugin_dir_url(__FILE__) . 'includes/js/custom.js');

    // Register & Enqueue Sweet Alert JS that will use as Popups
    if (!is_admin()) {
        wp_register_script('yspl_cf7r_sweetalert_js', plugin_dir_url(__FILE__) . 'includes/js/sweetalert2.min.js');
        wp_enqueue_script('yspl_cf7r_sweetalert_js');
    }
    wp_enqueue_script('ajax-script');

    // Bind attributes needed for ajax
    $passedValues = array(
        "url" => admin_url(sanitize_file_name('admin-ajax.php')),
        "action" => "yspl_cf7r_admin_scripts",
        "nonce" => $nonce
    );
    // Pass attributes object to ajax call 
    wp_localize_script('ajax-script', 'passed_object', $passedValues);
}
add_action('wp_enqueue_scripts', 'yspl_cf7r_load_wp_scripts');
add_action('admin_enqueue_scripts', 'yspl_cf7r_load_wp_scripts');

// Register scripts to be enqueue with wp-admin ajax
add_action('wp_ajax_yspl_cf7r_admin_scripts', 'yspl_cf7r_admin_scripts');
add_action('wp_ajax_nopriv_yspl_cf7r_admin_scripts', 'yspl_cf7r_admin_scripts');

// Handle ajax call
function yspl_cf7r_admin_scripts()
{
    check_ajax_referer("custom_redirect");
    $id = sanitize_text_field($_POST['id']);
    if (!empty($id)) {
        $form_check = get_post_meta($id, "check_id", true);

        // Retrieve file URL and ID
        $file_url = get_post_meta($id, 'file_url', true);
        $file_id = get_post_meta($id, 'file_id', true);

        $attachment = get_post($file_id);
        $file_title = $attachment->post_title;

        $yspl_cf7r_redirect_needed = get_post_meta($id, 'yspl_cf7r_redirect_needed', true);
        $yspl_cf7r_submission_download_needed = get_post_meta($id, 'yspl_cf7r_submission_download_needed', true);
        $yspl_cf7r_custom_js_needed = get_post_meta($id, 'yspl_cf7r_custom_js_needed', true);

        if (empty($form_check)) {
            wp_send_json_success(array('empty' => "true", "url" => "", 'file_url' => $file_url, 'file_id' => $file_id, 'file_title' => $file_title, 'redirection_need' => $yspl_cf7r_redirect_needed, 'submission_need' => $yspl_cf7r_submission_download_needed));
        } else {
            if ($form_check == 1) {
                $key = "succ_page_id";
                $form_url = get_permalink(get_post_meta($id, $key, true));
            } else {
                $key = "succ_page_url";
                $form_url = get_post_meta($id, $key, true);
            }

            if (empty($form_url)) {
                wp_send_json_success(array('empty' => "true", "url" => "", 'file_url' => $file_url, 'file_id' => $file_id, 'file_title' => $file_title, 'redirection_need' => $yspl_cf7r_redirect_needed, 'submission_need' => $yspl_cf7r_submission_download_needed));
            } else {
                wp_send_json_success(array('success' => "true", "url" => $form_url, 'file_url' => $file_url, 'file_id' => $file_id, 'file_title' => $file_title, 'redirection_need' => $yspl_cf7r_redirect_needed, 'submission_need' => $yspl_cf7r_submission_download_needed));
            }
        }
    }
}
/* End -- Include additional scripts  */
/*********************************************/

/*  Add new tab to "Edit Menu" of contact form  */
function yspl_cf7r_add_tab($panels)
{
    $panels["custom-redirect-settings"] = array("title" => "Form Actions", "callback" => "yspl_cf7r_set_redirects");
    update_option('cf7r_tab', array_search("custom-redirect-settings", array_keys($panels)));
    return $panels;
};
add_filter('wpcf7_editor_panels', 'yspl_cf7r_add_tab');

/*  Edit redirection tab view     */
function yspl_cf7r_set_redirects($post)
{

    wp_nonce_field('add_metabox', 'add_metabox_nonce');

    $check_id = get_post_meta(absint($post->id()), "check_id", true);
    $succ_page_id = get_post_meta(absint($post->id()), 'succ_page_id', true);
    $succ_page_url = get_post_meta(absint($post->id()), 'succ_page_url', true);
    $custom_js_cf7 = get_post_meta(absint($post->id()), 'custom_js_cf7', true);
    $file_url = get_post_meta(absint($post->id()), 'file_url', true);
    $file_id = get_post_meta(absint($post->id()), 'file_id', true);
    $yspl_cf7r_redirect_needed = get_post_meta(absint($post->id()), 'yspl_cf7r_redirect_needed', true);
    $yspl_cf7r_submission_download_needed = get_post_meta(absint($post->id()), 'yspl_cf7r_submission_download_needed', true);
    $yspl_cf7r_custom_js_needed = get_post_meta(absint($post->id()), 'yspl_cf7r_custom_js_needed', true);
    $form_id = absint($post->id());
?>

    <?php echo yspl_cf7r_custom_css_function(); ?>
    <div class="wrapper_container_setting">
        <h3><?php _e('Redirect Settings', 'wp-redirects-contact-form-7'); ?>
            <div class="component--example">
                <p class="mt20">
                    <span class="tooltip" data-tooltip="This allows you to customize form submission redirection as needed." data-tooltip-pos="right" data-tooltip-length="large"><span class="dashicons dashicons-info-outline"></span></span>
                </p>
            </div>
        </h3>
        <div class="checkbox-wrapper-8">
            <input type="checkbox" id="cb3-8" class="tgl tgl-skewed" name="yspl_cf7r_redirect_needed" value="checked" <?php echo esc_attr($yspl_cf7r_redirect_needed == 'checked') ? "checked" : ""; ?>>
            <label for="cb3-8" data-tg-on="ON" data-tg-off="OFF" class="tgl-btn"></label>
        </div>
    </div>
    <!-- Radio button to select id or url  -->
    <div class="input_wrraper_div_container">
        <input type="radio" id="type_existing" name="type" value="1" <?php echo esc_attr(!isset($check_id) || empty($check_id)  || $check_id == 1) ? "checked" : ""; ?>>
        <label for="type_existing" class="toggle-switch-header">
            <?php _e('Select from existing pages', 'wp-redirects-contact-form-7'); ?>
        </label>
        <input type="radio" id="type_custom" name="type" value="2" <?php echo esc_attr($check_id == 2) ? "checked" : ""; ?>>
        <label for="type_custom" class="toggle-switch-header">
            <?php _e('Add Custom Url', 'wp-redirects-contact-form-7'); ?>
        </label>
    </div>

    <!-- Custom url div -->
    <div class="select_url" hidden>
        <input type="text" name="url" value="<?php echo esc_url(($check_id == 2) ? $succ_page_url : ""); ?>">
    </div>
    <div class="select_div">
        <div class="select_type select_page">
            <div class="select_type select_page">
                <label for="select_type" class="toggle-switch-header">
                    <?php _e('Select type:', 'wp-redirects-contact-form-7'); ?>
                </label>
                <select id="select_type">
                    <option value=""><?php _e('-- Select Type --', 'wp-redirects-contact-form-7'); ?></option>
                    <?php
                    $args = array('public' => true);
                    $post_types = get_post_types($args, 'objects');
                    unset($post_types['attachment']);
                    $selected_post_type = get_post_type($succ_page_id);
                    foreach ($post_types as $post_type_obj) {
                        $labels = get_post_type_labels($post_type_obj);
                        $selected = ($post_type_obj->name == $selected_post_type) ? 'selected' : '';
                        echo '<option value="' . esc_attr($post_type_obj->name) . '" ' . esc_attr($selected) . '>' . esc_html($labels->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="select_page">
            <label for="succ_page_id" class="toggle-switch-header">
                <?php _e('Select a page:', 'wp-redirects-contact-form-7'); ?>
            </label>
            <select id="succ_page_id" name="succ_page_id">
                <option value=""><?php _e('-- Select an Option --', 'wp-redirects-contact-form-7'); ?></option>
            </select>
        </div>
        <input type="hidden" name="content_load_page_id" id="content_load_page_id" value="<?php echo esc_attr($succ_page_id); ?>">
        <input type="hidden" name="content_load_post_type" id="content_load_post_type" value="<?php echo esc_attr(get_post_type($succ_page_id)); ?>">
    </div>
    <?php echo custom_js_settings_page_html($custom_js_cf7, $file_url, $file_id, $yspl_cf7r_submission_download_needed, $yspl_cf7r_custom_js_needed, $form_id); ?>
<?php
}

// new code of sandeep start
/* ------------------------------------------------------------------
------------------- Admin Css form Form Actions Tab ------------------
------------------------------------------------------------------- */

function yspl_cf7r_custom_css_function()
{
    $css_file_path = plugin_dir_path(__FILE__) . 'includes/style/admin-custom-style.css';
    $css_file_url = plugins_url('includes/style/admin-custom-style.css', __FILE__);

    if (file_exists($css_file_path)) {
        echo '<style>';
        echo '@import url("' . esc_url($css_file_url) . '");';
        echo '</style>';
    } else {
        echo '<style>';
        echo '/* CSS file not found */';
        echo '</style>';
    }
}

/* ------------------------------------------------------------------
------------- Retrieve Options from Post Types as per need ----------
------------------------------------------------------------------- */
function custom_get_options($type)
{
    $options = '';

    if ($type == 'pages') {
        $pages = get_pages();
        if (!empty($pages)) {
            foreach ($pages as $page) {
                $options .= '<option class="level-0" value="' . esc_attr($page->ID) . '">' . esc_html($page->post_title) . '</option>';
            }
        } else {
            $options .= '<option class="level-0" value="">-- Select an Option --</option>';
        }
    } else if ($type == '') {
        $options .= '<option class="level-0" value="">-- Select an Option --</option>';
    } else {
        $args = array(
            'numberposts' => -1,
            'post_type' => $type
        );
        $posts = get_posts($args);
        if (!empty($posts)) {
            foreach ($posts as $post) {
                $options .= '<option class="level-0" value="' . esc_attr($post->ID) . '">' . esc_html($post->post_title) . '</option>';
            }
        } else {
            $options .= '<option class="level-0" value="">-- Select an Option --</option>';
        }
    }

    return $options;
}

/* ------------------------------------------------------------------
----------- Ajax for Redirection settings Posts and Page Wise --------
------------------------------------------------------------------- */
function custom_get_options_ajax()
{
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'custom_redirect')) {
        wp_send_json_error('Nonce verification failed', 403);
        wp_die();
    }

    if (isset($_POST['type'])) {
        echo custom_get_options(sanitize_text_field($_POST['type']));
    }
    wp_die();
}
add_action('wp_ajax_custom_get_options', 'custom_get_options_ajax');
add_action('wp_ajax_nopriv_custom_get_options', 'custom_get_options_ajax');


/* ------------------------------------------------------------------
---- Custom JS and CF7 download HTML for admin to check Settings -----
------------------------------------------------------------------- */
function custom_js_settings_page_html($custom_js, $existing_file_url, $existing_file_id, $submission_needed, $js_needed, $form_id)
{

    $yspl_cf7r_submission_popup_needed = get_post_meta(absint($form_id), 'yspl_cf7r_submission_popup_needed', true);
    $popup_mail_sent = get_post_meta(absint($form_id), 'popup_mail_sent', true);
    $popup_warning_invalid = get_post_meta(absint($form_id), 'popup_warning_invalid', true);
    $popup_failed_mail = get_post_meta(absint($form_id), 'popup_failed_mail', true);
    $popup_failed_recaptcha = get_post_meta(absint($form_id), 'popup_failed_recaptcha', true);

?>
    <?php echo custom_popup_html($yspl_cf7r_submission_popup_needed, $popup_mail_sent, $popup_warning_invalid, $popup_failed_mail, $popup_failed_recaptcha); ?>

    <div class="wrap wraaper_settings_custom_js">
        <div class="wrapper_container_setting">
            <h3><?php _e(' Downloadable File On Form Submission', 'wp-redirects-contact-form-7'); ?>
                <div class="component--example">
                    <p class="mt20">
                        <span class="tooltip" data-tooltip="This feature allows you to send a downloadable item, such as a brochure, pdf or image after a successful form submission." data-tooltip-pos="right" data-tooltip-length="large"><span class="dashicons dashicons-info-outline"></span></span>
                    </p>
                </div>
            </h3>
            <div class="checkbox-wrapper-8">
                <input type="checkbox" id="cb3-5" class="tgl tgl-skewed" name="yspl_cf7r_submission_download_needed" value="checked" <?php echo esc_attr($submission_needed == 'checked') ? "checked" : ""; ?>>
                <label for="cb3-5" data-tg-on="ON" data-tg-off="OFF" class="tgl-btn"></label>
            </div>
        </div>
        <?php
        if ($existing_file_id) {
            $attachment = get_post($existing_file_id);
            $existing_file_type = get_post_mime_type($existing_file_id);
            $existing_file_icon = wp_mime_type_icon($existing_file_id);
            $existing_file_title = $attachment->post_title;
            $existing_file_name = basename(get_attached_file($existing_file_id));
            $existing_file_size = size_format(filesize(get_attached_file($existing_file_id)));
        } else {
            $existing_file_type = '';
            $existing_file_icon = '';
            $existing_file_title = '';
            $existing_file_name = '';
            $existing_file_size = '';
        }
        ?>
        <div id="selected-file-container" data-file-url="<?php echo esc_attr($existing_file_url); ?>" data-file-id="<?php echo esc_attr($existing_file_id); ?>" data-file-type="<?php echo esc_attr($existing_file_type); ?>" data-file-icon="<?php echo esc_attr($existing_file_icon); ?>" data-file-title="<?php echo esc_attr($existing_file_title); ?>" data-file-name="<?php echo esc_attr($existing_file_name); ?>" data-file-size="<?php echo esc_attr($existing_file_size); ?>">
            <div id="file-preview" style="display: none;">
                <div id="file-details" style="display: none;">
                    <div id="file-icon"><img id="file-icon-img" src="" alt="File Icon" style="max-width: 50px; height: auto;" /></div>
                    <div id="file-info">
                        <p id="file-title"></p>
                        <p id="file-name"></p>
                        <p id="file-size"></p>
                    </div>
                </div>
                <img id="file-image" src="" style="max-width: 100px; height: auto; display: none;" />
                <div class="button_css_file">
                    <button id="change-file-button" class="button"><?php _e('Change File', 'wp-redirects-contact-form-7'); ?></button>
                    <button id="remove-file-button" class="button button-link-delete" style="color: red;"><?php _e('Remove File', 'wp-redirects-contact-form-7'); ?></button>
                </div>
            </div>
            <button id="select-file-button" class="button-add-site-icon"><?php _e('Select File', 'wp-redirects-contact-form-7'); ?></button>
            <input type="hidden" id="file-url" name="file_url" value="<?php echo esc_attr($existing_file_url); ?>" />
            <input type="hidden" id="file-id" name="file_id" value="<?php echo esc_attr($existing_file_id); ?>" />
        </div>
    </div>

    <div class="wrap wraaper_settings_custom_js">
        <div class="wrapper_container_setting">
            <h3><?php _e('Custom JS', 'wp-redirects-contact-form-7'); ?>
                <div class="component--example">
                    <p class="mt20">
                        <span class="tooltip" data-tooltip="This allows you to add custom JavaScript or JQuery code as needed." data-tooltip-pos="right" data-tooltip-length="large"><span class="dashicons dashicons-info-outline"></span></span>
                    </p>
                </div>
            </h3>
            <div class="checkbox-wrapper-8">
                <input type="checkbox" id="cb3-6" class="tgl tgl-skewed" name="yspl_cf7r_custom_js_needed" value="checked" <?php echo esc_attr($js_needed == 'checked') ? "checked" : ""; ?>>
                <label for="cb3-6" data-tg-on="ON" data-tg-off="OFF" class="tgl-btn"></label>
            </div>
        </div>
        <?php wp_nonce_field('custom_js_nonce_action', 'custom_js_nonce'); ?>
        <div class="input_wrraper_div_container_custom_js">
            <textarea id="custom_js_cf7" name="custom_js_cf7" rows="20" style="width:100%;"><?php echo esc_textarea($custom_js); ?></textarea>
        </div>
    </div>
<?php
}

/* ------------------------------------------------------------------
------------- Popup HTML for admin to check Settings ----------------
------------------------------------------------------------------- */
function custom_popup_html($yspl_cf7r_submission_popup_needed, $popup_mail_sent, $popup_warning_invalid, $popup_failed_mail, $popup_failed_recaptcha)
{
?>
    <div class="wrap wraaper_settings_custom_js">
        <div class="wrapper_container_setting">
            <h3 class="wrapper-container-header-spacing"><?php _e('Popup Settings', 'wp-redirects-contact-form-7'); ?>
                <div class="component--example">
                    <p class="mt20">
                        <span class="tooltip" data-tooltip="This lets you display Popups depending on the messages submitted through the contact form." data-tooltip-pos="right" data-tooltip-length="large"><span class="dashicons dashicons-info-outline"></span></span>
                    </p>
                </div>
            </h3>
            <div class="checkbox-wrapper-8">
                <input type="checkbox" id="cb3-1" class="tgl tgl-skewed" name="yspl_cf7r_submission_popup_needed" value="checked" <?php echo esc_attr($yspl_cf7r_submission_popup_needed == 'checked') ? "checked" : ""; ?>>
                <label for="cb3-1" data-tg-on="ON" data-tg-off="OFF" class="tgl-btn"></label>
            </div>
            <div class="checkbox-wrapper-container">
                <div class="checkbox-con">
                    <h4 class="check_box_title">
                        <label class="toggle-switch-header" for="popup_mail_sent">
                            <?php _e('Need Popup For Mail Sent?', 'wp-redirects-contact-form-7'); ?>
                        </label>
                    </h4>
                    <label class="toggle-switch" for="popup_mail_sent">
                        <input type="checkbox" id="popup_mail_sent" name="popup_mail_sent" value="success" <?php echo esc_attr($popup_mail_sent == 'success') ? "checked" : ""; ?>>
                        <div class="toggle-switch-background">
                            <div class="toggle-switch-handle"></div>
                        </div>
                    </label>

                </div>
                <div class="checkbox-con">
                    <h4 class="check_box_title">
                        <label class="toggle-switch-header" for="popup_warning_invalid">
                            <?php _e('Need Popup For Invalid Message?', 'wp-redirects-contact-form-7'); ?>
                        </label>
                    </h4>
                    <label class="toggle-switch" for="popup_warning_invalid">
                        <input type="checkbox" id="popup_warning_invalid" name="popup_warning_invalid" value="warning" <?php echo esc_attr($popup_warning_invalid == 'warning') ? "checked" : ""; ?>>
                        <div class="toggle-switch-background">
                            <div class="toggle-switch-handle"></div>
                        </div>
                    </label>

                </div>
                <div class="checkbox-con">
                    <h4 class="check_box_title">
                        <label class="toggle-switch-header" for="popup_failed_mail">
                            <?php _e('Need Popup For Failed Mail?', 'wp-redirects-contact-form-7'); ?>
                        </label>
                    </h4>
                    <label class="toggle-switch" for="popup_failed_mail">
                        <input type="checkbox" id="popup_failed_mail" name="popup_failed_mail" value="error" <?php echo esc_attr($popup_failed_mail == 'error') ? "checked" : ""; ?>>
                        <div class="toggle-switch-background">
                            <div class="toggle-switch-handle"></div>
                        </div>
                    </label>

                </div>
                <div class="checkbox-con">
                    <h4 class="check_box_title">
                        <label class="toggle-switch-header" for="popup_failed_recaptcha">
                            <?php _e('Need Popup For Failed Recaptcha ?', 'wp-redirects-contact-form-7'); ?>
                        </label>
                    </h4>
                    <label class="toggle-switch" for="popup_failed_recaptcha">
                        <input type="checkbox" id="popup_failed_recaptcha" name="popup_failed_recaptcha" value="error" <?php echo esc_attr($popup_failed_recaptcha == 'error') ? "checked" : ""; ?>>
                        <div class="toggle-switch-background">
                            <div class="toggle-switch-handle"></div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>
<?php
}

/* ------------------------------------------------------------------
------------- Output Custom JS when CF7 Form is Rendered -------------
------------------------------------------------------------------- */
function enqueue_custom_js_for_cf7($form)
{
    $form_id = $form->id();
    $custom_js = get_post_meta($form_id, 'custom_js_cf7', true);
    $yspl_cf7r_custom_js_available = get_post_meta($form_id, 'yspl_cf7r_custom_js_needed', true);

    if (!empty($custom_js) && !empty($yspl_cf7r_custom_js_available)) {
        add_action('wp_footer', function () use ($custom_js) {
            echo '<script type="text/javascript">' . $custom_js . '</script>';
        });
    }
}
add_action('wpcf7_contact_form', 'enqueue_custom_js_for_cf7');


/* ------------------------------------------------------------------
----------- Parameters Passed for Popup code as per need -------------
------------------------------------------------------------------- */
function add_custom_parameters_to_cf7_response($response, $result)
{
    $form_id = $result['contact_form_id'];

    $keys = [
        'yspl_cf7r_submission_popup_needed',
        'popup_mail_sent',
        'popup_warning_invalid',
        'popup_failed_mail',
        'popup_failed_recaptcha'
    ];

    foreach ($keys as $key) {
        $response[$key] = get_post_meta(absint($form_id), $key, true);
        // error_log("Key: $key, Value: " . $response[$key]); // Log the key and value for debugging
    }

    return $response;
}
add_filter('wpcf7_ajax_json_echo', 'add_custom_parameters_to_cf7_response', 10, 2);

/* ------------------------------------------------------------------
------------ CF7 DB Handler Advertisement Section HTML----------------
------------------------------------------------------------------- */
function cf7_custom_advertisement_section_html()
{
    ob_start();
?>
    <div class="cf7-ad-section">
        <h2><?php _e('Discover Our DB Handler Plugin!', 'wp-redirects-contact-form-7'); ?></h2>
        <div class="inside">
            <p><?php _e('Enhance your WordPress experience with our powerful DB Handler Plugin. It offers:', 'wp-redirects-contact-form-7'); ?></p>
            <ul>
                <li><?php _e('Seamless database management', 'wp-redirects-contact-form-7'); ?></li>
                <li><?php _e('Form-Specific Listings', 'wp-redirects-contact-form-7'); ?></li>
                <li><?php _e('Easy integration with existing plugins', 'wp-redirects-contact-form-7'); ?></li>
                <li><?php _e('Export to CSV', 'wp-redirects-contact-form-7'); ?></li>
                <li><?php _e('And much more...', 'wp-redirects-contact-form-7'); ?></li>
            </ul>
            <p>
                <a href="<?php echo admin_url('update.php?action=install-plugin&plugin=wp-contact-form-7-db-handler&_wpnonce=' . wp_create_nonce('install-plugin_wp-contact-form-7-db-handler')); ?>" class="button button-primary"><?php _e('Try Now', 'wp-redirects-contact-form-7'); ?></a>
            </p>
        </div>
    </div>
<?php
    return ob_get_clean();
}

/* ------------------------------------------------------------------
--------------- CF7 DB Handler Advertisement Section-----------------
------------------------------------------------------------------- */
add_action('admin_footer', 'cf7_add_custom_advertisemnet_section');

function cf7_add_custom_advertisemnet_section()
{
    // Check if the plugin is installed and activated
    if (is_plugin_active('wp-contact-form-7-db-handler/wp-contact-form-7-db-handler.php')) {
        return;
    }
    // Check if we're on the CF7 admin page
    $screen = get_current_screen();
    if ($screen->id != 'toplevel_page_wpcf7') {
        return;
    }

    // Get the custom help section HTML from the PHP function
    $custom_help_section_html = cf7_custom_advertisement_section_html();
    // Encode the HTML to safely pass it to JavaScript
    $custom_help_section_html_encoded = json_encode($custom_help_section_html);
?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Insert the custom help section HTML after the existing "informationdiv"
            $('#informationdiv').after(<?php echo $custom_help_section_html_encoded; ?>);
        });
    </script>
<?php
}
// Ensure the plugin.php file is included to use the is_plugin_active function
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

// new code of sandeep end

// Store Success Page/URL Info  -- Save contact form
function yspl_cf7r_save_contact_form($contact_form)
{
    $form_id = $contact_form->id();
    if (!isset($_POST) || empty($_POST) || !isset($_POST['type']) || !isset($_POST['custom_js_cf7'])) {
        // wpcf7_enqueue_scripts();
        return true;
    } else {
        $type = sanitize_text_field($_POST['type']);
        update_post_meta($form_id, 'custom_js_cf7', wp_unslash($_POST['custom_js_cf7']));
        update_post_meta($form_id, 'yspl_cf7r_redirect_needed', sanitize_text_field($_POST['yspl_cf7r_redirect_needed']));
        update_post_meta($form_id, 'yspl_cf7r_submission_download_needed', sanitize_text_field($_POST['yspl_cf7r_submission_download_needed']));
        update_post_meta($form_id, 'yspl_cf7r_custom_js_needed', sanitize_text_field($_POST['yspl_cf7r_custom_js_needed']));
        update_post_meta($form_id, 'yspl_cf7r_submission_popup_needed', sanitize_text_field($_POST['yspl_cf7r_submission_popup_needed']));

        update_post_meta($form_id, 'popup_mail_sent', sanitize_text_field($_POST['popup_mail_sent']));
        update_post_meta($form_id, 'popup_warning_invalid', sanitize_text_field($_POST['popup_warning_invalid']));
        update_post_meta($form_id, 'popup_failed_mail', sanitize_text_field($_POST['popup_failed_mail']));
        update_post_meta($form_id, 'popup_failed_recaptcha', sanitize_text_field($_POST['popup_failed_recaptcha']));

        if ($type == 1) {
            $meta_value = sanitize_text_field($_POST['succ_page_id']);

            update_post_meta($form_id, 'succ_page_id', $meta_value);
            update_post_meta($form_id, 'check_id', "1");
        }
        if ($type == 2) {
            $meta_value = sanitize_text_field($_POST['url']);

            update_post_meta($form_id, 'succ_page_url', $meta_value);
            update_post_meta($form_id, 'check_id', "2");
        }

        // Save file data

        $file_url = esc_url_raw($_POST['file_url']);
        $file_id = intval($_POST['file_id']);
        update_post_meta($form_id, 'file_url', $file_url);
        update_post_meta($form_id, 'file_id', $file_id);
    }
}
add_action('wpcf7_after_save', 'yspl_cf7r_save_contact_form');

// Store Success Page Info  === END
/*********************************************/


/***************************************************
        ADD CUSTOM OPTION PAGE TO Contact form MENU
 ****************************************************/
// Create submenu page for contact form 7 menu
add_action('admin_menu', 'yspl_cf7r_create_menu');

function yspl_cf7r_create_menu()
{

    $edit = add_submenu_page(
        'wpcf7',
        __('Edit Contact Form', 'contact-form-7'),
        __('Form Redirects', 'contact-form-7'),
        'wpcf7_edit_contact_forms',
        'wpcf7-redirects',
        'yspl_cf7r_redirect_page'
    );

    add_action('load-' . $edit, 'wpcf7_load_contact_form_admin');
}

// Data to be shown in submenu page
function yspl_cf7r_redirect_page()
{

    ob_start();
    $cf7rTable = new CF7_Redirection_Table();
?>
    <div class="wrap">
        <h2><?php _e('Contact Forms Redirection Listing', 'wp-redirects-contact-form-7'); ?></h2>
        <?php
        $cf7rTable->prepare_items();
        $cf7rTable->display();
        ?>
    </div>
<?php
    $output = ob_get_clean();
    echo wp_kses_post($output);
}
