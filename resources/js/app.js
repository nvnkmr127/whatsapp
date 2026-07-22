import './bootstrap';
import './chat-store';
import './global-notifications';
import './utilities/clipboard';
import chatWindow from './Components/chat-window';
import { maybeAutoStartTour } from './tour';

window.chatWindow = chatWindow;

// The layout renders #tour-autostart only for teams that haven't activated yet.
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('tour-autostart')) {
        maybeAutoStartTour();
    }
});
