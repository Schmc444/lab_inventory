{{-- resources/views/custom/dashboard-shortcuts.blade.php --}}
@if(Auth::check())
    @php
        $user = Auth::user();
        
        // Debug: Let's see what groups the user has
        $userGroups = $user->groups->pluck('name')->toArray();
        
        // Check for both singular and plural variations
        $isOperator = $user->groups->contains('name', 'Operador') || 
                      $user->groups->contains('name', 'Operadores');
        $isProfessor = $user->groups->contains('name', 'Profesor') || 
                       $user->groups->contains('name', 'Profesores');
    @endphp

    {{-- Debug section - Remove this after testing --}}
    <div class="row" style="margin-bottom: 10px;">
        <div class="col-md-12">
            <div class="alert alert-info">
                <strong>Debug Info:</strong><br>
                User Groups: {{ implode(', ', $userGroups) }}<br>
                Is Operator: {{ $isOperator ? 'Yes' : 'No' }}<br>
                Is Professor: {{ $isProfessor ? 'Yes' : 'No' }}
            </div>
        </div>
    </div>
    {{-- End debug section --}}

    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">Accesos Rápidos</h2>
                </div>
                <div class="box-body">
                    <div class="row text-center">
                        @if($isOperator)
                            {{-- Asignar Equipo Button --}}
                            <div class="col-md-3 col-sm-6 col-xs-12">
                                <a href="{{ route('hardware.bulkcheckout.show') }}" class="shortcut-button" style="display: block; padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 15px; margin-bottom: 20px; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                                    <div style="font-size: 48px; margin-bottom: 15px;">
                                        <i class="fa fa-hand-pointer-o"></i>
                                    </div>
                                    <h3 style="margin: 0; font-size: 20px; font-weight: 600;">Asignar Equipo</h3>
                                    <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;">Asignación masiva</p>
                                </a>
                            </div>

                            {{-- Ingresar Equipo Button --}}
                            <div class="col-md-3 col-sm-6 col-xs-12">
                                <a href="{{ route('assets.bulkaudit') }}" class="shortcut-button" style="display: block; padding: 30px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; text-decoration: none; border-radius: 15px; margin-bottom: 20px; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                                    <div style="font-size: 48px; margin-bottom: 15px;">
                                        <i class="fa fa-barcode"></i>
                                    </div>
                                    <h3 style="margin: 0; font-size: 20px; font-weight: 600;">Ingresar Equipo</h3>
                                    <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;">Ingreso rápido con escaneo</p>
                                </a>
                            </div>
                        @endif

                        @if($isProfessor)
                            {{-- Solicitar Equipo Button for Professors --}}
                            <div class="col-md-3 col-sm-6 col-xs-12">
                                <a href="{{ route('apartado.index') }}" class="shortcut-button" style="display: block; padding: 30px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; text-decoration: none; border-radius: 15px; margin-bottom: 20px; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                                    <div style="font-size: 48px; margin-bottom: 15px;">
                                        <i class="fa fa-clipboard"></i>
                                    </div>
                                    <h3 style="margin: 0; font-size: 20px; font-weight: 600;">Solicitar Equipo</h3>
                                    <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;">Apartado de equipo</p>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .shortcut-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        @media (max-width: 768px) {
            .shortcut-button {
                margin-bottom: 15px;
            }
        }
    </style>
@endif
