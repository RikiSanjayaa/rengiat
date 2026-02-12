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
        }

        th, td {
            border: 1px solid #374151;
            vertical-align: top;
            padding: 6px;
        }

        th {
            background: #f3f4f6;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.4px;
            text-align: center;
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

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
@foreach($days as $day)
    <section>
        <h1>{{ $title }}</h1>
        <h2>{{ $day['header_line'] }}</h2>

        <table>
            <thead>
            <tr>
                @foreach($day['columns'] as $column)
                    <th>{{ $column['unit_name'] }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            <tr>
                @foreach($day['columns'] as $column)
                    <td>
                        @if(count($column['entries']) === 0)
                            <div class="empty">-</div>
                        @else
                            <ol>
                                @foreach($column['entries'] as $entry)
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
                @endforeach
            </tr>
            </tbody>
        </table>

        <div class="meta">Generated: {{ $generated_at }}</div>
    </section>

    @if(! $loop->last)
        <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>
