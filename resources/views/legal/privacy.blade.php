<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900">Privacy Policy</h1>
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
                        {{ config('app.name') }} is committed to protecting your privacy and the privacy of children who use our platform. 
                        This Privacy Policy explains how we collect, use, store, and protect personal information, with special attention 
                        to compliance with the Children's Online Privacy Protection Act (COPPA) for users under 13 years of age.
                    </p>
                </section>

                <!-- COPPA Compliance -->
                <section>
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-6 mb-6">
                        <h3 class="text-lg font-semibold text-blue-900 mb-2">COPPA Compliance Statement</h3>
                        <p class="text-blue-800 leading-relaxed">
                            We comply with the Children's Online Privacy Protection Act (COPPA). We do not knowingly collect personal 
                            information from children under 13 without verifiable parental consent. Parents have full control over 
                            their child's account and can review, modify, or delete their child's information at any time.
                        </p>
                    </div>
                </section>

                <!-- Information We Collect -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">2. Information We Collect</h2>
                    
                    <div class="space-y-6">
                        <!-- For All Users -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-3">For All Users (Registration Information)</h3>
                            <ul class="list-disc list-inside pl-4 space-y-2 text-gray-700">
                                <li><strong>Personal Information:</strong> First name, middle initial (optional), last name, suffix (optional)</li>
                                <li><strong>Birthdate:</strong> To calculate age and determine appropriate content access</li>
                                <li><strong>Email Address:</strong> Currently limited to Gmail accounts for verification</li>
                                <li><strong>Password:</strong> Stored securely using industry-standard encryption</li>
                                <li><strong>Location:</strong> Region, province, city, barangay (for Philippine users)</li>
                                <li><strong>Username:</strong> Created during profile completion</li>
                            </ul>
                        </div>

                        <!-- For Child Users -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Additional Information for Users Under 13</h3>
                            <ul class="list-disc list-inside pl-4 space-y-2 text-gray-700">
                                <li><strong>Parent Information:</strong> Parent's name, email, and verified relationship</li>
                                <li><strong>Parental Consent:</strong> Timestamp of when parent created the child account</li>
                                <li><strong>Monitoring Permissions:</strong> Parent's viewing and approval settings</li>
                            </ul>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-3">
                                <p class="text-sm text-yellow-800">
                                    <strong>Note:</strong> Children under 13 cannot create accounts independently. A parent or legal 
                                    guardian must register, verify their email, and create a child account on their behalf.
                                </p>
                            </div>
                        </div>

                        <!-- Usage Data -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Usage & Learning Data</h3>
                            <ul class="list-disc list-inside pl-4 space-y-2 text-gray-700">
                                <li><strong>Learning Progress:</strong> Module enrollments, lesson completion, quiz attempts</li>
                                <li><strong>Quiz Responses:</strong> Answers, scores, and timestamps</li>
                                <li><strong>Achievements:</strong> Earned badges, certificates, and rewards</li>
                                <li><strong>Activity Logs:</strong> Login times, content views, and interactions</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- How We Use Information -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">3. How We Use Your Information</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">We use collected information for the following purposes:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>Account Management:</strong> Create and maintain user accounts, authenticate users</li>
                            <li><strong>Age-Appropriate Content:</strong> Display content suitable for the user's age bracket (5-12, 13-17, 18+)</li>
                            <li><strong>Educational Services:</strong> Track progress, generate certificates, manage quizzes</li>
                            <li><strong>Parent Monitoring:</strong> Enable parents to view their child's learning activities</li>
                            <li><strong>Communication:</strong> Send email verification, important notifications, and updates</li>
                            <li><strong>Platform Improvement:</strong> Analyze usage patterns to improve educational content</li>
                        </ul>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mt-4">
                            <p class="text-sm text-gray-700">
                                <strong>We do NOT:</strong> Sell personal information, share it with third parties for marketing, 
                                or use it for purposes unrelated to education.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Parent Rights (COPPA) -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">4. Parent Rights & Choices (For Children Under 13)</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">As a parent or legal guardian, you have the right to:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>Review:</strong> Access all personal information collected from your child</li>
                            <li><strong>Modify:</strong> Update or correct your child's information at any time</li>
                            <li><strong>Delete:</strong> Request permanent deletion of your child's account and all associated data</li>
                            <li><strong>Monitor:</strong> View your child's:
                                <ul class="list-circle list-inside pl-8 mt-2 space-y-1">
                                    <li>Learning progress and module completion status</li>
                                    <li>Quiz attempts, answers, and scores</li>
                                    <li>Earned achievements and certificates</li>
                                    <li>Activity logs and login history</li>
                                </ul>
                            </li>
                            <li><strong>Control:</strong> Set permissions for content access (when implemented)</li>
                            <li><strong>Refuse Further Collection:</strong> Stop data collection by deleting the child's account</li>
                        </ul>
                        <p class="leading-relaxed mt-4">
                            To exercise these rights, log in to your parent account and visit the "My Children" dashboard, or 
                            contact us at support@example.com.
                        </p>
                    </div>
                </section>

                <!-- Data Sharing -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">5. Information Sharing & Disclosure</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">We DO NOT sell or rent personal information. We may share information only in these limited cases:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>With Parents:</strong> Parents can view their child's learning data and activity</li>
                            <li><strong>With Instructors:</strong> Instructors can view learner progress for courses they manage</li>
                            <li><strong>Service Providers:</strong> Email delivery (Gmail SMTP), hosting services - only as necessary to operate the platform</li>
                            <li><strong>Legal Requirements:</strong> If required by law, court order, or government request</li>
                            <li><strong>Safety:</strong> To protect the safety of users or prevent harmful activity</li>
                        </ul>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                            <p class="text-sm text-gray-700">
                                Any third-party service providers we use are required to maintain the confidentiality and security 
                                of personal information and are prohibited from using it for any other purpose.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Data Security -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">6. Data Security</h2>
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
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">7. Data Retention</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">We retain personal information for as long as necessary to provide our services:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>Active Accounts:</strong> Data is retained while the account is active</li>
                            <li><strong>Inactive Accounts:</strong> Accounts inactive for 2+ years may be deleted after notification</li>
                            <li><strong>Deletion Requests:</strong> When you delete an account, personal information is removed within 30 days</li>
                            <li><strong>Legal Requirements:</strong> Some data may be retained longer if required by law</li>
                        </ul>
                        <p class="leading-relaxed mt-4">
                            Parents can request immediate deletion of child accounts at any time.
                        </p>
                    </div>
                </section>

                <!-- Email Communications -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">8. Email Communications</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">We send emails for:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>Account Verification:</strong> Required to verify email ownership (13+ users and parents)</li>
                            <li><strong>Important Notifications:</strong> Security alerts, policy changes, account issues</li>
                            <li><strong>Educational Updates:</strong> Course completion notifications, achievement unlocks</li>
                        </ul>
                        <p class="leading-relaxed mt-4">
                            <strong>Gmail Requirement:</strong> Currently, we only accept Gmail addresses (@gmail.com) for email verification. 
                            This may expand in the future.
                        </p>
                        <p class="leading-relaxed">
                            You cannot opt out of essential account emails (verification, security), but can control optional notifications 
                            in account settings.
                        </p>
                    </div>
                </section>

                <!-- Cookies -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">9. Cookies & Tracking</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">We use minimal cookies and tracking technologies:</p>
                        <ul class="list-disc list-inside pl-4 space-y-2">
                            <li><strong>Session Cookies:</strong> To maintain your login session (essential)</li>
                            <li><strong>CSRF Protection:</strong> For security against cross-site attacks (essential)</li>
                            <li><strong>Remember Me:</strong> If you choose to stay logged in (optional)</li>
                        </ul>
                        <p class="leading-relaxed mt-4">
                            We do NOT use third-party tracking cookies, analytics, or advertising cookies.
                        </p>
                    </div>
                </section>

                <!-- User Rights -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">10. Your Rights</h2>
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
                            Contact us at support@example.com to exercise these rights.
                        </p>
                    </div>
                </section>

                <!-- Third-Party Links -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">11. Third-Party Links</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            Our platform may contain links to external websites or embedded videos (e.g., YouTube). We are not 
                            responsible for the privacy practices of these third-party sites. We encourage parents to review the 
                            privacy policies of any sites their children visit.
                        </p>
                    </div>
                </section>

                <!-- International Users -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">12. International Users</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            This platform is designed for users in the Philippines. By using the platform, you consent to the transfer 
                            and storage of your information in accordance with Philippine data protection laws and COPPA (if applicable).
                        </p>
                    </div>
                </section>

                <!-- Changes to Policy -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">13. Changes to This Privacy Policy</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            We may update this Privacy Policy from time to time. Significant changes will be communicated via email. 
                            For changes affecting children's privacy, we will obtain new parental consent if required by law.
                        </p>
                        <p class="leading-relaxed">
                            The "Last Updated" date at the top of this page indicates when the policy was last revised.
                        </p>
                    </div>
                </section>

                <!-- Contact -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">14. Contact Us</h2>
                    <div class="space-y-3 text-gray-700">
                        <p class="leading-relaxed">
                            If you have questions, concerns, or requests regarding this Privacy Policy or your personal information, 
                            please contact us:
                        </p>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mt-3">
                            <p class="font-medium text-gray-900">{{ config('app.name') }} Support</p>
                            <p class="text-gray-600 text-sm mt-1">Email: support@example.com</p>
                            <p class="text-gray-600 text-sm mt-1">Response Time: Within 48 hours</p>
                        </div>
                        <p class="leading-relaxed mt-4 text-sm">
                            <strong>For Parents:</strong> To exercise your COPPA rights regarding your child's information, 
                            include "COPPA Request" in your email subject line for priority handling.
                        </p>
                    </div>
                </section>

                <!-- Acceptance -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
                    <p class="text-sm text-gray-700 leading-relaxed">
                        <strong>By creating an account or using {{ config('app.name') }}, you acknowledge that you have read 
                        and understood this Privacy Policy and agree to its terms.</strong>
                    </p>
                    <p class="text-sm text-gray-700 leading-relaxed mt-2">
                        <strong>For Parents:</strong> By creating a child account, you confirm that you have read this Privacy Policy 
                        and consent to the collection and use of your child's information as described.
                    </p>
                </div>
            </div>

            <!-- Footer Links -->
            <div class="mt-8 text-center space-x-4 pb-8">
                <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 font-medium">Back to Registration</a>
                <span class="text-gray-400">•</span>
                <a href="{{ route('terms') }}" class="text-blue-600 hover:text-blue-700 font-medium">Terms of Service</a>
            </div>
        </div>
    </div>
</body>
</html>
