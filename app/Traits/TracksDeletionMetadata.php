<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/** @mixin Model */
trait TracksDeletionMetadata
{
    abstract public static function deleting($callback);

    public static function bootTracksDeletionMetadata(): void
    {
        static::deleting(function ($model): void {
            if (!method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                return;
            }

            $table = $model->getTable();
            $dirty = false;

            if (Schema::hasColumn($table, 'deleted_by') && empty($model->getAttribute('deleted_by'))) {
                $model->setAttribute('deleted_by', Auth::id());
                $dirty = true;
            }

            $reason = trim((string) (request()->input('delete_reason') ?: request()->header('X-Delete-Reason') ?: ''));
            if (Schema::hasColumn($table, 'delete_reason') && $reason !== '' && blank($model->getAttribute('delete_reason'))) {
                $model->setAttribute('delete_reason', $reason);
                $dirty = true;
            }

            if ($dirty) {
                $model->saveQuietly();
            }
        });
    }

    public function eliminador()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}