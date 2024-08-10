<!DOCTYPE html>
<html>
<head>
    <title>Borrow Report</title>
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
    </style>
</head>
<body>
    <h1>Borrow Report</h1>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Book Title</th>
                <th>User Name</th>
                <th>Borrow Date</th>
                <th>Load Date</th>
            </tr>
        </thead>
        <tbody>
            @php $counter = 1; @endphp
            @foreach ($borrows as $borrow)
                <tr>
                    <td>{{ $counter++ }}</td>
                    <td>{{ $borrow->book->title }}</td>
                    <td>{{ $borrow->user->name }}</td>
                    <td>{{ $borrow->borrow_date }}</td>
                    <td>{{ $borrow->load_date ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
