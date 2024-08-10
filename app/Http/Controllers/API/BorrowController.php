<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Borrow\BorrowRequest;
use App\Http\Resources\Borrow\BorrowCollection;
use App\Http\Resources\Borrow\BorrowResource;
use App\Models\Books;
use App\Models\Borrows;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BorrowController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 1);
        $borrows = Borrows::with('user', 'book')->orderBy('created_at', 'desc')->paginate(6, ['*'], 'page', $perPage);
        // $borrowResponse = BorrowResource::collection($borrows);
        return (new BorrowCollection($borrows))->response()->setStatusCode(201);
    }
    public function store(BorrowRequest $request)
    {
        $validator = Validator::make($request->all(), $request->rules());
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first('load_date'),
            ], 400);
        }

        $data = $request->validated();
        $currentUser = auth()->user();
        $book = Books::find($data['book_id']);

        if (!$book || $book->stock <= 0) {
            return response()->json([
                'message' => 'Book not available or out of stock.'
            ], 400);
        }
        $borrow = Borrows::updateOrCreate(
            ['user_id' => $currentUser->id, 'book_id' => $data['book_id']],
            [
                'load_date' => $data['load_date'],
                'borrow_date' => $data['borrow_date'],
                'user_id' => $currentUser->id,
            ]
        );
        $book->stock -= 1;
        $book->save();

        return (new BorrowResource($borrow))->response()->setStatusCode(201);
    }

    public function generatePdf()
    {
        $borrows = Borrows::with('book', 'user')->orderBy('created_at', 'desc')->get();
        $html = view('pdf.borrow', ['borrows' => $borrows])->render();
        $pdf = new Dompdf();
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $pdf->setOptions($options);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        return $pdf->stream('borrows.pdf', array('Attachment' => 0));
    }

    public function getBorrowsByUserId()
    {
        $currentUser = auth()->user();
        $borrows = Borrows::where('user_id', $currentUser->id)->with('book')->orderBy('created_at', 'desc')->get();
        return response()->json([
            'data' => $borrows,
        ]);
    }

    public function search(Request $request)
    {
        $page = $request->input('page', 1);
        $size = $request->input('size', 6);
        $searchQuery = $request->input('query');
        $borrows = Borrows::query()
            ->with('user', 'book');

        if ($searchQuery) {
            $borrows = $borrows->where(function (Builder $builder) use ($searchQuery) {
                $builder->whereHas('user', function (Builder $query) use ($searchQuery) {
                    $query->where('name', 'like', '%' . $searchQuery . '%');
                })
                ->orWhereHas('book', function (Builder $query) use ($searchQuery) {
                    $query->where('title', 'like', '%' . $searchQuery . '%');
                })
                ->orWhere(function (Builder $query) use ($searchQuery) {
                    $query->whereDate('load_date', 'like', '%' . $searchQuery . '%')
                          ->orWhereDate('borrow_date', 'like', '%' . $searchQuery . '%');
                });
            });
        }
        $borrows = $borrows->paginate(perPage: $size, page: $page);
        return (new BorrowCollection($borrows))->response()->setStatusCode(200);
    }

}
