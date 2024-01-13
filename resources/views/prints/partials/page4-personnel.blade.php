<div style="display: flex;justify-content: flex-start;padding: 5px 0;">
    <h3 style="margin: 4px 0;font-size: 16px;">10. Silahlı Qüvvələrdə və hüquq-mühafizə orqanlarında xidməti</h3>
</div>

<div class="flex-col">
    <table>
        <thead>
        <th style="width:80px;padding-top: 0;padding-bottom: 0;" >
            <div style="">
                <p style="margin: 0;">Nə <br/> vaxtdan</p>
                <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
            </div>
        </th>
        <th style="width:80px;padding-top: 0;padding-bottom: 0;">
            <div style="">
                <p style="margin: 0;">Nə <br/> vaxtadək</p>
                <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
            </div>
        </th>
        <th style="padding-top: 0;padding-bottom: 0;">Vəzifəsi</th>
        <th>
            Orqanın, hissənin, dəstənin, <br> təhsil müəssisəsinin adı
        </th>
        <th style="padding-top: 0;padding-bottom: 0;">əmr kim <br> tərəfindən <br> verilib, <br> əmrin №-si və <br> tarixi</th>
        </thead>
        <tbody>
        @foreach($personnel->specialServices as $special)
            <tr>
                <td>{{ \Carbon\Carbon::parse($special->join_date)->format('d.m.Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($special->leave_date)->format('d.m.Y') }}</td>
                <td>{{ $special->position }}</td>
                <td>{{ $special->company_name }}</td>
                <td>{{ $special->order_given_by }}, {{ $special->order_no }}, {{ \Carbon\Carbon::parse($special->order_date)->format('d.m.Y') }}</td>
            </tr>
        @endforeach
        @for($i = 0;$i < 2;$i++)
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endfor
        </tbody>
    </table>
</div>
