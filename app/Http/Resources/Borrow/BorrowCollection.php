<?php

namespace App\Http\Resources\Borrow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BorrowCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'message' => 'Berhasil Menampilkan Data Peminjaman',
            'data' => BorrowResource::collection($this->collection),
            // 'pagination' => [
            //     'total' => $this->path()->total(),
            //     'count' => $this->count(),
            //     'per_page' => $this->perPage(),
            //     'current_page' => $this->currentPage(),
            //     'total_pages' => $this->lastPage()
            // ]
        ];
    }
}
