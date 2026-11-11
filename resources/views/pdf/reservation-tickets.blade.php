<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Billets {{ $brand }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #1a1a1a; font-size: 11px; }
        .ticket {
            page-break-after: always;
            border: 2px solid #1a1a1a;
            border-radius: 12px;
            margin: 0 0 24px;
            overflow: hidden;
            background: linear-gradient(135deg, #fdfdfc 0%, #f3f1eb 100%);
        }
        .ticket:last-child { page-break-after: auto; }
        .header {
            background: #1a1a1a;
            color: #f5f5f4;
            padding: 14px 20px;
            text-align: center;
        }
        .logo { height: 28px; margin-bottom: 6px; }
        .header h1 { font-size: 18px; letter-spacing: 3px; text-transform: uppercase; }
        .header p { font-size: 9px; margin-top: 4px; opacity: 0.85; }
        .body { padding: 20px; }
        .row { width: 100%; border-collapse: collapse; }
        .poster { width: 100px; height: 140px; object-fit: cover; border-radius: 8px; border: 1px solid #ccc; }
        .poster-placeholder {
            width: 100px; height: 140px; background: #e5e5e5; border-radius: 8px;
            text-align: center; line-height: 140px; font-size: 10px; color: #666;
        }
        .film-title { font-size: 18px; font-weight: bold; margin-bottom: 8px; }
        .meta { margin-bottom: 4px; color: #444; }
        .code {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 14px; font-weight: bold; letter-spacing: 1px; margin-top: 12px;
        }
        .qr { width: 120px; height: 120px; }
        .demo {
            margin-top: 16px; padding: 10px; background: #fff7ed;
            border: 1px dashed #ea580c; border-radius: 8px;
            font-size: 9px; color: #9a3412; text-align: center;
        }
        .stub {
            margin-top: 16px; border-top: 2px dashed #999;
            padding-top: 12px; font-size: 10px; color: #666;
        }
    </style>
</head>
<body>
@foreach ($tickets as $ticket)
    <div class="ticket">
        <div class="header">
            @if (!empty($logo_data_uri))
                <img src="{{ $logo_data_uri }}" alt="" class="logo">
            @endif
            <h1>{{ $brand }}</h1>
            <p>Billet numérique — démonstration</p>
        </div>
        <div class="body">
            <table class="row">
                <tr>
                    <td style="width: 110px; vertical-align: top;">
                        @if ($ticket['poster_data_uri'])
                            <img src="{{ $ticket['poster_data_uri'] }}" alt="" class="poster">
                        @else
                            <div class="poster-placeholder">Affiche</div>
                        @endif
                    </td>
                    <td style="vertical-align: top; padding-left: 16px;">
                        <div class="film-title">{{ $ticket['film_title'] }}</div>
                        <div class="meta"><strong>Date :</strong> {{ $ticket['seance_date'] }}</div>
                        <div class="meta"><strong>Horaire :</strong> {{ $ticket['seance_start'] }} – {{ $ticket['seance_end'] }}</div>
                        <div class="meta"><strong>Salle :</strong> {{ $ticket['salle_name'] }}</div>
                        <div class="meta"><strong>Réservation :</strong> {{ $ticket['reference'] }}</div>
                        <div class="code">{{ $ticket['ticket_code'] }}</div>
                    </td>
                    <td style="width: 130px; vertical-align: top; text-align: center;">
                        <img src="{{ $ticket['qr_data_uri'] }}" alt="QR" class="qr">
                    </td>
                </tr>
            </table>
            <div class="demo">
                Billet numérique de démonstration sans valeur commerciale.
                Le QR code contient uniquement l’identifiant du billet.
            </div>
            <div class="stub">Conservez ce billet jusqu’à la fin de la séance · {{ $brand }}</div>
        </div>
    </div>
@endforeach
</body>
</html>
