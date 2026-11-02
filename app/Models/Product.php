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
     * Add the delivered items to the stock ("pievedums").
     *
     * @param  int  $amount  How many items arrived.
     * @return int The updated quantity.
     *
     * @throws InvalidArgumentException When the amount does not cover at least one item.
     */
    public function addStock(int $amount): int
    {
        $this->guardAgainstInvalidAmount($amount);

        $this->quantity += $amount;
        $this->save();

        return $this->quantity;
    }

    /**
     * Take the sold or written off items out of the stock ("izpirkšana").
     *
     * @param  int  $amount  How many items left the store.
     * @return int The updated quantity.
     *
     * @throws InvalidArgumentException When the amount does not cover at least one item,
     *                                  or more items are taken than are in stock.
     */
    public function removeStock(int $amount): int
    {
        $this->guardAgainstInvalidAmount($amount);

        if ($amount > $this->quantity) {
            throw new InvalidArgumentException(
                sprintf('Cannot remove %d items, only %d are in stock.', $amount, $this->quantity)
            );
        }

        $this->quantity -= $amount;
        $this->save();

        return $this->quantity;
    }

    /**
     * Reject adjustments that do not describe a whole number of items.
     *
     * @throws InvalidArgumentException When the amount is zero, negative or empty.
     */
    private function guardAgainstInvalidAmount(int $amount): void
    {
        if ($amount < 1) {
            throw new InvalidArgumentException('A stock adjustment must cover at least one item.');
        }
    }
}
