<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ strtoupper($document->type) }} - {{ $document->title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 16pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            color: #0f172a;
        }

        .header h2 {
            font-size: 12pt;
            font-weight: bold;
            margin: 4px 0 0 0;
            color: #475569;
        }

        .header p {
            font-size: 9pt;
            color: #64748b;
            margin: 2px 0 0 0;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .meta-table td {
            padding: 6px 12px;
            font-size: 10pt;
            vertical-align: top;
        }

        .meta-label {
            font-weight: bold;
            color: #334155;
            width: 25%;
        }

        .section-title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 20px;
            margin-bottom: 8px;
        }

        .content-box {
            margin-bottom: 15px;
            text-align: justify;
            white-space: pre-line;
        }

        .budget-box {
            background-color: #f1f5f9;
            border: 1px dashed #cbd5e1;
            padding: 10px;
            font-family: monospace;
            font-size: 9.5pt;
            margin-bottom: 15px;
            white-space: pre-line;
        }

        .signature-table {
            width: 100%;
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-table td {
            text-align: center;
            vertical-align: top;
            width: 50%;
            font-size: 10pt;
        }

        .signature-space {
            height: 60px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 4px;
            background-color: #dcfce7;
            color: #166534;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8pt;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>DOKUMEN {{ strtoupper($document->type) }} KEGIATAN</h1>
        <h2>{{ $document->event->organization->name }}</h2>
        <p>Campus Organization Management System — Universitas</p>
    </div>

    <!-- Metadata -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Judul Dokumen:</td>
            <td>{{ $document->title }}</td>
        </tr>
        <tr>
            <td class="meta-label">Nama Kegiatan:</td>
            <td>{{ $document->event->title }}</td>
        </tr>
        <tr>
            <td class="meta-label">Tanggal Kegiatan:</td>
            <td>{{ $document->event->start_time ? $document->event->start_time->format('d F Y (H:i)') : '-' }} - {{ $document->event->end_time ? $document->event->end_time->format('d F Y (H:i)') : 'Selesai' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Lokasi:</td>
            <td>{{ $document->event->location }}</td>
        </tr>
        <tr>
            <td class="meta-label">Status Dokumen:</td>
            <td>
                <span class="status-badge">{{ strtoupper($document->status) }}</span>
            </td>
        </tr>
    </table>

    <!-- Content Sections -->
    <div class="section-title">1. LATAR BELAKANG / PENDAHULUAN</div>
    <div class="content-box">{{ $document->background }}</div>

    <div class="section-title">2. MAKSUD & TUJUAN KEGIATAN</div>
    <div class="content-box">{{ $document->objectives }}</div>

    @if ($document->budget_plan)
        <div class="section-title">3. RENCANA / REALISASI ANGGARAN BIAYA</div>
        <div class="budget-box">{{ $document->budget_plan }}</div>
    @endif

    @if ($document->summary_content)
        <div class="section-title">4. RINGKASAN PELAKSANAAN / CATATAN PENUTUP</div>
        <div class="content-box">{{ $document->summary_content }}</div>
    @endif

    <!-- Signatures -->
    <table class="signature-table">
        <tr>
            <td>
                Disusun Oleh,<br>
                <strong>Pengurus Organisasi</strong>
                <div class="signature-space"></div>
                <strong><u>{{ $document->submitter->name }}</u></strong><br>
                <span>NIM / ID: {{ $document->submitter->email }}</span>
            </td>
            <td>
                Disetujui Oleh,<br>
                <strong>Administrator Kampus</strong>
                <div class="signature-space"></div>
                <strong><u>{{ $document->approver ? $document->approver->name : '( Menunggu Persetujuan )' }}</u></strong><br>
                <span>{{ $document->approved_by ? 'Telah Disetujui Secara Resmi' : 'Status: ' . $document->status }}</span>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dokumen ini dicetak secara otomatis dari Campus Organization Management System pada {{ date('d F Y H:i') }}
    </div>
</body>
</html>
