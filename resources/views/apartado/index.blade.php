@extends('layouts/default')

@section('title')
    Apartado de Equipo
    @parent
@stop

@section('header_right')
@stop

@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('Solicitud de Apartado de Equipo') }}</h2>
            </div>

            <div class="box-body">
                <form id="apartado-form">
                    @csrf

                    <!-- Company Selection (Lab Location) -->
                    <div class="form-group">
                        <label for="company_id">{{ trans('Laboratorio') }} <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="company_id" name="company_id" required>
                            <option value="">Seleccione un laboratorio</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Professor Name field removed - using submitted_by from logged user -->

                    <!-- Workshop Name -->
                    <div class="form-group">
                        <label for="workshop_name">{{ trans('Nombre del Taller/Curso') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="workshop_name" name="workshop_name" required>
                    </div>

                    <!-- Needed Date -->
                    <div class="form-group">
                        <label for="needed_date">{{ trans('Fecha Necesaria') }} <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="needed_date" name="needed_date" min="{{ date('Y-m-d') }}" required>
                    </div>

                    <!-- Equipment List Section -->
                    <div class="form-group">
                        <label>{{ trans('Lista de Equipo') }} <span class="text-danger">*</span></label>
                        
                        <div id="equipment-list-container">
                            <!-- Equipment items will be added here -->
                        </div>

                        <button type="button" class="btn btn-primary btn-sm" id="add-equipment-btn" disabled>
                            <i class="fas fa-plus"></i> Agregar Equipo
                        </button>
                    </div>

                    <!-- Notes -->
                    <div class="form-group">
                        <label for="notes">{{ trans('Notas Adicionales') }}</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Información adicional para el asistente del laboratorio..."></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success" id="submit-btn">
                            <i class="fas fa-paper-plane"></i> Enviar Solicitud
                        </button>
                        <a href="{{ url('/') }}" class="btn btn-default">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Equipment Item Template -->
<template id="equipment-item-template">
    <div class="equipment-item panel panel-default" style="margin-bottom: 10px;">
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <label>Modelo de Equipo</label>
                    <select class="form-control model-select select2" name="equipment_model[]" required>
                        <option value="">Buscar modelo...</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Cantidad</label>
                    <input type="number" class="form-control quantity-input" name="equipment_quantity[]" min="1" value="1" required>
                    <small class="available-count text-muted"></small>
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label><br>
                    <button type="button" class="btn btn-danger btn-sm remove-equipment">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

@stop

@section('moar_scripts')
<script>
$(document).ready(function() {
    let companyId = null;
    let equipmentCounter = 0;

    // Enable add button when company is selected
    $('#company_id').on('change', function() {
        companyId = $(this).val();
        $('#add-equipment-btn').prop('disabled', !companyId);
        
        if (!companyId) {
            $('#equipment-list-container').empty();
            equipmentCounter = 0;
        }
    });

    // Add equipment item
    $('#add-equipment-btn').on('click', function() {
        if (!companyId) {
            alert('Por favor seleccione un laboratorio primero');
            return;
        }

        const template = document.getElementById('equipment-item-template');
        const clone = template.content.cloneNode(true);
        const container = $('#equipment-list-container');
        
        container.append(clone);
        equipmentCounter++;

        // Initialize select2 for the new model select
        const newSelect = container.find('.model-select').last();
        initializeModelSelect(newSelect);
    });

    // Remove equipment item
    $(document).on('click', '.remove-equipment', function() {
        $(this).closest('.equipment-item').remove();
        equipmentCounter--;
    });

    // Initialize model select with search
    function initializeModelSelect(selectElement) {
        $(selectElement).select2({
            ajax: {
                url: '{{ route("apartado.search-models") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term,
                        company_id: companyId
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(model) {
                            return {
                                id: model.id,
                                text: model.name + ' (' + model.available_count + ' disponibles)',
                                available: model.available_count
                            };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 0,
            placeholder: 'Buscar modelo de equipo...'
        });

        // Update available count when model is selected
        $(selectElement).on('change', function() {
            const modelId = $(this).val();
            const item = $(this).closest('.equipment-item');
            const countDisplay = item.find('.available-count');
            
            if (modelId) {
                $.get('{{ route("apartado.model-quantity") }}', {
                    company_id: companyId,
                    model_id: modelId
                }, function(response) {
                    countDisplay.text('Disponibles: ' + response.available_count);
                });
            } else {
                countDisplay.text('');
            }
        });
    }

    // Form submission
    $('#apartado-form').on('submit', function(e) {
        e.preventDefault();

        if (equipmentCounter === 0) {
            alert('Por favor agregue al menos un equipo a la lista');
            return;
        }

        const equipmentList = [];
        $('.equipment-item').each(function() {
            const modelId = $(this).find('.model-select').val();
            const quantity = $(this).find('.quantity-input').val();
            
            if (modelId && quantity) {
                equipmentList.push({
                    model_id: modelId,
                    quantity: parseInt(quantity)
                });
            }
        });

        const formData = {
            _token: $('input[name="_token"]').val(),
            company_id: $('#company_id').val(),
            workshop_name: $('#workshop_name').val(),
            needed_date: $('#needed_date').val(),
            equipment_list: equipmentList,
            notes: $('#notes').val()
        };

        $('#submit-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

        $.ajax({
            url: '{{ route("apartado.submit") }}',
            method: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function(response) {
                alert(response.message);
                window.location.href = '{{ url("/") }}';
            },
            error: function(xhr) {
                let errorMsg = 'Error al enviar la solicitud';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                }
                alert(errorMsg);
                $('#submit-btn').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Enviar Solicitud');
            }
        });
    });
});
</script>
@stop
