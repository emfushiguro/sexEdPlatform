<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate - {{ $certificate->certificate_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            padding: 40px;
            background: white;
        }
        .certificate {
            border: 12px double #d97706;
            padding: 60px;
            background: linear-gradient(to bottom right, #fef3c7, white);
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .logo {
            font-size: 48px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 36px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #6b7280;
            font-size: 14px;
        }
        .divider {
            border-top: 2px solid #d97706;
            margin: 40px 0;
        }
        .content {
            text-align: center;
            margin-bottom: 40px;
        }
        .content p {
            font-size: 16px;
            color: #374151;
            margin-bottom: 20px;
        }
        .recipient-name {
            font-size: 32px;
            font-weight: bold;
            color: #1f2937;
            margin: 30px 0;
            border-bottom: 2px solid #374151;
            display: inline-block;
            padding-bottom: 5px;
        }
        .module-title {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin: 30px 0;
        }
        .description {
            color: #6b7280;
            font-size: 14px;
        }
        .footer {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            padding-top: 30px;
            border-top: 2px solid #d1d5db;
        }
        .footer-item {
            text-align: left;
        }
        .footer-item:last-child {
            text-align: right;
        }
        .footer-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .footer-value {
            font-weight: bold;
            color: #1f2937;
        }
        .cert-number {
            font-family: 'Courier New', monospace;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            <div class="logo">🏆</div>
            <h1>Certificate of Completion</h1>
            <p class="subtitle">Sexual and Reproductive Health Education Platform</p>
        </div>

        <div class="divider"></div>

        <div class="content">
            <p>This is to certify that</p>
            <div class="recipient-name">{{ $certificate->learner_name }}</div>
            <p>has successfully completed the module</p>
            <div class="module-title">{{ $certificate->module_title }}</div>
            <p class="description">Demonstrating knowledge and understanding of the course material</p>
        </div>

        <div class="footer">
            <div class="footer-item">
                <div class="footer-label">Certificate Number:</div>
                <div class="footer-value cert-number">{{ $certificate->certificate_number }}</div>
            </div>
            <div class="footer-item">
                <div class="footer-label">Date Issued:</div>
                <div class="footer-value">{{ $certificate->issued_at->format('F d, Y') }}</div>
            </div>
        </div>
    </div>

    <script>
        // Auto-print when PDF view is opened
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
