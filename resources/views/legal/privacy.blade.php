<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy &mdash; {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gray-50">

    <!-- Gradient Header -->
    <header style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-9 h-9 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-white">Privacy Policy</h1>
                    </div>
                    <p class="text-purple-200 text-sm">Last Updated: November 14, 2026</p>
                </div>
                <x-legal-back-link class="bg-white/15 text-white hover:bg-white/25" />
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 sm:p-10 space-y-8">
                
                <!-- Introduction -->
                <section>
                    <h2 class="text-xl font-semibold mb-4" style="color: #730DB1;">1. Introduction</h2>
                    <p class="text-gray-700 leading-relaxed">
                        {{ config('app.name') }} is committed to protecting your privacy and the privacy of children who use our platform. 
                        This Privacy Policy explains how we collect, use, store, and protect personal information, including information about minors and supported dependents.
                    </p>
                </section>

                <!-- COPPA Compliance -->
                <section>
                    <div class="rounded-xl p-6 mb-6 border-l-4" style="background:#fdf4ff; border-color:#730DB1;">
                        <h3 class="text-lg font-semibold mb-2" style="color:#730DB1;">Privacy for dependents</h3>
                        <p class="text-gray-700 leading-relaxed">
                            Minors need an approved Guardian relationship and authorization to register. Adult dependents may consent personally or through a separately reviewed representative. A Guardian's access is limited by the relationship and permissions; passwords and private messages are not shared through those permissions.
                        </p>
                    </div>
                </section>

                <!-- Information We Collect -->
                <section>
                    <h2 class="text-xl font-semibold mb-4" style="color: #730DB1;">2. Information We Collect</h2>
                    
                    <div class="space-y-6">
                        <!-- For All Users -->
                        <div>
                            <h3 class="text-lg font-medium mb-3" style="color:#A30EB2;">For All Users (Registration Information)</h3>
                            <ul class="list-disc list-inside pl-4 space-y-2 text-gray-700">
                                <li><strong>Personal Information:</strong> First name, middle initial (optional), last name, suffix (optional)</li>
                                <li><strong>Birthdate:</strong> To calculate age and determine appropriate content access</li>
                                <li><strong>Email Address:</strong> Used for account access and verification when provided; a dependent's personal email is optional</li>
                                <li><strong>Password:</strong> Stored as a password hash</li>
                                <li><strong>Location:</strong> Region, province, city, barangay (for Philippine users)</li>
                                <li><strong>Username:</strong> Created during profile completion</li>
                            </ul>
                        </div>

                        <!-- For Child Users -->
                        <div>
                            <h3 class="text-lg font-medium mb-3" style="color:#A30EB2;">Additional Information for Dependents</h3>
                            <ul class="list-disc list-inside pl-4 space-y-2 text-gray-700">
                                <li><strong>Guardian Information:</strong> Guardian's name, email, and verified relationship</li>
                                <li><strong>Authorization:</strong> Who authorized registration, the notice versions accepted, and the time of authorization; representative authority evidence is reviewed separately</li>
                                <li><strong>Verification:</strong> Identity documents or alternative evidence, a selfie, relationship evidence, and review decisions</li>
                                <li><strong>Permissions:</strong> Guardian viewing and support-information settings</li>
                                <li><strong>Optional Support Information:</strong> Support details supplied for the dependent's use, with access controlled by permissions</li>
                            </ul>
                            <div class="rounded-xl p-4 mt-3 bg-amber-50 border border-amber-200">
                                <p class="text-sm text-yellow-800">
                                    <strong>Note:</strong> Users under 18 require Guardian authorization. Adult dependents control optional Guardian access. Identity and relationship reviews are separate.
                                </p>
                            </div>
                        </div>

                        <!-- Usage Data -->
                        <div>
                            <h3 class="text-lg font-medium mb-3" style="color:#A30EB2;">Usage & Learning Data</h3>
                            <ul class="list-disc list-inside pl-4 space-y-2 text-gray-700">
                                <li><strong>Learning Progress:</strong> Module enrollments, lesson completion, quiz attempts</li>
                                <li><strong>Quiz Responses:</strong> Answers, scores, and timestamps</li>
                                <li><strong>Achievements:</strong> Earned certificates</li>
                                <li><strong>Activity Logs:</strong> Login times, content views, and interactions</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- How We Use Information -->
                <section>
                    <h2 class="text-xl font-semibold mb-4" style="color: #730DB1;">3. How We Use Your Information</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">We use collected information for the following purposes:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>Account Management:</strong> Create and maintain user accounts, authenticate users</li>
                            <li><strong>Age-Appropriate Content:</strong> Use date of birth to determine the available learning category; content for users under 10 is currently limited</li>
                            <li><strong>Educational Services:</strong> Track progress, generate certificates, manage quizzes</li>
                            <li><strong>Guardian Support:</strong> Share permitted information with an approved Guardian according to the relationship and access settings</li>
                            <li><strong>Communication:</strong> Send email verification, important notifications, and updates</li>
                            <li><strong>Platform Improvement:</strong> Analyze usage patterns to improve educational content</li>
                        </ul>
                        <div class="rounded-xl p-4 mt-4 bg-green-50 border border-green-200">
                            <p class="text-sm text-gray-700">
                                <strong>We do NOT:</strong> Sell personal information, share it with third parties for marketing, 
                                or use it for purposes unrelated to education.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Guardian Rights (COPPA) -->
                <section>
                    <h2 class="text-xl font-semibold mb-4" style="color: #730DB1;">4. Guardian Access & Privacy Choices</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">A verified, eligible Guardian automatically has access to view, edit and remove a minor dependent's optional Health &amp; Support Information. Adult dependents control this sharing through their access settings. Learning access follows the relationship permissions. The platform may allow a Guardian to:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>Review:</strong> View permitted learning and support information</li>
                            <li><strong>Requests:</strong> Contact support about access, correction, or deletion requests</li>
                            <li><strong>Monitor:</strong> View your child's:
                                <ul class="list-circle list-inside pl-8 mt-2 space-y-1">
                                    <li>Learning progress and module completion status</li>
                                    <li>Quiz attempts, answers, and scores</li>
                                    <li>Earned certificates</li>
                                </ul>
                            </li>
                            <li><strong>Content:</strong> Review content requests when the relationship permits it</li>
                        </ul>
                        <p class="leading-relaxed mt-4">
                            Adult dependents can grant or revoke optional Guardian access in account settings. Guardians do not receive the dependent's password or private messages through these permissions. For privacy requests, use the platform's help page.
                        </p>
                    </div>
                </section>

                <!-- Data Sharing -->
                <section>
                    <h2 class="text-xl font-semibold mb-4" style="color: #730DB1;">5. Information Sharing & Disclosure</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">We DO NOT sell or rent personal information. We may share information only in these limited cases:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>With Guardians:</strong> Approved Guardians can view information permitted by the relationship and its settings</li>
                            <li><strong>With Instructors:</strong> Instructors can view learner progress for courses they manage</li>
                            <li><strong>Service Providers:</strong> Email delivery, hosting, and identity verification providers when needed to operate the platform</li>
                            <li><strong>Legal Requirements:</strong> If required by law, court order, or government request</li>
                            <li><strong>Safety:</strong> To protect the safety of users or prevent harmful activity</li>
                        </ul>
                        <div class="rounded-xl p-4 mt-4" style="background:#fdf4ff; border:1px solid #c084fc;">
                            <p class="text-sm text-gray-700">
                                Any third-party service providers we use are required to maintain the confidentiality and security 
                                of personal information and are prohibited from using it for any other purpose.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Data Security -->
                <section>
                    <h2 class="text-xl font-semibold mb-4" style="color: #730DB1;">6. Data Security</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">We implement industry-standard security measures to protect your information:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>Password Encryption:</strong> All passwords are hashed using bcrypt encryption</li>
                            <li><strong>HTTPS/TLS:</strong> All data transmitted between your browser and our servers is encrypted</li>
                            <li><strong>Email Verification:</strong> Ensures account ownership before granting access</li>
                            <li><strong>Access Controls:</strong> Role-based permissions limit who can view data</li>
                            <li><strong>Regular Security Updates:</strong> Platform software is kept up-to-date</li>
                        </ul>
                        <p class="leading-relaxed mt-4">
                            However, no method of transmission over the internet is 100% secure. While we strive to protect your 
                            information, we cannot guarantee absolute security.
                        </p>
                    </div>
                </section>

                <!-- Data Retention -->
                <section>
                    <h2 class="text-xl font-semibold mb-4" style="color: #730DB1;">7. Data Retention</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">We retain account, verification, authorization, and review records according to the applicable retention and deletion requirements. Contact support through the platform for information about a particular record.</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>Active Accounts:</strong> Data is retained while needed to operate the account</li>
                            <li><strong>Deletion Requests:</strong> A user or authorized representative may contact support about a request</li>
                            <li><strong>Legal Requirements:</strong> Some records may need to be retained longer</li>
                        </ul>
                        <p class="leading-relaxed mt-4">
                            Guardians may contact support about deletion requests for dependents they are authorized to represent.
                        </p>
                        <p class="leading-relaxed mt-4">
                            For learner identity verification, we collect an ID or alternative evidence and a selfie. Authorized reviewers may also review relationship and representative-authority evidence. These records follow the applicable retention and deletion requirements, including legal requirements.
                        </p>
                    </div>
                </section>

                <!-- Email Communications -->
                <section>
                    <h2 class="text-xl font-semibold mb-4" style="color: #730DB1;">8. Email Communications</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">We send emails for:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>Account Verification:</strong> Used to verify email ownership when an email is provided</li>
                            <li><strong>Important Notifications:</strong> Security alerts, policy changes, account issues</li>
                            <li><strong>Educational Updates:</strong> Course completion notifications, achievement unlocks</li>
                        </ul>
                        <p class="leading-relaxed mt-4">
                            <strong>Email Verification:</strong> A valid email address is required to receive account verification messages.
                        </p>
                        <p class="leading-relaxed">
                            You cannot opt out of essential account emails (verification, security), but can control optional notifications 
                            in account settings.
                        </p>
                    </div>
                </section>

                <!-- Cookies -->
                <section>
                    <h2 class="text-xl font-semibold mb-4" style="color: #730DB1;">9. Cookies & Tracking</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">We use minimal cookies and tracking technologies:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>Session Cookies:</strong> To maintain your login session (essential)</li>
                            <li><strong>CSRF Protection:</strong> For security against cross-site attacks (essential)</li>
                            <li><strong>Remember Me:</strong> If you choose to stay logged in (optional)</li>
                        </ul>
                        <p class="leading-relaxed mt-4">
                            Embedded services or analytics may use additional cookies. Their use depends on the services enabled for the platform.
                        </p>
                    </div>
                </section>

                <!-- User Rights -->
                <section>
                    <h2 class="text-xl font-semibold mb-4" style="color: #730DB1;">10. Your Rights</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">All users have the right to:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>Access:</strong> View all personal information we have about you</li>
                            <li><strong>Correction:</strong> Update or correct your information</li>
                            <li><strong>Deletion:</strong> Request account deletion and data removal</li>
                            <li><strong>Data Portability:</strong> Request a copy of your data in a portable format</li>
                            <li><strong>Withdraw Consent:</strong> Delete your account if you no longer want to use the platform</li>
                        </ul>
                        <p class="leading-relaxed mt-4">
                            Use the <a class="underline" href="{{ route('help.index') }}">help page</a> to contact us about these rights.
                        </p>
                    </div>
                </section>

                <!-- Third-Party Links -->
                <section>
                    <h2 class="text-xl font-semibold mb-4" style="color: #730DB1;">11. Third-Party Links</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            Our platform may contain links to external websites or embedded videos (e.g., YouTube). We are not 
                            responsible for the privacy practices of these third-party sites. We encourage guardians to review the
                            privacy policies of any sites their children visit.
                        </p>
                    </div>
                </section>

                <!-- International Users -->
                <section>
                    <h2 class="text-xl font-semibold mb-4" style="color: #730DB1;">12. International Users</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            This platform is designed for users in the Philippines. Information may be processed by the service providers described in this notice.
                        </p>
                    </div>
                </section>

                <!-- Changes to Policy -->
                <section>
                    <h2 class="text-xl font-semibold mb-4" style="color: #730DB1;">13. Changes to This Privacy Policy</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            We may update this Privacy Policy. We may provide notice through the platform or by email where available, and request renewed consent where required.
                        </p>
                        <p class="leading-relaxed">
                            The "Last Updated" date at the top of this page indicates when the policy was last revised.
                        </p>
                    </div>
                </section>

                <!-- Contact -->
                <section>
                    <h2 class="text-xl font-semibold mb-4" style="color: #730DB1;">14. Contact Us</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            If you have questions, concerns, or requests regarding this Privacy Policy or your personal information, 
                            please contact us:
                        </p>
                        <div class="rounded-xl p-4 mt-3 border" style="border-color: #730DB1; background: #fdf4ff;">
                            <p class="font-semibold" style="color: #730DB1;">{{ config('app.name') }} Support</p>
                            <p class="text-gray-600 text-sm mt-1"><a class="underline" href="{{ route('help.index') }}">Open the help page</a></p>
                        </div>
                        <p class="leading-relaxed mt-4 text-sm">
                            Guardians and authorized representatives can use the help page for questions about a dependent's information.
                        </p>
                    </div>
                </section>

                <!-- Acceptance banner -->
                <div class="rounded-xl p-6 mt-8" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                    <p class="text-sm text-white leading-relaxed text-center">
                        <strong>By creating an account or using {{ config('app.name') }}, you acknowledge that you have read
                        and understood this Privacy Policy and agree to its terms.</strong>
                    </p>
                    <p class="text-sm text-purple-200 leading-relaxed text-center mt-2">
                        <strong>For Guardians:</strong> By creating a child account, you confirm that you have read this Privacy Policy
                        and consent to the collection and use of your child's information as described.
                    </p>
                </div>
        </div><!-- /card -->

        <!-- Footer Links -->
        <div class="mt-8 text-center space-x-4 pb-10 text-sm">
            <x-legal-back-link style="color: #730DB1;" />
            <span class="text-gray-400">•</span>
            <a href="{{ route('terms') }}" class="font-medium hover:opacity-75 transition-opacity" style="color: #730DB1;">Terms of Service</a>
        </div>
    </main>

</body>
</html>
