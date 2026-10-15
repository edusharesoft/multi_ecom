@if ($b->isSuspended())
    <span class="badge bg-danger">Suspended</span>
@elseif (! $b->isVerified())
    <span class="badge bg-warning text-dark">OTP pending</span>
@elseif (! $b->isOnboarded())
    <span class="badge bg-info text-dark">Setup pending</span>
@elseif ($b->status !== 'active')
    <span class="badge bg-secondary">Hidden</span>
@else
    <span class="badge bg-success">Live</span>
@endif
