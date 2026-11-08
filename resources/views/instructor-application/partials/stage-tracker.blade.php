@php
    $stageOrder = ['identity', 'review', 'application', 'final'];
    $stageLabels = [
        'identity' => 'Identity information',
        'review' => 'Identity review',
        'application' => 'Instructor application',
        'final' => 'Final decision',
    ];
    $currentStage = array_search($stage, $stageOrder, true);
    $currentStage = $currentStage === false ? 0 : $currentStage;
    $steps = [];

    foreach ($stageLabels as $key => $label) {
        $step = array_search($key, $stageOrder, true);
        $steps[] = [
            'label' => $label,
            'done' => $step < $currentStage,
            'active' => $step === $currentStage,
        ];
    }
@endphp

<x-wizard-stepper :steps="$steps" />
