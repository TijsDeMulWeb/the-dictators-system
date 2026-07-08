<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $points
 */
#[Fillable(['name', 'points'])]
class Tier extends Model
{
    //
}
