<div
    style="position:absolute; bottom:25px; {{ $position }}:25px; transform: scale({{ $source === 'desktop' ? 1 : 0.8 }}); transform-origin: {{ $position }} bottom;">
    <!-- Chat Popup -->
    <div
        style="width:{{ $source === 'desktop' ? '320px' : '280px' }}; background:#fff; border-radius:30px; box-shadow:0 20px 50px rgba(0,0,0,0.1); margin-bottom:15px; border:1px solid #eee; overflow:hidden;">
        <!-- Header -->
        <div style="background:{{ $widget_color }}; padding:20px; display:flex; align-items:center; gap:12px;">
            @if($brand_logo_url)
                <img src="{{ $brand_logo_url }}"
                    style="width:40px; height:40px; border-radius:50%; background:#fff; object-fit:cover; border:2px solid #fff;">
            @else
                <div
                    style="width:40px; height:40px; border-radius:50%; background:#fff; display:flex; align-items:center; justify-content:center; color:{{ $widget_color }}; font-size:16px; font-weight:900;">
                    B</div>
            @endif
            <div style="color:white; text-align:left;">
                <p style="font-weight:900; font-size:14px; margin:0; line-height: 1;">
                    {{ $brand_name ?: ($name ?: 'Your Brand') }}</p>
                <p style="font-size:10px; opacity:0.9; margin:0; display:flex; align-items:center; gap:3px;">
                    <span style="width:6px; height:6px; background:#25D366; border-radius:50%;"></span>
                    {{ $brand_subtitle ?: 'online' }}
                </p>
            </div>
        </div>

        <!-- Body -->
        <div style="padding:20px; background:#f8fafc; display:flex; flex-direction:column; gap:15px;">
            <!-- Agent Bubble -->
            <div
                style="background:#fff; padding:12px 18px; border-radius:15px; border-top-left-radius:0; box-shadow:0 2px 5px rgba(0,0,0,0.05); font-size:13px; color:#475569; position:relative; text-align:left; line-height:1.4;">
                {{ $welcome_message ?: 'Hi, How can I help you?' }}
                <div
                    style="position:absolute; top:0; left:-6px; width:0; height:0; border-top: 10px solid #fff; border-left: 10px solid transparent;">
                </div>
            </div>

            @if($collect_name)
                <div
                    style="height:35px; background:#fff; border:1px solid #e2e8f0; border-radius:12px; display:flex; align-items:center; px-3; font-size:11px; color:#cbd5e1; padding-left:12px;">
            {{ $placeholder_name ?: 'Full Name' }}</div> @endif
            @if($collect_email)
                <div
                    style="height:35px; background:#fff; border:1px solid #e2e8f0; border-radius:12px; display:flex; align-items:center; px-3; font-size:11px; color:#cbd5e1; padding-left:12px;">
            {{ $placeholder_email ?: 'Email Address' }}</div> @endif

            <!-- CTA Button -->
            <div
                style="background:{{ $widget_color }}; border-radius:12px; height:45px; display:flex; align-items:center; justify-content:center; color:white; font-size:12px; font-weight:900; letter-spacing:1px; border-bottom: 4px solid rgba(0,0,0,0.1);">
                {{ strtoupper($button_text ?: 'CONTINUE TO CHAT') }}
            </div>
        </div>
        <!-- Footer -->
        <div
            style="padding:10px; text-align:center; font-size:9px; color:#94a3b8; font-weight:900; text-transform:uppercase; letter-spacing:1.5px; background:#f8fafc;">
            {{ $footer_text ?: 'Powered by Growth Tools' }}</div>
    </div>

    <!-- Floating Button -->
    <div
        style="background:{{ $widget_color }}; color:white; padding:14px 28px; border-radius:{{ $border_radius }}px; box-shadow:0 10px 30px rgba(0,0,0,0.2); font-size:14px; font-weight:900; display:flex; align-items:center; gap:12px; float:{{ $position }};">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path
                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.445 0 .081 5.363.079 11.969c0 2.112.552 4.175 1.598 6.013L0 24l6.15-1.613a11.893 11.893 0 005.894 1.565h.005c6.598 0 11.964-5.365 11.966-11.972.003-3.194-1.246-6.195-3.511-8.455z" />
        </svg>
        {{ $button_text ?: 'Chat with us' }}
    </div>
</div>