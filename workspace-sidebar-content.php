<?php
/**
 * Workspace Sidebar Content
 * PHP + vanilla JS version with all React design elements preserved
 *
 * @package Workspaces_Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current workspace using centralized helper (from plugin)
// Falls back gracefully if plugin not active
$plugin_active = function_exists('workspaces_get_current');
$current_workspace = $plugin_active ? workspaces_get_current() : null;
$workspace_data = $plugin_active ? workspaces_get_data($current_workspace) : null;

// Get menu location - use workspace menu if available, otherwise fall back to 'primary' or 'sidebar'
if ($plugin_active && $current_workspace) {
    $workspace_menu_location = workspaces_get_menu_location($current_workspace);
    $has_workspace_menu = $workspace_menu_location && has_nav_menu($workspace_menu_location);
} else {
    // Fallback to standard WordPress menu locations
    $workspace_menu_location = has_nav_menu('sidebar') ? 'sidebar' : (has_nav_menu('primary') ? 'primary' : '');
    $has_workspace_menu = !empty($workspace_menu_location);
}

$current_user = wp_get_current_user();

// Try to get profile data from frs-users API first
$profile_data = null;
if ($current_user->ID > 0) {
    $api_url = rest_url("frs-users/v1/profiles/user/{$current_user->ID}");
    $response = wp_remote_get($api_url, array('timeout' => 5));

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['success']) && $body['success'] && isset($body['data'])) {
            $profile_data = $body['data'];
        }
    }
}

// Set user data - prefer API data, fallback to WordPress data
if ($profile_data) {
    $user_avatar = $profile_data['profile_photo'] ?? get_avatar_url($current_user->ID, ['size' => 200]);
    $user_name = trim(($profile_data['first_name'] ?? '') . ' ' . ($profile_data['last_name'] ?? '')) ?: $current_user->display_name;
    $user_job_title = $profile_data['job_title'] ?? 'Team Member';
} else {
    $user_avatar = get_avatar_url($current_user->ID, ['size' => 200]);
    $user_name = $current_user->display_name;
    $user_job_title = get_user_meta($current_user->ID, 'job_title', true) ?: 'Team Member';
}

// Get gradient video URL from plugin constant
$gradient_url = '';
if (defined('LRH_URL')) {
    $gradient_url = LRH_URL . 'assets/images/Blue-Dark-Blue-Gradient-Color-and-Style-Video-Background-1.mp4';
}

// Calculate profile completion percentage
$profile_completion = 0;
$total_fields = 0;
$completed_fields = 0;

// Define required profile fields to check
$profile_fields = array(
    'first_name' => $current_user->user_firstname,
    'last_name' => $current_user->user_lastname,
    'user_email' => $current_user->user_email,
    'description' => get_user_meta($current_user->ID, 'description', true), // Bio
    'job_title' => get_user_meta($current_user->ID, 'job_title', true),
    'phone' => get_user_meta($current_user->ID, 'phone', true),
    'company' => get_user_meta($current_user->ID, 'company', true),
    'nmls_id' => get_user_meta($current_user->ID, 'nmls_id', true),
    'facebook' => get_user_meta($current_user->ID, 'facebook', true),
    'twitter' => get_user_meta($current_user->ID, 'twitter', true),
    'linkedin' => get_user_meta($current_user->ID, 'linkedin', true),
);

// Count total and completed fields
foreach ($profile_fields as $field_value) {
    $total_fields++;
    if (!empty($field_value)) {
        $completed_fields++;
    }
}

// Calculate percentage
if ($total_fields > 0) {
    $profile_completion = round(($completed_fields / $total_fields) * 100);
}

// Get user role
$user_roles = $current_user->roles;
$role = '';
if (in_array('loan_officer', $user_roles)) {
    $role = 'loan_officer';
} elseif (in_array('realtor_partner', $user_roles)) {
    $role = 'realtor';
} elseif (in_array('administrator', $user_roles)) {
    $role = 'admin';
}
?>

<div id="workspace-sidebar-root" class="overflow-hidden scrollbar-hide flex flex-col" style="height: calc(100dvh - 60px); background-color: #0B102C;">

    <!-- 16:9 Header Widget Area -->
    <div class="workspace-sidebar-header-widget">
        <?php if (is_active_sidebar('workspace-sidebar-header')) : ?>
            <?php dynamic_sidebar('workspace-sidebar-header'); ?>
        <?php endif; ?>
    </div>

    <!-- Sidebar content - split into scrollable menu and fixed bottom widgets -->
    <div class="flex-1 overflow-y-auto scrollbar-hide flex flex-col">

        <!-- Menu items - take available space -->
        <nav class="flex flex-col flex-1 pt-4" id="workspace-nav" data-wp-interactive="workspaces">
            <?php
            // Special handling for Learning workspace and Tutor LMS pages - use Tutor dashboard sections
            $is_learning_workspace = ($current_workspace && $current_workspace->slug === 'learning');
            $is_tutor_page = (
                is_singular('courses') ||
                is_singular('lesson') ||
                is_singular('tutor_quiz') ||
                is_singular('tutor_assignments') ||
                is_post_type_archive('courses')
            );
            $show_learning_menu = ($is_learning_workspace || $is_tutor_page);

            if ($show_learning_menu && class_exists('Workspaces_Tutor_Dashboard')) :
                $tutor_dashboard = Workspaces_Tutor_Dashboard::instance();
                $sections = $tutor_dashboard->get_dashboard_sections();
                $dashboard_url = home_url('/learning/');
                $current_hash = '';

                // Get current section from URL hash (for active state)
                if (isset($_SERVER['REQUEST_URI'])) {
                    $uri_parts = parse_url($_SERVER['REQUEST_URI']);
                    if (isset($uri_parts['fragment'])) {
                        $current_hash = $uri_parts['fragment'];
                    }
                }

                // Map Tutor icons to Lucide icons
                $tutor_to_lucide = array(
                    'tutor-icon-dashboard'      => 'layout-dashboard',
                    'tutor-icon-user-bold'      => 'user',
                    'tutor-icon-mortarboard-o'  => 'book',
                    'tutor-icon-star-bold'      => 'star',
                    'tutor-icon-quiz-attempt'   => 'clipboard',
                    'tutor-icon-question'       => 'help-circle',
                    'tutor-icon-bookmark-bold'  => 'bookmark',
                    'tutor-icon-cart-bold'      => 'shopping-cart',
                    'tutor-icon-rocket'         => 'zap',
                    'tutor-icon-bullhorn'       => 'bell',
                    'tutor-icon-wallet'         => 'credit-card',
                    'tutor-icon-quiz-o'         => 'clipboard',
                    'tutor-icon-assignment'     => 'file-text',
                    'tutor-icon-brand-zoom'     => 'video',
                    'tutor-icon-chart-pie'      => 'pie-chart',
                    'tutor-icon-gear'           => 'settings',
                );

                // Check if user is admin or instructor
                $is_admin = current_user_can('manage_options');
                $show_instructor_menu = $sections['is_instructor'] || $is_admin;

                // Student sections
                foreach ($sections['student'] as $key => $section) :
                    $href = $dashboard_url . '#' . $section['key'];
                    $is_active = ($current_hash === $section['key']) || ($current_hash === '' && $section['key'] === 'dashboard');
                    $lucide_icon = isset($tutor_to_lucide[$section['icon']]) ? $tutor_to_lucide[$section['icon']] : 'circle';
                    $icon_html = class_exists('Lucide_Icons') ? Lucide_Icons::render($lucide_icon, 20) : '';
                ?>
                    <a href="<?php echo esc_url($href); ?>"
                       class="flex items-center gap-2 px-4 py-3 text-white/70 hover:text-white transition-colors frs-nav-link<?php echo $is_active ? ' active' : ''; ?>"
                       data-section="<?php echo esc_attr($section['key']); ?>">
                        <?php echo $icon_html; ?>
                        <span><?php echo esc_html($section['title']); ?></span>
                    </a>
                <?php endforeach; ?>

                <?php if ($show_instructor_menu && !empty($sections['instructor'])) : ?>
                    <!-- Instructor Dropdown -->
                    <div class="instructor-dropdown-wrapper" onclick="toggleInstructorMenu(event)">
                        <div class="flex items-center cursor-pointer">
                            <span class="flex items-center gap-2 px-4 py-3 text-white/70 hover:text-white transition-colors frs-nav-link flex-1">
                                <?php echo class_exists('Lucide_Icons') ? Lucide_Icons::render('briefcase', 20) : ''; ?>
                                <span><?php esc_html_e('Instructor', 'workspaces'); ?></span>
                            </span>
                            <span class="px-3 py-3 text-white/70 hover:text-white transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" id="instructor-chevron" style="transition: transform 0.2s ease-in-out;"><path d="m9 18 6-6-6-6"/></svg>
                            </span>
                        </div>
                    </div>
                    <div id="menu-instructor" class="frs-submenu" style="display: none;">
                        <?php foreach ($sections['instructor'] as $key => $section) :
                            $href = $dashboard_url . '#' . $section['key'];
                            $is_active = $current_hash === $section['key'];
                            $lucide_icon = isset($tutor_to_lucide[$section['icon']]) ? $tutor_to_lucide[$section['icon']] : 'circle';
                            $icon_html = class_exists('Lucide_Icons') ? Lucide_Icons::render($lucide_icon, 20) : '';
                        ?>
                            <a href="<?php echo esc_url($href); ?>"
                               class="flex items-center gap-2 pl-8 pr-4 py-2 text-sm text-white/60 hover:text-white transition-colors frs-nav-link<?php echo $is_active ? ' active' : ''; ?>"
                               data-section="<?php echo esc_attr($section['key']); ?>">
                                <?php echo $icon_html; ?>
                                <span><?php echo esc_html($section['title']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($has_workspace_menu) : ?>
                <?php
                wp_nav_menu(array(
                    'theme_location' => $workspace_menu_location,
                    'container'      => false,
                    'items_wrap'     => '%3$s',
                    'walker'         => new Workspace_Nav_Walker(),
                    'fallback_cb'    => false,
                ));
                ?>
            <?php else : ?>
                <div class="px-4 py-3 text-xs text-white/50">
                    <?php
                    if ($current_workspace) {
                        printf(
                            __('No menu assigned. Go to Appearance → Menus and assign a menu to "Workspace: %s"', 'workspaces'),
                            esc_html($current_workspace->name)
                        );
                    } else {
                        _e('No workspace detected for this page.', 'workspaces');
                    }
                    ?>
                </div>
            <?php endif; ?>
        </nav>

        <!-- Bottom widgets - stick to bottom -->
        <div class="mt-auto">
            <!-- Profile Completion Widget -->
            <div class="relative overflow-hidden" style="min-height: 80px; background: <?php echo $gradient_url ? 'transparent' : 'linear-gradient(135deg, #2563eb 0%, #2dd4da 100%)'; ?>;">
                <?php if ($gradient_url): ?>
                <!-- Video Background -->
                <video autoplay loop muted playsinline class="absolute inset-0 w-full h-full object-cover" style="z-index: 0;">
                    <source src="<?php echo esc_url($gradient_url); ?>" type="video/mp4">
                </video>
                <!-- Glassy overlay -->
                <div class="absolute inset-0" style="z-index: 1; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px);"></div>
                <?php endif; ?>

                <div class="relative px-4 py-3" style="z-index: 10;">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-xs font-semibold text-white uppercase tracking-wider drop-shadow-md">Profile Completion</div>
                        <div class="text-sm font-semibold text-white drop-shadow-md"><?php echo esc_html($profile_completion); ?>%</div>
                    </div>
                    <div class="w-full bg-white/30 rounded-full h-2">
                        <div class="h-2 rounded-full bg-white" style="width: <?php echo esc_attr($profile_completion); ?>%;"></div>
                    </div>
                </div>
            </div>

            <!-- Profile Header with Avatar (Horizontal Layout) - Bottom -->
            <div class="relative w-full h-20 px-4 flex items-center gap-3" style="background-color: #0B102C;">
                <!-- Glassy overlay -->
                <div class="absolute inset-0" style="z-index: 1; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px);"></div>

                <!-- Avatar -->
                <div class="relative flex-shrink-0" style="z-index: 10;">
                    <div class="w-[42px] h-[42px] rounded-full overflow-hidden shadow-lg border-2 border-white/20">
                        <img src="<?php echo esc_url($user_avatar); ?>" alt="<?php echo esc_attr($user_name); ?>" class="w-full h-full object-cover">
                    </div>
                </div>

                <!-- Name and Title -->
                <div class="relative flex-1 min-w-0" style="z-index: 10;">
                    <h3 class="font-bold text-white text-base mb-0.5 truncate"><?php echo esc_html($user_name); ?></h3>
                    <p class="font-normal text-white/70 text-sm truncate"><?php echo esc_html($user_job_title); ?></p>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Toggle instructor submenu visibility - attached to window for global access
window.toggleInstructorMenu = function(event) {
    event.preventDefault();
    event.stopPropagation();

    const submenu = document.getElementById('menu-instructor');
    const chevron = document.getElementById('instructor-chevron');

    if (!submenu) return;

    const isHidden = submenu.style.display === 'none' || submenu.style.display === '';

    if (isHidden) {
        submenu.style.display = 'block';
        if (chevron) chevron.style.transform = 'rotate(90deg)';
    } else {
        submenu.style.display = 'none';
        if (chevron) chevron.style.transform = 'rotate(0deg)';
    }
};

// Set active link based on current URL
function setActiveLink() {
    const currentPath = window.location.pathname;
    const links = document.querySelectorAll('.frs-nav-link');

    links.forEach(link => {
        const href = link.getAttribute('href');
        const linkPath = new URL(href, window.location.origin).pathname;

        if (currentPath === linkPath || currentPath === linkPath + '/') {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// Initialize on load
setActiveLink();

// Update active link on navigation (for browser back/forward)
window.addEventListener('popstate', setActiveLink);

// Listen for Interactivity API navigation events
document.addEventListener('wp-router-navigated', setActiveLink);

// Add click listener for instructor dropdown (runs immediately since DOM is already loaded)
(function() {
    const instructorWrapper = document.querySelector('.instructor-dropdown-wrapper');
    if (instructorWrapper) {
        instructorWrapper.addEventListener('click', function(e) {
            window.toggleInstructorMenu(e);
        });
    }
})();
</script>

<style>
/* Workspace Sidebar Header Widget - 16:9 edge-to-edge */
.workspace-sidebar-header-widget {
    width: 100%;
    aspect-ratio: 16 / 9;
    flex-shrink: 0;
    overflow: hidden;
}

