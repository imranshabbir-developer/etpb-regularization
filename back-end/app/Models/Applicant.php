<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Applicant extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_indigent'   => 'boolean',
            'is_widow'      => 'boolean',
            'is_orphan'     => 'boolean',
            'is_verified'   => 'boolean',
        ];
    }

    public function user(): BelongsTo             { return $this->belongsTo(User::class); }
    public function addressDistrict(): BelongsTo  { return $this->belongsTo(District::class, 'address_district_id'); }
    public function applications(): HasMany       { return $this->hasMany(Application::class); }

    /** Displayed as "Name s/o Father" or "w/o Husband", per the parentage type. */
    public function nameWithParentage(): string
    {
        $rel = $this->parentage_type === 'HUSBAND' ? 'w/o' : 's/o';

        return trim("{$this->full_name} {$rel} {$this->parentage_name}");
    }

    /**
     * CNIC is masked by default. Clause 12 status (indigent, widow, orphan)
     * and full CNIC are privileged views and are audited when opened.
     */
    public function maskedCnic(): string
    {
        if (strlen((string) $this->cnic) !== 13) {
            return (string) $this->cnic;
        }

        return substr($this->cnic, 0, 5) . '-XXXXX-' . substr($this->cnic, -1);
    }

    public function formattedCnic(): string
    {
        if (strlen((string) $this->cnic) !== 13) {
            return (string) $this->cnic;
        }

        return substr($this->cnic, 0, 5) . '-' . substr($this->cnic, 5, 7) . '-' . substr($this->cnic, -1);
    }

    /** Whether any Clause 12 remission ground is recorded. */
    public function hasRemissionGround(): bool
    {
        return $this->is_indigent || $this->is_widow || $this->is_orphan;
    }
}
