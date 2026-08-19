{{--
    The six heads of the requirements shown as six steps, so an applicant
    always knows where they are and how much is left.
--}}
<ol class="wizard-steps" aria-label="Application steps">
    @foreach ($steps as $n => $step)
        @php
            $state = $n < $currentStep ? 'is-done' : ($n === $currentStep ? 'is-current' : '');
        @endphp
        <li class="wizard-step {{ $state }}"
            @if ($n === $currentStep) aria-current="step" @endif>
            <span class="n">{{ $n < $currentStep ? '✓' : $n }}</span>
            <span class="t">{{ $step['title'] }}</span>
            <span class="h">{{ $step['head'] }}</span>
        </li>
    @endforeach
</ol>
