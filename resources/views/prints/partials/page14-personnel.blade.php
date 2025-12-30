<table style="width: 100%;">
    <thead>
        <tr>
            <th class="caption-table" colspan="3">13. Azərbaycan Respublikasının, yaxud xarici dövlətlərin hansı orden və medalları ilə təltif olunub (həmçinin Azərbaycan Milli Qəhrəmanı və s. adlar qeyd olunmalıdır).</th>
        </tr>
        <tr>
            <th style="padding-top: 0;padding-bottom: 0;">Ordenin, medalların adı və hansı fəxri ada <br> layiq gorülüb</th>
            <th style="padding-top: 0;padding-bottom: 0;">Nə üçün təltif olunmuşdur (döyüşdə <br> fərqlənməyə, uzun müddətli xidmətə görə)</th>
            <th style="padding-top: 0;padding-bottom: 0;">Təltif və fəxri adın <br> verilməsi haqqında <br> fərmanın, əmrin kim <br> tərəfindən verilmişdir,<br> əmrin №-si və tarixi.</th>
        </tr>
    </thead>
    <tbody>
    @foreach($personnel->awards as $award)
        <tr>
            <td>{{ $award->award->name }}</td>
            <td>{{ $award->reason }}</td>
            @php
                $orderParts = array_filter([
                    $award->order_given_by ?: null,
                    $award->order_no ? '№'.$award->order_no : null,
                    $award->order_date ? \Carbon\Carbon::parse($award->order_date)->format('d.m.Y') : null,
                ]);
            @endphp
            <td>{{ implode(', ', $orderParts) }}</td>
        </tr>
    @endforeach
    @for($i = 0;$i < (18 - $personnel->awards->count());$i++)
        <tr>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    @endfor
    </tbody>
</table>
