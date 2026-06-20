@php
    $rowActions = $this->rowActions($personnel);
@endphp

<x-personnel.row-actions
    :actions="$rowActions"
    :status="$status"
/> 
