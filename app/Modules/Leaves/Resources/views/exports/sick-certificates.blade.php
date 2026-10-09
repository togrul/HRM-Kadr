<table>
    <thead>
    <tr>
        <th>{{ __('leaves::sick_certificates.labels.employee') }}</th>
        <th>{{ __('leaves::sick_certificates.labels.tabel_no') }}</th>
        <th>{{ __('leaves::sick_certificates.labels.series') }}</th>
        <th>{{ __('leaves::sick_certificates.labels.number') }}</th>
        <th>{{ __('leaves::sick_certificates.labels.medical_institution') }}</th>
        <th>{{ __('leaves::sick_certificates.labels.doctor_name') }}</th>
        <th>{{ __('leaves::sick_certificates.labels.starts_at') }}</th>
        <th>{{ __('leaves::sick_certificates.labels.ends_at') }}</th>
        <th>{{ __('leaves::sick_certificates.labels.days') }}</th>
        <th>{{ __('leaves::sick_certificates.labels.status') }}</th>
        <th>{{ __('leaves::sick_certificates.labels.continuation_of') }}</th>
        <th>{{ __('leaves::sick_certificates.labels.notes') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($rows as $certificate)
        <tr>
            <td>{{ $certificate->leave?->personnel?->fullname ?? '' }}</td>
            <td>{{ $certificate->tabel_no }}</td>
            <td>{{ $certificate->series }}</td>
            <td>{{ $certificate->number }}</td>
            <td>{{ $certificate->medical_institution }}</td>
            <td>{{ $certificate->doctor_name }}</td>
            <td>{{ filled($certificate->period_start) ? \Carbon\CarbonImmutable::parse(substr((string) $certificate->period_start, 0, 10))->format('d.m.Y') : '' }}</td>
            <td>{{ filled($certificate->period_end) ? \Carbon\CarbonImmutable::parse(substr((string) $certificate->period_end, 0, 10))->format('d.m.Y') : '' }}</td>
            <td>{{ \App\Modules\Leaves\Application\Services\SickCertificateRegister::days($certificate) }}</td>
            <td>{{ __('leaves::sick_certificates.statuses.'.$certificate->status) }}</td>
            <td>{{ $certificate->continuationOf?->fullNumber() }}</td>
            <td>{{ $certificate->notes }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
