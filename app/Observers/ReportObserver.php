<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Report;

class ReportObserver
{
    public function created(Report $report): void
    {
        ActivityLog::create([
            'user_id' => $report->reporter_id,
            'action' => 'report_filed',
            'subject_type' => 'Report',
            'subject_id' => $report->id,
            'properties' => ['target_type' => $report->target_type],
            'created_at' => now(),
        ]);
    }
}
