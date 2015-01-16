<?php
/*
 * Plugin Name: AdEntify
 * Plugin URI: http://wordpress.adentify.com
 * Description: A brief description of the Plugin.
 * Version: 1.0.1
 * Author: ValYouAd
 * Author URI: http://www.valyouad.com
 * License: GPL2
 */

/*  Copyright 2014  ValYouAd

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined('ABSPATH') or die("No script kiddies please!");

define( 'ADENTIFY_URL', 'https://adentify.com/%s');
define( 'ADENTIFY_API_ROOT_URL', sprintf(ADENTIFY_URL, 'api/v1/%s') );
define( 'ADENTIFY_TOKEN_URL', sprintf(ADENTIFY_URL, 'oauth/v2/token'));
define( 'ADENTIFY_AUTHORIZATION_URL', sprintf(ADENTIFY_URL, 'oauth/v2/auth'));
define( 'ADENTIFY__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ADENTIFY__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADENTIFY__PLUGIN_SETTINGS', serialize(array(
    'IS_PRIVATE' => 'photoIsPrivate',
    'TAGS_VISIBILITY' => 'tagsVisibility',
    'TAGS_SHAPE' => 'tagShape',
    'GOOGLE_MAPS_KEY' => 'googleMapsKey',
    'PRODUCT_PROVIDERS' => 'productsProviders',
)));
define( 'ADENTIFY_PLUGIN_SETTINGS_PAGE_NAME', 'adentify_plugin_submenu');
define( 'ADENTIFY_REDIRECT_URI', admin_url(sprintf('options-general.php?page=%s', ADENTIFY_PLUGIN_SETTINGS_PAGE_NAME)) );
define( 'ADENTIFY_ADMIN_URL', admin_url('admin-ajax.php'));
define( 'ADENTIFY_AJAX_URL', plugin_dir_url( __FILE__ ) . 'ajax.php');
define( 'ADENTIFY_API_CLIENT_NAME', sprintf('plugin_wordpress_%s', $_SERVER['HTTP_HOST']));
define( 'ADENTIFY_API_CLIENT_ID_KEY', 'api_client_id');
define( 'ADENTIFY_API_CLIENT_SECRET_KEY', 'api_client_secret');
define( 'ADENTIFY_API_ACCESS_TOKEN', 'api_access_token');
define( 'ADENTIFY_API_REFRESH_TOKEN', 'api_refresh_token');
define( 'ADENTIFY_API_EXPIRES_TIMESTAMP', 'api_expires_timestamp');
define( 'PLUGIN_VERSION', '1.0.1');
define( 'ADENTIFY_SQL_TABLE_PHOTOS', 'adentify_photos');

require 'vendor/autoload.php';
require_once( ADENTIFY__PLUGIN_DIR . 'public/APIManager.php' );
require_once( ADENTIFY__PLUGIN_DIR . 'public/DBManager.php' );
require_once( ADENTIFY__PLUGIN_DIR . 'public/Photo.php' );
require_once( ADENTIFY__PLUGIN_DIR . 'public/Tag.php' );
require_once( ADENTIFY__PLUGIN_DIR . 'public/Twig.php' );

if (!APIManager::getInstance()->isAccesTokenValid()) {
    APIManager::getInstance()->revokeAccessToken();
}

putenv('LC_ALL='.get_locale());
setlocale(LC_ALL, get_locale());

// Specify location of translation tables
bindtextdomain("adentify", ADENTIFY__PLUGIN_DIR."languages");
bind_textdomain_codeset('adentify', 'UTF-8');

// Choose domain
textdomain("adentify");

/**
 * Add a icon to the beginning of every post page.
 *
 * @uses is_single()
 */
