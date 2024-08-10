<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Book\BookCreateRequest;
use App\Http\Requests\Book\BookUpdateRequest;
use App\Http\Resources\Book\BookCollection;
use App\Http\Resources\Book\BookResource;
use App\Http\Resources\Book\BookResourceById;
use App\Models\Books;
use App\Models\Borrows;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{

    public function __construct()
    {
        $this->middleware(['isOwner', 'auth:api'])->only('store', 'update', 'destroy');
    }

    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $books = Books::orderBy('created_at', 'desc')->paginate(6, ['*'], 'page', $page);
        return (new BookCollection($books))->response()->setStatusCode(201);
    }

    public function chartIndexBook(): JsonResponse
    {
        $ownerRole = DB::table('roles')->where('name', 'owner')->first();
        $totalBooks = Books::count();
        $borrowedBooks = Borrows::distinct('book_id')->count();
        $userAvaible = User::where('role_id', $ownerRole->id)->count();
        $availableBooks = Books::where('stock', '>', 0)->count();

        return response()->json([
            'totalBooks' => $totalBooks,
            'borrowedBooks' => $borrowedBooks,
            'availableBooks' => $availableBooks,
            'reservedBooks' => $userAvaible,
        ]);
    }

    public function borrowStats(): JsonResponse
    {
        $currentYear = date('Y');
        $borrowStats = Borrows::select(
            DB::raw('MONTH(borrow_date) as month'),
            DB::raw('count(*) as count')
        )
        ->whereYear('borrow_date', $currentYear)
        ->groupBy('month')
        ->pluck('count', 'month')
        ->toArray();
        $stats = [];
        for ($i = 1; $i <= 12; $i++) {
            $stats[$i] = $borrowStats[$i] ?? 0;
        }

        return response()->json($stats);
    }

    public function getAll(): JsonResponse
    {
        $book = Books::all();
        return response()->json([
            'message' => 'Success',
            'data' => BookResource::collection($book)
        ], 200);
    }

    public function bookNews()
    {
        $books = Books::where('stock', '>', 0)
                    ->orderBy('created_at', 'desc')
                    ->take(4)
                    ->get();
        
        return response()->json([
            'message' => 'Success',
            'data' => BookResource::collection($books)
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(BookCreateRequest $request) : JsonResponse
    {
        try {
            $data = $request->validated();
            $cloudinaryImage = $request->file('image')->storeOnCloudinary('movies');
            $url = $cloudinaryImage->getSecurePath();
            $public_id = $cloudinaryImage->getPublicId();
            $data['image'] = $url;
            $data['public_image_id'] = $public_id;
            $book = Books::create($data);
            return (new BookResource($book))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }    

    public function bookZero()
    {
        $books = Books::where('stock', 0)->get();
        return response()->json([
            'message' => 'Success',
            'data' => BookResource::collection($books)
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $bookId = Books::with('category','list_borrows')->find($id);
        if (!$bookId) {
            return response()->json([
                'message' => 'Book dengan ID '. $id.'tidak ditemukan',
            ], 404);
        }
        return (new BookResourceById($bookId))->response()->setStatusCode(201);
    }

    public function generatePdf()
    {
        $books = Books::with('category')->orderBy('created_at', 'desc')->get();
        $html = view('pdf.book', ['books' => $books])->render();
        $pdf = new Dompdf();
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $pdf->setOptions($options);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        return $pdf->stream('book.pdf', ['Attachment' => 0]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BookUpdateRequest $request, string $id) : JsonResponse
    {
        try {
            $book = Books::find($id);
            if (!$book) {
                return response()->json([
                    'message' => 'Book dengan ID ' . $id . ' tidak ditemukan',
                ], 404);
            }
            
            $data = $request->validated();

            if ($request->hasFile('image')) {
                if ($book->public_image_id) {
                    Cloudinary::destroy($book->public_image_id);
                }
                $cloudinaryImage = $request->file('image')->storeOnCloudinary('movies');
                $url = $cloudinaryImage->getSecurePath();
                $public_id = $cloudinaryImage->getPublicId();
                $data['image'] = $url;
                $data['public_image_id'] = $public_id;
            }
            $book->update($data);
            return (new BookResource($book))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'gagal',
                'message' => 'Gagal memperbarui gambar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $book = Books::find($id);
        if (!$book) {
            return response()->json([
                'message' => 'Movie dengan ID '. $id.'tidak ditemukan',
            ], 404);
        }
        Cloudinary::destroy($book->public_image_id);
        $book->delete();
        return response()->json([
            'message' => 'Book berhasil dihapus',
        ], 200);
    }
    public function search(Request $request)
    {
        $page = $request->input('page', 1);
        $size = $request->input('size', 8);
        $searchQuery = $request->input('query');
        $books = Books::query();
        if ($searchQuery) {
            $books = $books->where(function (Builder $builder) use ($searchQuery) {
                $builder->where('title', 'like', '%' . $searchQuery . '%')
                        ->orWhere('summary', 'like', '%' . $searchQuery . '%')
                        ->orWhere('stock', 'like', '%' . $searchQuery . '%');
            });
        }

        $books = $books->paginate(perPage: $size, page: $page);
        return (new BookCollection($books))->response()->setStatusCode(201);
    }
}
