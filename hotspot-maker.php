<?php
/**
 * Plugin Name: Hotspot Maker
 * Plugin URI: https://abidkp.com
 * Description: A plugin to create interactive hotspots on images
 * Version: 1.0.0
 * Author: Abid KP
 * Author URI: https://abidkp.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hotspot-maker
 */

// Stop the file if accessed directly (for security)
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Set up plugin path and URL constants for easy file loading later.
 */
define('HOTSPOT_MAKER_PATH', plugin_dir_path(__FILE__));
define('HOTSPOT_MAKER_URL', plugin_dir_url(__FILE__));

/**
 * Load the admin code for managing hotspots in the WordPress dashboard.
 */
require_once HOTSPOT_MAKER_PATH . 'includes/admin.php';

/**
 * Add the plugin's CSS file to the website's frontend.
 * This makes sure the hotspots look correct for visitors.
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'hotspot-maker-style', // Unique name for the style
        HOTSPOT_MAKER_URL . 'assets/style.css', // Path to the CSS file
        [], // No dependencies
        '1.0' // Version number
    );
});

/**
 * Register the [hotspot id="123"] shortcode.
 * This lets users display a hotspot image with interactive points anywhere on their site.
 */
add_shortcode('hotspot', function ($atts) {
    // Get the "id" attribute from the shortcode, default to empty
    $atts = shortcode_atts([
        'id' => ''
    ], $atts);

    // Make sure the ID is a valid number
    $post_id = intval($atts['id']);
    if (!$post_id) {
        return '';
    }

    // Get the image URL and hotspots data from the database
    $image = get_post_meta($post_id, 'hotspot_image', true);
    $spots = get_post_meta($post_id, 'hotspot_spots', true);
    $spots = json_decode($spots, true);

    // If no image or no hotspots, show nothing
    if (!$image || empty($spots) || !is_array($spots)) {
        return '';
    }

    // Start capturing the HTML output
    ob_start();
    ?>

    <!-- Hotspot image and points -->
    <div class="hotspot-wrapper">
        <img src="<?php echo esc_url($image); ?>" class="hotspot-bg" alt="Hotspot Image">

        <?php foreach ($spots as $spot): 
            // Make sure each spot has the required data
            if (
                !isset($spot['x'], $spot['y'], $spot['title'], $spot['text'])
            ) continue;
        ?>
            <div class="hotspot-item"
                 style="top:<?php echo esc_attr($spot['y']); ?>%;
                        left:<?php echo esc_attr($spot['x']); ?>%;">
                
                <!-- Show icon image if set, otherwise show a plus sign -->
                <?php if (!empty($spot['icon'])) : ?>
                    <img src="<?php echo esc_url($spot['icon']); ?>" class="hotspot-icon-img" />
                <?php else : ?>
                    <span class="hotspot-icon">+</span>
                <?php endif; ?>

                <!-- Tooltip with title and description -->
                <div class="hotspot-tooltip">
                    <strong><?php echo esc_html($spot['title']); ?></strong>
                    <p><?php echo esc_html($spot['text']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php
    // Return the HTML to display on the page
    return ob_get_clean();
});