function my_the_content_filter( $content ) {
    preg_match_all('/\[adentify=([0-9]+)\]/i', $content, $matches);
    $i = count($matches[1]);
    if (isset($matches[1])) {
        foreach($matches[1] as $photoId)
        {
            $photo = new Photo($photoId);
            $photo->load();

            $content = preg_replace(sprintf('/\[adentify=(%s)\]/i', $photoId), $photo->render(true, $i--), $content, 1);
        }
    }
    $content = '<div class="ad-post-container">'. $content .'</div>';
    return $content;
}
add_filter( 'the_content', 'my_the_content_filter', 20 );
add_filter( 'the_excerpt', 'my_the_content_filter', 20 );

function adentify_setting_menu() {
    $settingsTab = null;
    switch (substr(get_locale(), 0, 2)) {
        case 'fr':
            $settingsTab = 'Paramètres AdEntify';
            break;
        default:
            $settingsTab = "AdEntify settings";
    }
	add_options_page( 'Adentify settings', $settingsTab, 'manage_options', ADENTIFY_PLUGIN_SETTINGS_PAGE_NAME, 'adentify_plugin_settings' );
}
add_action( 'admin_menu', 'adentify_setting_menu' );

function adentify_plugin_settings() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

    if (isset($_GET['code'])) {
        APIManager::getInstance()->getAccessTokenWithAuthorizationCode($_GET['code']);
    }

    $settings = array();
    $productProvidersId = array();

    //fill the settings array with the user's providers
    $productProviders = APIManager::getInstance()->getProductProviders();
    if (!empty($productProviders))
    {
        foreach(json_decode($productProviders) as $provider)
        {
            $providerName = $provider->product_providers->provider_key;
            $settings['providers_list'][] = $providerName;
            $productProvidersId[$providerName] = $provider->product_providers->id;
            $settings['productProvidersKey'][$providerName.'ProviderKeyVal'] = get_option($providerName.'ProviderKey');
        }
    }

    //fill the settings array with wordpress options if they are already set
    foreach(unserialize(ADENTIFY__PLUGIN_SETTINGS) as $key)
        $settings[$key.'Val'] = get_option($key);

    if ($_POST) {
        //update the settings array & the wordpress options
	    foreach(unserialize(ADENTIFY__PLUGIN_SETTINGS) as $key) {
            $settings[$key.'Val'] = (isset($_POST[$key])) ? $_POST[$key] : null;
            update_option($key, (isset($_POST[$key])) ? $_POST[$key] : null);
            foreach (json_decode($productProviders) as $provider) {
                $providerKey = $provider->product_providers->provider_key.'ProviderKey';
                if ($providerKey != 'adentifyProviderKey') {
                    $settings['productProvidersKey'][$providerKey.'Val'] = (isset($_POST[$providerKey])) ? $_POST[$providerKey] : null;
                    update_option($providerKey, (isset($_POST[$providerKey])) ? $_POST[$providerKey] : null);
                    APIManager::getInstance()->putUserProductProvider($productProvidersId[substr($providerKey, 0, strpos($providerKey, 'ProviderKey'))], $_POST[$providerKey]);
                }
            }
        }

        echo '<div class="updated"><p><strong>Settings saved.</strong></p></div>';

        wp_localize_script('adentify-admin-js', 'adentifyTagsData', array(
            'admin_ajax_url' => ADENTIFY_ADMIN_URL,
            'tag_shape' => get_option(unserialize(ADENTIFY__PLUGIN_SETTINGS)['TAGS_SHAPE']),
        ));
    }

    echo Twig::render('adentify.settings.html.twig', $settings);
}

function adentify_button($editor_id = 'content') {
    $max_upload_size = wp_max_upload_size() / pow(2, 20);

    //displays the button
    printf( '<a href="#" id="adentify-upload-img" class="button add_media" data-editor="%s" title="%s">%s</a>',
        esc_attr( $editor_id ),
        esc_attr__( 'Upload images with AdEntify plugin' ),
        'AdEntify'
    );
    echo Twig::render('admin\modals\upload.modal.html.twig', array(
        'photos' => DBManager::getInstance()->getPhotos(),
        'max_upload_size' => $max_upload_size
    ));
    echo Twig::render('admin\modals\tag.modal.html.twig', array());
}
add_action( 'media_buttons', 'adentify_button' );

