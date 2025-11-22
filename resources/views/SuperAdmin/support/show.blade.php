@extends('layouts.superadmin')

@section('content')
<div class="p-6 space-y-4">
  {{-- Page title + back link --}}
  <div class="flex justify-between items-center mb-6">
    <div class="flex items-center gap-3">
      @php
        $initial = strtoupper(mb_substr($business->name ?? 'B', 0, 1));
      @endphp

      @php
        use Illuminate\Support\Str;
        $logoPath = (string) ($business->logo ?? '');
        $logoUrl = Str::startsWith($logoPath, ['http://','https://'])
            ? $logoPath
            : (Str::startsWith($logoPath, ['storage/','/storage/'])
                ? asset(ltrim($logoPath,'/'))
                : ($logoPath !== '' ? asset('storage/'.ltrim($logoPath,'/')) : ''));
      @endphp

      @if($logoUrl)
        <img
          src="{{ $logoUrl }}"
          alt="{{ $business->name }}"
          class="w-9 h-9 rounded-full object-cover"
          style="border:3px solid var(--sidebar, #1F2937);"
        >
      @else
        <div
          class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-700 font-semibold"
          style="border:3px solid var(--sidebar, #1F2937);"
        >
          {{ $initial }}
        </div>
      @endif

      <h1 class="text-2xl font-bold">
        @tr('Conversation —')
        {{ $business->name ?? (tr('Business #') . $business->id) }}
      </h1>
    </div>

    <a href="{{ route('admin.conversations') }}" class="text-blue-600 hover:underline">
      @tr('Back to Conversations')
    </a>
  </div>

  {{-- Chat card (matches Settings/Business cards: white, rounded, shadow) --}}
  <div class="bg-white rounded-lg shadow-md p-6 flex flex-col">
    {{-- Messages stream --}}
    <div id="chatStream"
         class="flex-1 overflow-y-auto space-y-3 max-h-[65vh] border-b border-gray-200 pb-4"
         style="scroll-behavior:smooth;">
      @foreach($messages as $m)
        @php
          $isMe = $m->sender_role === 'admin';
          $rtl  = app()->isLocale('ar');
          // EN: my msgs right | AR: my msgs left
          $rowClass = $isMe
                      ? ($rtl ? 'justify-start' : 'justify-end')
                      : ($rtl ? 'justify-end' : 'justify-start');
        @endphp

        <div class="flex {{ $rowClass }}">
          <div class="rounded-2xl px-3 py-2 shadow-sm max-w-[75%] break-words
                      {{ $isMe ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-900' }}">
            <div class="text-sm">{{ $m->body }}</div>
            <div class="text-[10px] mt-1 {{ $isMe ? 'text-white/80' : 'text-gray-500' }}">
              {{ $m->created_at->format('Y-m-d H:i') }}
            </div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- Composer: align styles with Business side (input focus ring + primary hover button) --}}
    <form method="post"
          action="{{ route('admin.conversations.reply', $business->id ?? 0) }}"
          class="mt-3 flex gap-2">
      @csrf
      {{-- Text input: neutral border, strong focus ring to match theme --}}
      <input
        name="body"
        placeholder="@tr('Type reply...')"
        class="flex-1 border border-gray-300 rounded-md px-3 py-2
               focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
      >
      {{-- Send button: primary color with hover darken and smooth transition --}}
      <button
        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded
               transition duration-300 ease-in-out"
      >
        @tr('Send')
      </button>
    </form>
  </div>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var box = document.getElementById('chatStream');
    if (box) box.scrollTop = box.scrollHeight;     // land at bottom after load
    window.addEventListener('resize', function () {
      if (box) box.scrollTop = box.scrollHeight;
    });
  });
</script>
@endpush
@endsection
