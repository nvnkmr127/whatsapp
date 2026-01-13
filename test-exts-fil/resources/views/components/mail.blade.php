<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? (get_setting('general.site_name') ?: config('app.name')) }}</title>
</head>

<body
  style="margin: 0; padding: 0; background-color: #f5f6fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
  <!-- Main Table -->
  <table width="100%" cellpadding="0" cellspacing="0" border="0"
    style="background-color: #f5f6fa; padding: 30px;">
    <tr>
      <td align="center">
        <!-- Content Table -->
        <table width="100%" cellpadding="0" cellspacing="0" border="0"
          style="max-width: 800px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
          <!-- Header -->
          <tr>
            <td
              style="background-color: #6366f1; padding: 30px; text-align: center; border-top-left-radius: 12px; border-top-right-radius: 12px;">
              @php
                $logo = get_setting('general.site_dark_logo');
                $logoUrl = '';

                if (!empty($logo) && Storage::disk('public')->exists($logo)) {
                    $logoUrl =  asset('storage/' . $logo);
                } else {
                    $logoUrl = asset('img/dark_logo.png');
                }
              @endphp
              <img src="{{ $logoUrl }}"
                alt="{{ get_setting('general.site_name') ?: config('app.name') }}"
                style="height: 40px; width: auto; margin-bottom: 10px;">
            </td>
          </tr>

          <!-- Content -->
          <tr>
            <td style="padding: 40px 50px;">
              <h2 style="color: #1a1a1a; font-size: 24px; margin: 0 0 25px 0; font-weight: 600;">
                {{ $greeting ?? 'Hello there,' }}
              </h2>

              {!! $body !!}

              @if (!empty($actionUrl) && !empty($actionText))
                <!-- Action Button -->
                <table width="100%" cellpadding="0" cellspacing="0" border="0"
                  style="margin: 30px 0;">
                  <tr>
                    <td align="center">
                      <a href="{{ $actionUrl }}"
                        style="display: inline-block; background-color: #6366f1; color: #ffffff;
                                                  padding: 12px 24px; border-radius: 6px; text-decoration: none;
                                                  font-weight: 600; font-size: 16px;">
                        {{ $actionText }}
                      </a>
                    </td>
                  </tr>
                </table>
              @endif

              <!-- Divider -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                style="margin: 35px 0;">
                <tr>
                  <td style="border-bottom: 1px solid #e5e7eb;"></td>
                </tr>
              </table>

              <!-- Additional Info -->
              <p style="color: #6b7280; font-size: 14px; line-height: 24px; margin: 0;">
                This is an automated message. Please do not reply to this email.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td
              style="background-color: #f8fafc; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
              <p style="color: #6b7280; font-size: 14px; margin: 0 0 10px 0;">
                © {{ date('Y') }} {{ get_setting('general.site_name') ?: config('app.name') }}.
                All
                rights reserved.
              </p>
              <p style="color: #6b7280; font-size: 14px; margin: 0;">
                Made with ♥ by {{ get_setting('general.site_name') ?: config('app.name') }}
              </p>
            </td>
          </tr>
        </table>

        <!-- Preview Text (Hidden) -->
        <div style="display: none; max-height: 0px; overflow: hidden;">
          {{ get_setting('general.site_name') ?: config('app.name') }} SMTP Configuration Test
          Email
        </div>
        <div style="display: none; max-height: 0px; overflow: hidden;">
          &#847; &zwnj; &nbsp;
        </div>
      </td>
    </tr>
  </table>
</body>

</html>