/* CSS and JS files */
function wptuts_styles_with_the_lot() {
    wp_register_style( 'adentify-tags-style', plugins_url( '/css/adentify-tags.css', __FILE__ ), array(), PLUGIN_VERSION, 'all');
    wp_enqueue_style( 'adentify-tags-style' );

    wp_register_script( 'jquery.ui.widget.js', plugins_url( '/js/vendor/jquery.ui.widget.js', __FILE__ ), array('jquery'), PLUGIN_VERSION, 'all');
    wp_register_script( 'jquery.iframe-transport.js', plugins_url( '/js/vendor/jquery.iframe-transport.js', __FILE__ ), array('jquery'), PLUGIN_VERSION, 'all');
    wp_register_script( 'jquery.fileupload.js', plugins_url( '/js/vendor/jquery.fileupload.js', __FILE__ ), array('jquery'), PLUGIN_VERSION, 'all');
    wp_register_script( 'adentify-js', plugins_url( '/js/adentify.js', __FILE__ ), array('jquery'), PLUGIN_VERSION, 'all');

    wp_localize_script('adentify-js', 'adentifyTagsData', array(
        'admin_ajax_url' => ADENTIFY_ADMIN_URL,
    ));

    wp_enqueue_script( 'adentify-js' );
    wp_enqueue_script( 'jquery.ui.widget.js' );
    wp_enqueue_script( 'jquery.iframe-transport.js' );
    wp_enqueue_script( 'jquery.fileupload.js' );

    $googleMapsKey = get_option(unserialize(ADENTIFY__PLUGIN_SETTINGS)['GOOGLE_MAPS_KEY']);
    if (!empty($googleMapsKey))
        wp_enqueue_script( 'google-maps', '//maps.googleapis.com/maps/api/js?key=' . $googleMapsKey,  array(), false, false);

}
/* CSS and JS Files for admin */
function wptuts_admin_styles_with_the_lot() {
    wp_register_style( 'adentify-admin-style', plugins_url( '/css/adentify.admin.css', __FILE__ ), array(), PLUGIN_VERSION, 'all' );
    wp_enqueue_style( 'adentify-admin-style' );

    // AdEntify
    wp_register_script( 'adentify-admin-js', plugins_url( '/js/adentify.admin.js', __FILE__ ), array('jquery'), PLUGIN_VERSION, 'all');
    $providers = get_option(unserialize(ADENTIFY__PLUGIN_SETTINGS)['PRODUCT_PROVIDERS']);
    if (is_array($providers) && count($providers)) {
        $providers = implode('+', $providers);
    }
    wp_localize_script('adentify-admin-js', 'adentifyTagsData', array(
        'admin_ajax_url' => ADENTIFY_ADMIN_URL,
        'adentify_api_brand_search_url' => sprintf(ADENTIFY_API_ROOT_URL, 'brand/search'),
        'adentify_api_brand_get_url' => sprintf(ADENTIFY_API_ROOT_URL, 'brands/'),
        'adentify_api_product_search_url' => sprintf(ADENTIFY_API_ROOT_URL, 'product/search'),
        'adentify_api_product_get_url' => sprintf(ADENTIFY_API_ROOT_URL, 'products/'),
        'adentify_api_venue_search_url' => sprintf(ADENTIFY_API_ROOT_URL, 'venue/search'),
        'adentify_api_venue_get_url' => sprintf(ADENTIFY_API_ROOT_URL, 'venues/'),
        'adentify_api_venue_post_url' => sprintf(ADENTIFY_API_ROOT_URL, 'venue'),
        'adentify_api_person_search_url' => sprintf(ADENTIFY_API_ROOT_URL, 'person/search'),
        'adentify_api_person_get_url' => sprintf(ADENTIFY_API_ROOT_URL, 'people/'),
        'adentify_api_csrf_token' => sprintf(ADENTIFY_API_ROOT_URL, 'csrftokens/'),
        'adentify_api_analytics_post_url' => sprintf(ADENTIFY_API_ROOT_URL, 'analytics'),
        'adentify_api_access_token' => APIManager::getInstance()->getAccessToken(),
        'tag_shape' => get_option(unserialize(ADENTIFY__PLUGIN_SETTINGS)['TAGS_SHAPE']),
        'product_providers' => $providers
    ));
    wp_enqueue_script( 'adentify-admin-js' );

    // SELECT2.js
    wp_register_style( 'adentify-select2-style', plugins_url( '/js/vendor/select2/select2.css', __FILE__ ), array(), PLUGIN_VERSION, 'all' );
    wp_enqueue_style( 'adentify-select2-style' );
    wp_register_script( 'adentify-select2-js', plugins_url( '/js/vendor/select2/select2.min.js', __FILE__ ), array('jquery'), PLUGIN_VERSION, 'all');
    wp_enqueue_script( 'adentify-select2-js' );
    // tinyeditor
    wp_register_style( 'adentify-tinyeditor-style', plugins_url( '/js/vendor/tinyeditor/tinyeditor.css', __FILE__ ), array(), PLUGIN_VERSION, 'all' );
    wp_enqueue_style( 'adentify-tinyeditor-style' );
    wp_register_script( 'adentify-tinyeditor-js', plugins_url( '/js/vendor/tinyeditor/tiny.editor.packed.js', __FILE__ ), array('jquery'), PLUGIN_VERSION, 'all');
    wp_enqueue_script( 'adentify-tinyeditor-js' );
}
add_action( 'wp_enqueue_scripts', 'wptuts_styles_with_the_lot' );
add_action( 'admin_enqueue_scripts', 'wptuts_styles_with_the_lot' );
add_action( 'admin_enqueue_scripts', 'wptuts_admin_styles_with_the_lot' );

