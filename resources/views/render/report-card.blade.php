<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report No {{ $report->report_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { background: #ffffff; }
        body {
            font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1a1a1a;
            -webkit-font-smoothing: antialiased;
        }
        #card {
            width: 760px;
            padding: 48px 56px;
            background: #ffffff;
        }
        .title { font-size: 34px; font-weight: 800; margin-bottom: 22px; }
        .title u { text-underline-offset: 4px; }
        .meta { font-size: 26px; line-height: 1.55; }
        .meta .label { color: #1a1a1a; }
        .result-win { color: #2ea44f; font-weight: 700; }
        .result-loss { color: #e5484d; font-weight: 700; }
        .challenge-tier { color: #444; }
        .challenge-bonus { color: #d08700; font-weight: 700; }
        .score-head { font-size: 26px; margin: 6px 0 10px; }
        .score-head .colon { color: #e5484d; }
        ol.players { list-style: none; counter-reset: rank; }
        ol.players li {
            counter-increment: rank;
            font-size: 26px;
            line-height: 1.75;
            display: flex;
            gap: 14px;
        }
        ol.players li::before {
            content: counter(rank) ".";
            width: 34px;
            text-align: right;
            color: #444;
            font-variant-numeric: tabular-nums;
        }
        .player-name { font-weight: 500; }
        .player-country { color: #444; }
        .player-points { font-weight: 600; }
        .footer {
            margin-top: 26px;
            font-size: 15px;
            color: #9aa0a6;
            letter-spacing: .3px;
        }
    </style>
</head>
<body>
    <div id="card">
        <div class="title">Report No <u>{{ $report->report_number }}.</u></div>

        <div class="meta">
            <div><span class="label">Game –</span> {{ $report->game }}</div>
            @if (! is_null($report->day))
                <div><span class="label">Day –</span> {{ $report->day }}</div>
            @endif
            @if ($report->challenge)
                <div>
                    <span class="label">Challenge –</span> {{ $report->challenge->name }}
                    <span class="challenge-tier">({{ ucfirst($report->challenge->tier) }})</span>
                    <span class="challenge-bonus">+{{ number_format((int) $report->challenge_bonus) }} each</span>
                </div>
            @endif
            <div><span class="label">Leader –</span> {{ $report->leader->display_name }}</div>
            <div>
                <span class="label">Result:</span>
                <span class="{{ $report->result->value === 'win' ? 'result-win' : 'result-loss' }}">
                    {{ $report->result->label() }}
                </span>
            </div>
        </div>

        <div class="score-head">Score<span class="colon">:</span></div>

        <ol class="players">
            @foreach ($report->players as $player)
                <li>
                    <span>
                        <span class="player-name">{{ $player->display_name }}</span>
                        @if ($player->pivot->country)
                            <span class="player-country">({{ $player->pivot->country }})</span>
                        @endif
                        <span class="player-points">– {{ number_format((int) $player->pivot->points) }}</span>
                    </span>
                </li>
            @endforeach
        </ol>

        <div class="footer">The Dictators System · Report #{{ $report->report_number }}</div>
    </div>
</body>
</html>