.workspace-sidebar-header-widget .widget {
    margin: 0;
    padding: 0;
    height: 100%;
    width: 100%;
}

.workspace-sidebar-header-widget .widget > * {
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
    border-radius: 0 !important;
}

.workspace-sidebar-header-widget .wp-block-cover {
    min-height: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    border-radius: 0 !important;
}

.workspace-sidebar-header-widget .wp-block-cover__inner-container {
    padding: 1rem;
}

/* Active link styling */
.frs-nav-link.active {
    color: white !important;
}

/* Hover state */
.frs-nav-link:hover:not(.active) {
    color: white !important;
}

/* Smooth transitions */
.frs-nav-link, .frs-nav-button {
    transition: all 0.2s ease-in-out;
}

/* Instructor dropdown wrapper */
.instructor-dropdown-wrapper {
    cursor: pointer;
}

.instructor-dropdown-wrapper:hover .frs-nav-link {
    color: white !important;
}

/* CSS View Transitions */
@supports (view-transition-name: none) {
    ::view-transition-old(root),
    ::view-transition-new(root) {
        animation-duration: 0.3s;
    }

    ::view-transition-old(root) {
        animation-name: fade-out;
    }

    ::view-transition-new(root) {
        animation-name: fade-in;
    }

    @keyframes fade-out {
        to {
            opacity: 0;
        }
    }

    @keyframes fade-in {
        from {
            opacity: 0;
        }
    }
}
</style>
