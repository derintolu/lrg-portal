<?php
/**
 * Workspace Taxonomy Archive Template
 *
 * Custom layout for workspace homepages (e.g., /me/, /marketing/, /learning/)
 * Shows the same sidebar as workspace objects with the workspace's content
 *
 * @package Workspaces_Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add body class for workspace sidebar (must be before get_header)
add_filter('body_class', function($classes) {
    $classes[] = 'has-workspace-sidebar';
    return $classes;
});

// Output hero BEFORE <main> tag opens (via Blocksy hook)
add_action('blocksy:content:before', function() {
    if (function_exists('blocksy_output_hero_section')) {
        echo blocksy_output_hero_section(['type' => 'type-2']);
    }
});

get_header();

// Get current workspace term
$workspace = get_queried_object();
// Note: Sidebar frame is included via blocksy:header:after hook in functions.php
?>

<div class="workspace-archive-content">
        <?php
        // Special handling for Learning workspace - show Tutor dashboard directly
        if ($workspace->slug === 'learning' && class_exists('Workspaces_Tutor_Dashboard')) {
            echo do_shortcode('[tutor_workspace_dashboard]');
        }
        // Check if there's a designated homepage object for this workspace (set in term meta)
        elseif ($homepage_id = get_term_meta($workspace->term_id, '_workspace_homepage', true)) {
            $home_object = get_post($homepage_id);
            if ($home_object && $home_object->post_status === 'publish') {
                // Show the designated home object content
                global $post;
                $post = $home_object;
                setup_postdata($post);
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <div class="entry-content">
                        <?php
                        // Apply content filters for blocks, shortcodes, etc.
                        echo apply_filters('the_content', $post->post_content);
                        ?>
                    </div>
                </article>
                <?php
                wp_reset_postdata();
            }
        } else {
            // Default: Show workspace description and grid of objects
            ?>
            <div class="workspace-header mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo esc_html($workspace->name); ?></h1>
                <?php if ($workspace->description) : ?>
                    <p class="text-gray-600"><?php echo esc_html($workspace->description); ?></p>
                <?php endif; ?>
            </div>

            <div class="workspace-objects-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $objects = get_posts(array(
                    'post_type'      => 'workspace_object',
                    'posts_per_page' => -1,
                    'post_status'    => 'publish',
                    'orderby'        => 'menu_order',
                    'order'          => 'ASC',
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'workspace',
                            'field'    => 'term_id',
                            'terms'    => $workspace->term_id,
                        ),
                    ),
                ));

                if ($objects) :
                    foreach ($objects as $post) :
                        setup_postdata($post);
                        $icon = get_post_meta($post->ID, '_object_icon', true) ?: 'layout-dashboard';
                        ?>
                        <a href="<?php the_permalink(); ?>" class="workspace-object-card block p-6 bg-white rounded-lg border border-gray-200 hover:border-blue-500 hover:shadow-lg transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <span class="text-blue-600" data-lucide="<?php echo esc_attr($icon); ?>"></span>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900"><?php the_title(); ?></h3>
                                    <?php if (has_excerpt()) : ?>
                                        <p class="text-sm text-gray-500"><?php echo get_the_excerpt(); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        <?php
                    endforeach;
                    wp_reset_postdata();
                else :
                    ?>
                    <p class="text-gray-500 col-span-full">No objects in this workspace yet.</p>
                    <?php
                endif;
                ?>
            </div>
            <?php
        }
        ?>
</div>

<?php get_footer(); ?>
