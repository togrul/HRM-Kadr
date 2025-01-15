<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Azərbaycan Respublikası Prezidentinin Təhlükəsizlik Xidməti - Xidmət dəftərçəsi</title>
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
    </style>
</head>
<body>

<div class="content">
    <?php echo $__env->make('prints.partials.page1-personnel', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    
    <?php echo $__env->make('prints.partials.page2-personnel', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    

    
    <?php echo $__env->make('prints.partials.page3-personnel', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    

    
    <?php echo $__env->make('prints.partials.page4-personnel', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    

    
    <?php echo $__env->make('prints.partials.page5-personnel', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    

    
    <?php echo $__env->make('prints.partials.page14-personnel', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    

    
    <?php echo $__env->make('prints.partials.page15-personnel', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    

    
    <?php echo $__env->make('prints.partials.page16-personnel', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    
</div>

</body>
</html>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HR-CRM/resources/views/prints/personnel.blade.php ENDPATH**/ ?>