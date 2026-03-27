<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate - {{ $certificate->certificate_number }}</title>
    <style>
        @page {
            margin: 24px;
            size: A4 landscape;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
        }

        .certificate {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 28px 34px;
        }

        .title {
            text-align: center;
            font-size: 28pt;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            font-size: 12pt;
            color: #4b5563;
            margin-bottom: 26px;
        }

        .learner {
            text-align: center;
            font-size: 30pt;
            font-weight: 700;
            margin: 6px 0 16px;
        }

        .module {
            text-align: center;
            font-size: 22pt;
            font-weight: 600;
            margin: 10px 0 24px;
        }

        .body-copy {
            text-align: center;
            font-size: 12pt;
            line-height: 1.55;
            color: #374151;
        }

        .meta {
            margin-top: 28px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            display: table;
            width: 100%;
        }

        .meta-left,
        .meta-right {
            display: table-cell;
            width: 50%;
        }

        .meta-right {
            text-align: right;
        }

        .label {
            font-size: 10pt;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .value {
            font-size: 12pt;
            font-weight: 600;
        }

        .mono {
            font-family: DejaVu Sans Mono, monospace;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="title">Certificate of Completion</div>
        <p class="subtitle">Conscious Connections</p>

        <p class="body-copy">This certifies that</p>
        <div class="learner">{{ $certificate->learner_name }}</div>
        <p class="body-copy">has successfully completed the module</p>
        <div class="module">{{ $certificate->module_title }}</div>

        <div class="meta">
            <div class="meta-left">
                <div class="label">Certificate Number</div>
                <div class="value mono">{{ $certificate->certificate_number }}</div>
            </div>
            <div class="meta-right">
                <div class="label">Issued</div>
                <div class="value">{{ $certificate->issued_at->format('F d, Y') }}</div>
            </div>
        </div>
    </div>
</body>
</html>
