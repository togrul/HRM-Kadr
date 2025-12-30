<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Azərbaycan Respublikası Dövlət Mühafizə Xidməti - Xidmət dəftərçəsi</title>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 5mm; /* Adjust as needed for your layout */
                size: A4; 
                font-family: Arial, sans-serif;
            }
            @page
            {
                margin: 0.3in 0.6in 0.3in 0.6in !important;
            }
            .no-print {
                display: none !important;
            }
        }

        table { page-break-inside:auto }
        tr    { page-break-inside:avoid; page-break-after:auto }
        thead { display:table-header-group; }

        table, th, td {
            border-collapse: collapse;
        }

        table th:not(.caption-table),
        table td
        {
            border: 1px solid black;
        }

        table th.caption-table{
            padding: 10px 0;
            font-size: 16px;
            text-align: left;
        }

        th,td{
            padding: 10px 10px;
        }

        th
        {
            font-size: 12px;
        }

        tr td{
            font-size: 14px;
            padding: 0 5px;
            height: 25px;
        }

        .flex-center{
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .flex-col{
            display: flex;
            flex-direction: column;
        }

        .flex-between{
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-v-2 th{
            width: 30%;
        }

        .table-v-2 td
        {
            padding: 0 5px;
        }

        .table-v-2 tr td {
            text-align: justify;
        }

        .table-v-2 tr th{
            text-align: justify;
            font-size: 14px;
        }

        .table-v-2 tr h2
        {
            text-align: justify;
            font-size: 16px;
            margin: 0;
        }

        .seperated-column
        {
            height: 50%;
            justify-content: center;
            align-items: start;
            padding: 0 5px;
        }

        .seperated-column span{
            text-align: justify;
        }

        .print-action{
            position: fixed;
            top: 16px;
            left: 16px;
            padding: 8px 12px;
            font-size: 14px;
            background: #2563eb;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<a class="no-print print-action" href="{{ route('print.personnel.word', $personnel->id) }}">
   {{ __('Export to Word') }}
</a>

<div class="content">
    @include('prints.partials.page1-personnel')

    {{--START PAGE 2--}}
    @include('prints.partials.page2-personnel')
    {{--END PAGE 2--}}

    {{--START PAGE 3--}}
    @include('prints.partials.page3-personnel')
    {{--END PAGE 3--}}

    {{--START PAGE 4--}}
    @include('prints.partials.page4-personnel')
    {{--END PAGE 4--}}

    {{--START PAGE 13--}}
    @include('prints.partials.page5-personnel')
    {{--END PAGE 13--}}

    {{--START PAGE 14--}}
    @include('prints.partials.page14-personnel')
    {{--END PAGE 14--}}

    {{--START PAGE 15--}}
    @include('prints.partials.page15-personnel')
    {{--END PAGE 15--}}

    {{--START PAGE 16--}}
    @include('prints.partials.page16-personnel')
    {{--END PAGE 16--}}
</div>

</body>
</html>
