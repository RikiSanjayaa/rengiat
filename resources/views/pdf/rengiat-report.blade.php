<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rengiat Report</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 14mm 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            color: #111827;
            font-size: 11px;
            margin: 0;
        }

        h1 {
            font-size: 14px;
            margin: 0 0 12px;
            text-align: center;
            letter-spacing: 0.4px;
        }

        h2 {
            font-size: 12px;
            margin: 8px 0;
            text-align: left;
            border-top: 1px solid #374151;
            border-bottom: 1px solid #374151;
            padding: 6px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
        }

        th, td {
            border: 1px solid #374151;
            vertical-align: top;
            padding: 6px;
            word-break: break-word;
        }

        th {
            background: #f3f4f6;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.4px;
            text-align: center;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        ol {
            margin: 0;
            padding-left: 16px;
        }

        li {
            margin-bottom: 5px;
            line-height: 1.4;
        }

        .empty {
            text-align: center;
            color: #6b7280;
            font-weight: 600;
        }

        .meta {
            margin-top: 10px;
            font-size: 9px;
            color: #6b7280;
            text-align: right;
        }

        .day-section {
            margin-bottom: 10px;
            page-break-inside: auto;
        }

        .day-section + .day-section {
            page-break-before: always;
        }
    </style>
</head>
<body>
<h1>{{ $title }}</h1>

@if(count($days) === 0)
    <section>
        <h2>TIDAK ADA DATA KEGIATAN PADA RENTANG TANGGAL TERPILIH</h2>
    </section>
@else
    @foreach($days as $day)
        <section class="day-section">
            <h2>{{ $day['header_line'] }}</h2>

            <table>
                <thead>
                <tr>
                    <th style="width: 20%">Subdit</th>
                    <th style="width: 20%">Unit</th>
                    <th>Kegiatan</th>
                </tr>
                </thead>
                <tbody>
                @foreach($day['rows'] as $row)
                    <tr>
                        <td><strong>{{ $row['subdit_name'] }}</strong></td>
                        <td><strong>{{ $row['unit_name'] }}</strong></td>
                        <td>
                            @if(count($row['entries']) === 0)
                                <div class="empty">-</div>
                            @else
                                <ol>
                                    @foreach($row['entries'] as $entry)
                                        <li>
                                            @if($entry['time_start'])
                                                [{{ $entry['time_start'] }}]
                                            @endif
                                            {{ $entry['description'] }}
                                            @if($entry['has_attachment'])
                                                [LAMPIRAN]
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>
    @endforeach
@endif

<div class="meta">Generated: {{ $generated_at }}</div>
</body>
</html>
