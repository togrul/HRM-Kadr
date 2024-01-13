<table class="table-v-2" style="width: 100%;margin-top: 10px;">
    <tr style="height: 60px;">
        <th rowspan="1">1. Anadan olduğu gün, ay və il</th>
        <td>{{ \Carbon\Carbon::parse($personnel->birthdate)->format('d.m.Y') }}</td>
    </tr>

    <tr style="height: 90px;">
        <th>2. Anadan olduğu yer <br/>
            <span style="font-weight: 400;font-size:11px;">(doldurulduğu günə qədər inzibati bölgü üzrə)</span>
        </th>
        <td>
            {{ $personnel->idDocuments?->bornCountry?->title }},{{ $personnel->idDocuments?->bornCity?->name }}
        </td>
    </tr>
    <tr>
        <th>3. Milliyəti</th>
        <td>{{ $personnel->nationality?->title }}</td>
    </tr>
    <tr>
        <th>4. Sosial mənşəyi</th>
        <td>{{ $personnel->socialOrigin?->name }}</td>
    </tr>
    <tr>
        <th style="padding: 0;">
            <div style="display: grid;grid-template-columns: repeat(5, minmax(0, 1fr));align-items: center">
                <h2 style="margin-left: 10px;grid-column: span 2 / span 2;">5. Təhsili</h2>
                <div class="flex-col" style="height:580px;grid-column: span 3 / span 3; border-left: 1px solid #000;">
                    <div class="flex-col seperated-column" style="border-bottom: 1px solid #000;">
                        <h2>a) Mülki</h2>
                        <span style="font-weight: 400;font-size:11px;">(nə vaxt və hansı təhsil müəssisələrini bitirib; ixtisası)</span>
                    </div>
                    <div class="flex-col seperated-column">
                        <h2>b) Hərbi <br/> (xüsusi)</h2>
                        <span style="font-weight: 400;font-size:11px;">(nə vaxt və hansı təhsil müəssisələrini və kursları bitirib; ixtisası)</span>
                    </div>
                </div>
            </div>
        </th>
        <td style="padding: 0">
            <div class="flex-col" style="height: 100%;">
                <div style="height: 50%;border-bottom: 1px solid #000;">
                    @if(!empty($personnel['education']))
                        @if(!$personnel['education']['is_military'])
                            <x-education-list
                                :name="$personnel->education->institution->name"
                                :specialty="$personnel->education->specialty"
                                :admission_year="$personnel->education->admission_year"
                                :graduated_year="$personnel->education->graduated_year"
                            >
                            </x-education-list>

                        @endif
                    @endif
                    @if(count($personnel['extraEducations']) > 0)
                        @foreach($personnel['extraEducations'] as $extraEdu)
                                @if(!$extraEdu['is_military'])
                                    <x-education-list
                                        :name="$extraEdu->institution->name"
                                        :specialty="$extraEdu->education_program_name"
                                        :admission_year="$extraEdu->admission_year"
                                        :graduated_year="$extraEdu->graduated_year"
                                    >
                                    </x-education-list>
                                @endif
                        @endforeach
                    @endif
                </div>

                <div style="height: 50%;">
                    @if(!empty($personnel->education))
                        @if($personnel->education->is_military)
                            <x-education-list
                                :name="$personnel->education->institution->name"
                                :specialty="$personnel->education->specialty"
                                :admission_year="$personnel->education->admission_year"
                                :graduated_year="$personnel->education->graduated_year"
                            >
                            </x-education-list>

                        @endif
                    @endif
                        @if(count($personnel->extraEducations) > 0)
                            @foreach($personnel->extraEducations as $extraEdu)
                                @if($extraEdu->is_military)
                                    <x-education-list
                                        :name="$extraEdu->institution->name"
                                        :specialty="$extraEdu->education_program_name"
                                        :admission_year="$extraEdu->admission_year"
                                        :graduated_year="$extraEdu->graduated_year"
                                    >
                                    </x-education-list>
                                @endif
                            @endforeach
                        @endif
                </div>
            </div>
        </td>
    </tr>

    <tr style="height: 90px;">
        <th>6. Hansı xarici dilləri bilir</th>
        <td>
                @foreach($personnel->foreignLanguages as $lang)
                    {{ $lang->language->name }} - {{ $lang->knowledge_status }} @if(!$loop->last) , @endif
                @endforeach
        </td>
    </tr>

    <tr style="height: 90px;">
        <th>7. Elmi dərəcələri, elmi adı və verildiyi tarix </th>
        <td>
            @if(count($personnel->degreeAndNames) > 0)
                @foreach($personnel->degreeAndNames as $degree)
                    <div style="padding: 3px;">
                        <span>{{ $degree->degreeAndName->name }}</span>,
                        <span>{{ $degree->science }}</span> -
                        <span>{{ \Carbon\Carbon::parse($degree->given_date)->format('d.m.Y') }}</span>
                    </div>
                @endforeach
            @endif
        </td>
    </tr>
    <tr style="height: 60px;">
        <th>8. Hansı elmi əsərləri və ixtiraları var</th>
        <td>{{ $personnel['scientific_works_inventions'] }}</td>
    </tr>
</table>
