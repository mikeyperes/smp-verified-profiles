<?php namespace smp_verified_profiles;

function enable_snippet_adjust_profiles_category_meta_box(){
// Hook into the WordPress 'add_meta_boxes' action to modify the category meta box to use radio buttons for 'profile' post type
add_action('add_meta_boxes', __NAMESPACE__ . '\\change_meta_box_to_radio', 30, 2);
}
// Ensure function existence before declaring it
if (!function_exists(__NAMESPACE__ . '\\change_meta_box_to_radio')) {
    /**
     * Replaces the default category meta box with a custom radio button meta box for 'profile' post type.
     * This allows users to select only one category (either 'Person' or 'Organization').
     *
     * @param string $post_type The current post type.
     * @param WP_Post $post The current post object.
     */
    function change_meta_box_to_radio($post_type, $post) {

        $settings = get_verified_profile_settings();
$slug = $settings['slug'];

        
        // Only allow administrators to access this functionality
        if (!current_user_can('administrator')) {
            return;
        }

        // Apply only to 'profile' post type
        if ($slug!== $post_type) {
            return;
        }

        // Remove the default category meta box
        remove_meta_box('categorydiv', $post_type, 'side');

        // Add a custom radio button category meta box
        add_meta_box(
            'single-category-radio', // Meta box ID
            'Category', // Title
            __NAMESPACE__ . '\\single_term_radio_meta_box', // Callback function
            $post_type, // Post type
            'side', // Context
            'default', // Priority
            array('taxonomy' => 'category') // Callback arguments
        );
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\change_meta_box_to_radio function is already declared", true);


// Ensure function existence before declaring it
if (!function_exists(__NAMESPACE__ . '\\single_term_radio_meta_box')) {
    /**
     * Outputs a custom radio button category meta box for the 'profile' post type.
     * Provides an option to select either 'Person' or 'Organization' as the category.
     *
     * @param WP_Post $post The current post object.
     * @param array $box The meta box arguments.
     */
    function single_term_radio_meta_box($post, $box) {
        // Set default taxonomy to 'category'
        $defaults = array('taxonomy' => 'category');
        $args = isset($box['args']) && is_array($box['args']) ? $box['args'] : array();
        extract(wp_parse_args($args, $defaults), EXTR_SKIP);

        // Get taxonomy data
        $taxonomy = get_taxonomy($taxonomy);

        // Fetch term IDs for 'person' and 'organization' categories
        $person_id = get_term_by('slug', 'person', $taxonomy->name)->term_id;
        $organization_id = get_term_by('slug', 'organization', $taxonomy->name)->term_id;

        // Get currently selected term IDs for the post
        $term_ids = wp_get_object_terms($post->ID, $taxonomy->name, array('fields' => 'ids'));

        ?>
        <div id="taxonomy-<?php echo $taxonomy->name; ?>" class="categorydiv">
            <ul id="<?php echo $taxonomy->name; ?>-tabs" class="category-tabs">
                <li class="tabs">
                    <a href="#<?php echo $taxonomy->name; ?>-all"><?php echo $taxonomy->labels->all_items; ?></a>
                </li>
            </ul>
            <div id="<?php echo $taxonomy->name; ?>-all" class="tabs-panel">
                <input type="hidden" name="tax_input[<?php echo $taxonomy->name; ?>][]" value="0"/>
                <?php
                // Radio button for 'person' category
                $checked = in_array($person_id, $term_ids) || empty($term_ids) ? 'checked="checked"' : '';
                echo '<label><input type="radio" name="tax_input[' . $taxonomy->name . '][]" value="' . $person_id . '" ' . $checked . '> Person</label><br />';

                // Radio button for 'organization' category
                $checked = in_array($organization_id, $term_ids) ? 'checked="checked"' : '';
                echo '<label><input type="radio" name="tax_input[' . $taxonomy->name . '][]" value="' . $organization_id . '" ' . $checked . '> Organization</label><br />';
                ?>
            </div>
        </div>
        <?php
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\single_term_radio_meta_box function is already declared", true);