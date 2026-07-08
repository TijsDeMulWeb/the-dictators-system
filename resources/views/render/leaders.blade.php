<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Games per Leader</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { background: #ffffff; }
        body {
            font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #111;
            -webkit-font-smoothing: antialiased;
        }
        #board { width: 620px; padding: 24px; background: #ffffff; }
        h1 { font-size: 24px; font-weight: 800; margin-bottom: 16px; }
        table { border-collapse: collapse; width: 100%; font-size: 17px; }
        thead th {
            background: #2f7fd6;
            color: #ffef8a;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: .4px;
            padding: 9px 10px;
            border: 1px solid #ffffff;
            text-align: center;
        }
        thead th.name { text-align: left; }
        tbody td {
            padding: 8px 10px;
            border: 1px solid #e3e6ea;
            text-align: center;
            font-variant-numeric: tabular-nums;
        }
        tbody td.rank { color: #888; width: 46px; }
        tbody td.name { text-align: left; font-weight: 700; }
        tbody td.games { font-weight: 800; font-size: 19px; }
        tbody tr:nth-child(even) td { background: #f6f8fa; }
        .wl { color: #667; font-size: 15px; }
        .empty { padding: 24px 10px; color: #888; font-style: italic; }
    </style>
</head>
<body>
    <div id="board">
        <h1>Games per Leader</h1>

        <table>
            <thead>
                <tr>
                    <th class="rank">#</th>
                    <th class="name">Leader</th>
                    <th>Games</th>
                    <th>W / L</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="rank">{{ $row['rank'] }}</td>
                        <td class="name">{{ $row['name'] }}</td>
                        <td class="games">{{ $row['games'] }}</td>
                        <td class="wl">{{ $row['wins'] }} / {{ $row['losses'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">No approved reports yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
