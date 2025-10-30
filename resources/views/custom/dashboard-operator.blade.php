@extends('layouts/default')

@section('title')
{{ trans('general.dashboard') }}
@parent
@stop

@section('content')

<div class="row">
    <!-- Asignar Equipo -->
    <div class="col-lg-4 col-xs-6">
        <a href="{{ route('hardware.bulkcheckout.show') }}">
            <div class="dashboard small-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="inner">
                    <h3 style="font-size: 38px; margin-bottom: 10px;">
                        <i class="fa fa-hand-pointer-o"></i>
                    </h3>
                    <p style="font-size: 18px; font-weight: 600;">Asignar Equipo</p>
                    <p style="font-size: 14px; opacity: 0.9; margin-top: 5px;">Asignación masiva</p>
                </div>
                <span class="small-box-footer">
                    Ir a asignación
                    <i class="fa fa-arrow-circle-right" aria-hidden="true"></i>
                </span>
            </div>
        </a>
    </div>

    <!-- Ingresar Equipo (Check-in) -->
    <div class="col-lg-4 col-xs-6">
        <a href="{{ url('hardware/quickscancheckin') }}">
            <div class="dashboard small-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="inner">
                    <h3 style="font-size: 38px; margin-bottom: 10px;">
                        <i class="fa fa-barcode"></i>
                    </h3>
                    <p style="font-size: 18px; font-weight: 600;">Ingresar Equipo</p>
                    <p style="font-size: 14px; opacity: 0.9; margin-top: 5px;">Escaneo rápido de ingreso</p>
                </div>
                <span class="small-box-footer">
                    Ir a ingreso
                    <i class="fa fa-arrow-circle-right" aria-hidden="true"></i>
                </span>
            </div>
        </a>
    </div>

    <!-- Ver Activos -->
    <div class="col-lg-4 col-xs-6">
        <a href="{{ route('hardware.index') }}">
            <div class="dashboard small-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="inner">
                    <h3 style="font-size: 38px; margin-bottom: 10px;">
                        <i class="fa fa-barcode"></i>
                    </h3>
                    <p style="font-size: 18px; font-weight: 600;">Ver Activos</p>
                    <p style="font-size: 14px; opacity: 0.9; margin-top: 5px;">Listado completo</p>
                </div>
                <span class="small-box-footer">
                    Ver todos
                    <i class="fa fa-arrow-circle-right" aria-hidden="true"></i>
                </span>
            </div>
        </a>
    </div>
</div>

@stop
