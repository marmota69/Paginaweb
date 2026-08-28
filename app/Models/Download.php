<?php

namespace App\Models;

use Database\Factories\DownloadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $downloadable_type
 * @property int $downloadable_id
 * @property string|null $session_id
 * @property Carbon|null $created_at
 */
#[Fillable(['downloadable_type', 'downloadable_id', 'session_id'])]
class Download extends Model
{
    /** @use HasFactory<DownloadFactory> */
    use HasFactory;

    /**
     * This is an append-only event log, so there is nothing to update.
     */
    public const UPDATED_AT = null;

    /**
     * The course or guide this download belongs to.
     *
     * @return MorphTo<Model, $this>
     */
    public function downloadable(): MorphTo
    {
        return $this->morphTo();
    }
}
