<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate - {{ $certificate->certificate_number }}</title>
    @php
        $learnerNameLength = mb_strlen((string) $certificate->learner_name);
        $moduleTitleLength = mb_strlen((string) $certificate->module_title);
        $learnerNameFontSize = max(8, min(34, 800 / max(1, $learnerNameLength)));
        $moduleTitleFontSize = max(8, min(25, 1400 / max(1, $moduleTitleLength)));
    @endphp
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
        }

        .certificate-page {
            position: fixed;
            left: 0;
            top: 0;
            width: 841.89pt;
            height: 595.28pt;
            background-image: url('data:image/png;base64,{{ $templateImageBase64 }}');
            background-size: 841.89pt 595.28pt;
            background-repeat: no-repeat;
        }

        .field {
            position: absolute;
            color: #111827;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .learner-name {
            left: 22%;
            top: 43%;
            width: 56%;
            text-align: center;
            word-wrap: break-word;
            font-size: {{ $learnerNameFontSize }}pt;
            font-weight: 700;
            line-height: 1.02;
        }

        .module-name {
            left: 22%;
            top: 64%;
            width: 56%;
            text-align: center;
            word-wrap: break-word;
            font-size: {{ $moduleTitleFontSize }}pt;
            font-weight: 500;
            line-height: 1.1;
        }

        .issued-date {
            left: 5.69cm;
            top: 17.63cm;
            width: 6.5cm;
            text-align: left;
            font-size: 11pt;
            font-weight: 400;
            color: #6b7280;
        }

        .certificate-id {
            left: 12.71cm;
            top: 19.70cm;
            width: 4.3cm;
            text-align: center;
            font-size: 11pt;
            font-weight: 400;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="certificate-page">
        <div class="field learner-name">{{ $certificate->learner_name }}</div>
        <div class="field module-name">{{ $certificate->module_title }}</div>
        <div class="field issued-date">Issued {{ $certificate->issued_at->format('F d, Y') }}</div>
        <div class="field certificate-id">{{ $certificate->certificate_number }}</div>
    </div>
</body>
</html>
