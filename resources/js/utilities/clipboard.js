export const copyToClipboard = (input, btn = null) => {
    if (!navigator.clipboard) {
        console.error('Clipboard API not available');
        return;
    }

    let text = input;
    // Check if input is an element ID
    const element = document.getElementById(input);
    if (element) {
        text = element.innerText || element.value;
    }

    const originalHTML = btn ? btn.innerHTML : null;
    const isButtonText = btn && btn.innerText && (btn.innerText.toUpperCase().includes('COPY') || btn.innerText.includes('!'));

    navigator.clipboard.writeText(text).then(() => {
        if (btn) {
            if (isButtonText) {
                const oldText = btn.innerText;
                btn.innerText = 'COPIED!';
                setTimeout(() => {
                    btn.innerText = oldText;
                }, 2000);
            } else {
                btn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Copied!</span>
                `;
                const originalClasses = btn.className;
                btn.classList.add('bg-wa-teal', 'text-white');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.className = originalClasses;
                }, 2000);
            }
        }
    }).catch(err => {
        console.error('Copy failed', err);
    });
};

window.copyToClipboard = copyToClipboard;
