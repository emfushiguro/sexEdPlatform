<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

class WizardStepper extends Component
{
    public ?array $steps;

    private const LEARNER_FLOW = [
        ['label' => 'Create Account',   'route' => 'register'],
        ['label' => 'Verify Email',      'route' => 'verification.notice'],
        ['label' => 'Complete Profile',  'route' => 'profile.complete'],
    ];

    private const PARENT_FLOW = [
        ['label' => 'Parent Required',       'route' => 'parent.registration.required'],
        ['label' => 'Parent Registers',      'route' => 'parent.register'],
        ['label' => 'Verify Email',          'route' => 'verification.notice'],
        ['label' => 'Complete Profile',      'route' => 'profile.complete'],
        ['label' => 'Create Child Account',  'route' => 'parent.create-child'],
    ];

    public function __construct(
        private ?string $currentRoute = null,
        private ?bool $isParentFlow = null,
    ) {
        $this->currentRoute = $currentRoute ?? Route::currentRouteName() ?? '';
        $this->isParentFlow = $isParentFlow ?? (bool) session('is_parent_registration');
        $this->steps = $this->buildSteps();
    }

    private function buildSteps(): ?array
    {
        $map = $this->isParentFlow ? self::PARENT_FLOW : self::LEARNER_FLOW;

        $activeIndex = null;
        foreach ($map as $i => $step) {
            if ($step['route'] === $this->currentRoute) {
                $activeIndex = $i;
                break;
            }
        }

        if ($activeIndex === null) {
            return null;
        }

        return array_map(function (array $step, int $i) use ($activeIndex) {
            return [
                'label'       => $step['label'],
                'isCompleted' => $i < $activeIndex,
                'isActive'    => $i === $activeIndex,
                'isUpcoming'  => $i > $activeIndex,
            ];
        }, $map, array_keys($map));
    }

    public function render()
    {
        return view('components.wizard-stepper');
    }
}
