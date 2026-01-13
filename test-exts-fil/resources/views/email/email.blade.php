<x-mail :title="$content['subject']" :greeting="$content['greeting']" :actionUrl="$content['action_url']" :actionText="$content['action_text']">
  <p>{{ $content['body'] }}</p>
</x-mail>
