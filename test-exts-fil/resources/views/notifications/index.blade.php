@php
  $notifications = [
      [
          'id' => 1,
          'group' => 'Today',
          'title' => 'Datacorp',
          'message' => 'Caleb Flakelar commented on Admin',
          'time' => '1 min ago',
          'icon' => 'mgc_message_3_line',
      ],
      [
          'id' => 2,
          'group' => 'Yesterday',
          'title' => 'Datacorp',
          'message' => 'Caleb Flakelar commented on Admin',
          'time' => '',
          'icon' => 'mgc_message_1_line',
      ],
  ];
@endphp
@json($groupedNotifications, JSON_PRETTY_PRINT)
