<table>
    @php
        $isMilitaryMode = ($candidateMode ?? 'military') === 'military';
    @endphp
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('Fullname') }}</th>
            <th>{{ __('Structure') }}</th>
            <th>{{ __('Height') }}</th>
            @if ($isMilitaryMode)
                <th>{{ __('Military service') }}</th>
            @endif
            <th>{{ __('Phone') }}</th>
            <th>{{ __('Knowledge test') }}</th>
            @if ($isMilitaryMode)
                <th>{{ __('Physical fitness exam') }}</th>
            @endif
            <th>{{ __('Research result') }}</th>
            <th>{{ __('Research date') }}</th>
            <th>{{ __('Discrediting information') }}</th>
            <th>{{ __('Examination date') }}</th>
            <th>{{ __('Appeal date') }}</th>
            <th>{{ __('Application date') }}</th>
            <th>{{ __('Requisition date') }}</th>
            <th>{{ __('Initial documents') }}</th>
            <th>{{ __('Documents completeness') }}</th>
            @if ($isMilitaryMode)
                <th>{{ __('Attitude to military') }}</th>
            @endif
            <th>{{ __('Characteristics') }}</th>
            @if ($isMilitaryMode)
                <th>{{ __('HHK date') }}</th>
                <th>{{ __('HHK result') }}</th>
                <th>{{ __('Useless info') }}</th>
            @endif
            <th>{{ __('Note') }}</th>
            <th>{{ __('Presented by') }}</th>
            <th>{{ __('Created date') }}</th>
            <th>{{ __('Status') }}</th>
        </tr>
    </thead>
    <tbody>
    @foreach( $report as $r )
        <tr>
            <th>{{ $loop->iteration }}</th>
            <th>{{ $r['name'] }} {{ $r['surname'] }} {{ $r['patronymic'] }} </th>
            <th>{{ $r['structure']['name'] ?? '' }} </th>
            <th>{{ $r['height'] ?? '' }} </th>
            @if ($isMilitaryMode)
                <th>{{ $r['military_service'] ?? '' }} </th>
            @endif
            <th>{{ $r['phone'] ?? '' }} </th>
            <th>{{ $r['knowledge_test'] ?? '' }} </th>
            @if ($isMilitaryMode)
                <th>{{ $r['physical_fitness_exam'] ?? '' }} </th>
            @endif
            <th>{{ $r['research_result'] ?? '' }} </th>
            <th>{{ !empty($r['research_date']) ? \Carbon\Carbon::parse($r['research_date'])->format('d.m.Y') : '' }} </th>
            <th>{{ $r['discrediting_information'] ?? '' }} </th>
            <th>{{ !empty($r['examination_date']) ? \Carbon\Carbon::parse($r['examination_date'])->format('d.m.Y') : '' }} </th>
            <th>{{ !empty($r['appeal_date']) ? \Carbon\Carbon::parse($r['appeal_date'])->format('d.m.Y') : '' }} </th>
            <th>{{ !empty($r['application_date']) ? \Carbon\Carbon::parse($r['application_date'])->format('d.m.Y') : '' }} </th>
            <th>{{ !empty($r['requisition_date']) ? \Carbon\Carbon::parse($r['requisition_date'])->format('d.m.Y') : '' }} </th>
            <th>{{ $r['initial_documents'] ?? '' }} </th>
            <th>{{ $r['documents_completeness'] ?? '' }} </th>
            @if ($isMilitaryMode)
                <th>{{ $r['attitude_to_military'] ?? '' }} </th>
            @endif
            <th>{{ $r['characteristics'] ?? '' }} </th>
            @if ($isMilitaryMode)
                <th>{{ !empty($r['hhk_date']) ? \Carbon\Carbon::parse($r['hhk_date'])->format('d.m.Y') : '' }} </th>
                <th>{{ $r['hhk_result'] ?? '' }} </th>
                <th>{{ $r['useless_info'] ?? '' }} </th>
            @endif
            <th>{{ $r['note'] ?? '' }} </th>
            <th>{{ $r['presented_by'] ?? '' }} </th>
            <th>{{ !empty($r['created_at']) ? \Carbon\Carbon::parse($r['created_at'])->format('d.m.Y H:i') : '' }} </th>
            <th>{{ $r['status']['name'] ?? '' }} </th>
        </tr>
    @endforeach
    </tbody>
</table>
