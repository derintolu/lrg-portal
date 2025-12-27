/**
 * Workspace Sidebar - Interactivity API Store
 *
 * Handles sidebar toggle state with localStorage persistence
 */
import { store, getContext } from '@wordpress/interactivity';

const STORAGE_KEY = 'workspace-sidebar-offcanvas';

store('workspaces/sidebar', {
    actions: {
        toggleSidebar() {
            const context = getContext();
            context.isCollapsed = !context.isCollapsed;
            // Also toggle body class for CSS targeting .site-main
            document.body.classList.toggle('sidebar-offcanvas', context.isCollapsed);
            localStorage.setItem(STORAGE_KEY, context.isCollapsed);
        },
    },
    callbacks: {
        initFromStorage() {
            const context = getContext();
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved === 'true') {
                context.isCollapsed = true;
                document.body.classList.add('sidebar-offcanvas');
            }
        },
    },
});
