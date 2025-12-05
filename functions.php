<?php
/**
 * Blocksy Child Theme - FRS
 *
 * @package Blocksy_Child_FRS
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load child theme styles (Blocksy doesn't auto-load by default)
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('blocksy-child-style', get_stylesheet_uri());
});

/**
 * Child theme constants
 */
define('BLOCKSY_CHILD_FRS_VERSION', '1.0.0');
define('BLOCKSY_CHILD_FRS_PATH', get_stylesheet_directory());
define('BLOCKSY_CHILD_FRS_URL', get_stylesheet_directory_uri());

/**
 * Tell frs-lrg to load portal assets on portal pages (using theme template, not shortcode in post content)
 */
add_filter('lrh_should_load_portal', function($should_load) {
    if (blocksy_child_is_portal_page()) {
        return true;
    }
    return $should_load;
});

/**
 * Register custom sidebar widget area
 */
add_action('widgets_init', function () {
    register_sidebar(array(
        'name'          => __('FRS Below React Sidebar', 'blocksy-child-frs'),
        'id'            => 'frs-below-react-sidebar',
        'description'   => __('Widget area below the React sidebar component.', 'blocksy-child-frs'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
});

/**
 * Register Customizer settings for header background
 */
add_action('customize_register', function ($wp_customize) {
    // Add Header Background Section
    $wp_customize->add_section('frs_header_background', array(
        'title'       => __('FRS Header Background', 'blocksy-child-frs'),
        'description' => __('Customize the header background image or video.', 'blocksy-child-frs'),
        'priority'    => 30,
    ));

    // Background Type Setting
    $wp_customize->add_setting('frs_header_bg_type', array(
        'default'           => 'none',
        'sanitize_callback' => 'blocksy_child_frs_sanitize_bg_type',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('frs_header_bg_type', array(
        'label'    => __('Background Type', 'blocksy-child-frs'),
        'section'  => 'frs_header_background',
        'type'     => 'select',
        'choices'  => array(
            'none'  => __('None', 'blocksy-child-frs'),
            'image' => __('Image', 'blocksy-child-frs'),
            'video' => __('Video', 'blocksy-child-frs'),
        ),
    ));

    // Background Image Setting
    $wp_customize->add_setting('frs_header_bg_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'frs_header_bg_image', array(
        'label'           => __('Header Background Image', 'blocksy-child-frs'),
        'section'         => 'frs_header_background',
        'active_callback' => function () {
            return get_theme_mod('frs_header_bg_type') === 'image';
        },
    )));

    // Background Video URL Setting
    $wp_customize->add_setting('frs_header_bg_video', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('frs_header_bg_video', array(
        'label'           => __('Header Background Video URL', 'blocksy-child-frs'),
        'description'     => __('Enter a URL to an MP4 video file.', 'blocksy-child-frs'),
        'section'         => 'frs_header_background',
        'type'            => 'url',
        'active_callback' => function () {
            return get_theme_mod('frs_header_bg_type') === 'video';
        },
    ));

    // Video Poster Image (fallback)
    $wp_customize->add_setting('frs_header_bg_video_poster', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'frs_header_bg_video_poster', array(
        'label'           => __('Video Poster Image (Fallback)', 'blocksy-child-frs'),
        'description'     => __('Displayed while video loads or on mobile.', 'blocksy-child-frs'),
        'section'         => 'frs_header_background',
        'active_callback' => function () {
            return get_theme_mod('frs_header_bg_type') === 'video';
        },
    )));

    // Overlay Color Setting
    $wp_customize->add_setting('frs_header_bg_overlay_color', array(
        'default'           => 'rgba(0, 0, 0, 0.3)',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('frs_header_bg_overlay_color', array(
        'label'           => __('Overlay Color', 'blocksy-child-frs'),
        'description'     => __('Use rgba format, e.g., rgba(0, 0, 0, 0.3)', 'blocksy-child-frs'),
        'section'         => 'frs_header_background',
        'type'            => 'text',
        'active_callback' => function () {
            return get_theme_mod('frs_header_bg_type') !== 'none';
        },
    ));

    // Header Height Setting
    $wp_customize->add_setting('frs_header_bg_height', array(
        'default'           => '400',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('frs_header_bg_height', array(
        'label'           => __('Header Height (px)', 'blocksy-child-frs'),
        'section'         => 'frs_header_background',
        'type'            => 'number',
        'input_attrs'     => array(
            'min'  => 100,
            'max'  => 1000,
            'step' => 10,
        ),
        'active_callback' => function () {
            return get_theme_mod('frs_header_bg_type') !== 'none';
        },
    ));
});

/**
 * Sanitize background type
 */
function blocksy_child_frs_sanitize_bg_type($value) {
    $valid = array('none', 'image', 'video');
    return in_array($value, $valid, true) ? $value : 'none';
}

/**
 * Output header background CSS
 */
add_action('wp_head', function () {
    $bg_type = get_theme_mod('frs_header_bg_type', 'none');

    if ($bg_type === 'none') {
        return;
    }

    $overlay_color = get_theme_mod('frs_header_bg_overlay_color', 'rgba(0, 0, 0, 0.3)');
    $height = get_theme_mod('frs_header_bg_height', 400);

    if ($bg_type === 'image') {
        $image_url = get_theme_mod('frs_header_bg_image', '');
        if ($image_url) {
            echo '<style>
                .frs-header-background {
                    position: relative;
                    width: 100%;
                    height: ' . esc_attr($height) . 'px;
                    background-image: url(' . esc_url($image_url) . ');
                    background-size: cover;
                    background-position: center;
                }
                .frs-header-background::after {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: ' . esc_attr($overlay_color) . ';
                }
                .frs-header-background-content {
                    position: relative;
                    z-index: 1;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
            </style>';
        }
    } elseif ($bg_type === 'video') {
        $video_url = get_theme_mod('frs_header_bg_video', '');
        $poster_url = get_theme_mod('frs_header_bg_video_poster', '');

        if ($video_url) {
            echo '<style>
                .frs-header-background {
                    position: relative;
                    width: 100%;
                    height: ' . esc_attr($height) . 'px;
                    overflow: hidden;
                }
                .frs-header-background video {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    min-width: 100%;
                    min-height: 100%;
                    width: auto;
                    height: auto;
                    transform: translate(-50%, -50%);
                    object-fit: cover;
                }
                .frs-header-background::after {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: ' . esc_attr($overlay_color) . ';
                    z-index: 1;
                }
                .frs-header-background-content {
                    position: relative;
                    z-index: 2;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                @media (max-width: 768px) {
                    .frs-header-background video {
                        display: none;
                    }
                    .frs-header-background {
                        background-image: url(' . esc_url($poster_url ?: $video_url) . ');
                        background-size: cover;
                        background-position: center;
                    }
                }
            </style>';
        }
    }
});

/**
 * Render header background section
 * Usage: blocksy_child_frs_render_header_background();
 */
function blocksy_child_frs_render_header_background() {
    $bg_type = get_theme_mod('frs_header_bg_type', 'none');

    if ($bg_type === 'none') {
        return;
    }

    echo '<div class="frs-header-background">';

    if ($bg_type === 'video') {
        $video_url = get_theme_mod('frs_header_bg_video', '');
        $poster_url = get_theme_mod('frs_header_bg_video_poster', '');

        if ($video_url) {
            echo '<video autoplay muted loop playsinline';
            if ($poster_url) {
                echo ' poster="' . esc_url($poster_url) . '"';
            }
            echo '>';
            echo '<source src="' . esc_url($video_url) . '" type="video/mp4">';
            echo '</video>';
        }
    }

    echo '<div class="frs-header-background-content">';
    do_action('frs_header_background_content');
    echo '</div>';
    echo '</div>';
}

/**
 * Auto-insert header background after header (optional)
 */
add_action('blocksy:header:after', function () {
    $bg_type = get_theme_mod('frs_header_bg_type', 'none');
    $auto_insert = apply_filters('frs_auto_insert_header_background', false);

    if ($bg_type !== 'none' && $auto_insert) {
        blocksy_child_frs_render_header_background();
    }
});

/**
 * Add JavaScript to handle sidebar scroll
 * Ensures sidebar scrolls independently when user hovers and scrolls
 */
add_action('wp_footer', function () {
    ?>
    <script>
    (function() {
        const sidebar = document.querySelector('.ct-sidebar');
        if (!sidebar) return;

        sidebar.addEventListener('wheel', function(e) {
            const maxScroll = this.scrollHeight - this.clientHeight;
            const currentScroll = this.scrollTop;
            const delta = e.deltaY;

            // Only prevent default if we can scroll in that direction
            if ((delta > 0 && currentScroll < maxScroll) || (delta < 0 && currentScroll > 0)) {
                e.preventDefault();
                e.stopPropagation();
                this.scrollTop += delta;
            }
        }, { passive: false });
    })();
    </script>
    <?php
}, 100);

/**
 * Custom Nav Walker for Portal Sidebar Menu
 * Generates navigation with icons, dropdowns, and proper styling
 */
class Portal_Nav_Walker extends Walker_Nav_Menu {
    private $menu_counter = 0;

    /**
     * Start the element output for parent items
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        // Get icon from menu item meta
        $icon = get_post_meta($item->ID, '_menu_item_icon', true) ?: 'circle';

        // Check if item has children
        $has_children = !empty($item->classes) && in_array('menu-item-has-children', $item->classes);

        // Get active state
        $is_active = in_array('current-menu-item', $item->classes) || in_array('current-page-ancestor', $item->classes);
        $active_class = $is_active ? ' active' : '';

        if ($depth === 0) {
            // Parent item
            if ($has_children) {
                // Generate unique menu ID
                $menu_id = 'menu-' . ++$this->menu_counter;

                // Parent with clickable link AND dropdown toggle
                $output .= '<div class="flex items-center">';
                $output .= '<a href="' . esc_url($item->url) . '" data-wp-router-link class="flex items-center gap-2 px-4 py-3 text-base font-semibold text-white/70 hover:text-white hover:bg-white/5 transition-colors frs-nav-link flex-1' . $active_class . '">';
                $output .= $this->get_icon_svg($icon);
                $output .= '<span>' . esc_html($item->title) . '</span>';
                $output .= '</a>';
                $output .= '<button onclick="toggleMenu(\'' . $this->menu_counter . '\', event)" class="px-3 py-3 text-white/70 hover:text-white hover:bg-white/5 transition-colors" style="background: none; border: none; cursor: pointer;">';
                $output .= '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="frs-chevron" style="transition: transform 0.2s ease-in-out;"><path d="m9 18 6-6-6-6"/></svg>';
                $output .= '</button>';
                $output .= '</div>';

                // Store menu ID for submenu
                $item->menu_id = $menu_id;
            } else {
                // Parent link without children
                $output .= '<a href="' . esc_url($item->url) . '" data-wp-router-link class="flex items-center gap-2 px-4 py-3 text-base font-semibold text-white/70 hover:text-white hover:bg-white/5 transition-colors frs-nav-link' . $active_class . '">';
                $output .= $this->get_icon_svg($icon);
                $output .= '<span>' . esc_html($item->title) . '</span>';
                $output .= '</a>';
            }
        } else {
            // Child item (submenu link)
            $output .= '<a href="' . esc_url($item->url) . '" data-wp-router-link class="block px-4 py-2 text-sm text-white/60 hover:text-white hover:bg-white/5 transition-colors frs-nav-link' . $active_class . '">';
            $output .= esc_html($item->title);
            $output .= '</a>';
        }
    }

    /**
     * Start level (submenu container)
     */
    public function start_lvl(&$output, $depth = 0, $args = null) {
        // Find the parent menu ID from the last item
        // We set this in start_el
        $menu_id = 'menu-' . $this->menu_counter;

        $output .= '<div id="' . esc_attr($menu_id) . '" class="frs-submenu pl-6 border-l border-white/10 ml-4" style="display: none;">';
    }

    /**
     * End level (close submenu container)
     */
    public function end_lvl(&$output, $depth = 0, $args = null) {
        $output .= '</div>';
    }

    /**
     * End element (no closing tag needed for our links)
     */
    public function end_el(&$output, $item, $depth = 0, $args = null) {
        // No closing tag needed
    }

    /**
     * Get SVG icon markup
     */
    private function get_icon_svg($icon_name) {
        $icons = array(
            'home' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
            'user' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
            'megaphone' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',
            'clipboard' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>',
            'wrench' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
            'settings' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>',
            'bell' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>',
            'circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>',
        );

        return isset($icons[$icon_name]) ? $icons[$icon_name] : $icons['circle'];
    }
}

/**
 * Portal Frame Layout
 * Creates an app-like shell with logo section, top bar, sidebar, and content area
 */

// Check if current page should use portal frame
function blocksy_child_is_portal_page() {
    // Check if we're on a portal page post type using get_queried_object
    // This works during wp_enqueue_scripts, unlike is_singular()
    $queried_object = get_queried_object();
    if ($queried_object && isset($queried_object->post_type)) {
        $portal_post_types = array('lo_portal_page', 'frs_re_portal', 'frs_partner_portal');
        if (in_array($queried_object->post_type, $portal_post_types)) {
            return true;
        }
    }

    // Legacy: Check if we're on a portal page or its subpage
    if (is_page()) {
        global $post;

        // Get the page slug
        $slug = $post->post_name;

        // Portal container pages
        $portal_pages = array('lo', 're');

        // Check if current page is a portal container
        if (in_array($slug, $portal_pages)) {
            return true;
        }

        // Check if parent is a portal container
        if ($post->post_parent) {
            $parent = get_post($post->post_parent);
            if ($parent && in_array($parent->post_name, $portal_pages)) {
                return true;
            }
        }
    }

    // BuddyPress removed - no longer checking bp_is_user()

    return false;
}

// Get the portal type (lo or re)
function blocksy_child_get_portal_type() {
    if (!is_page()) {
        return null;
    }

    global $post;

    // Check current page
    if ($post->post_name === 'lo') {
        return 'lo';
    }
    if ($post->post_name === 're') {
        return 're';
    }

    // Check parent page
    if ($post->post_parent) {
        $parent = get_post($post->post_parent);
        if ($parent) {
            if ($parent->post_name === 'lo') {
                return 'lo';
            }
            if ($parent->post_name === 're') {
                return 're';
            }
        }
    }

    return null;
}


// Enqueue portal frame assets
// Using template_redirect hook instead of wp_enqueue_scripts to ensure queried object is set
add_action('template_redirect', function() {
    // Only enqueue on portal pages
    if (!blocksy_child_is_portal_page()) {
        return;
    }

    // Add enqueue action that will run during wp_print_scripts
    add_action('wp_print_scripts', function() {
    $manifest_path = get_stylesheet_directory() . '/assets/sidebar/manifest.json';
    if (file_exists($manifest_path)) {
        $manifest = json_decode(file_get_contents($manifest_path), true);

        if (isset($manifest['src/components-export.tsx'])) {
            $component_file = $manifest['src/components-export.tsx']['file'];
            $component_css = $manifest['src/components-export.tsx']['css'] ?? [];

            // Enqueue CSS if present
            foreach ($component_css as $css) {
                wp_enqueue_style(
                    'frs-components-css',
                    get_stylesheet_directory_uri() . '/assets/sidebar/' . $css,
                    [],
                    BLOCKSY_CHILD_FRS_VERSION
                );
            }

            // Enqueue JS as module
            // The manifest file is already in /assets/sidebar/, so component_file path is relative to that
            wp_enqueue_script(
                'frs-components',
                get_stylesheet_directory_uri() . '/assets/sidebar/' . $component_file,
                [],
                BLOCKSY_CHILD_FRS_VERSION,
                true
            );
            // Add module type attribute for ES module support
            add_filter('script_loader_tag', function($tag, $handle) {
                if ($handle === 'frs-components') {
                    return str_replace(' src', ' type="module" src', $tag);
                }
                return $tag;
            }, 10, 2);
        }
    }

    // Enqueue LRG portal sidebar assets (must be done here, not in shortcode, because shortcode runs after scripts are printed)
    if (class_exists('\LendingResourceHub\Assets\Frontend')) {
        \LendingResourceHub\Assets\Frontend::get_instance()->enqueue_portal_assets_public();
    }

    // Add portal frame styles
    wp_add_inline_style('blocksy-child-style', '
        body.portal-frame {
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body.portal-frame .ct-header,
        body.portal-frame header[data-id] {
            display: none !important;
        }

        body.portal-frame #main-container {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        .portal-frame-container {
            display: flex;
            height: 100vh;
            max-height: 100vh;
            width: 100vw;
            overflow: hidden;
            position: relative;
        }

        .portal-sidebar-wrapper {
            width: 320px;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            height: 100vh;
            max-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .portal-logo-section {
            background: #0b102c;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 0 1rem;
            flex-shrink: 0;
        }

        .portal-logo-section img {
            height: 32px;
            width: auto;
        }

        .portal-sidebar-container {
            flex: 1;
            background: #0b102c;
            /* border-right: 1px solid #e5e7eb; */
            overflow-y: auto;
        }

        .portal-content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            margin-left: 320px;
            height: 100vh;
            max-height: 100vh;
        }

        .portal-top-bar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            flex-shrink: 0;
        }

        .portal-top-bar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .portal-top-bar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .portal-content-area {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1rem;
            min-height: 0;
        }

        .portal-content-area > * {
            margin-top: 0 !important;
        }

        .portal-content-area .entry-content {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        .portal-content-area h2,
        .portal-content-area .wp-block-heading {
            margin-top: 0 !important;
        }

        @media (max-width: 768px) {
            .portal-sidebar-wrapper {
                position: fixed;
                left: -280px;
                top: 0;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
            }

            .portal-sidebar-wrapper.open {
                left: 0;
            }
        }
    ');
    }); // End wp_print_scripts
}); // End template_redirect

// Add body class for portal pages
add_filter('body_class', function($classes) {
    if (blocksy_child_is_portal_page()) {
        $classes[] = 'portal-frame';

        // Don't add has-portal-sidebar class for frs_re_portal - they have their own React sidebar
        $queried_object = get_queried_object();
        if (!($queried_object && isset($queried_object->post_type) && $queried_object->post_type === 'frs_re_portal')) {
            $classes[] = 'has-portal-sidebar';
        }
    }
    return $classes;
});

// Include Portal Sidebar Frame on portal pages
add_action('wp_body_open', function() {
    if (!blocksy_child_is_portal_page()) {
        return;
    }

    // Don't include old sidebar on frs_re_portal pages - they have their own React sidebar
    $queried_object = get_queried_object();
    if ($queried_object && isset($queried_object->post_type) && $queried_object->post_type === 'frs_re_portal') {
        return;
    }

    // Include the persistent sidebar frame
    include get_stylesheet_directory() . '/portal-sidebar-frame.php';
});

/**
 * Interactivity API for Portal Navigation
 * Enables client-side navigation using @wordpress/interactivity-router
 */
add_action('wp_enqueue_scripts', function() {
    if (!blocksy_child_is_portal_page()) {
        return;
    }

    // Enqueue portal navigation module with router dependency
    // wp_enqueue_script_module is available since WordPress 6.5
    wp_enqueue_script_module(
        'frs-portal-navigation',
        get_stylesheet_directory_uri() . '/assets/js/portal-navigation.js',
        ['@wordpress/interactivity', '@wordpress/interactivity-router'],
        BLOCKSY_CHILD_FRS_VERSION
    );
});

