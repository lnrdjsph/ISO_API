{{-- ═══ MANAGER · EMAIL NOTIFICATIONS ═══ --}}
<x-guide.section id="email-notifications" number="📧" title="Email Notifications" roles="manager" color="purple" tag="Reference Guide">

    <p class="mb-4 text-sm text-gray-600">System notifications are sent automatically. Here's what each one means.</p>

    @php
        $emails = [
            [
                'accent' => 'purple', 'title' => '1. Order Submitted for Approval',
                'sub' => 'Sent to you when personnel request approval',
                'points' => ['Store personnel submit an order and click "Request For Approval".', 'You receive the email immediately.', 'It contains direct links to Review, Approve, or Reject.'],
                'badgeTitle' => '⏱️ Action Required', 'badgeText' => 'Review within 24 hours',
                'img' => 'email-order-for-approval.png',
                'subject' => 'Subject: "[ACTION REQUIRED] New Order #SOF202602-002 Awaiting Your Approval"',
            ],
            [
                'accent' => 'green', 'title' => '2. Order Approved',
                'sub' => 'Sent to store personnel when you approve',
                'points' => ['You approve the order and upload a supporting document.', 'Personnel receive an approval confirmation.', 'Includes a link to download the supporting document.'],
                'badgeTitle' => '📎 Document Attached', 'badgeText' => 'Personnel can now Generate SO#',
                'img' => 'email-order-approved.png',
                'subject' => 'Subject: "✅ Order #SOF202602-002 Has Been APPROVED"',
            ],
        ];
        $accents = [
            'purple' => ['border' => 'border-purple-100', 'bg' => 'bg-purple-50', 'text' => 'text-purple-800', 'sub' => 'text-purple-600', 'chip' => 'bg-purple-50 text-purple-800'],
            'green'  => ['border' => 'border-green-100',  'bg' => 'bg-green-50',  'text' => 'text-green-800',  'sub' => 'text-green-600',  'chip' => 'bg-green-50 text-green-800'],
        ];
    @endphp

    @foreach ($emails as $e)
        @php $a = $accents[$e['accent']]; @endphp
        <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="border-b {{ $a['border'] }} {{ $a['bg'] }} px-4 py-3">
                <h3 class="text-sm font-semibold {{ $a['text'] }}">{{ $e['title'] }}</h3>
                <p class="mt-1 text-xs {{ $a['sub'] }}">{{ $e['sub'] }}</p>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">What happens</h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            @foreach ($e['points'] as $p)
                                <li class="flex items-start gap-2">
                                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0 {{ $a['sub'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span>{{ $p }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="md:col-span-1">
                        <div class="rounded-lg {{ $a['chip'] }} p-3">
                            <p class="mb-1 text-xs font-medium">{{ $e['badgeTitle'] }}</p>
                            <p class="text-xs {{ $a['sub'] }}">{{ $e['badgeText'] }}</p>
                        </div>
                    </div>
                </div>
                <x-guide.screenshot :src="$e['img']" height="h-auto" :caption="$e['subject']" />
            </div>
        </div>
    @endforeach

    <x-guide.table :headers="['Email Type', 'Recipient', 'When Sent', 'Action Required']" :rows="[
        ['<span class=\'font-medium text-gray-800\'>Order For Approval</span>', 'Manager', 'Personnel request approval', '<span class=\'inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800\'>Review &amp; Decide</span>'],
        ['<span class=\'font-medium text-gray-800\'>Order Approved</span>', 'Personnel', 'Manager approves', '<span class=\'inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800\'>Generate SO#</span>'],
    ]" />
</x-guide.section>
