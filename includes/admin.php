<?php

// Prevent direct access to this file for security
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register a custom post type for Hotspot Sections.
 * This lets you create and manage "Hotspot Sections" in the WordPress admin.
 */
add_action('init', function () {
    register_post_type('hotspot_section', [
        'labels' => [
            'name' => 'Hotspot Sections', // Plural name in admin menu
            'singular_name' => 'Hotspot Section', // Single item name
            'add_new' => 'Add New Section', // Button text
            'add_new_item' => 'Add New Hotspot Section',
            'edit_item' => 'Edit Hotspot Section',
            'new_item' => 'New Hotspot Section'
        ],
        'public' => false, // Not shown on the front-end
        'show_ui' => true, // Show in admin dashboard
        'supports' => ['title'], // Only needs a title
        'menu_icon' => 'dashicons-location-alt' // Pin icon in admin menu
    ]);
});

/**
 * Add a meta box to the Hotspot Section edit screen.
 * This box lets you enter image and hotspot data.
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'hotspot_meta', // HTML ID
        'Hotspot Settings', // Box title
        'hotspot_meta_callback', // Function to display box
        'hotspot_section', // Post type
        'normal', // Context
        'high' // Priority
    );
});

/**
 * Show the meta box UI for entering hotspot data.
 * Lets you set the background image and add/edit hotspots.
 *
 * @param WP_Post $post The current post object.
 */
function hotspot_meta_callback($post) {
    // Get saved image and hotspots (if any)
    $image = get_post_meta($post->ID, 'hotspot_image', true);
    $spots = get_post_meta($post->ID, 'hotspot_spots', true);
    $spots = $spots ? json_decode($spots, true) : [];

    // Security nonce for saving
    wp_nonce_field('save_hotspot_meta', 'hotspot_nonce');
    ?>

    <!-- Input for background image URL -->
    <p><strong>Background Image URL</strong></p>
    <input type="text" name="hotspot_image" value="<?php echo esc_attr($image); ?>" style="width:100%" placeholder="Paste image URL">
    <p>Click Update to view the image</p>
    <hr>
    <?php if ($image) {
    echo '<p><strong>Preview:</strong></p>';
    echo '<img src="' . esc_url($image) . '" style="max-width:100%;border:1px solid #ddd;">';
    }?>

    <!-- Hotspot fields -->
    <h3>Hotspots</h3>

    <div id="hotspot-container">
        <?php if (!empty($spots)) : ?>
            <?php foreach ($spots as $spot) : ?>
                <div class="hotspot-row" style="margin-bottom:10px;">
                    <!-- X and Y are position in percent -->
                    <input type="number" name="hotspot_x[]" value="<?php echo esc_attr($spot['x']); ?>" placeholder="X (%)" style="width:80px;">
                    <input type="number" name="hotspot_y[]" value="<?php echo esc_attr($spot['y']); ?>" placeholder="Y (%)" style="width:80px;">
                    <input type="text" name="hotspot_title[]" value="<?php echo esc_attr($spot['title']); ?>" placeholder="Title" style="width:150px;">
                    <input type="text" name="hotspot_text[]" value="<?php echo esc_attr($spot['text']); ?>" placeholder="Description" style="width:300px;">
                    <input type="text" name="hotspot_icon[]" value="<?php echo esc_attr($spot['icon'] ?? ''); ?>"placeholder="Icon Image URL" style="width:250px;">
                    <button type="button" class="button remove-hotspot">Remove</button>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <!-- Show one empty row if no hotspots yet -->
            <div class="hotspot-row" style="margin-bottom:10px;">
                <input type="number" name="hotspot_x[]" placeholder="X (%)" style="width:80px;">
                <input type="number" name="hotspot_y[]" placeholder="Y (%)" style="width:80px;">
                <input type="text" name="hotspot_title[]" placeholder="Title" style="width:150px;">
                <input type="text" name="hotspot_text[]" placeholder="Description" style="width:300px;">
            </div>

        <?php endif; ?>

    </div>
    <!-- Button to add more hotspots -->
    <button type="button" class="button" id="add-hotspot">Add Hotspot</button>

    <p><small>X and Y values should be between 0–100 (percentage based positioning).</small></p>

    <?php
}

/**
 * Save the meta box data when the post is saved.
 * Stores the image URL and all hotspot data as post meta.
 */
add_action('save_post', function ($post_id) {
    // Check security nonce
    if (!isset($_POST['hotspot_nonce']) ||
        !wp_verify_nonce($_POST['hotspot_nonce'], 'save_hotspot_meta')) {
        return;
    }

    // Prevent auto-save from overwriting
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }


    // Save the background image URL
    if (isset($_POST['hotspot_image'])) {
        update_post_meta(
            $post_id,
            'hotspot_image',
            esc_url($_POST['hotspot_image']) // Clean the URL
        );
    }

    // Save all hotspots (positions, titles, descriptions, icons)
    if (
        isset($_POST['hotspot_x'], $_POST['hotspot_y'],
              $_POST['hotspot_title'], $_POST['hotspot_text'])
    ) {
        $spots = [];

        foreach ($_POST['hotspot_x'] as $i => $x) {
            if ($x === '') continue; // Skip empty rows

            $spots[] = [
                'x' => intval($x), // X position (percent)
                'y' => intval($_POST['hotspot_y'][$i]), // Y position (percent)
                'title' => sanitize_text_field($_POST['hotspot_title'][$i]), // Hotspot title
                'text' => sanitize_text_field($_POST['hotspot_text'][$i]), // Hotspot description
                'icon'  => esc_url($_POST['hotspot_icon'][$i] ?? ''), // Optional icon image URL
            ];
        }

        // Save all hotspots as a JSON string
        update_post_meta($post_id, 'hotspot_spots', json_encode($spots));
    }
});

/**
 * Show the [hotspot id="..."] shortcode in the Hotspot Section list table in admin.
 * Makes it easy to copy the shortcode for use in posts or pages.
 */
add_filter('manage_hotspot_section_posts_columns', function ($columns) {
    $columns['hotspot_shortcode'] = 'Shortcode';
    return $columns;
});

/**
 * Output the actual shortcode in the custom column for each Hotspot Section.
 */
add_action('manage_hotspot_section_posts_custom_column', function ($column, $post_id) {
    if ($column === 'hotspot_shortcode') {
        echo '<code>[hotspot id="' . $post_id . '"]</code>';
    }
}, 10, 2);

/**
 * Add JavaScript to the admin footer for dynamic hotspot row management.
 * Lets you add or remove hotspot rows without reloading the page.
 */
add_action('admin_footer', function () {
?>
<script>
jQuery(document).ready(function($){
    // Add new hotspot row
    $('#add-hotspot').on('click', function(){
        var row = $('.hotspot-row:first').clone();
        row.find('input').val('');
        $('#hotspot-container').append(row);
    });

    // Remove hotspot row (but keep at least one)
    $(document).on('click', '.remove-hotspot', function(){
        if ($('.hotspot-row').length > 1) {
            $(this).closest('.hotspot-row').remove();
        } else {
            alert('At least one hotspot is required.');
        }
    });
});
</script>
<?php
});
