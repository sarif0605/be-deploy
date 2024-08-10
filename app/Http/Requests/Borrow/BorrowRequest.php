<?php

namespace App\Http\Requests\Borrow;

use Illuminate\Foundation\Http\FormRequest;

class BorrowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'load_date' => "date|nullable",
            'borrow_date' => 'date|nullable',
            'user_id' => 'nullable|exists:users,id',
            'book_id' => 'nullable|exists:books,id'
        ];
    }

    public function messages(): array
    {
        return [
            'load_date.nullable' => 'The Load Date field is nullable.',
            'load_date.date' => 'The Load Date field is date time.',
            'borrow_date.required' => 'The Borrow Date field is nullable.',
            'borrow_date.date' => 'The Borrow Date field is date time.',
            'user_id.required' => 'The User field is required.',
            'user_id.exists' => 'The User does not exist.',
            'books_id.required' => 'The Books field is required.',
            'books_id.exists' => 'The Books does not exist.',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();
            if (isset($data['load_date']) && isset($data['borrow_date']) && $data['load_date'] > $data['borrow_date']) {
                $validator->errors()->add('load_date', 'Tanggal pinjam harus sebelum atau sama dengan tanggal peminjaman.');
            }
        });
    }
}
