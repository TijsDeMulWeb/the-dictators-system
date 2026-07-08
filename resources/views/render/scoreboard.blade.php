<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scoreboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { background: #ffffff; }
        body {
            font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #111;
            -webkit-font-smoothing: antialiased;
        }
        #board { width: 1040px; padding: 24px; background: #ffffff; }
        h1 { font-size: 26px; font-weight: 800; margin-bottom: 4px; }
        .subtitle { font-size: 14px; color: #667; margin-bottom: 16px; }
        table { border-collapse: collapse; width: 100%; font-size: 17px; }
        thead th {
            background: #2f7fd6;
            color: #ffef8a;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: .4px;
            padding: 9px 8px;
            border: 1px solid #ffffff;
            text-align: center;
        }
        thead th:first-child { text-align: left; }
        tbody td {
            padding: 7px 8px;
            border: 1px solid rgba(255,255,255,.7);
            text-align: center;
            font-variant-numeric: tabular-nums;
        }
        tbody td.name { text-align: left; font-weight: 700; }
        tbody td.final { font-weight: 800; }
        /* tier bands echo the original sheet */
        .tier-1 td { background: #f2f4a0; }
        .tier-2 td { background: #cfd4da; }
        .tier-3 td { background: #f6cba6; }
        .tier-4 td { background: #b7ac74; }
        .tier-5 td { background: #e8615f; color: #2a0000; }
    </style>
</head>
<body>
    <div id="board">
        <h1>{{ $title ?? 'The Dictators — Scoreboard' }}</h1>
        <div class="subtitle">
            Final Score = Avg Points × (ln(Games + 1))² × Win Rate ·
            {{ now()->format('d M Y H:i') }}
        </div>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>NOG</th>
                    <th>Win</th>
                    <th>Lose</th>
                    <th>P. / Game</th>
                    <th>Points</th>
                    <th>% of W</th>
                    <th>Final Score</th>
                </tr>
            </thead>
            <tbody>
                @php $total = count($rows); @endphp
                @foreach ($rows as $row)
                    @php
                        $fraction = $total > 1 ? ($row['rank'] - 1) / ($total - 1) : 0;
                        $tier = (int) floor($fraction * 4) + 1;
                        $tier = max(1, min(5, $tier));
                    @endphp
                    <tr class="tier-{{ $tier }}">
                        <td class="name">{{ $row['name'] }}</td>
                        <td>{{ $row['games'] }}</td>
                        <td>{{ $row['wins'] }}</td>
                        <td>{{ $row['losses'] }}</td>
                        <td>{{ number_format($row['avg_points']) }}</td>
                        <td>{{ number_format($row['total_points']) }}</td>
                        <td>{{ round($row['win_rate'] * 100) }}%</td>
                        <td class="final">{{ number_format(round($row['final_score'])) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
