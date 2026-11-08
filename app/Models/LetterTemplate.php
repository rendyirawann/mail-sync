<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Template surat keluar (HTML + penanda {{kunci}}). team_id null = template bawaan untuk semua tim.
 *
 * @property string $id
 * @property string|null $team_id
 * @property string $name
 * @property string $paper
 * @property string $body
 * @property list<array{key: string, label: string, type: string, required?: bool}> $fields
 */
class LetterTemplate extends Model
{
    use BelongsToTeam, HasUuids;

    protected $fillable = ['team_id', 'name', 'description', 'paper', 'body', 'fields', 'is_active'];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Template milik tim aktif + template bawaan (team_id null).
     *
     * @return \Illuminate\Database\Eloquent\Builder<LetterTemplate>
     */
    public static function available(): \Illuminate\Database\Eloquent\Builder
    {
        $teamId = app(\App\Support\Tenancy\CurrentTeam::class)->id();

        return static::withoutGlobalScope('team')
            ->where(fn ($q) => $q->whereNull('team_id')->orWhere('team_id', $teamId))
            ->where('is_active', true)
            ->orderByRaw('team_id is null')
            ->orderBy('name');
    }

    public function isBuiltIn(): bool
    {
        return $this->team_id === null;
    }
}
