<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'expiration_date',
        'status',
        'quantity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expiration_date' => 'date',
        'status' => ProductStatus::class,
        'quantity' => 'integer',
    ];

    /**
     * Adjust the stock level by the given delta.
     *
     * A positive delta models a delivery arriving at the store
     * ("pievedums"), while a negative delta models a sale or
     * redemption ("izpirkšana").
     *
     * @return int The updated quantity.
     *
     * @throws InvalidArgumentException When more stock is removed than is available.
     */
    public function adjustStock(int $delta): int
    {
        $newQuantity = $this->quantity + $delta;

        if ($newQuantity < 0) {
            throw new InvalidArgumentException(
                sprintf('Cannot remove %d items, only %d are in stock.', abs($delta), $this->quantity)
            );
        }

        $this->quantity = $newQuantity;
        $this->save();

        return $this->quantity;
    }
}
