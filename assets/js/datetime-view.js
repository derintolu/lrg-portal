/**
 * Header DateTime - Interactivity API View Script
 * Live clock and date display for workspace header bar
 */

import { store } from '@wordpress/interactivity';

const formatTime = () => {
    const now = new Date();
    return now.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
};

const formatDate = () => {
    const now = new Date();
    return now.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const { state } = store('workspaces/datetime', {
    state: {
        timeString: formatTime(),
        dateString: formatDate(),
    },
    callbacks: {
        startClock() {
            // Update immediately
            state.timeString = formatTime();
            state.dateString = formatDate();

            // Update every second
            setInterval(() => {
                state.timeString = formatTime();
                state.dateString = formatDate();
            }, 1000);
        },
    },
});
