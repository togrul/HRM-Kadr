<!DOCTYPE html>
<html lang="az">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Azərbaycan Respublikası Dövlət Mühafizə Xidməti</title>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 5mm;
                size: A4;
                font-family: Arial, sans-serif;
            }

            @page {
                margin: 0.1in 0.3in 0.3in 0.7in !important;
            }

            .export-word-btn{
              display: none;
            }
        }

        body {
            margin: 0;
            padding: 12px;
            font-family: Arial, sans-serif;
            color: #000;
        }

        .container {
            width: 100%;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .title {
            font-size: 16pt;
            text-align: center;
            margin: 0;
            font-weight: bold;
            margin-top: 10px;
        }

        .subtitle {
            font-size: 14pt;
            text-align: center;
            text-decoration: underline;
            margin-top: 20px;
            max-width: 320px;
            display: inline-block;
        }

        .image-section {
            border: 1px solid #000;
            width: 3.5cm;
            height: 4.5cm;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .placeholder {
            padding: 0 15px;
            text-align: center;
            font-size: 12pt;
        }

        .info-row {
            display: flex;
            width: 100%;
            font-size: 12pt;
            margin-top: 2px;
        }

        .info-label {
            width: 30%;
            margin: 0 20px 0 0;
            font-weight: 600;
        }

        .info-label-md {
            width: 40%;
            margin: 0 20px 0 0;
            font-weight: 600;
        }

        .info-value {
            width: 70%;
            font-style: italic;
            margin: 0;
        }

        .history-table {
            width: 100%;
            margin-top: 5px;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside:auto;
        }

        .history-table tr {
          page-break-inside:avoid;
        }

        .history-table th,
        .history-table td {
            border: 1px solid #000;
            padding: 0px 5px;
            word-break: break-word;
            vertical-align: center;
        }

        .history-table tbody td {
           font-size: 12pt !important;
        }

        .history-head {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .history-head .title-header {
            border-bottom: 1px solid #000;
            font-size: 10pt;
        }

        .history-head .columns {
            display: flex;
            width: 100%;
            height: 100%;
        }

        .history-head .columns > div {
            width: 50%;
            padding: 3px 5px;
        }

        .history-head .columns > div:first-child {
            border-right: 1px solid #000;
        }

        table {
            page-break-inside: auto !important;
        }

        tr {
            page-break-inside: avoid !important;
            page-break-after: auto !important;
        }

        thead {
            display: table-header-group;
        }

        table,
        th,
        td {
            border-collapse: collapse;
        }

        table th:not(.caption-table),
        table td {
            border: 1px solid black;
        }

        table th.caption-table {
            padding: 10px 0;
            font-size: 16px;
            text-align: left;
        }

        th,
        td {
            padding: 10px 10px;
        }

        th {
            font-size: 12px;
        }

        tr td {
            font-size: 14px;
            padding: 0 5px;
            height: 25px;
        }

        .table-v-2 th {
            width: 30%;
        }

        .table-v-2 td {
            padding: 0 5px;
        }

        .table-v-2 tr td {
            text-align: justify;
        }

        .table-v-2 tr th {
            text-align: justify;
            font-size: 14px;
        }

        .table-v-2 tr h2 {
            text-align: justify;
            font-size: 16px;
            margin: 0;
        }

        .seperated-column {
            height: 50%;
            justify-content: center;
            align-items: start;
            padding: 0 5px;
        }

        .seperated-column span {
            text-align: justify;
        }

        .flex-col-center {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .export-word-btn{
          position: absolute;
          top: 20px;
          left: 20px;
          padding: 10px 15px;
          background-color: #2196F3; 
          border-radius: 10px;
          appearance: none;
          border: none;
          color: white;
          font-size: 14px;
          cursor: pointer;
          transition: background-color 0.3s ease;
        }
        .export-word-btn:hover{
          background-color: #0b7dda;
        }
    </style>
</head>

<body>
    @php
        $hideWatermark = $hideWatermark ?? false;
        $watermarkUrl = $cvData['watermark_url'] ?? asset('assets/images/gerb.png');
    @endphp
    @if(! $hideWatermark)
        <img src="{{ $watermarkUrl }}" alt="gerb" style="width: 100%;position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.1; z-index: 0;filter:grayscale(100);">
    @endif
    @if(empty($exportWord))
        <a class="export-word-btn" href="{{ route('print.cv.word', $cvData['id'] ?? null) }}">{{ __('Export to Word') }}</a>
    @endif
    <div class="container">
        <div class="header">
            <div></div>
            <div class="flex-col-center">
                {{-- <img src="{{ asset('assets/images/gerb.png') }}" alt="gerb" style="width: 64px;"> --}}
                <h1 class="title">
                    {{ $cvData['rank'] ?? '' }}
                    <br />
                    {{ $cvData['fullname'] ?? '' }}
                </h1>
                <p class="subtitle">
                    {{ $cvData['structure_label'] ?? '' }}
                    {{ \Illuminate\Support\Str::lower($cvData['position_label'] ?? '') }} 
                    {{ $cvData['hasActiveDisposal'] ?? '' }}
                </p>
            </div>
            <div class="image-section">
                @if ($cvData['photo_url'])
                    <img src="{{ $cvData['photo_url'] }}" alt="Photo" style="width: 100%; height: 100%;object-fit: cover;">
                @else
                    <div class="placeholder">
                        <p>Fotoşəkil üçün yer</p>
                        <p>(3.5 x 4.5 sm)</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="info-row" style="margin-top: 30px;">
            <div class="info-label">
                Doğulduğu gün, ay, il və yer:
            </div>
            <div class="info-value">
                {{ $cvData['birth']['day'] ?? '' }}
                {{ \Illuminate\Support\Str::lower($cvData['birth']['month'] ?? '') }}
                {{ $cvData['birth']['year'] ?? '' ? $cvData['birth']['year'] . ' ' . __('year') : '' }},
                {{ $cvData['birth']['city'] ?? '' }} şəhəri
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">
                Təhsili:
            </div>
            <div class="info-value">
                @if (!empty($cvData['education']['institution']))
                    {{ \Illuminate\Support\Str::ucfirst($cvData['education']['degree']) }},
                    {{ $cvData['education']['graduation_year'] ?? '' }} ildə
                    {{ $cvData['education']['institution'] ?? '' }} bitirib.
                @endif
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">
                Mükafatlandırılıb:
            </div>
            <div class="info-value">
                {{ $cvData['awards_count'] ?? '' }}
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">
                İntizam cəzaları:
            </div>
            <div class="info-value">
                {{ $cvData['punishments_count'] ?? '' }}
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">
                Ailə vəziyyəti:
            </div>
            <div class="info-value">
                {{ $cvData['family_status'] ?? '' }}
            </div>
        </div>

        <div class="info-row">
            <div class="info-label">
                Ünvan:
            </div>
            <div class="info-value">
                @if($cvData['similarity_percentage'] > 90)
                  {{ $cvData['residental_address'] ?? '' }}
                @else
                  Qeyd: {{ $cvData['registered_address'] ?? '' }} <br>
                  Yaş: {{ $cvData['residental_address'] ?? '' }}
                @endif
            </div>
        </div>

        <div class="info-row" style="margin-top:20px;">
            <div class="info-label-md">
                Fəaliyyətləri barədə məlumat:
            </div>
        </div>

        <div class="info-row" style="display:block;">
            <table class="history-table">
                <thead>
                    <tr>
                        <th colspan="2" style="width:30%; padding:0;">
                            <div class="history-head">
                                <div class="title-header flex-center">
                                    Tarix (gün, ay və il)
                                </div>
                                <div class="columns">
                                    <div>
                                        <p style="margin: 0;">daxil olduğu</p>
                                    </div>
                                    <div>
                                        <p style="margin: 0;">çıxdığı</p>
                                    </div>
                                </div>
                            </div>
                        </th>

                        <th style="width: 70%;">
                            <p style="margin: 0;">İdarə, təşkilat, müəssisə, nazirlik, <br> vəzifə</p>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cvData['service_history']['military'] as $military)
                        <tr style="font-style: italic;">
                            <td style="text-align:center;">{{ $military['start_date'] }}</td>
                            <td style="text-align:center;">{{ $military['end_date'] ?? 'hal/hazıra kimi' }}</td>
                            <td>{{ $military['location'] }};</td>
                        </tr>
                    @endforeach
                    @foreach($cvData['service_history']['labor'] as $labor)
                        <tr style="font-style: italic;font-size:12pt;">
                            <td style="text-align:center;">{{ $labor['join_date'] }}</td>
                            <td style="text-align:center;">{{ $labor['leave_date'] ?? 'hal/hazıra kimi' }}</td>
                            <td>{{ $labor['structure'] }};</td>
                        </tr>
                    @endforeach
                    @for ($i = 0; $i < (22 - $cvData['totalCountLabor']); $i++)
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
