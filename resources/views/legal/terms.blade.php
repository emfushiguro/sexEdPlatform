<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900">Terms of Service</h1>
                    <a href="{{ route('register') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                        ← Back to Registration
                    </a>
                </div>
                <p class="mt-2 text-sm text-gray-600">Last Updated: {{ now()->format('F d, Y') }}</p>
            </div>
        </div>

        <!-- Content -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 space-y-8">
                
                <!-- Introduction -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">1. Introduction</h2>
                    <p class="text-gray-700 leading-relaxed">
                        Welcome to Sex Education Learning Platform. By accessing or using our platform, you agree to be bound by these Terms of Service. 
                        This platform is designed to provide age-appropriate sexual education and health information to learners of all ages.
                    </p>
                </section>

                <!-- Age Requirements -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">2. Age Requirements & Parental Consent</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            <strong>For Users Under 13 Years Old:</strong> In compliance with the Children's Online Privacy Protection Act (COPPA), 
                            children under the age of 13 cannot create an account without parental consent. A parent or legal guardian must:
                        </p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li>Register as a parent user (must be 18 years or older)</li>
                            <li>Verify their email address</li>
                            <li>Create a child account on behalf of the minor</li>
                            <li>Maintain oversight of the child's account and activities</li>
                        </ul>
                        <p class="leading-relaxed">
                            <strong>For Users 13 Years and Older:</strong> Users aged 13-17 may create their own accounts but must provide 
                            a valid email address and complete email verification before accessing the platform.
                        </p>
                        <p class="leading-relaxed">
                            <strong>For Adult Users (18+):</strong> Adult users may create accounts, enroll in courses, and optionally 
                            create and manage accounts for their children.
                        </p>
                    </div>
                </section>

                <!-- User Accounts -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">3. User Accounts</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed"><strong>Account Creation:</strong></p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li>You must provide accurate, complete information during registration</li>
                            <li>Currently, only Gmail accounts (@gmail.com) are accepted for email verification</li>
                            <li>You are responsible for maintaining the confidentiality of your account credentials</li>
                            <li>Parent accounts are verified through email confirmation</li>
                        </ul>
                        <p class="leading-relaxed mt-4"><strong>Account Security:</strong></p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li>Never share your password with anyone</li>
                            <li>Notify us immediately if you suspect unauthorized access to your account</li>
                            <li>Parents are responsible for securing their child accounts</li>
                        </ul>
                    </div>
                </section>

                <!-- Platform Use -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">4. Acceptable Use</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">You agree to use the platform only for lawful educational purposes. You may NOT:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li>Share inappropriate content or engage in harassment</li>
                            <li>Attempt to access other users' accounts or data</li>
                            <li>Disrupt the platform's functionality or security</li>
                            <li>Misrepresent your age or identity</li>
                            <li>Share copyrighted materials without permission</li>
                            <li>Use the platform for commercial purposes without authorization</li>
                        </ul>
                    </div>
                </section>

                <!-- Parent Rights -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">5. Parent Rights & Responsibilities</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">Parents who create accounts for children under 13 have the right to:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li>View their child's learning progress and module completion status</li>
                            <li>Review quiz attempts, answers, and scores</li>
                            <li>Approve or restrict access to specific content (when implemented)</li>
                            <li>Request deletion of their child's account and all associated data</li>
                        </ul>
                        <p class="leading-relaxed mt-4">Parents are responsible for:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li>Monitoring their child's platform usage and activities</li>
                            <li>Discussing age-appropriate content with their child</li>
                            <li>Ensuring their child understands safe online behavior</li>
                            <li>Keeping their parent account credentials secure</li>
                        </ul>
                    </div>
                </section>

                <!-- Content & IP -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">6. Content & Intellectual Property</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            All educational content, modules, quizzes, videos, and materials on this platform are the intellectual property of 
                            {{ config('app.name') }} and its content creators. You may not:
                        </p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li>Reproduce, distribute, or sell platform content without permission</li>
                            <li>Remove copyright notices or attribution</li>
                            <li>Use content for commercial purposes without authorization</li>
                        </ul>
                    </div>
                </section>

                <!-- Privacy -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">7. Privacy & Data Protection</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            Your privacy is important to us. Please review our 
                            <a href="{{ route('privacy') }}" class="text-blue-600 hover:text-blue-700 underline">Privacy Policy</a> 
                            to understand how we collect, use, and protect your personal information, especially for users under 13.
                        </p>
                    </div>
                </section>

                <!-- Disclaimers -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">8. Educational Disclaimers</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            The information provided on this platform is for educational purposes only and should not replace 
                            professional medical advice, diagnosis, or treatment. Always consult with a qualified healthcare provider 
                            for medical concerns.
                        </p>
                        <p class="leading-relaxed">
                            We strive to provide accurate, age-appropriate information, but we make no warranties about the completeness 
                            or accuracy of the content.
                        </p>
                    </div>
                </section>

                <!-- Limitation of Liability -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">9. Limitation of Liability</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            Sex Education Learning Platform is provided "as is" for educational purposes. We are not liable for:
                        </p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li>Any damages arising from your use of the platform</li>
                            <li>Content inaccuracies or errors</li>
                            <li>Service interruptions or data loss</li>
                            <li>Unauthorized access to your account due to stolen credentials</li>
                        </ul>
                    </div>
                </section>

                <!-- Termination -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">10. Termination</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            We reserve the right to suspend or terminate accounts that violate these Terms of Service. 
                            You may request account deletion at any time by contacting support.
                        </p>
                        <p class="leading-relaxed">
                            Parents may request immediate deletion of child accounts, and we will comply within 30 days.
                        </p>
                    </div>
                </section>

                <!-- Changes to Terms -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">11. Changes to Terms</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            We may update these Terms of Service from time to time. Significant changes will be communicated via 
                            email to registered users. Continued use of the platform after changes constitutes acceptance of the new terms.
                        </p>
                    </div>
                </section>

                <!-- Contact -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">12. Contact Information</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            If you have questions about these Terms of Service, please contact us at:
                        </p>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mt-3">
                            <p class="font-medium text-gray-900">{{ config('app.name') }} Support</p>
                            <p class="text-gray-600 text-sm mt-1">sexeducation@platform.com</p>
                        </div>
                    </div>
                </section>

                <!-- Acceptance -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
                    <p class="text-sm text-gray-700 leading-relaxed">
                        <strong>By creating an account or using {{ config('app.name') }}, you acknowledge that you have read, 
                        understood, and agree to be bound by these Terms of Service.</strong>
                    </p>
                </div>
            </div>

            <!-- Footer Links -->
            <div class="mt-8 text-center space-x-4 pb-8">
                <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 font-medium">Back to Registration</a>
                <span class="text-gray-400">•</span>
                <a href="{{ route('privacy') }}" class="text-blue-600 hover:text-blue-700 font-medium">Privacy Policy</a>
            </div>
        </div>
    </div>
</body>
</html>
