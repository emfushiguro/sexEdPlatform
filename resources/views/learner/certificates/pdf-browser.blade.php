<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate - {{ $certificate->certificate_number }}</title>
    @php
        $learnerNameLength = mb_strlen((string) $certificate->learner_name);
        $moduleTitleLength = mb_strlen((string) $certificate->module_title);
        $learnerNameFontSize = match (true) {
            $learnerNameLength > 90 => 13,
            $learnerNameLength > 60 => 18,
            $learnerNameLength > 38 => 26,
            $learnerNameLength > 22 => 32,
            $learnerNameLength > 14 => 38,
            default => 42,
        };
        $moduleTitleFontSize = match (true) {
            $moduleTitleLength > 140 => 12,
            $moduleTitleLength > 90 => 16,
            $moduleTitleLength > 60 => 21,
            default => 28,
        };
    @endphp
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: "Poppins", "Montserrat", "DejaVu Sans", sans-serif;
        }

        .certificate-page {
            position: relative;
            width: 297mm;
            height: 210mm;
            overflow: hidden;
        }

        .certificate-page img.background {
            width: 297mm;
            height: 210mm;
            display: block;
        }

        .field {
            position: absolute;
            color: #0f172a;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .learner-name {
            left: 31.04%;
            top: 44.62%;
            width: 38.05%;
            text-align: center;
            white-space: nowrap;
            font-size: {{ $learnerNameFontSize }}pt;
            font-weight: 700;
            line-height: 1.02;
            letter-spacing: 0.005em;
        }

        .module-name {
            left: 23.47%;
            top: 64.57%;
            width: 52.86%;
            text-align: center;
            font-size: {{ $moduleTitleFontSize }}pt;
            font-weight: 500;
            line-height: 1.1;
            letter-spacing: 0.01em;
        }

        .issued-date {
            left: 19.16%;
            top: 83.95%;
            width: 21.89%;
            text-align: left;
            font-size: 11pt;
            font-weight: 500;
            color: #64748b;
            letter-spacing: 0.03em;
        }

        .certificate-id {
            left: 42.79%;
            top: 93.81%;
            width: 14.48%;
            text-align: center;
            font-size: 11pt;
            font-weight: 500;
            color: #64748b;
            letter-spacing: 0.03em;
        }
    </style>
</head>
<body>
    <div class="certificate-page">
        <img
            src="data:image/png;base64,{{ $templateImageBase64 }}"
            alt="Certificate template"
            class="background"
        >

        <div class="field learner-name">{{ $certificate->learner_name }}</div>
        <div class="field module-name">{{ $certificate->module_title }}</div>
        <div class="field issued-date">Issued {{ $certificate->issued_at->format('F d, Y') }}</div>
        <div class="field certificate-id">{{ $certificate->certificate_number }}</div>
    </div>
</body>
</html>
