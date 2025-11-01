<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ucfirst($type) }} Resumen</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 11px;
            color: #2c3e50;
            margin: 0;
            padding: 30px;
            background: #ecf0f3;
        }
        
        .container {
            background: #ffffff;
            max-width: 1200px;
            margin: 0 auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .header {
            background: #1e3a5f;
            color: #ffffff;
            padding: 35px 40px;
            border-bottom: 4px solid #d55a2a;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }
        
        .header .subtitle {
            font-size: 13px;
            color: #b8c5d6;
            font-weight: 400;
        }
        
        .content {
            padding: 40px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .info-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 18px 20px;
            border-left: 3px solid #d55a2a;
        }
        
        .info-card .label {
            font-size: 10px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }
        
        .info-card .value {
            font-size: 13px;
            font-weight: 600;
            color: #1e3a5f;
            line-height: 1.5;
        }
        
        .note-section {
            background: #fff8f0;
            border-left: 3px solid #d55a2a;
            padding: 18px 20px;
            margin-bottom: 32px;
            border: 1px solid #f5d5b8;
        }
        
        .note-section strong {
            color: #c25d0f;
            font-size: 11px;
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .note-section p {
            color: #7d5639;
            font-size: 11px;
            line-height: 1.6;
            margin: 0;
        }
        
        .table-container {
            border: 1px solid #dee2e6;
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #2c5282;
        }
        
        th {
            color: #ffffff;
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        td {
            padding: 14px 16px;
            border-bottom: 1px solid #e9ecef;
            font-size: 11px;
            color: #495057;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        .footer {
            margin-top: 50px;
            padding: 24px 40px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            text-align: center;
        }
        
        .footer .main-text {
            font-size: 10px;
            color: #6c757d;
            margin-bottom: 18px;
            line-height: 1.6;
        }
        
        .footer .main-text strong {
            color: #2c5282;
            font-weight: 600;
        }
        
        .footer .credits {
            font-size: 8px;
            color: #adb5bd;
            line-height: 1.6;
        }
        
        .accent-bar {
            height: 2px;
            background: #d55a2a;
            margin: 32px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $type === 'checkout' ? 'BOLETA DE PRÉSTAMO DE EQUIPO' : 'BOLETA DE RECEPCIÓN DE EQUIPO' }}</h1>
            <p class="subtitle">{{ $date }}</p>
        </div>

        <div class="content">
            <div class="info-grid">
                <div class="info-card">
                    <div class="label">{{ $type === 'checkout' ? 'Asignado a' : 'Asignado a' }}</div>
                    <div class="value">
                        @if($target)
                            @if(is_object($target))
                                {{ $target->name ?? $target->display_name ?? 'N/A' }}
                            @else
                                {{ $target }}
                            @endif
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="label">Operador</div>
                    <div class="value">
                        {{ $admin->first_name ?? '' }} {{ $admin->last_name ?? '' }}<br>
                        <span style="font-size: 11px; color: #6c757d; font-weight: 400;">{{ $admin->username ?? 'N/A' }}</span>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="label">Total de Activos</div>
                    <div class="value">{{ $assets->count() }} activos</div>
                </div>
                
                <div class="info-card">
                    <div class="label">Número de Boleta</div>
                    <div class="value">{{ $batch_id }}</div>
                </div>
            </div>

            @if($note)
            <div class="note-section">
                <strong>Nota</strong>
                <p>{{ $note }}</p>
            </div>
            @endif

            <div class="accent-bar"></div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Número de Activo</th>
                            <th>Categoría</th>
                            <th>Modelo</th>
                            <th>Serie</th>
                            <th>Estado</th>
                            @if($type === 'checkout')
                            <th>Ubicación</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assets as $index => $asset)
                        <tr>
                            <td style="font-weight: 600; color: #2c5282;">{{ $index + 1 }}</td>
                            <td style="font-weight: 600; color: #1e3a5f;">{{ $asset->asset_tag ?? 'N/A' }}</td>
                            <td>{{ $asset->model->category->name ?? 'N/A' }}</td>
                            <td>{{ $asset->model->name ?? 'N/A' }}</td>
                            <td style="font-family: 'Courier New', monospace; font-size: 10px;">{{ $asset->serial ?? 'N/A' }}</td>
                            <td>{{ $asset->assetstatus->name ?? 'N/A' }}</td>
                            @if($type === 'checkout')
                            <td>{{ $asset->location->name ?? 'N/A' }}</td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="footer">
            <p class="main-text">
                <strong>Sistema de Inventario The Archivist</strong><br>
                Este documento es solo para fines de referencia
            </p>
            <p class="credits">
                Brought to you by SanJoma, maintained by Santiago Chacon/TEC :) and tailored by Josue Montero/UNA &lt;3<br>
                Job 26:7
            </p>
        </div>
    </div>
</body>
</html>