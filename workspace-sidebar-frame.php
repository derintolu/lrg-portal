<?php
/**
 * Workspace Sidebar Frame
 *
 * Persistent 320px sidebar frame that stays fixed on all workspace pages
 * Uses WordPress Interactivity API for sidebar toggle
 *
 * @package Workspaces_Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue Tailwind CSS for sidebar (scoped to sidebar only)
add_action('wp_head', function() {
    static $tailwind_loaded = false;
    if (!$tailwind_loaded) {
        echo '<script src="https://cdn.tailwindcss.com"></script>';
        $tailwind_loaded = true;
    }
}, 1);

$workspace_slug = get_query_var('workspace');
$header_title = ($workspace_slug === 'learning') ? 'Learning' : get_the_title();
?>

<div
    data-wp-interactive="workspaces/sidebar"
    <?php echo wp_interactivity_data_wp_context(array('isCollapsed' => false)); ?>
    data-wp-class--sidebar-offcanvas="context.isCollapsed"
    data-wp-init="callbacks.initFromStorage"
    class="workspace-frame-wrapper"
>
    <!-- Workspace Header Bar -->
    <div class="workspace-header-bar">
        <!-- Sidebar Toggle Button -->
        <button
            class="sidebar-toggle-btn"
            aria-label="Toggle sidebar"
            data-wp-on--click="actions.toggleSidebar"
        >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <!-- Content Header -->
        <div class="workspace-header-content">
            <h1 class="workspace-page-title"><?php echo esc_html($header_title); ?></h1>
        </div>
    </div>

    <!-- Workspace Sidebar Frame -->
    <div class="workspace-sidebar-frame">
        <?php include get_stylesheet_directory() . '/workspace-sidebar-content.php'; ?>
    </div>
</div><style>
/* Workspace Sidebar - Using theme.json custom properties */
:root {
    --workspace-sidebar-width: var(--wp--custom--sidebar--width, 320px);
    --workspace-sidebar-width-collapsed: var(--wp--custom--sidebar--width-collapsed, 64px);
    --workspace-sidebar-bg: var(--wp--custom--sidebar--background, var(--wp--preset--color--workspace-dark, #0B102C));
    --workspace-header-height: var(--wp--custom--sidebar--header-height, 80px);
    --workspace-bar-height: var(--wp--custom--sidebar--workspace-header-height, 60px);
    --workspace-header-bg: var(--wp--preset--color--workspace-header, #dce2eb);
    --workspace-border-color: var(--wp--preset--color--workspace-border, #a8b4c8);
    --workspace-z-index: var(--wp--custom--sidebar--z-index, 100);
    --workspace-transition: var(--wp--custom--sidebar--transition, all 0.3s ease);
    --workspace-glass-bg: var(--wp--custom--glass--background, rgba(255, 255, 255, 0.1));
    --workspace-glass-blur: var(--wp--custom--glass--backdrop-filter, blur(2px));
}

/* Workspace Sidebar Frame */
.workspace-sidebar-frame {
    position: fixed;
    top: var(--workspace-header-height);
    left: 0;
    width: var(--workspace-sidebar-width);
    height: calc(100vh - var(--workspace-header-height));
    background: #0B102C;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    border-right: 1px solid var(--workspace-border-color);
    box-shadow: 2px 0 4px rgba(168, 180, 200, 0.1);
    z-index: var(--workspace-z-index);
    transition: var(--workspace-transition);
}

/* Workspace Header Bar */
.workspace-header-bar {
    position: fixed;
    top: var(--workspace-header-height);
    left: var(--workspace-sidebar-width);
    right: 0;
    display: flex;
    height: var(--workspace-bar-height);
    background: var(--workspace-header-bg);
    border-bottom: 1px solid var(--workspace-border-color);
    box-shadow: 0 2px 4px rgba(168, 180, 200, 0.15);
    z-index: calc(var(--workspace-z-index) - 1);
    transition: var(--workspace-transition);
}

/* Admin bar adjustments */
body.admin-bar .workspace-sidebar-frame {
    top: calc(var(--workspace-header-height) + 32px);
    height: calc(100vh - var(--workspace-header-height) - 32px);
}

body.admin-bar .workspace-header-bar {
    top: calc(var(--workspace-header-height) + 32px);
}

.workspace-header-content {
    flex: 1;
    display: flex;
    align-items: center;
    padding: 0 var(--wp--preset--spacing--60, 2rem);
}

/* Sidebar Toggle Button - P2 style */
.sidebar-toggle-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 100%;
    background: transparent;
    border: none;
    border-right: 1px solid var(--workspace-border-color);
    color: var(--wp--preset--color--workspace-dark, #0b102c);
    cursor: pointer;
    transition: background 0.2s ease;
}

.sidebar-toggle-btn:hover {
    background: rgba(0, 0, 0, 0.05);
}

/* Offcanvas sidebar state - using body class for reliable targeting */
body.sidebar-offcanvas .workspace-sidebar-frame {
    transform: translateX(-100%);
}

body.sidebar-offcanvas .workspace-header-bar {
    left: 0;
}

body.sidebar-offcanvas.has-workspace-sidebar .site-main {
    margin-left: 0;
}

.workspace-page-title {
    margin: 0;
    font-size: var(--wp--preset--font-size--lg, 20px);
    font-weight: 600;
    color: var(--wp--preset--color--workspace-dark, #0b102c);
}

.workspace-sidebar-content {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    background: #0B102C;
}

#lrh-portal-sidebar-root,
#workspace-sidebar-root {
    background: #0B102C;
    min-height: 100%;
}

/* Fix sidebar avatar size - prevent CSS bleed */
.workspace-sidebar-frame .rounded-full img {
    width: 100% !important;
    height: 100% !important;
    max-width: 42px;
    max-height: 42px;
    object-fit: cover;
}

.workspace-sidebar-frame .w-\[42px\] {
    width: 42px !important;
    min-width: 42px;
    max-width: 42px;
}

.workspace-sidebar-frame .h-\[42px\] {
    height: 42px !important;
    min-height: 42px;
    max-height: 42px;
}

body.has-workspace-sidebar .site-main {
    margin-left: var(--workspace-sidebar-width);
    margin-top: 0;
    padding: var(--wp--preset--spacing--60, 2rem);
    padding-top: calc(var(--workspace-bar-height) + var(--wp--preset--spacing--40, 1rem));
    min-height: calc(100vh - var(--workspace-header-height) - var(--workspace-bar-height));
    transition: var(--workspace-transition);
}

body.has-workspace-sidebar #primary {
    margin-left: 0;
}

/* Collapsed sidebar state */
body.has-workspace-sidebar.sidebar-collapsed .workspace-sidebar-frame {
    width: var(--workspace-sidebar-width-collapsed);
}

body.has-workspace-sidebar.sidebar-collapsed .workspace-header-bar {
    left: var(--workspace-sidebar-width-collapsed);
}

body.has-workspace-sidebar.sidebar-collapsed .site-main {
    margin-left: var(--workspace-sidebar-width-collapsed);
}

/* Mobile admin bar is taller (46px) below 783px */
@media (max-width: 782px) {
    body.admin-bar .workspace-sidebar-frame {
        top: calc(var(--workspace-header-height) + 46px);
        height: calc(100vh - var(--workspace-header-height) - 46px);
    }

    body.admin-bar .workspace-header-bar {
        top: calc(var(--workspace-header-height) + 46px);
    }
}

@media (max-width: 768px) {
    .workspace-sidebar-frame {
        transform: translateX(-100%);
        width: var(--workspace-sidebar-width);
    }

    .workspace-sidebar-frame.mobile-open {
        transform: translateX(0);
    }

    .workspace-header-bar {
        left: 0;
    }

    body.has-workspace-sidebar .site-main {
        margin-left: 0;
        padding: 0;
        padding-top: var(--workspace-bar-height);
    }
}
</style>

