@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('general.quickscan_checkin') }}
    @parent
@stop

{{-- Page content --}}
@section('content')

    <style>

        .input-group {
            padding-left: 0px !important;
        }
    </style>



    <div class="row">
    <form method="POST" action="{{ route('hardware/quickscancheckin') }}" accept-charset="UTF-8" class="form-horizontal" role="form" id="checkin-form">
        <!-- left column -->
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title"> {{ trans('admin/hardware/general.bulk_checkin') }} </h2>
                </div>
                <div class="box-body">
                    {{csrf_field()}}

                    <!-- Asset Tag -->
                    <div class="form-group {{ $errors->has('asset_tag') ? 'error' : '' }}">
                        <label for="asset_tag" class="col-md-3 control-label" id="checkin_tag">{{ trans('general.asset_tag') }}</label>
                        <div class="col-md-9">
                            <div class="input-group col-md-11 required">
                                <input type="text" class="form-control" name="asset_tag" id="asset_tag" value="{{ old('asset_tag') }}" required>

                            </div>
                            {!! $errors->first('asset_tag', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-group {{ $errors->has('status_id') ? 'error' : '' }}">
                        <label for="status_id" class="col-md-3 control-label">
                            {{ trans('admin/hardware/form.status') }}
                        </label>
                        <div class="col-md-7">
                            <x-input.select
                                name="status_id"
                                id="status_id"
                                :options="$statusLabel_list"
                                style="width:100%"
                                aria-label="status_id"
                            />
                            {!! $errors->first('status_id', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>

                    <!-- Locations -->
                    @include ('partials.forms.edit.location-select', ['translated_name' => trans('general.location'), 'fieldname' => 'location_id'])

                    <!-- Note -->
                        <div class="form-group {{ $errors->has('note') ? 'error' : '' }}">
                            <label for="note" class="col-md-3 control-label">{{ trans('admin/hardware/form.notes') }}</label>
                            <div class="col-md-8">
                                <textarea class="col-md-6 form-control" id="note" name="note">{{ old('note') }}</textarea>
                                {!! $errors->first('note', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                        </div>



                </div> <!--/.box-body-->
                <div class="box-footer">
                    <a class="btn btn-link" href="{{ route('hardware.index') }}"> {{ trans('button.cancel') }}</a>
                    <button type="submit" id="checkin_button" class="btn btn-primary pull-right" style="margin-right: 5px;"><x-icon type="plus" /> Agregar a Lista</button>
                    <button type="button" id="process_checkins_button" class="btn btn-success pull-right" style="display: none; margin-right: 5px;"><x-icon type="checkmark" /> Procesar Checkins</button>
                </div>



            </div>



            </form>
        </div> <!--/.col-md-6-->

        <div class="col-md-6">
            <div class="box box-default" id="checkedin-div" style="display: none">
                <div class="box-header with-border">
                    <h2 class="box-title"> {{ trans('general.quickscan_checkin_status') }} (<span id="checkin-counter">0</span> {{ trans('general.assets_checked_in_count') }}) </h2>
                </div>
                <div class="box-body">

                    <table id="checkedin" class="table table-striped snipe-table">
                        <thead>
                        <tr>
                            <th>{{ trans('general.asset_tag') }}</th>
                            <th>{{ trans('general.asset_model') }}</th>
                            <th>{{ trans('general.model_no') }}</th>
                            <th>{{ trans('general.quickscan_checkin_status') }}</th>
                            <th>Acción</th>
                        </tr>
                        <tr id="checkin-loader" style="display: none;">
                            <td colspan="3">
                                <x-icon type="spinner" />  {{ trans('general.processing') }}...
                            </td>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>


@stop


@section('moar_scripts')
    <script nonce="{{ csrf_token() }}">
        // Array to accumulate assets for batch checkin
        var pendingAssets = [];

        $("#checkin-form").submit(function (event) {
            event.preventDefault();
            $('#checkedin-div').show();
            $('#checkin-loader').show();

            var assetTag = $('input#asset_tag').val();
            
            // Validate that the asset exists
            $.ajax({
                url: "{{ route('api.assets.index') }}",
                type: 'GET',
                headers: {
                    "X-Requested-With": 'XMLHttpRequest',
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    search: assetTag,
                    limit: 1
                },
                dataType: 'json',
                success: function (data) {
                    if (data.total > 0) {
                        var asset = data.rows[0];
                        
                        // Check if asset is already in the list
                        if (pendingAssets.includes(asset.asset_tag)) {
                            @if ($user?->enable_sounds)
                            var audio = new Audio('{{ config('app.url') }}/sounds/error.mp3');
                            audio.play();
                            @endif
                            $('#checkedin tbody').prepend("<tr class='warning'><td>" + asset.asset_tag + "</td><td>" + asset.model.name + "</td><td>" + (asset.model_number || '') + "</td><td>Ya está en la lista</td><td><i class='fas fa-exclamation-triangle text-warning'></i></td></tr>");
                        } else if (!asset.assigned_to) {
                            @if ($user?->enable_sounds)
                            var audio = new Audio('{{ config('app.url') }}/sounds/error.mp3');
                            audio.play();
                            @endif
                            $('#checkedin tbody').prepend("<tr class='danger'><td>" + asset.asset_tag + "</td><td>" + asset.model.name + "</td><td>" + (asset.model_number || '') + "</td><td>El activo no está asignado</td><td><i class='fas fa-times text-danger'></i></td></tr>");
                        } else {
                            // Add to pending list
                            pendingAssets.push(asset.asset_tag);
                            
                            @if ($user?->enable_sounds)
                            var audio = new Audio('{{ config('app.url') }}/sounds/success.mp3');
                            audio.play();
                            @endif
                            
                            $('#checkedin tbody').prepend("<tr class='info' data-asset-tag='" + asset.asset_tag + "'><td>" + asset.asset_tag + "</td><td>" + asset.model.name + "</td><td>" + (asset.model_number || '') + "</td><td>Listo para procesar</td><td><button class='btn btn-sm btn-danger remove-asset' data-asset-tag='" + asset.asset_tag + "'><i class='fas fa-times'></i></button></td></tr>");
                            
                            incrementOnSuccess();
                            $('#process_checkins_button').show();
                        }
                    } else {
                        @if ($user?->enable_sounds)
                        var audio = new Audio('{{ config('app.url') }}/sounds/error.mp3');
                        audio.play();
                        @endif
                        $('#checkedin tbody').prepend("<tr class='danger'><td>" + assetTag + "</td><td></td><td></td><td>Activo no encontrado</td><td><i class='fas fa-times text-danger'></i></td></tr>");
                    }
                    $('input#asset_tag').val('').focus();
                },
                error: function (data) {
                    @if ($user?->enable_sounds)
                    var audio = new Audio('{{ config('app.url') }}/sounds/error.mp3');
                    audio.play();
                    @endif
                    $('#checkedin tbody').prepend("<tr class='danger'><td>" + assetTag + "</td><td></td><td></td><td>Error al validar</td><td><i class='fas fa-times text-danger'></i></td></tr>");
                    $('input#asset_tag').val('').focus();
                },
                complete: function() {
                    $('#checkin-loader').hide();
                }
            });

            return false;
        });

        // Handle remove asset from list
        $(document).on('click', '.remove-asset', function() {
            var assetTag = $(this).data('asset-tag');
            pendingAssets = pendingAssets.filter(tag => tag !== assetTag);
            $("tr[data-asset-tag='" + assetTag + "']").remove();
            
            var x = parseInt($('#checkin-counter').html());
            $('#checkin-counter').html(x - 1);
            
            if (pendingAssets.length === 0) {
                $('#process_checkins_button').hide();
            }
        });

        // Process all checkins
        $('#process_checkins_button').click(function() {
            if (pendingAssets.length === 0) {
                alert('No hay activos para procesar');
                return;
            }

            if (!confirm('¿Procesar el ingreso de ' + pendingAssets.length + ' activos?')) {
                return;
            }

            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
            $('#checkin-loader').show();

            var formData = {
                asset_tags: pendingAssets,
                status_id: $('#status_id').val(),
                location_id: $('#location_id').val(),
                note: $('#note').val()
            };

            $.ajax({
                url: "{{ route('api.asset.bulkCheckinByTags') }}",
                type: 'POST',
                headers: {
                    "X-Requested-With": 'XMLHttpRequest',
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                data: formData,
                success: function (data) {
                    if (data.status === 'success') {
                        @if ($user?->enable_sounds)
                        var audio = new Audio('{{ config('app.url') }}/sounds/success.mp3');
                        audio.play();
                        @endif

                        // Update all pending rows to success
                        $('tr.info').removeClass('info').addClass('success').each(function() {
                            $(this).find('td:eq(3)').text('Ingresado exitosamente');
                            $(this).find('td:eq(4)').html('<i class="fas fa-check text-success"></i>');
                        });

                        // Clear pending list
                        pendingAssets = [];
                        $('#process_checkins_button').hide().prop('disabled', false).html('<x-icon type="checkmark" /> Procesar Checkins');

                        // Show success message
                        alert('✓ ' + data.payload.count + ' activos ingresados exitosamente');
                    } else {
                        @if ($user?->enable_sounds)
                        var audio = new Audio('{{ config('app.url') }}/sounds/error.mp3');
                        audio.play();
                        @endif
                        alert('Error al procesar los checkins');
                    }
                },
                error: function (data) {
                    @if ($user?->enable_sounds)
                    var audio = new Audio('{{ config('app.url') }}/sounds/error.mp3');
                    audio.play();
                    @endif
                    alert('Error al procesar los checkins');
                    $('#process_checkins_button').prop('disabled', false).html('<x-icon type="checkmark" /> Procesar Checkins');
                },
                complete: function() {
                    $('#checkin-loader').hide();
                }
            });
        });

        function incrementOnSuccess() {
            var x = parseInt($('#checkin-counter').html());
            y = x + 1;
            $('#checkin-counter').html(y);
        }

        $("#checkin_tag").focus();

    </script>
@stop
