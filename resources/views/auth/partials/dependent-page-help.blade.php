<details class="my-5 rounded-2xl border border-purple-200 bg-purple-50/60 p-4 text-sm leading-6 text-gray-800">
    <summary class="cursor-pointer font-semibold text-purple-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-purple-700">Help me use this page</summary>
    <div class="mt-3 space-y-3">
        @if($step === 'activation')
            <p><strong>Your account:</strong> You are setting up your own sign-in. Your Guardian can help you read or use this page. You can make your own choices about the account.</p>
            <p><strong>Your password:</strong> Before typing it, you can ask the person helping you to look away or give you privacy. You may paste a password or use a password manager. Do not share your password.</p>
            <p><strong>Your code:</strong> It is temporary and can be used once. If it expires, ask your Guardian to create a new activation code from your relationship page. A new code replaces an unused one.</p>
        @elseif($step === 'identity')
            <p><strong>Why we ask:</strong> Your identity documents and selfie help an administrator check who you are. Follow the instructions for your ID or the assisted review option, if available.</p>
            <p><strong>Getting help:</strong> A trusted person may read the instructions or help you navigate. You can ask questions and take your time before submitting.</p>
            <p><strong>What happens next:</strong> An administrator reviews your submission. You can check your verification status afterward.</p>
        @else
            <p><strong>Your account:</strong> Use these settings to manage your profile and password. You can ask a trusted person to help you read or navigate.</p>
            <p><strong>Your privacy:</strong> Keep your password and recovery codes private. Your Guardian does not automatically receive your password or private messages through the Guardian access settings.</p>
            <p><strong>Your choices:</strong> If you are an adult dependent, you can review optional Guardian access from My Guardian.</p>
        @endif
        <p>Read the <a href="{{ route('privacy') }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-purple-800 underline">Privacy Notice<span class="sr-only"> (opens in a new tab)</span></a> for details about how information is used.</p>
    </div>
</details>
