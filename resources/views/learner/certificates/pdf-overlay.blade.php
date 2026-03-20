<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate - {{ $certificate->certificate_number }}</title>
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
            position: relative;
            width: 29.7cm;
            height: 21cm;
            overflow: hidden;
        }

        .certificate-page img.background {
            width: 29.7cm;
            height: 21cm;
            display: block;
        }

        .field {
            position: absolute;
            color: #111827;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .learner-name {
            left: 9.22cm;
            top: 9.37cm;
            width: 11.3cm;
            text-align: center;
            font-size: 40pt;
            font-weight: 700;
            line-height: 1.02;
        }

        .module-name {
            left: 6.97cm;
            top: 13.56cm;
            width: 15.7cm;
            text-align: center;
            font-size: 26pt;
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
