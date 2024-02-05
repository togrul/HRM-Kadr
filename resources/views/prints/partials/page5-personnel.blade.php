<div class="flex-col">
    <table>
        <thead>
           <tr>
               <th class="caption-table" colspan="4">11. Pensiya təyin edilərkən xidmət illərinin güzəştli hesablanmasına hüquq verən xidmət dövrləri</th>
           </tr>
            <tr>
                <th>Sənədin adı, №-si və tarixi</th>
                <th  style="width:40px;">əmsal</th>
                <th  style="width:90px;">
                    <div style="padding: 5px;">
                        <p style="margin: 0;">Nə vaxtdan</p>
                        <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
                    </div>
                </th>
                <th style="width:90px;">
                    <div style="padding: 5px;">
                        <p style="margin: 0;">Nə vaxtadək</p>
                        <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
                    </div>
                </th></tr>
        </thead>
        <tbody>
        @for($i = 0;$i < 26;$i++)
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endfor
        </tbody>
    </table>
</div>

<div style="display: flex;justify-content: start">
    <h3 style="font-size: 16px;">12. Xidməti vəzifələrini yerinə yetirərkən yaralanması, kontuziyaları (nə vaxt və harada); onların xüsusiyyətləri </h3>
</div>


<table style="width: 100%;">
    <thead style="display: none;">
        <th></th>
    </thead>
    <tbody>
    @if(count($personnel->injuries) > 0)
        @foreach($personnel->injuries as $injury)
            <tr>
                <td>{{ $injury->injury_type }} , {{ \Carbon\Carbon::parse($injury->date_time)->format('d.m.Y') }} , {{ $injury->location }} , {{ $injury->description }}</td>
            </tr>
        @endforeach
        @for($i = 0;$i < 1;$i++)
            <tr>
                <td></td>
            </tr>
        @endfor
    @else
        @for($i = 0;$i < 3;$i++)
            <tr>
                <td></td>
            </tr>
        @endfor
    @endif
    </tbody>
</table>
