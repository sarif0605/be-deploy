<?php

namespace App\Http\Resources\Borrow;

use App\Http\Resources\Auth\UserResource;
use App\Http\Resources\Book\BookResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'load_date' => $this->load_date,
            'borrow_date' => $this->borrow_date,
            'user' => new UserResource($this->user),
            'book' => new BookResource($this->book),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
