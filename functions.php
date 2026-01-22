<?php
/**
 * Workspaces Theme - Blocksy Child Theme
 *
 * @package Workspaces_Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load child theme styles (Blocksy doesn't auto-load by default)
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('workspaces-style', get_stylesheet_uri());

    // Enqueue Interactivity API for workspace navigation
    if (function_exists('workspaces_is_workspace_page') && workspaces_is_workspace_page()) {
        wp_enqueue_script_module('@wordpress/interactivity-router');
    }
});

/**
 * Child theme constants
 */
define('WORKSPACES_THEME_VERSION', '1.0.0');
define('WORKSPACES_THEME_PATH', get_stylesheet_directory());
define('WORKSPACES_THEME_URL', get_stylesheet_directory_uri());

/**
 * Load includes
 */
require_once WORKSPACES_THEME_PATH . '/includes/sidebar-insights-slider.php';

/**
 * Time-based greeting shortcode
 * Usage: [time_greeting] outputs "Good Morning, Derin!"
 */
add_shortcode('time_greeting', function() {
    $hour = (int) current_time('G');
    
    if ($hour >= 5 && $hour < 12) $greeting = 'Good Morning';
    elseif ($hour >= 12 && $hour < 17) $greeting = 'Good Afternoon';
    elseif ($hour >= 17 && $hour < 21) $greeting = 'Good Evening';
    else $greeting = 'Good Night';
    
    $first_name = wp_get_current_user()->user_firstname ?: 'there';
    
    return '<h2 style="color: #ffffff; font-size: 20px; font-weight: 600; margin: 0;">' . esc_html("$greeting, $first_name!") . '</h2>';
});

// Enable shortcodes in widgets
add_filter('widget_text', 'do_shortcode');
add_filter('widget_custom_html_content', 'do_shortcode');

/**
 * Load Lucide Icons from workspaces plugin for sidebar navigation
 */
$lucide_icons_path = WP_PLUGIN_DIR . '/workspaces/includes/class-lucide-icons.php';
if (file_exists($lucide_icons_path) && !class_exists('Lucide_Icons')) {
    require_once $lucide_icons_path;
}

/**
 * Tell frs-lrg to load workspace assets on workspace pages (using theme template, not shortcode in post content)
 */
add_filter('lrh_should_load_portal', function($should_load) {
    if (workspaces_is_workspace_page()) {
        return true;
    }
    return $should_load;
});

/**
 * Register custom sidebar widget areas
 */
