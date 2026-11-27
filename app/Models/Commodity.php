<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commodity extends Model
{
    protected $guarded = ['id'];

    public function name(): string
    {
        return app()->getLocale() === 'sw' ? $this->name_sw : $this->name_en;
    }

    public static function mvp(): self
    {
        return self::where('slug', config('freshroute.commodity'))->firstOrFail();
    }
}
