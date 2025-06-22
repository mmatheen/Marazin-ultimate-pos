<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;

trait CustomLogsActivity
{
    // Set the $customLogName property in your Model to customize the log name

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->useLogName($this->getCustomLogName())
            ->setDescriptionForEvent(function (string $eventName) {
                // Use the custom log name and user's name if available
                $userName = $this->user->user_name ?? 'Unknown';
                return "A {$this->getCustomLogName()} has been {$eventName} by user: {$userName}";
            });
    }

    protected function getCustomLogName(): string
    {
        // Use the property if it exists, otherwise fallback to 'default'
        return property_exists($this, 'customLogName') ? $this->customLogName : 'default';
    }
}
