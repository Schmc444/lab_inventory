<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #3c8dbc;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .info-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border-left: 4px solid #3c8dbc;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .equipment-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background-color: white;
        }
        .equipment-table th {
            background-color: #3c8dbc;
            color: white;
            padding: 10px;
            text-align: left;
        }
        .equipment-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .equipment-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .warning {
            color: #d9534f;
            font-weight: bold;
        }
        .notes {
            background-color: #fcf8e3;
            padding: 15px;
            border-left: 4px solid #f0ad4e;
            margin-top: 15px;
        }
        .footer {
            margin-top: 20px;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Nueva Solicitud de Apartado de Equipo</h2>
        </div>

        <div class="content">
            <div class="info-section">
                <p><span class="info-label">Laboratorio:</span> {{ $data['company']->name }}</p>
                <p><span class="info-label">Taller/Curso:</span> {{ $data['workshop_name'] }}</p>
                <p><span class="info-label">Fecha Necesaria:</span> {{ \Carbon\Carbon::parse($data['needed_date'])->format('d/m/Y') }}</p>
            </div>

            <h3 style="color: #3c8dbc;">Lista de Equipo Solicitado</h3>
            
            <table class="equipment-table">
                <thead>
                    <tr>
                        <th>Modelo</th>
                        <th>Cantidad Solicitada</th>
                        <th>Disponibles</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['equipment'] as $item)
                    <tr>
                        <td>
                            {{ $item['model']->name }}
                            @if($item['model']->model_number)
                                <br><small style="color: #777;">{{ $item['model']->model_number }}</small>
                            @endif
                        </td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>
                            {{ $item['available'] }}
                            @if($item['quantity'] > $item['available'])
                                <br><span class="warning">⚠ Insuficiente</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($data['notes'])
            <div class="notes" style="margin-top: 20px; border-left-color: #3c8dbc;">
                <p><span class="info-label">Notas Adicionales:</span></p>
                <p>{{ $data['notes'] }}</p>
            </div>
            @endif

            <div class="info-section" style="margin-top: 20px; border-left-color: #3c8dbc;">
                <p><span class="info-label">Solicitado por:</span> {{ $data['submitted_by'] }}</p>
                <p><span class="info-label">Fecha de solicitud:</span> {{ $data['submitted_at'] }}</p>
            </div>
        </div>

        <div class="footer">
            <p>Este es un correo automático generado por el sistema de inventario Archives</p>
            <p>Esta solicitud es solo informativa y no afecta la disponibilidad de los activos en el sistema.</p>
            <p><strong>Recuerde que la asignación de activos debe realizarse manualmente en el sistema.</strong></p>
        </div>
    </div>
</body>
</html>
