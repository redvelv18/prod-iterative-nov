<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The products that carry this tag.
     *
     * One tag can belong to any number of products, just as a product
     * can carry any number of tags.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->orderBy('name');
    }

    /**
     * Look the tag up by name and create it when it does not exist yet.
     *
     * Matching ignores letter case and surrounding whitespace, so
     * "Organic" and " organic " reuse the same tag instead of
     * piling up near-identical rows.
     */
    public static function findOrCreate(string $name): static
    {
        $name = trim($name);

        return static::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first()
            ?? static::create(['name' => $name]);
    }
}
