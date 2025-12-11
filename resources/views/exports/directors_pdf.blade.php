<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 120px 30px 70px 30px; /* hely a fejlécnek és láblécnek */
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 100px;
            text-align: center;
        }

        .logo {
            width: 120px;     /* KICSI fix méret */
            height: auto;     /* ne torzuljon */
            margin-bottom: 5px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            margin-top: -5px;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #555;
            padding: 6px;
            font-size: 13px;
        }

        th {
            background: #f0f0f0;
        }
    </style>
</head>

<body>

<header>
    <img src="{{ public_path('img/logo.jpg') }}" class="logo" alt="Logo">
    <div class="title">{{ $title }}</div>
</header>

<footer>
    Oldal generálva: {{ date('Y-m-d H:i') }}
</footer>

<table>
    <thead>
        <tr>
            @foreach($columns as $col)
                <th>{{ $col }}</th>
            @endforeach
        </tr>
    </thead>

    <tbody>
        @foreach($items as $item)
            <tr>
                @foreach($fields as $f)
                    <td>{{ $item[$f] ?? '-' }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
