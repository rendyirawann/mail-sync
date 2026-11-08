<?php

namespace App\Models\Concerns;

use App\Models\Team;
use App\Support\Tenancy\CurrentTeam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Isolasi data per tim: semua query otomatis difilter ke tim aktif dan
 * team_id terisi otomatis saat membuat data. Mencegah kebocoran data
 * antar tim walaupun ID ditebak (IDOR lintas tenant).
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait BelongsToTeam
{
    public static function bootBelongsToTeam(): void
    {
        static::addGlobalScope('team', function (Builder $builder): void {
            $teamId = app(CurrentTeam::class)->id();

            if ($teamId !== null) {
                $builder->where($builder->getModel()->qualifyColumn('team_id'), $teamId);
            }
        });

        static::creating(function ($model): void {
            if (empty($model->team_id)) {
                $model->team_id = app(CurrentTeam::class)->id();
            }
        });
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