function adentify_register_attachments_tax()
{
    register_taxonomy( 'adentify-category', 'attachment',
        array(
            'labels' =>  array(
                'name'              => 'Plugin',
                'singular_name'     => 'Plugin',
                'search_items'      => 'Search plugin Categories',
                'all_items'         => 'All Plugin Categories',
                'edit_item'         => 'Edit Plugin Categories',
                'update_item'       => 'Update Plugin Category',
                'add_new_item'      => 'Add New Plugin Category',
                'new_item_name'     => 'New Plugin Category Name',
                'menu_name'         => 'Plugin',
            ),
            'hierarchical' => true,
            'sort' => true,
            'show_admin_column' => true
        )
    );
}
add_action( 'init', 'adentify_register_attachments_tax', 0 );

/* AdEntify plugin activated */
function adentify_activated() {

    APIManager::getInstance()->registerPluginClient();

    // Database config
    global $wpdb;
    $table_name = $wpdb->prefix . ADENTIFY_SQL_TABLE_PHOTOS;

    /*
     * We'll set the default character set and collation for this table.
     * If we don't do this, some characters could end up being converted
     * to just ?'s when saved in our table.
    */
    $charset_collate = '';

    if ( ! empty( $wpdb->charset ) ) {
        $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
    }

    if ( ! empty( $wpdb->collate ) ) {
        $charset_collate .= " COLLATE {$wpdb->collate}";
    }

    $sql = "CREATE TABLE $table_name (
      id bigint(20) NOT NULL AUTO_INCREMENT,
      time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      wordpress_photo_id bigint(20) NOT NULL,
      adentify_photo_id bigint(20) NOT NULL,
      thumb_url text NOT NULL,
      UNIQUE KEY id (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    add_option('adentify_plugin_redirect', true);
}
register_activation_hook( __FILE__, 'adentify_activated' );

function adentify_redirect() {
    if (get_option('adentify_plugin_redirect', false)) {
        delete_option('adentify_plugin_redirect');
        wp_redirect(ADENTIFY_REDIRECT_URI);
    }
}
add_action('admin_init', 'adentify_redirect');

function ad_upload() {
    if (APIManager::getInstance()->getAccessToken())
    {
        // upload the file in the upload folder
        $upload = wp_upload_bits($_FILES["ad-upload-img"]["name"], null, file_get_contents($_FILES["ad-upload-img"]["tmp_name"]));

        if (!empty($upload))
        {
            // $filename should be the path to a file in the upload directory.
            $filename = $upload['file'];

            // The ID of the post this attachment is for.
            $parent_post_id = 0;

            // Check the type of tile. We'll use this as the 'post_mime_type'.
            $filetype = wp_check_filetype( basename( $filename ), null );

            // Get the path to the upload directory.
            $wp_upload_dir = wp_upload_dir();

            // Prepare an array of post data for the attachment.
            $attachment = array(
                'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
                'post_mime_type' => $filetype['type'],
                'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );

            // Insert the attachment.
            $attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );

            // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
            require_once( ABSPATH . 'wp-admin/includes/image.php' );

            // Generate the metadata for the attachment, and update the database record.
            $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
            wp_update_attachment_metadata( $attach_id, $attach_data );

            // Set the AdEntify category to the new image
            if ($attach_id)
                wp_set_object_terms( $attach_id, array('AdEntify'), 'adentify-category', true );

            $photo = new Photo();
            if ($result = APIManager::getInstance()->postPhoto($photo, fopen($_FILES['ad-upload-img']['tmp_name'], 'r'),
                'http'.(isset($_SERVER['HTTPS']) ? 's' : '').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']))
            {
                $result = $result->getBody();
                $photo->setSmallUrl(json_decode($result)->small_url);
                $photo->setId(json_decode($result)->id);
                DBManager::getInstance()->insertPhoto($photo, $attach_id);
                wp_send_json_success(array(
                    'photo' => json_decode($result),
                    'wp_photo_id' => $attach_id
                ));
            }
            else
                wp_send_json_error("unknown error");
        }
    }
    else
        wp_send_json_error("status code: 401 Unauthorized");
}
add_action( 'wp_ajax_ad_upload', 'ad_upload' );

function ad_tag() {
    $tag = Tag::loadPost($_POST['tag']);
    if (is_array($tag) && array_key_exists('error', $tag)) {
        throw new Exception('tag error');
    }
    else if ($result = APIManager::getInstance()->postTag($tag))
        echo $result->getBody();
    exit();
}
add_action( 'wp_ajax_ad_tag', 'ad_tag' );

function ad_get_photo() {
    @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
    echo APIManager::getInstance()->getPhoto($_GET['photo_id']);
    wp_die();
}
add_action( 'wp_ajax_ad_get_photo', 'ad_get_photo' );

function ad_analytics() {
    echo APIManager::getInstance()->postAnalytic($_POST['analytic']);
}
add_action( 'wp_ajax_nopriv_ad_analytics', 'ad_analytics');
//add_action( 'wp_ajax_ad_analytics', 'ad_analytics');

function ad_admin_notice() {
    if (!APIManager::getInstance()->getAccessToken() && !array_key_exists('code', $_GET))
        echo Twig::render('admin\notices.html.twig', array(
            'authorization_url' => APIManager::getInstance()->getAuthorizationUrl()
        ));
}
add_action( 'admin_notices', 'ad_admin_notice' );

function ad_delete_photo() {
    if (APIManager::getInstance()->getAccessToken()) {
        wp_delete_attachment($_GET['wp_photo_id']);
        DBManager::getInstance()->deletePhoto($_GET['wp_photo_id']);
        print_r(APIManager::getInstance()->deletePhoto($_GET['photo_id']));
    }
}
add_action( 'wp_ajax_ad_delete_photo', 'ad_delete_photo' );

function ad_remove_tag() {
    if (APIManager::getInstance()->getAccessToken()) {
        print_r(APIManager::getInstance()->deleteTag($_GET['tag_id']));
    }
}
add_action( 'wp_ajax_ad_remove_tag', 'ad_remove_tag' );