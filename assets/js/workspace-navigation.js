/**
 * Portal Navigation Interactivity API Store
 * Handles sub-page navigation state changes
 */
import { store, getContext } from '@wordpress/interactivity';

store('lrh-portal', {
    actions: {
        navigateToSubPage: (event) => {
            const context = getContext();
            const subPage = event.target.dataset.subpage || '';
            context.currentSubPage = subPage;

            // Emit custom event for React to listen to
            window.dispatchEvent(new CustomEvent('frsPortalNavigate', {
                detail: { subPage }
            }));
        },
    },
    callbacks: {
        onSubPageChange: () => {
            const context = getContext();
            console.log('Current sub-page:', context.currentSubPage);
        },
    }
});