add_action('widgets_init', function () {
    register_sidebar(array(
        'name'          => __('Workspace Below Sidebar', 'workspaces'),
        'id'            => 'workspace-below-sidebar',
        'description'   => __('Widget area below the workspace sidebar component.', 'workspaces'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => __('Workspace Sidebar Header', 'workspaces'),
        'id'            => 'workspace-sidebar-header',
        'description'   => __('16:9 header area at top of workspace sidebar. Use Cover block for image/video backgrounds.', 'workspaces'),
        'before_widget' => '<div id="%1$s" class="widget workspace-header-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '',
        'after_title'   => '',
    ));
});

/**
 * Register Customizer settings for header background
 */
add_action('customize_register', function ($wp_customize) {
    // Add Header Background Section
    $wp_customize->add_section('workspaces_header_background', array(
        'title'       => __('Workspace Header Background', 'workspaces'),
        'description' => __('Customize the header background image or video.', 'workspaces'),
        'priority'    => 30,
    ));

    // Background Type Setting
    $wp_customize->add_setting('workspaces_header_bg_type', array(
        'default'           => 'none',
        'sanitize_callback' => 'workspaces_sanitize_bg_type',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('workspaces_header_bg_type', array(
        'label'    => __('Background Type', 'workspaces'),
        'section'  => 'workspaces_header_background',
        'type'     => 'select',
        'choices'  => array(
            'none'  => __('None', 'workspaces'),
            'image' => __('Image', 'workspaces'),
            'video' => __('Video', 'workspaces'),
        ),
    ));

    // Background Image Setting
    $wp_customize->add_setting('workspaces_header_bg_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'workspaces_header_bg_image', array(
        'label'           => __('Header Background Image', 'workspaces'),
        'section'         => 'workspaces_header_background',
        'active_callback' => function () {
            return get_theme_mod('workspaces_header_bg_type') === 'image';
        },
    )));

    // Background Video URL Setting
    $wp_customize->add_setting('workspaces_header_bg_video', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('workspaces_header_bg_video', array(
        'label'           => __('Header Background Video URL', 'workspaces'),
        'description'     => __('Enter a URL to an MP4 video file.', 'workspaces'),
        'section'         => 'workspaces_header_background',
        'type'            => 'url',
        'active_callback' => function () {
            return get_theme_mod('workspaces_header_bg_type') === 'video';
        },
    ));

    // Video Poster Image (fallback)
    $wp_customize->add_setting('workspaces_header_bg_video_poster', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'workspaces_header_bg_video_poster', array(
        'label'           => __('Video Poster Image (Fallback)', 'workspaces'),
        'description'     => __('Displayed while video loads or on mobile.', 'workspaces'),
        'section'         => 'workspaces_header_background',
        'active_callback' => function () {
            return get_theme_mod('workspaces_header_bg_type') === 'video';
        },
    )));

    // Overlay Color Setting
    $wp_customize->add_setting('workspaces_header_bg_overlay_color', array(
        'default'           => 'rgba(0, 0, 0, 0.3)',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('workspaces_header_bg_overlay_color', array(
        'label'           => __('Overlay Color', 'workspaces'),
        'description'     => __('Use rgba format, e.g., rgba(0, 0, 0, 0.3)', 'workspaces'),
        'section'         => 'workspaces_header_background',
        'type'            => 'text',
        'active_callback' => function () {
            return get_theme_mod('workspaces_header_bg_type') !== 'none';
        },
    ));

    // Header Height Setting
    $wp_customize->add_setting('workspaces_header_bg_height', array(
        'default'           => '400',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('workspaces_header_bg_height', array(
        'label'           => __('Header Height (px)', 'workspaces'),
        'section'         => 'workspaces_header_background',
        'type'            => 'number',
        'input_attrs'     => array(
            'min'  => 100,
            'max'  => 1000,
            'step' => 10,
        ),
        'active_callback' => function () {
            return get_theme_mod('workspaces_header_bg_type') !== 'none';
        },
    ));
});

/**
 * Sanitize background type
 */
function workspaces_sanitize_bg_type($value) {
    $valid = array('none', 'image', 'video');
    return in_array($value, $valid, true) ? $value : 'none';
}

/**
 * Output header background CSS
 */
add_action('wp_head', function () {
    $bg_type = get_theme_mod('workspaces_header_bg_type', 'none');

    if ($bg_type === 'none') {
        return;
    }

    $overlay_color = get_theme_mod('workspaces_header_bg_overlay_color', 'rgba(0, 0, 0, 0.3)');
    $height = get_theme_mod('workspaces_header_bg_height', 400);

    if ($bg_type === 'image') {
        $image_url = get_theme_mod('workspaces_header_bg_image', '');
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
        $video_url = get_theme_mod('workspaces_header_bg_video', '');
        $poster_url = get_theme_mod('workspaces_header_bg_video_poster', '');

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
 * Usage: workspaces_render_header_background();
 */
function workspaces_render_header_background() {
    $bg_type = get_theme_mod('workspaces_header_bg_type', 'none');

    if ($bg_type === 'none') {
        return;
    }

    echo '<div class="workspace-header-background">';

    if ($bg_type === 'video') {
        $video_url = get_theme_mod('workspaces_header_bg_video', '');
        $poster_url = get_theme_mod('workspaces_header_bg_video_poster', '');

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

    echo '<div class="workspace-header-background-content">';
    do_action('workspaces_header_background_content');
    echo '</div>';
    echo '</div>';
}

/**
 * Auto-insert header background after header (optional)
 */
add_action('blocksy:header:after', function () {
    $bg_type = get_theme_mod('workspaces_header_bg_type', 'none');
    $auto_insert = apply_filters('workspaces_auto_insert_header_background', false);

    if ($bg_type !== 'none' && $auto_insert) {
        workspaces_render_header_background();
    }
});

/**
 * Include Workspace Sidebar Frame via Blocksy hook
 * Always present when theme is active - sidebar can be toggled offcanvas
 */
add_action('blocksy:header:after', function () {
    include get_stylesheet_directory() . '/workspace-sidebar-frame.php';
});

/**
 * Force Blocksy account modal to load in footer for sidebar login
 * Shows for logged-out users on workspace pages
 */
add_action('wp_footer', function () {
    // Only load for logged-out users on workspace pages
    if (is_user_logged_in()) {
        return;
    }
    
    if (!function_exists('workspaces_is_workspace_page') || !workspaces_is_workspace_page()) {
        return;
    }
    
    // Use Blocksy Pro's header class to render the modal
    if (class_exists('\Blocksy\Plugin') && class_exists('Blocksy_Header_Builder_Render')) {
        $plugin = \Blocksy\Plugin::instance();
        if ($plugin->header && method_exists($plugin->header, 'retrieve_account_modal')) {
            echo $plugin->header->retrieve_account_modal();
        }
    }
}, 5);

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
 * Custom Nav Walker for Workspace Sidebar Menu
 * Generates navigation with icons, dropdowns, and proper styling
 */
class Workspace_Nav_Walker extends Walker_Nav_Menu {
    private $menu_counter = 0;

    /**
     * Start the element output for parent items
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        // Get icon from menu item meta
        $icon_name = get_post_meta($item->ID, '_menu_item_icon', true);
        $has_children = !empty($item->classes) && in_array('menu-item-has-children', $item->classes);
        $is_active = in_array('current-menu-item', $item->classes) || in_array('current-page-ancestor', $item->classes);
        $active_class = $is_active ? ' active' : '';

        // Icon markup using Lucide
        $icon_html = $icon_name ? Lucide_Icons::render($icon_name, 20) : '';

        if ($depth === 0) {
            if ($has_children) {
                $menu_id = 'menu-' . ++$this->menu_counter;
                $output .= '<div class="flex items-center">';
                $output .= '<a href="' . esc_url($item->url) . '" class="flex items-center gap-2 px-4 py-3 text-white/70 hover:text-white hover:bg-white/5 transition-colors frs-nav-link flex-1' . $active_class . '">';
                $output .= $icon_html;
                $output .= '<span>' . esc_html($item->title) . '</span>';
                $output .= '</a>';
                $output .= '<button onclick="toggleMenu(\'' . $this->menu_counter . '\', event)" class="px-3 py-3 text-white/70 hover:text-white hover:bg-white/5 transition-colors" style="background: none; border: none; outline: none; cursor: pointer;">';
                $output .= '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="frs-chevron" style="transition: transform 0.2s ease-in-out;"><path d="m9 18 6-6-6-6"/></svg>';
                $output .= '</button>';
                $output .= '</div>';
                $item->menu_id = $menu_id;
            } else {
                $output .= '<a href="' . esc_url($item->url) . '" class="flex items-center gap-2 px-4 py-3 text-white/70 hover:text-white hover:bg-white/5 transition-colors frs-nav-link' . $active_class . '">';
                $output .= $icon_html;
                $output .= '<span>' . esc_html($item->title) . '</span>';
                $output .= '</a>';
            }
        } else {
            $output .= '<a href="' . esc_url($item->url) . '" class="flex items-center gap-2 pl-8 pr-4 py-2 text-sm text-white/60 hover:text-white hover:bg-white/5 transition-colors frs-nav-link' . $active_class . '">';
            $output .= $icon_html;
            $output .= '<span>' . esc_html($item->title) . '</span>';
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

        $output .= '<div id="' . esc_attr($menu_id) . '" class="frs-submenu pl-6 ml-4" style="display: none;">';
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
 * Custom Nav Walker for User Popup Menu
 * Simple walker for the user dropdown menu with icons
 */
class User_Menu_Walker extends Walker_Nav_Menu {
    /**
     * Start element output
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        // Get icon from menu item meta
        $icon_name = get_post_meta($item->ID, '_menu_item_icon', true);
        $is_active = in_array('current-menu-item', $item->classes);
        $active_class = $is_active ? ' bg-white/10' : '';

        // Icon markup using Lucide
        $icon_html = '';
        if ($icon_name && class_exists('Lucide_Icons')) {
            $icon_html = Lucide_Icons::render($icon_name, 18);
        }

        $output .= '<a href="' . esc_url($item->url) . '" class="flex items-center gap-3 px-4 py-2.5 text-white/70 hover:text-white hover:bg-white/5 transition-colors' . $active_class . '">';
        $output .= $icon_html;
        $output .= '<span class="text-sm">' . esc_html($item->title) . '</span>';
        $output .= '</a>';
    }

    /**
     * End element (no closing tag needed)
     */
    public function end_el(&$output, $item, $depth = 0, $args = null) {
        // No closing tag needed
    }
}

/**
 * Workspace Frame Layout
 * Creates an app-like shell with logo section, top bar, sidebar, and content area
 */

// Check if current page should use workspace frame
function workspaces_is_workspace_page() {
    // Use plugin helper if available
    if (function_exists('workspaces_is_in_workspace')) {
        return workspaces_is_in_workspace();
    }

    // Fallback detection if plugin not active
    $queried_object = get_queried_object();

    // Check if we're on a workspace taxonomy archive
    if ($queried_object instanceof WP_Term && $queried_object->taxonomy === 'workspace') {
        return true;
    }

    // Check if current post/page has a workspace assigned
    if ($queried_object && isset($queried_object->ID)) {
        $terms = get_the_terms($queried_object->ID, 'workspace');
        if ($terms && !is_wp_error($terms) && !empty($terms)) {
            return true;
        }
    }

    return false;
}

// Get the workspace type (lo or re)
function workspaces_get_workspace_type() {
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


// Enqueue workspace frame assets
add_action('template_redirect', function() {
    // Add enqueue action that will run during wp_print_scripts
    add_action('wp_print_scripts', function() {

    // Enqueue LRG portal sidebar assets (must be done here, not in shortcode, because shortcode runs after scripts are printed)
    if (class_exists('\LendingResourceHub\Assets\Frontend')) {
        \LendingResourceHub\Assets\Frontend::get_instance()->enqueue_portal_assets_public();
    }

    // Add workspace frame styles
    wp_add_inline_style('workspaces-style', '
        body.workspace-frame {
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body.workspace-frame #main-container {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        .workspace-frame-container {
            display: flex;
            height: 100vh;
            max-height: 100vh;
            width: 100vw;
            overflow: hidden;
            position: relative;
        }

        .workspace-sidebar-wrapper {
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

        .workspace-logo-section {
            background: #0b102c;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 0 1rem;
            flex-shrink: 0;
        }

        .workspace-logo-section img {
            height: 32px;
            width: auto;
        }

        .workspace-sidebar-container {
            flex: 1;
            background: #0b102c;
            /* border-right: 1px solid #e5e7eb; */
            overflow-y: auto;
        }

        .workspace-content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            margin-left: 320px;
            height: 100vh;
            max-height: 100vh;
        }

        .workspace-top-bar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            flex-shrink: 0;
        }

        .workspace-top-bar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .workspace-top-bar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .workspace-content-area {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1rem;
            min-height: 0;
        }

        .workspace-content-area > * {
            margin-top: 0 !important;
        }

        .workspace-content-area .entry-content {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        .workspace-content-area h2,
        .workspace-content-area .wp-block-heading {
            margin-top: 0 !important;
        }

        @media (max-width: 768px) {
            .workspace-sidebar-wrapper {
                position: fixed;
                left: -280px;
                top: 0;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
            }

            .workspace-sidebar-wrapper.open {
                left: 0;
            }
        }
    ');
    }); // End wp_print_scripts
}); // End template_redirect

// Add body class for workspace sidebar - always present when theme is active
add_filter('body_class', function($classes) {
    // Don't add has-workspace-sidebar class for frs_re_portal - they have their own React sidebar
    $queried_object = get_queried_object();
    if ($queried_object && isset($queried_object->post_type) && $queried_object->post_type === 'frs_re_portal') {
        return $classes;
    }

    $classes[] = 'has-workspace-sidebar';

    // Additional class for workspace-specific pages
    if (workspaces_is_workspace_page()) {
        $classes[] = 'workspace-frame';
    }

    // Add sidebar-offcanvas class only for lesson pages (sidebar collapsed by default)
    if (is_singular('lesson')) {
        $classes[] = 'sidebar-offcanvas';
    }

    return $classes;
});


/**
 * Interactivity API for Workspace Navigation & Sidebar
 * Enables client-side navigation using @wordpress/interactivity-router
 */
add_action('wp_enqueue_scripts', function() {
    // Sidebar toggle is always available when theme is active
    wp_enqueue_script_module(
        'workspaces-sidebar',
        get_stylesheet_directory_uri() . '/assets/js/sidebar-view.js',
        ['@wordpress/interactivity'],
        WORKSPACES_THEME_VERSION
    );

    // Header datetime display on workspace and course pages
    $is_course_page = (
        is_singular('courses') ||
        is_singular('lesson') ||
        is_singular('tutor_quiz') ||
        is_singular('tutor_assignments') ||
        is_post_type_archive('courses')
    );
    if (workspaces_is_workspace_page() || $is_course_page) {
        wp_enqueue_script_module(
            'workspaces-datetime',
            get_stylesheet_directory_uri() . '/assets/js/datetime-view.js',
            ['@wordpress/interactivity'],
            WORKSPACES_THEME_VERSION
        );
    }

    // Navigation router only on workspace pages
    if (workspaces_is_workspace_page()) {
        wp_enqueue_script_module(
            'workspaces-navigation',
            get_stylesheet_directory_uri() . '/assets/js/workspace-navigation.js',
            ['@wordpress/interactivity', '@wordpress/interactivity-router'],
            WORKSPACES_THEME_VERSION
        );
    }
});

/**
 * Register workspace menu locations
 */
add_action('after_setup_theme', function () {
    register_nav_menus(array(
        'workspace_menu' => __('Workspace Sidebar Menu', 'workspaces'),
        'user_menu' => __('User Popup Menu', 'workspaces'),
    ));
});

/**
 * Enqueue block assets for workspace homepage objects
 *
 * When a workspace has a custom homepage object set, we need to enqueue
 * that object's block assets BEFORE the template renders. Otherwise,
 * block styles won't be loaded since WordPress only auto-enqueues for
 * the main queried object.
 *
 * This is especially important for Greenshift blocks which store their
 * CSS in _gspb_post_css post meta.
 */
add_action('wp_enqueue_scripts', function () {
    // Check if we're on a workspace archive (custom rewrite rule sets 'workspace' query var)
    // but NOT on a single workspace_object (which also has 'workspace' query var)
    $workspace_slug = get_query_var('workspace');
    $workspace_object = get_query_var('workspace_object');

    // If no workspace query var, or if viewing a workspace_object, skip
    if (!$workspace_slug || $workspace_object) {
        return;
    }

    // Get the workspace term
    $workspace = get_term_by('slug', $workspace_slug, 'workspace');
    if (!$workspace || is_wp_error($workspace)) {
        return;
    }

    // Check if this workspace has a homepage object
    $homepage_id = get_term_meta($workspace->term_id, '_workspace_homepage', true);
    if (!$homepage_id) {
        return;
    }

    $homepage_post = get_post($homepage_id);
    if (!$homepage_post || $homepage_post->post_status !== 'publish') {
        return;
    }

    // Load Greenshift CSS for the homepage object
    // Greenshift stores compiled CSS in _gspb_post_css post meta
    $gspb_css = get_post_meta($homepage_id, '_gspb_post_css', true);
    if ($gspb_css) {
        // Use Greenshift's helper if available, otherwise output raw
        if (function_exists('gspb_get_final_css')) {
            $gspb_css = gspb_get_final_css($gspb_css);
        }
        wp_register_style('greenshift-workspace-homepage-css', false);
        wp_enqueue_style('greenshift-workspace-homepage-css');
        wp_add_inline_style('greenshift-workspace-homepage-css', $gspb_css);
    }

    // Parse the homepage content for blocks and enqueue their assets
    $blocks = parse_blocks($homepage_post->post_content);
    workspaces_enqueue_block_assets_recursive($blocks);

}, 20); // Run after default priority to ensure block registration is complete

/**
 * Render Blocksy account modal for login popup
 *
 * Blocksy's account modal normally only renders when there's an account
 * header element with modal login enabled. Since we want to trigger it
 * from the sidebar, we need to manually ensure the modal is rendered.
 */
add_filter('blocksy:footer:offcanvas-drawer', function($els, $payload) {
    // Only at the start location
    if ($payload['location'] !== 'start') {
        return $els;
    }

    // Only for logged-out users
    if (is_user_logged_in()) {
        return $els;
    }

    // Check if Blocksy Companion Pro is active
    if (!class_exists('Blocksy\Plugin') || !function_exists('blocksy_render_view')) {
        return $els;
    }

    // Check if modal is already being rendered by Blocksy
    foreach ($els as $el) {
        if (strpos($el, 'id="account-modal"') !== false) {
            return $els; // Modal already added
        }
    }

    // Render the account modal with default settings
    $modal_path = WP_PLUGIN_DIR . '/blocksy-companion-pro/framework/features/header/account-modal.php';
    if (!file_exists($modal_path)) {
        return $els;
    }

    $atts = [
        'account_close_button_type' => 'type-1'
    ];

    $html = blocksy_render_view($modal_path, [
        'current_url' => blocksy_current_url(),
        'header_id' => null,
        'atts' => $atts
    ]);

    if ($html) {
        $els[] = $html;
    }

    return $els;
}, 20, 2);

/**
 * Recursively enqueue assets for blocks and their inner blocks
 *
 * @param array $blocks Array of parsed blocks
 */
function workspaces_enqueue_block_assets_recursive($blocks) {
    foreach ($blocks as $block) {
        if (!empty($block['blockName'])) {
            // Enqueue block styles
            $block_name = $block['blockName'];

            // Get block type registry
            $block_registry = WP_Block_Type_Registry::get_instance();
            $block_type = $block_registry->get_registered($block_name);

            if ($block_type) {
                // Enqueue block styles if defined
                if (!empty($block_type->style)) {
                    $styles = is_array($block_type->style) ? $block_type->style : [$block_type->style];
                    foreach ($styles as $style_handle) {
                        wp_enqueue_style($style_handle);
                    }
                }

                // Enqueue editor styles that might be needed on frontend
                if (!empty($block_type->editor_style)) {
                    // Skip editor-only styles
                }

                // Enqueue block scripts
                if (!empty($block_type->script)) {
                    $scripts = is_array($block_type->script) ? $block_type->script : [$block_type->script];
                    foreach ($scripts as $script_handle) {
                        wp_enqueue_script($script_handle);
                    }
                }

                // Enqueue view scripts (frontend-only)
                if (!empty($block_type->view_script)) {
                    $view_scripts = is_array($block_type->view_script) ? $block_type->view_script : [$block_type->view_script];
                    foreach ($view_scripts as $script_handle) {
                        wp_enqueue_script($script_handle);
                    }
                }

                // Enqueue view script modules (WP 6.5+)
                if (!empty($block_type->view_script_module)) {
                    $view_modules = is_array($block_type->view_script_module) ? $block_type->view_script_module : [$block_type->view_script_module];
                    foreach ($view_modules as $module_handle) {
                        if (function_exists('wp_enqueue_script_module')) {
                            wp_enqueue_script_module($module_handle);
                        }
                    }
                }
            }
        }

        // Recursively process inner blocks
        if (!empty($block['innerBlocks'])) {
            workspaces_enqueue_block_assets_recursive($block['innerBlocks']);
        }
    }
}
