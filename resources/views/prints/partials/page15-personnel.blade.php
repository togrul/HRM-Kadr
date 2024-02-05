<table style="width: 100%;">
    <thead>
        <tr>
            <th class="caption-table" colspan="4">14. Xarici ezamiyyətlər</th>
        </tr>
       <tr>
           <th>Harada, nə məqsədlə olub</th>
           <th style="width: 80px;">Kimin əmri ilə <br> <span style="font-weight: 400;">(əmrin №-si və tarixi)</span></th>
           <th style="width: 90px;">
               <div style="padding: 5px;">
                   <p style="margin: 0;">Nə vaxtdan</p>
                   <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
               </div>
           </th>
           <th style="width: 90px;">
               <div style="padding: 5px;">
                   <p style="margin: 0;">Nə vaxtadək</p>
                   <span style="font-weight: 400;font-size:11px;">(gün,ay,il)</span>
               </div>
           </th>
       </tr>
    </thead>
    <tbody>
    @for($i = 0;$i < 21;$i++)
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    @endfor
    </tbody>
</table>

<div style="display: flex;flex-direction:column;justify-content: start">
    <h3 style="margin-bottom: 5px;font-size: 16px;">15. Hansı seçki orqanlarına seçilmişdir <span style="font-weight: 400;font-size: 16px;">(harada və nə vaxt)</span></h3>
    @if(count($personnel->elections) > 0)
        @foreach($personnel->elections as $election)
            <div style="border-bottom: 1px solid #000; height: {{$i == 0 ? 10 : 25}}px;">
                {{ $election->election_type }} - {{ $election->location }} - {{ \Carbon\Carbon::parse($election->elected_date)->format('d.m.Y') }}
            </div>
        @endforeach
        @for($i = 0;$i < 1;$i++)
            <div style="border-bottom: 1px solid #000; height: 25px;"></div>
        @endfor
    @else
        @for($i = 0;$i < 3;$i++)
            <div style="border-bottom: 1px solid #000; height: {{$i == 0 ? 10 : 25}}px;"></div>
        @endfor
    @endif
</div>

<div style="display: flex;flex-direction:column;justify-content: start">
    <h3 style="margin-bottom: 5px;font-size: 16px;">16. Əsirlikdə olubmu <span style="font-weight: 400;font-size: 16px;">(hansı şəraitdə, harada, nə vaxt əsir düşüb və azad olunub)</span></h3>
    @if(count($personnel->captives) > 0)
        @foreach($personnel->captives as $captive)
            <div style="border-bottom: 1px solid #000; height: {{$i == 0 ? 10 : 25}}px;">
                {{ \Carbon\Carbon::parse($captive->taken_captive_date)->format('d.m.Y') }} tarixində {{ $captive->location }} ərazisində
                {{ $captive->condition }} əsirlikdə olub.
                @if(!empty($captive->release_date))
                    {{ \Carbon\Carbon::parse($captive->release_date)->format('d.m.Y') }} tarixində əsirlikdən azad olub.
                @endif
            </div>
        @endforeach
        @for($i = 0;$i < 1;$i++)
            <div style="border-bottom: 1px solid #000; height: 25px;"></div>
        @endfor
    @else
        @for($i = 0;$i < 6;$i++)
            <div style="border-bottom: 1px solid #000; height: {{$i == 0 ? 10 : 25}}px;">
            </div>
        @endfor
    @endif
</div>
