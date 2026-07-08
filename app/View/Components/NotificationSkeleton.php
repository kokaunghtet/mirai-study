<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class NotificationSkeleton extends Component
{
    public function __construct(public int $count) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('components.notification-skeleton');
    }
}
