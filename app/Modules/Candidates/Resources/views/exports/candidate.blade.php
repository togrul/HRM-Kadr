<table>
    @php
        $isMilitaryMode = ($candidateMode ?? 'military') === 'military';
    @endphp
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('candidates::common.labels.fullname') }}</th>
            <th>{{ __('candidates::common.labels.structure') }}</th>
            <th>{{ __('candidates::common.labels.height') }}</th>
            @if ($isMilitaryMode)
                <th>{{ __('candidates::common.labels.military_service') }}</th>
            @endif
            <th>{{ __('candidates::common.labels.phone') }}</th>
            <th>{{ __('candidates::common.labels.knowledge_test') }}</th>
            @if ($isMilitaryMode)
                <th>{{ __('candidates::common.labels.physical_fitness_exam') }}</th>
            @endif
            <th>{{ __('candidates::common.labels.research_result') }}</th>
            <th>{{ __('candidates::common.labels.research_date') }}</th>
            <th>{{ __('candidates::common.labels.discrediting_information') }}</th>
            <th>{{ __('candidates::common.labels.examination_date') }}</th>
            <th>{{ __('candidates::common.labels.appeal_date') }}</th>
            <th>{{ __('candidates::common.labels.application_date') }}</th>
            <th>{{ __('candidates::common.labels.requisition_date') }}</th>
            <th>{{ __('candidates::common.labels.initial_documents') }}</th>
            <th>{{ __('candidates::common.labels.documents_completeness') }}</th>
            @if ($isMilitaryMode)
                <th>{{ __('candidates::common.labels.attitude_to_military') }}</th>
            @endif
            <th>{{ __('candidates::common.labels.characteristics') }}</th>
            @if ($isMilitaryMode)
                <th>{{ __('candidates::common.labels.hhk_date') }}</th>
                <th>{{ __('candidates::common.labels.hhk_result') }}</th>
                <th>{{ __('candidates::common.labels.useless_info') }}</th>
            @endif
            <th>{{ __('candidates::common.labels.note') }}</th>
            <th>{{ __('candidates::common.labels.presented_by') }}</th>
            <th>{{ __('candidates::common.labels.created_date') }}</th>
            <th>{{ __('candidates::common.labels.status') }}</th>
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
