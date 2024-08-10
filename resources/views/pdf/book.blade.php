<!DOCTYPE html>
<html>
<head>
    <title>Book Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        img {
            max-width: 100px;
            height: auto;
        }
    </style>
</head>
<body>
    <h1>Book Report</h1>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Title</th>
                <th>Stock</th>
                <th>Image</th>
                <th>Category</th>
            </tr>
        </thead>
        <tbody>
            @php $counter = 1; @endphp
            @foreach ($books as $book)
                <tr>
                    <td>{{ $counter++ }}</td>
                    <td>{{ $book->title }}</td>
                    <td>{{ $book->stock }}</td>
                    <td><img src="{{ $book->image }}" alt="Book Image"></td>
                    <td>{{ $book->category->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
