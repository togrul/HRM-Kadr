<div class="flex-col">
    <table>
        <thead>
            <tr>
                <th class="caption-table" colspan="4">9. Əmək fəaliyyəti</th>
            </tr>
            <tr>
                <th style="padding: 0;" colspan="2">
                    <div class="flex-col">
                        <div class="flex-center" style="border-bottom: 1px solid #000;padding: 3px 0;">
                            Tarix
                        </div>
                        <div style="display: flex;width: 100%;">
                            <div style="border-right: 1px solid #000; padding: 3px 5px;width: 50%;">
                                <p style="margin: 0;">Nə vaxtdan</p>
                                <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
                            </div>
                            <div style="padding: 5px;width: 50%;">
                                <p style="margin: 0;">Nə vaxtadək</p>
                                <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
                            </div>
                        </div>
                    </div>
                </th>

                <th>
                    <p style="margin: 0;">İş yeri <span style="font-weight: 400;">(müəssisənin,təşkilatın və s. adı)</span> <br> və harada yerləşir <span style="font-weight: 400;">(şəhər, rayon ,kənd)</span></p>
                </th>
                <th>Vəzifəsi</th>
            </tr>

        </thead>
        <tbody>
         @forelse($personnel->laborActivities as $labor)
             <tr>
                 <td>{{ \Carbon\Carbon::parse($labor->join_date)->format('d.m.Y') }}</td>
                 <td>{{ \Carbon\Carbon::parse($labor->leave_date)->format('d.m.Y') }}</td>
                 <td>{{ $labor->company_name }}</td>
                 <td>{{ $labor->position }}</td>
             </tr>
         @empty
             <tr>
                 <td></td>
                 <td></td>
                 <td>Əmək fəaliyyəti yoxdur</td>
                 <td></td>
             </tr>
         @endforelse
         @for($i = 0;$i < 2;$i++)
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
