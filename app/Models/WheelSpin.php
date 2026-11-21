<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['popup_id', 'email', 'prize', 'coupon_code', 'ip'])]
class WheelSpin extends Model
{
    /**
     * @return BelongsTo<Popup, $this>
     */
    public function popup(): BelongsTo
    {
        return $this->belongsTo(Popup::class);
    }
}
