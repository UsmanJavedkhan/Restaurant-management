<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'slug' => $this->slug, 'category' => $this->category->name, 'category_id' => $this->category_id, 'description' => $this->description, 'image' => $this->image,
            'price' => (float) $this->price, 'discountPrice' => $this->discount_price !== null ? (float) $this->discount_price : null,
            'available' => $this->is_available, 'featured' => $this->is_featured, 'isDeal' => $this->is_deal, 'dealContents' => $this->deal_contents ?? [], 'preparationTime' => $this->preparation_time,
            'tag' => ! $this->is_available ? 'Unavailable' : ($this->is_deal ? 'Special deal' : ($this->is_featured ? 'House favorite' : 'Made fresh')),
            'variations' => $this->variations->where('is_active', true)->values()->map(fn ($item) => ['id' => $item->id, 'name' => $item->name, 'price' => (float) $item->price]),
            'addons' => $this->addons->where('is_active', true)->values()->map(fn ($item) => ['id' => $item->id, 'name' => $item->name, 'price' => (float) $item->price]),
        ];
    }
}
