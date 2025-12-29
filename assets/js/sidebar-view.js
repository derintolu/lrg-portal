/**
 * Workspace Sidebar - Interactivity API Store
 *
 * Handles sidebar toggle state with localStorage persistence
 * Course pages default to collapsed, other pages default to expanded
 */
import { store, getContext } from '@wordpress/interactivity';

const STORAGE_KEY = 'workspace-sidebar-offcanvas';
const STORAGE_KEY_COURSE = 'workspace-sidebar-offcanvas-course';

store('workspaces/sidebar', {
    actions: {
        toggleSidebar() {
            const context = getContext();
            context.isCollapsed = !context.isCollapsed;
            // Also toggle body class for CSS targeting .site-main
            document.body.classList.toggle('sidebar-offcanvas', context.isCollapsed);
            // Use different storage key for course pages
            const key = context.isCoursePage ? STORAGE_KEY_COURSE : STORAGE_KEY;
            localStorage.setItem(key, context.isCollapsed);
        },
    },
    callbacks: {
        initFromStorage() {
            const context = getContext();
            const key = context.isCoursePage ? STORAGE_KEY_COURSE : STORAGE_KEY;
            const saved = localStorage.getItem(key);

            if (saved !== null) {
                // Use saved preference and update body class accordingly
                context.isCollapsed = saved === 'true';
            } else if (context.isCoursePage) {
                // No saved preference for course pages - default to collapsed
                context.isCollapsed = true;
            }

            // Sync body class with context state
            document.body.classList.toggle('sidebar-offcanvas', context.isCollapsed);
        },
    },
});
