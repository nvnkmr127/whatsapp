import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

const SEEN_KEY = 'watxio_tour_seen_v1';

// Steps are declared against [data-tour="..."] hooks in the blade templates.
// Any step whose target is missing (permission-gated nav, feature not on the
// current plan) is dropped rather than shown against an empty highlight.
const STEPS = [
    {
        el: 'tour-checklist',
        title: 'Start here',
        body: 'This is your activation checklist. Work through it top to bottom and your account goes live — no support ticket needed.',
    },
    {
        el: 'tour-whatsapp',
        title: 'Connect WhatsApp',
        body: 'The one step that matters. Sign in with Facebook here and we detect your Business Account and phone number automatically.',
    },
    {
        el: 'tour-deliverability',
        title: 'Protect your number',
        body: "Meta rates every business number, and a poor rating throttles or blocks your sending. This page shows your rating over time so you can see trouble coming.",
    },
    {
        el: 'tour-inbox',
        title: 'Shared inbox',
        body: 'Every conversation from your WhatsApp number lands here, and your whole team can reply from one place.',
    },
    {
        el: 'tour-contacts',
        title: 'Contacts',
        body: 'Import your customer list or let contacts create themselves as people message you.',
    },
    {
        el: 'tour-templates',
        title: 'Templates',
        body: 'WhatsApp requires pre-approved templates to start a conversation. Create them here and we submit them to Meta for you.',
    },
    {
        el: 'tour-campaigns',
        title: 'Broadcasting',
        body: 'Once a template is approved, send it to a segment of your contacts from here.',
    },
    {
        el: 'tour-help',
        title: 'Replay any time',
        body: 'Stuck later? Reopen this tour from here whenever you need it.',
    },
];

function buildSteps() {
    return STEPS.filter((s) => document.querySelector(`[data-tour="${s.el}"]`)).map((s) => ({
        element: `[data-tour="${s.el}"]`,
        popover: {
            title: s.title,
            description: s.body,
            side: 'right',
            align: 'start',
        },
    }));
}

export function startProductTour() {
    const steps = buildSteps();

    if (steps.length === 0) {
        return;
    }

    driver({
        showProgress: true,
        allowClose: true,
        nextBtnText: 'Next',
        prevBtnText: 'Back',
        doneBtnText: 'Got it',
        steps,
        onDestroyed: () => localStorage.setItem(SEEN_KEY, '1'),
    }).drive();
}

// Auto-run once for a brand-new user. The blade layout sets this flag only when
// the team has not finished WhatsApp setup, so returning power users are spared.
export function maybeAutoStartTour() {
    if (localStorage.getItem(SEEN_KEY)) {
        return;
    }

    // Let Livewire paint the sidebar before we try to highlight it.
    setTimeout(startProductTour, 800);
}

window.startProductTour = startProductTour;
