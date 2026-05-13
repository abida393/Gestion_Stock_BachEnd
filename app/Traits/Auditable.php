<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->logAudit('created');
        });

        static::updated(function ($model) {
            $model->logAudit('updated');
        });

        static::deleted(function ($model) {
            $model->logAudit('deleted');
        });
    }

    protected function logAudit(string $event)
    {
        $oldValues = [];
        $newValues = [];

        if ($event === 'updated') {
            $oldValues = array_intersect_key($this->getOriginal(), $this->getDirty());
            $newValues = $this->getDirty();
            
            // Don't log if no meaningful changes
            if (empty($newValues)) return;
        } elseif ($event === 'created') {
            $newValues = $this->getAttributes();
        } elseif ($event === 'deleted') {
            $oldValues = $this->getAttributes();
        }

        // Remove sensitive or irrelevant fields
        $exclude = ['password', 'remember_token', 'cree_le', 'mis_a_jour_le', 'supprime_le', 'created_at', 'updated_at', 'deleted_at'];
        $oldValues = array_diff_key($oldValues, array_flip($exclude));
        $newValues = array_diff_key($newValues, array_flip($exclude));

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
