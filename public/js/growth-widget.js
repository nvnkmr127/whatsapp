(function () {
    const script = document.getElementById('wa-growth-tool') || document.currentScript;
    const widgetId = script.getAttribute('widget-id');
    const scriptUrl = script.src;
    const baseUrl = scriptUrl.substring(0, scriptUrl.indexOf('/js/growth-widget.js'));

    fetch(`${baseUrl}/growth-tools/config/${widgetId}`)
        .then(response => response.json())
        .then(config => {
            if (!config.active) return;
            if (shouldShow(config)) {
                renderWidget(config);
            }
        });

    function shouldShow(config) {
        if (config.show_mobile === false && window.innerWidth < 768) return false;
        if (config.show_desktop === false && window.innerWidth >= 768) return false;

        if (config.page_targeting) {
            const currentUrl = window.location.href;
            const targetPatterns = config.page_targeting.split(',').map(p => p.trim());
            const matches = targetPatterns.some(pattern => {
                const regex = new RegExp('^' + pattern.replace(/\*/g, '.*') + '$', 'i');
                return regex.test(currentUrl) || regex.test(window.location.pathname);
            });
            if (!matches) return false;
        }

        if (config.business_hours && Object.keys(config.business_hours).length > 0) {
            const now = new Date();
            const days = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
            const day = days[now.getDay()];
            const hours = config.business_hours[day];
            if (!hours || !hours.start || !hours.end) return false;
            const currentTime = now.getHours() * 60 + now.getMinutes();
            const [startH, startM] = hours.start.split(':').map(Number);
            const [endH, endM] = hours.end.split(':').map(Number);
            const startTime = startH * 60 + startM;
            const endTime = endH * 60 + endM;
            if (currentTime < startTime || currentTime > endTime) return false;
        }

        return true;
    }

    function renderWidget(config) {
        const style = document.createElement('style');
        style.innerHTML = `
            #wt-bubble-${config.slug} { position:fixed; bottom:${config.bottom_margin}px; ${config.position}:${config.side_margin}px; z-index:9999; font-family:sans-serif; display:none; }
            .wt-btn { display:flex; align-items:center; gap:12px; padding:12px 24px; background:${config.color}; color:#fff; border-radius:${config.border_radius}px; box-shadow:0 10px 30px rgba(0,0,0,0.2); cursor:pointer; transition:all .3s ease; font-weight:700; font-size:14px; border:none; }
            .wt-btn:hover { transform:scale(1.05); }
            .wt-pulse { animation: wt-pulse-ring 2s infinite; }
            @keyframes wt-pulse-ring { 0% { box-shadow: 0 0 0 0 ${config.color}66; } 70% { box-shadow: 0 0 0 15px ${config.color}00; } 100% { box-shadow: 0 0 0 0 ${config.color}00; } }
            
            .wt-form { position:absolute; bottom:80px; ${config.position}:0; width:340px; background:#fff; border-radius:30px; box-shadow:0 20px 50px rgba(0,0,0,0.15); display:none; flex-direction:column; border:1px solid #eee; overflow:hidden; }
            .wt-form.active { display:flex; animation: wt-slide-up .3s ease; }
            @keyframes wt-slide-up { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
            
            .wt-header { background:${config.color}; padding:25px; color:white; display:flex; align-items:center; gap:15px; position:relative; }
            .wt-brand-logo { width:50px; height:50px; border-radius:50%; background:#fff; object-fit:cover; border:2px solid #fff; }
            .wt-brand-info { color:#fff; }
            .wt-brand-name { font-weight:800; font-size:18px; margin:0; line-height:1.2; text-align:left; }
            .wt-brand-subtitle { font-size:12px; opacity:0.9; margin:0; display:flex; align-items:center; gap:5px; text-align:left; }
            .wt-status-dot { width:8px; height:8px; background:#25D366; border-radius:50%; border:1px solid #fff; }
            
            .wt-body { padding:25px; display:flex; flex-direction:column; gap:20px; background:#f5f7f9; }
            .wt-welcome-msg { background:#fff; padding:15px 20px; border-radius:18px; border-top-left-radius:0; box-shadow:0 4px 10px rgba(0,0,0,0.05); position:relative; font-size:14px; color:#444; line-height:1.5; text-align:left; }
            .wt-welcome-msg:before { content:''; position:absolute; top:0; left:-10px; border-width:10px; border-style:solid; border-color:#fff transparent transparent transparent; }

            .wt-input { width:100%; padding:15px; border:1px solid #e1e4e8; border-radius:15px; font-size:14px; outline:none; box-sizing:border-box; transition:all .2s; }
            .wt-input:focus { border-color:${config.color}; box-shadow:0 0 0 3px ${config.color}15; }
            .wt-submit { background:${config.color}; color:#fff; border:none; padding:15px; border-radius:15px; font-weight:800; cursor:pointer; text-transform:uppercase; letter-spacing:1px; transition:all .2s; }
            .wt-submit:hover { opacity:0.9; transform:translateY(-1px); }
            
            .wt-footer { padding:12px; text-align:center; font-size:10px; color:#aaa; font-weight:700; background:#f5f7f9; border-top:1px solid #efefef; }
            .wt-close { position:absolute; top:20px; right:20px; cursor:pointer; color:#fff; font-size:24px; opacity:0.7; transition:opacity .2s; }
            .wt-close:hover { opacity:1; }
        `;
        document.head.appendChild(style);

        const b = document.createElement('div');
        b.id = 'wt-bubble-' + config.slug;
        b.innerHTML = `
            <div class="wt-form" id="wt-form-${config.slug}">
                <div class="wt-header">
                    <div class="wt-close" onclick="document.getElementById('wt-form-${config.slug}').classList.remove('active')">✕</div>
                    ${config.brand_logo ? `<img src="${config.brand_logo}" class="wt-brand-logo">` : `<div class="wt-brand-logo" style="display:flex;align-items:center;justify-content:center;background:#fff;color:${config.color}"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.445 0 .081 5.363.079 11.969c0 2.112.552 4.175 1.598 6.013L0 24l6.15-1.613a11.893 11.893 0 005.894 1.565h.005c6.598 0 11.964-5.365 11.966-11.972.003-3.194-1.246-6.195-3.511-8.455z" /></svg></div>`}
                    <div class="wt-brand-info">
                        <p class="wt-brand-name">${config.brand_name || config.name}</p>
                        <p class="wt-brand-subtitle"><span class="wt-status-dot"></span> ${config.brand_subtitle || 'online'}</p>
                    </div>
                </div>
                <div class="wt-body">
                    <div class="wt-welcome-msg">${config.welcome_message || 'Hi, How can I help you?'}</div>
                    <form action="${config.lead_url}" method="POST">
                        ${config.collect_name ? `<input type="text" name="name" placeholder="${config.placeholder_name || 'Full Name'}" required class="wt-input">` : ''}
                        ${config.collect_email ? `<input type="email" name="email" placeholder="${config.placeholder_email || 'Email Address'}" required class="wt-input">` : ''}
                        ${config.collect_phone ? `<input type="tel" name="phone" placeholder="${config.placeholder_phone || 'WhatsApp Number'}" required class="wt-input">` : ''}
                        <button type="submit" class="wt-submit">${config.text}</button>
                    </form>
                </div>
                <div class="wt-footer">${config.footer_text || 'Powered by Growth Tools'}</div>
            </div>
            <button class="wt-btn wt-pulse" id="wt-btn-${config.slug}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.445 0 .081 5.363.079 11.969c0 2.112.552 4.175 1.598 6.013L0 24l6.15-1.613a11.893 11.893 0 005.894 1.565h.005c6.598 0 11.964-5.365 11.966-11.972.003-3.194-1.246-6.195-3.511-8.455z" /></svg>
                <span>${config.text}</span>
            </button>
        `;
        document.body.appendChild(b);

        const btn = document.getElementById('wt-btn-' + config.slug);
        const form = document.getElementById('wt-form-' + config.slug);
        const bubble = document.getElementById('wt-bubble-' + config.slug);

        btn.onclick = () => {
            if (!form.classList.contains('active')) {
                // Tracking Click
                fetch(config.click_url).catch(() => { });
            }
            form.classList.toggle('active');
        };

        const showFn = () => {
            bubble.style.display = 'block';
            if (config.open_by_default) {
                setTimeout(() => {
                    form.classList.add('active');
                    fetch(config.click_url).catch(() => { });
                }, 1000);
            }
        };

        if (config.time_on_page > 0) {
            setTimeout(showFn, config.time_on_page * 1000);
        } else {
            showFn();
        }

        if (config.exit_intent) {
            document.addEventListener('mouseleave', (e) => {
                if (e.clientY < 0) showFn();
            }, { once: true });
        }
    }
})();
